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
  if ( ! current_user_can( 'administrator' ) || ! is_admin() ) {
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
      'Confirmed',
      'Code',
      'Created At',
      'Updated At'
    );

    // Item rows
    $rows = [];

    foreach ( $subscribers as $subscriber ) {
      $rows[] = array(
        $subscriber['id'],
        $subscriber['email'],
        $subscriber['confirmed'],
        $subscriber['code'],
        $subscriber['created_at'],
        $subscriber['updated_at']
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
