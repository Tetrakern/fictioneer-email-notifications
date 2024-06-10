<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// =======================================================================================
// SUBSCRIBERS
// =======================================================================================

/**
 * Submit new subscriber to the database
 *
 * @since 0.1.0
 */

function fcnen_submit_subscriber() {
  // Verify request
  if ( ! isset( $_POST['fcnen-nonce'] ) || ! check_admin_referer( 'submit-subscriber', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcnen' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcnen' ) );
  }

  // Setup
  $email = sanitize_email( $_POST['email'] ?? '' );
  $confirmed = ( $_POST['confirmed'] ?? 0 ) ? 1 : 0;
  $scope_everything = ( $_POST['everything'] ?? 0 ) ? 1 : 0;
  $scope_posts = ( $_POST['posts'] ?? 0 ) ? 1 : 0;
  $scope_stories = ( $_POST['stories'] ?? 0 ) ? 1 : 0;
  $scope_chapters = ( $_POST['chapters'] ?? 0 ) ? 1 : 0;

  // Check email
  if ( empty( $email ) ) {
    wp_die( __( 'Missing or invalid email address.', 'fcnen' ) );
  }

  if ( fcnen_subscriber_exists( $email ) ) {
    wp_safe_redirect( add_query_arg( ['fcnen-notice' => 'subscriber-already-exists'], $_POST['_wp_http_referer'] ) );
    exit();
  }

  // Prepare data
  $args = array(
    'confirmed' => $confirmed,
    'scope-everything' => $scope_everything,
    'scope-posts' => $scope_posts,
    'scope-stories' => $scope_stories,
    'scope-chapters' => $scope_chapters
  );

  // Add subscriber
  $result = fcnen_add_subscriber( $email, $args );

  // Failure?
  if ( empty( $result ) ) {
    wp_safe_redirect(
      add_query_arg(
        array( 'fcnen-notice' => 'subscriber-adding-failure', 'fcnen-message' => $email ),
        $_POST['_wp_http_referer']
      )
    );
  }

  // Log
  fcnen_log( "Submitted {$email} as new subscriber with ID #{$result}." );

  // Success!
  wp_safe_redirect(
    add_query_arg(
      array( 'fcnen-notice' => 'subscriber-adding-success', 'fcnen-message' => $email ),
      $_POST['_wp_http_referer']
    )
  );

  // Terminate
  exit();
}
add_action( 'admin_post_fcnen_submit_subscriber', 'fcnen_submit_subscriber' );

/**
 * Empty trashed subscribers
 *
 * @since 0.10
 * @global wpdb $wpdb  The WordPress database object.
 */

function fcnen_empty_trashed_subscribers() {
  global $wpdb;

  // Verify request
  if ( ! isset( $_GET['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-empty-trash', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcnen' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcnen' ) );
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $wpdb->query( "DELETE FROM $table_name WHERE trashed = 1" );

  // Log
  fcnen_log( "Deleted all trashed subscribers permanently." );

  // Redirect
  wp_safe_redirect(
    add_query_arg(
      array( 'fcnen-notice' => 'emptied-trashed-subscribers' ),
      admin_url( 'admin.php?page=fcnen-subscribers' )
    )
  );

  // Terminate
  exit();
}
add_action( 'admin_post_fcnen_empty_trashed_subscribers', 'fcnen_empty_trashed_subscribers' );

/**
 * Export subscribers' data as a CSV file
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 */

function fcnen_export_subscribers_csv() {
  global $wpdb;

  // Verify request
  if ( ! isset( $_GET['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-export-csv', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcnen' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcnen' ) );
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $subscribers = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );

  if ( ! empty( $subscribers ) ) {
    // Header row
    $header_row = array(
      'ID',
      'Email',
      'Code',
      'Everything',
      'Post IDs',
      'Post Types',
      'Categories',
      'Tags',
      'Taxonomies',
      'Created At',
      'Updated At',
      'Confirmed',
      'Trashed'
    );

    // Item rows
    $rows = [];

    foreach ( $subscribers as $subscriber ) {
      $rows[] = array(
        $subscriber['id'],
        $subscriber['email'],
        $subscriber['code'],
        $subscriber['everything'],
        $subscriber['post_ids'],
        $subscriber['post_types'],
        $subscriber['categories'],
        $subscriber['tags'],
        $subscriber['taxonomies'],
        $subscriber['created_at'],
        $subscriber['updated_at'],
        $subscriber['confirmed'],
        $subscriber['trashed']
      );
    }

    // Build CSV
    $csv_content = implode( ',', $header_row ) . "\n";

    foreach ( $rows as $row ) {
      $csv_content .= implode( ',', $row ) . "\n";
    }

    // Prepare download
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment; filename="fcnen-subscribers_' . date( 'Y-m-d_H-i-s', time() ) . '.csv"' );

    echo $csv_content;

    // Log
    fcnen_log( "Started CSV export." );

    // Terminate
    exit();
  }
}
add_action( 'admin_post_fcnen_export_subscribers_csv', 'fcnen_export_subscribers_csv' );

/**
 * Import subscribers' data from a CSV file
 *
 * @since 0.1.0
 */

function fcnen_import_subscribers_csv() {
  // Verify request
  if ( ! isset( $_POST['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-import-csv', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcnen' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcnen' ) );
  }

  // Check file
  if ( empty( $_FILES['csv-file'] ?? 0 ) || $_FILES['csv-file']['error'] != UPLOAD_ERR_OK ) {
    wp_die( __( 'Error. The file was not uploaded properly.', 'fcnen' ) );
  }

  // Setup
  $file = $_FILES['csv-file']['tmp_name'];
  $reset_scopes = ( $_POST['reset-scopes'] ?? 0 ) ? 1 : 0;
  $allowed_mimes = ['text/comma-separated-values', 'text/csv', 'application/csv', 'application/excel', 'text/plain'];
  $allowed_types = ['post', 'fcn_story', 'fcn_chapter'];
  $count = 0;

  // File empty?
  if ( $_FILES['csv-file']['size'] < 1) {
    wp_die( __( 'Error. The file is empty.', 'fcnen' ) );
  }

  // CSV?
  if ( ! in_array( $_FILES['csv-file']['type'], $allowed_mimes ) ) {
    wp_die( __( 'Error. Invalid file.', 'fcnen' ) );
  }

  // Open in read mode
  $csv = fopen( $file, 'r' );

  // Not opened?
  if ( $csv === false ) {
    wp_die( __( 'Error. File could not be opened.', 'fcnen' ) );
  }

  // Skip header row
  fgetcsv( $csv );

  // Digest
  while ( ( $row = fgetcsv( $csv ) ) !== false ) {
    // Check ID
    if ( ! is_numeric( $row[0] ) ) {
      continue;
    }

    // Get email
    $email = sanitize_email( $row[1] ); // 0 is the ID, which will be newly assigned

    // Valid and not already in list?
    if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) || fcnen_subscriber_exists( $email ) )  {
      continue;
    }

    // Data
    $code = sanitize_text_field( $row[2] );
    $created_at = date( 'Y-m-d H:i:s', strtotime( $row[9] ) ) ?: current_time( 'mysql' );
    $updated_at = date( 'Y-m-d H:i:s', strtotime( $row[10] ) ) ?: $created_at;
    $confirmed = absint( $row[11] ?? 0 ) ? 1 : 0;
    $trashed = absint( $row[12] ?? 0 ) ? 1 : 0;

    if ( $reset_scopes ) {
      $everything = 1;
      $post_ids = [];
      $post_types = [];
      $categories = [];
      $tags = [];
      $taxonomies = [];
    } else {
      $everything = absint( $row[3] ) ? 1 : 0;
      $post_ids = is_serialized( $row[4] ) ? maybe_unserialize( $row[4] ) : [];
      $post_types = is_serialized( $row[5] ) ? maybe_unserialize( $row[5] ) : [];
      $categories = is_serialized( $row[6] ) ? maybe_unserialize( $row[6] ) : [];
      $tags = is_serialized( $row[7] ) ? maybe_unserialize( $row[7] ) : [];
      $taxonomies = is_serialized( $row[8] ) ? maybe_unserialize( $row[8] ) : [];
    }

    // Process arrays
    if ( empty( $reset_scopes ) ) {
      // Only allowed post types
      $post_types = is_array( $post_types ) ? array_intersect( $post_types, $allowed_types ) : [];

      // Sanitize post IDs and cast to string for SQL purposes (index type != value type)
      $post_ids = is_array( $post_ids ) ? array_map( 'absint', $post_ids ) : [];
      $post_ids = array_map( 'strval', $post_ids );

      // Sanitize categories and cast to string for SQL purposes (index type != value type)
      $categories = is_array( $categories ) ? array_map( 'absint', $categories ) : [];
      $categories = array_map( 'strval', $categories );

      // Sanitize tags and cast to string for SQL purposes (index type != value type)
      $tags = is_array( $tags ) ? array_map( 'absint', $tags ) : [];
      $tags = array_map( 'strval', $tags );

      // Sanitize taxonomies and cast to string for SQL purposes (index type != value type)
      $taxonomies = is_array( $taxonomies ) ? array_map( 'absint', $taxonomies ) : [];
      $taxonomies = array_map( 'strval', $taxonomies );
    }

    // Add subscriber
    $result = fcnen_add_subscriber(
      $email,
      array(
        'code' => $code,
        'scope-everything' => $everything,
        'post_ids' => $post_ids,
        'post_types' => $post_types,
        'categories' => $categories,
        'tags' => $tags,
        'taxonomies' => $taxonomies,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
        'confirmed' => $confirmed,
        'trashed' => $trashed,
        'skip-confirmation-email' => 1
      )
    );

    if ( $result ) {
      $count++;
    }
  }

  // Log
  fcnen_log( 'Imported CSV.' );

  // Success!
  wp_safe_redirect(
    add_query_arg(
      array( 'fcnen-notice' => 'csv-imported', 'fcnen-message' => $count ),
      admin_url( 'admin.php?page=fcnen-subscribers' )
    )
  );

  // Terminate
  exit();
}
add_action( 'admin_post_fcnen_import_subscribers_csv', 'fcnen_import_subscribers_csv' );

// =======================================================================================
// NOTIFICATIONS
// =======================================================================================

/**
 * Submit new notification to the database
 *
 * @since 0.1.0
 *
 * @param int $post_id  The ID of the notification post.
 */

function fcnen_submit_notification() {
  // Verify request
  if ( ! isset( $_POST['fcnen-nonce'] ) || ! check_admin_referer( 'submit-notification', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcnen' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcnen' ) );
  }

  // Setup
  $post_id = absint( $_POST['post_id'] ?? 0 );
  $redirect_url = admin_url( 'admin.php?page=fcnen-notifications' );
  $query_args = array( 'fcnen-message' => $post_id );

  // Check for unsent duplicate
  if ( fcnen_unsent_notification_exists( $post_id ) ) {
    $query_args['fcnen-notice'] = 'submit-notification-duplicate';

    wp_safe_redirect( add_query_arg( $query_args, $redirect_url ) );
    exit();
  }

  // Sendable?
  $sendable = fcnen_post_sendable( $post_id, true );

  if ( ! $sendable['sendable'] ) {
    $query_args['fcnen-notice'] = 'submit-notification-' . $sendable['message'];

    wp_safe_redirect( add_query_arg( $query_args, $redirect_url ) );
    exit();
  }

  // Add to table
  if ( fcnen_add_notification( $post_id ) ) {
    $post = get_post( $post_id );
    $query_args['fcnen-notice'] = 'submit-notification-successful';

    fcnen_log( "Added notification for \"{$post->post_title}\" (#{$post_id})." );
  } else {
    $query_args['fcnen-notice'] = 'submit-notification-failure';
  }

  // Redirect and terminate
  wp_safe_redirect( add_query_arg( $query_args, $redirect_url ) );
  exit();
}
add_action( 'admin_post_fcnen_submit_notification', 'fcnen_submit_notification' );

/**
 * Preview notification email for a subscriber
 *
 * @since 0.1.0
 */

function fcnen_preview_notification() {
  // Verify request
  if ( ! isset( $_GET['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-preview-notification', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcnen' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcnen' ) );
  }

  // Setup
  $email = sanitize_email( $_GET['email'] );
  $subscriber = fcnen_get_subscriber_by_email( $email );
  $from = fcnen_get_from_email_address();
  $name = fcnen_get_from_email_name();
  $subject = fcnen_replace_placeholders( fcnen_get_notification_email_subject() );

  // Found?
  if ( ! $subscriber ) {
    wp_die( __( 'Subscriber not found.', 'fcnen' ) );
  }

  // Notification
  $notification = fcnen_get_notification_emails(
    array( 'subscribers' => [ (array) $subscriber ], 'preview' => 1 )
  );

  // Nothing?
  if ( empty( $notification['email_bodies'] ) ) {
    wp_die( __( 'No matching notifications queued.', 'fcnen' ) );
  }

  // Render
  foreach ( $notification['email_bodies'] as $email => $body ) {
    // Start HTML ---> ?>
    <html>
      <head>
        <meta charset="<?php echo get_bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
        <title><?php _e( 'Notification Preview', 'fcnen' ); ?></title>
        <style>body{font-family: '-apple-system', 'Segoe UI', Roboto, 'Oxygen-Sans', Ubuntu, Cantarell, 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 20px;}</style>
      </head>
      <body>
        <div style="display: flex; flex-direction: column; gap: 10px; margin: 0 auto; max-width: 640px;">
          <div style="background: rgb(0 0 0 / 5%); padding: 5px;">
            <?php printf( __( '<strong>From:</strong> %s', 'fcnen' ), $from ); ?>
          </div>
          <div style="background: rgb(0 0 0 / 5%); padding: 5px;">
            <?php printf( __( '<strong>To:</strong> %s', 'fcnen' ), $email ); ?>
          </div>
          <div style="background: rgb(0 0 0 / 5%); padding: 5px;">
            <?php printf( __( '<strong>Name:</strong> %s', 'fcnen' ), $name ); ?>
          </div>
          <div style="background: rgb(0 0 0 / 5%); padding: 5px;">
            <?php printf( __( '<strong>Subject:</strong> %s', 'fcnen' ), $subject ); ?>
          </div>
          <div style="margin-top: 20px;">
            <?php echo $body; ?>
          </div>
        </div>
      </body>
    </html>
    <?php // <--- End HTML
  }

  // Terminate
  exit;
}
add_action( 'admin_post_fcnen_preview_notification', 'fcnen_preview_notification' );

// =======================================================================================
// PROFILE
// =======================================================================================

/**
 * Update subscription data in the user frontend profile
 *
 * @since 0.1.0
 */

function fcnen_update_profile() {
  // Verify request
  if ( ! isset( $_POST['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-update-profile', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed. Please try again.', 'fcnen' ) );
  }

  // Setup
  $current_user = wp_get_current_user();
  $email = sanitize_email( $_POST['fcnen-email'] ?? '' );
  $code = sanitize_text_field( $_POST['fcnen-code'] ?? '' );
  $by_follow = filter_var( $_POST['fcnen_enable_subscribe_by_follow'] ?? 0, FILTER_VALIDATE_BOOLEAN );
  $updated_user_id = absint( $_POST['user_id'] ?? 0 );

  // User?
  if ( ! is_user_logged_in() || empty( $current_user ) || $current_user->ID !== $updated_user_id ) {
    wp_die( __( 'Wrong user or not logged-in.', 'fcnen' ) );
  }

  // Validate email if set
  if ( empty( $email ) && ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
    $email = '';
  }

  // Update subscriber email
  update_user_meta( $updated_user_id, 'fcnen_subscription_email', $email );

  // Update subscriber code
  update_user_meta( $updated_user_id, 'fcnen_subscription_code', $code );

  // Update subscribe by Follow
  update_user_meta( $updated_user_id, 'fcnen_enable_subscribe_by_follow', $by_follow );

  // Redirect
  wp_safe_redirect(
    add_query_arg(
      array( 'fcnen-notice' => 'profile-updated' ),
      wp_get_referer()
    ) . '#fcnen'
  );

  // Terminate
  exit();
}
add_action( 'admin_post_fcnen_update_profile', 'fcnen_update_profile' );

// =======================================================================================
// QUEUE
// =======================================================================================

/**
 * Clear email queue
 *
 * @since 0.1.0
 */

function fcnen_clear_queue() {
  // Verify request
  if ( ! isset( $_GET['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-clear-queue', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed. Please try again.', 'fcnen' ) );
  }

  // Delete queue
  delete_transient( 'fcnen_request_queue' );

  // Redirect
  wp_safe_redirect(
    add_query_arg(
      array( 'fcnen-notice' => 'queue-cleared' ),
      wp_get_referer()
    )
  );

  // Terminate
  exit();
}
add_action( 'admin_post_fcnen_clear_queue', 'fcnen_clear_queue' );

// =======================================================================================
// BULK STATUS
// =======================================================================================

/**
 * Check status of bulk email request
 *
 * @since 0.1.0
 */

function fcnen_check_mailersend_bulk_status() {
  // Verify
  if ( ! isset( $_GET['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-mailersend-bulk-status', 'fcnen-nonce' ) ) {
    wp_die( __( 'Nonce verification failed. Please try again.', 'fcnen' ) );
  }

  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions.', 'fcnen' ) );
  }

  // Setup
  $api_key = get_option( 'fcnen_api_key' );
  $bulk_id = $_GET['id'] ?? 0;

  // API key missing
  if ( empty( $api_key ) ) {
    return wp_die( __( 'API key has not been set.', 'fcnen' ) );
  }

  // ID missing
  if ( empty( $bulk_id ) ) {
    return wp_die( __( 'Bulk ID missing.', 'fcnen' ) );
  }

  // Request
  $response = wp_remote_get(
    str_replace( '{bulk_email_id}', $bulk_id, FCNEN_API['mailersend']['bulk_status'] ),
    array(
      'headers' => array(
        'Authorization' => "Bearer {$api_key}",
        'Content-Type' => 'application/json'
      )
    )
  );

  // Response
  $message = '';

  if ( ! is_wp_error( $response ) ) {
    $response_body = @json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $response_body ) || json_last_error() !== JSON_ERROR_NONE ) {
      wp_die( wp_remote_retrieve_body( $response ) );
    } else {
      $message = fcnen_array_to_html( $response_body );
    }
  } else {
    wp_die( $response->get_error_message() );
  }

  // Start HTML ---> ?>
  <html>
    <head>
      <meta charset="<?php echo get_bloginfo( 'charset' ); ?>">
      <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
      <title><?php _e( 'API Response', 'fcnen' ); ?></title>
      <style>body{font: 14px/1.5 "-apple-system", "Segoe UI", Roboto, "Oxygen-Sans", Ubuntu, Cantarell, "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 20px;}.fcnen-array{background: rgb(0 0 0 / 5%); padding: 12px;}.fcnen-array-node:not(:first-child){margin-top:12px;}.fcnen-array-nested{background: rgb(0 0 0 / 5%); padding: 12px; margin-top: 12px;}</style>
    </head>
    <body>
      <h1><?php _e( 'Bulk Request Status', 'fcnen' ); ?></h1>
      <?php echo $message; ?>
    </body>
  </html>
  <?php // <--- End HTML

  // Terminate
  exit();
}
add_action( 'admin_post_fcnen_check_mailersend_bulk_status', 'fcnen_check_mailersend_bulk_status' );
