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

function fcncn_ajax_subscribe_or_update() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcncn' ) );
  }

  if ( ! check_ajax_referer( 'fcncn-subscribe', 'nonce', false ) ) {
    wp_send_json_error(
      array( 'notice' => __( 'Nonce verification failed. Please reload and try again.', 'fcncn' ) )
    );
  }

  // Setup
  $email = sanitize_email( $_POST['email'] ?? '' );
  $code = sanitize_text_field( $_POST['code'] ?? '' );
  $scope = sanitize_key( $_POST['scope'] ?? 'everything' );
  $default_notice = __( 'Submission successful. If everything was in order, you will get an email.', 'fcncn' );
  $result = false;

  // Validate email
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) )  {
    wp_send_json_error( array( 'notice' => __( 'Invalid email address.', 'fcncn' ) ) );
  }

  // Arguments
  $args = array(
    'scope' => $scope
  );

  // New or update?
  $is_new_subscriber = ! fcncn_subscriber_exists( $email );

  // New subscriber!
  if ( $is_new_subscriber ) {
    $result = fcncn_add_subscriber( $email, $args );
  }

  // Update subscriber!
  if ( ! $is_new_subscriber ) {
    // Code?
    if ( ! $code ) {
      $notice = WP_DEBUG ? __( 'Code missing.', 'fcncn' ) : $default_notice;
      wp_send_json_error( array( 'notice' => $notice ) );
    }

    // Query subscriber
    $subscriber = fcncn_get_subscriber_by_email_and_code( $email, $code );

    // Code did not match email
    if ( empty( $subscriber ) ) {
      $notice = WP_DEBUG ? __( 'Code did not match email.', 'fcncn' ) : $default_notice;
      wp_send_json_error( array( 'notice' => $notice ) );
    }

    // Update
    $result = fcncn_update_subscriber( $email, $args );
  }

  // Response
  if ( $result ) {
    wp_send_json_success( array( 'notice' => $default_notice ) );
  } else {
    $notice = WP_DEBUG ? __( 'Could not create or update subscription.', 'fcncn' ) : $default_notice;

    // Do not expose informative errors to strangers
    wp_send_json_success( array( 'notice' => $notice ) );
  }
}
add_action( 'wp_ajax_fcncn_ajax_subscribe_or_update', 'fcncn_ajax_subscribe_or_update' );
add_action( 'wp_ajax_nopriv_fcncn_ajax_subscribe_or_update', 'fcncn_ajax_subscribe_or_update' );

/**
 * AJAX callback to unsubscribe
 *
 * @since 0.1.0
 */

function fcncn_ajax_subscribe() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcncn' ) );
  }

  if ( ! check_ajax_referer( 'fcncn-subscribe', 'nonce', false ) ) {
    wp_send_json_error(
      array( 'notice' => __( 'Nonce verification failed. Please reload and try again.', 'fcncn' ) )
    );
  }

  // Setup
  $email = sanitize_email( $_POST['email'] ?? '' );
  $code = sanitize_text_field( $_POST['code'] ?? '' );
  $default_notice = __( 'Successfully unsubscribed.', 'fcncn' );
  $result = false;

  // Validate email
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) )  {
    wp_send_json_error( array( 'notice' => __( 'Invalid email address.', 'fcncn' ) ) );
  }

  // Email and code present?
  if ( empty( $email ) || empty( $code ) ) {
    wp_send_json_error( array( 'notice' => __( 'Email or code missing.', 'fcncn' ) ) );
  }

  // Query subscriber
  $subscriber = fcncn_get_subscriber_by_email_and_code( $email, $code );

  // Match found...
  if ( ! $subscriber ) {
    // ... no match
    $notice = WP_DEBUG ? __( 'No matching subscription found.', 'fcncn' ) : $default_notice;

    // Do not expose informative errors to strangers
    wp_send_json_success( array( 'notice' => $notice ) );
  } else {
    // ... found
    $result = fcncn_delete_subscriber( $email );
  }

  // Response
  if ( $result ) {
    wp_send_json_success( array( 'notice' => $default_notice ) );
  } else {
    $notice = WP_DEBUG ? __( 'Could not delete subscription.', 'fcncn' ) : $default_notice;

    // Do not expose informative errors to strangers
    wp_send_json_success( array( 'notice' => $notice ) );
  }
}
add_action( 'wp_ajax_fcncn_ajax_subscribe', 'fcncn_ajax_subscribe' );
add_action( 'wp_ajax_nopriv_fcncn_ajax_subscribe', 'fcncn_ajax_subscribe' );
