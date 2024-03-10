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

function fcncn_submit_subscriber() {
  // Verify request
  if ( ! isset( $_POST['fcncn-nonce'] ) || ! check_admin_referer( 'submit_subscriber', 'fcncn-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcncn' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcncn' ) );
  }

  // Setup
  $email = sanitize_email( $_POST['email'] ?? '' );
  $confirmed = ( $_POST['confirmed'] ?? 0 ) ? 1 : 0;

  // Check email
  if ( empty( $email ) ) {
    wp_die( __( 'Missing or invalid email address.', 'fcncn' ) );
  }

  if ( fcncn_subscriber_exists( $email ) ) {
    wp_safe_redirect( add_query_arg( ['fcncn-notice' => 'subscriber-already-exists'], $_POST['_wp_http_referer'] ) );
    exit();
  }

  // Add subscriber
  $result = fcncn_add_subscriber( $email, array( 'confirmed' => $confirmed ) );

  // Failure?
  if ( empty( $result ) ) {
    wp_safe_redirect(
    add_query_arg(
      array( 'fcncn-notice' => 'subscriber-adding-failure', 'fcncn-message' => $email ),
      $_POST['_wp_http_referer']
    )
  );
  }

  // Success!
  wp_safe_redirect(
    add_query_arg(
      array( 'fcncn-notice' => 'subscriber-adding-success', 'fcncn-message' => $email ),
      $_POST['_wp_http_referer']
    )
  );

  // Terminate
  exit();
}
add_action( 'admin_post_fcncn_submit_subscriber', 'fcncn_submit_subscriber' );

/**
 * Empty trashed subscribers
 *
 * @since 0.10
 * @global wpdb $wpdb  The WordPress database object.
 */

function fcncn_empty_trashed_subscribers() {
  global $wpdb;

  // Verify request
  if ( ! isset( $_GET['fcncn-nonce'] ) || ! check_admin_referer( 'fcncn-empty-trash', 'fcncn-nonce' ) ) {
    wp_die( __( 'Nonce verification failed.', 'fcncn' ) );
  }

  // Guard
  if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
    wp_die( __( 'Insufficient permissions.', 'fcncn' ) );
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcncn_subscribers';
  $wpdb->query( "DELETE FROM $table_name WHERE trashed = 1" );

  // Redirect
  wp_safe_redirect(
    add_query_arg(
      array( 'fcncn-notice' => 'emptied-trashed-subscribers' ),
      admin_url( 'admin.php?page=fcncn-subscribers' )
    )
  );

  // Terminate
  exit();
}
add_action( 'admin_post_fcncn_empty_trashed_subscribers', 'fcncn_empty_trashed_subscribers' );
