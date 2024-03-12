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
  if ( ! isset( $_POST['fcnen-nonce'] ) || ! check_admin_referer( 'submit_subscriber', 'fcnen-nonce' ) ) {
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
  $scope_content = ( $_POST['content'] ?? 0 ) ? 1 : 0;

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
    'scope-content' => $scope_content
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
      'Confirmed'
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
        $subscriber['confirmed']
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
    $confirmed = absint( $row[11] ) ? 1 : 0;

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
    if ( ! empty( $reset_scopes ) ) {
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
        'skip-confirmation-email' => 1
      )
    );

    if ( $result ) {
      $count++;
    }
  }

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
