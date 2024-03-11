<?php

/**
 * AJAX callback to retrieve the modal content
 *
 * @since 0.1.0
 */

function fcncn_ajax_get_form_content() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcncn' ) );
  }

  // Get form
  $html = fictioneer_minify_html( fcncn_get_modal_content() );

  // Response
  wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_fcncn_ajax_get_form_content', 'fcncn_ajax_get_form_content' );
add_action( 'wp_ajax_nopriv_fcncn_ajax_get_form_content', 'fcncn_ajax_get_form_content' );

/**
 * AJAX callback to subscribe
 *
 * @since 0.1.0
 */

function fcncn_ajax_subscribe() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcncn' ) );
  }

  // if ( ! check_ajax_referer( 'fcncn-subscribe', 'nonce', false ) ) {
  //   wp_send_json_error(
  //     array( 'error' => __( 'Nonce verification failed. Please reload and try again.', 'fcncn' ) )
  //   );
  // }

  // Setup
  $email = sanitize_email( $_POST['email'] ?? '' );
  $code = sanitize_text_field( $_POST['code'] ?? '' );
  $scope = sanitize_key( $_POST['scope'] ?? 'everything' );
  $result = false;

  // Validate email
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) )  {
    wp_send_json_error( array( 'error' => __( 'Invalid email address.', 'fcncn' ) ) );
  }

  // New subscriber?
  $new_subscriber = ! fcncn_subscriber_exists( $email );

  // New or update?
  if ( $new_subscriber ) {
    // New subscriber
    $result = fcncn_add_subscriber( $email, array( 'scope' => $scope ) );
  } else {
    // Update subscriber
    // Check code
  }

  // Response
  if ( $result ) {
    wp_send_json_success( array( 'notice' => 'Successfully subscribed!' ) );
  } else {
    wp_send_json_error( array( 'error' => __( 'Could not subscribe.', 'fcncn' ) ) );
  }
}
add_action( 'wp_ajax_fcncn_ajax_subscribe', 'fcncn_ajax_subscribe' );
add_action( 'wp_ajax_nopriv_fcncn_ajax_subscribe', 'fcncn_ajax_subscribe' );
