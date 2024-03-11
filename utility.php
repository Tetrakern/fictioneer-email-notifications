<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// =======================================================================================
// GENERAL
// =======================================================================================

define(
  'fcnen_REPLACEMENTS',
  array(
    '{{site_name}}' => get_bloginfo( 'name' ),
    '{{site_link}}' => esc_url( home_url() ),
  )
);

/**
 * Replace (conditional) placeholders in a string with corresponding values
 *
 * @since 0.1.0
 *
 * @param string $string  The string containing the placeholders.
 * @param array  $extra   Additional replacements.
 *
 * @return string The string with replaced placeholders.
 */

function fcnen_replace_placeholders( $string, $extra = [] ) {
  // Setup
  $replacements = array_merge( fcnen_REPLACEMENTS, $extra );

  // Replace conditional placeholders {{#placeholder}}content{{/placeholder}}
  $string = preg_replace_callback( '/{{\#([\w\d_-]+)}}((?:(?!{{\/\1}}).)*){{\/\1}}/s', function( $matches ) use ( $replacements ) {
    $placeholder = $matches[1];
    $replacement = isset( $replacements[ "{{{$placeholder}}}" ] ) ? $replacements[ "{{{$placeholder}}}" ] : '';

    // If the replacement value is not empty, replace the placeholder with the inner content; otherwise, remove the placeholder
    return ! empty( $replacement ) ? str_replace( $matches[0], $matches[2], $matches[0] ) : '';
  }, $string );

  // Replace inverted conditional placeholders {{^placeholder}}content{{/placeholder}}
  $string = preg_replace_callback( '/{{\^([\w\d_-]+)}}((?:(?!{{\/\1}}).)*){{\/\1}}/s', function( $matches ) use ( $replacements ) {
    $placeholder = $matches[1];
    $replacement = isset( $replacements[ "{{{$placeholder}}}" ] ) ? $replacements[ "{{{$placeholder}}}" ] : '';

    // If the replacement value is empty, replace the placeholder with the inner content; otherwise, remove the placeholder
    return empty( $replacement ) ? str_replace( $matches[0], $matches[2], $matches[0] ) : '';
  }, $string );

  // Replace regular placeholders {placeholder}
  $string = str_replace( array_keys( $replacements ), array_values( $replacements ), $string );

  return $string;
}

// =======================================================================================
// EMAILS
// =======================================================================================

/**
 * Get the from email name
 *
 * @since 0.1.0
 *
 * @return string The from email name.
 */

function fcnen_get_from_email_name() {
  // From email address set?
  $from = get_option( 'fcnen_from_email_name' );

  if ( $from ) {
    return $from;
  }

  // Return the blog name
  return get_bloginfo( 'name' );
}

/**
 * Get the from email address
 *
 * @since 0.1.0
 *
 * @return string The from email address.
 */

function fcnen_get_from_email_address() {
  // From email address set?
  $from = get_option( 'fcnen_from_email_address' );

  if ( $from ) {
    return $from;
  }

  // Setup
  $parsed_url = wp_parse_url( get_home_url() );
  $domain = isset( $parsed_url['host'] ) ? preg_replace( '/^www\./i', '', $parsed_url['host'] ) : '';

  // Fallback
  if ( empty( $domain ) ) {
    return get_option( 'admin_email' );
  }

  // Return the noreply email address
  return 'noreply@' . $domain;
}

/**
 * Get the activation link for the subscriber
 *
 * @since 0.1.0
 *
 * @param string $email  Email address of the subscriber.
 * @param string $code   Code of the subscriber.
 *
 * @return string The activation link.
 */

function fcnen_get_activation_link( $email, $code ) {
  // Setup
  $query_args = array(
    'fcnen' => 1,
    'fcnen-action' => 'activation',
    'fcnen-email' => urlencode( $email ),
    'fcnen-code' => urlencode( $code )
  );

  // Return link
  return add_query_arg( $query_args, home_url() );
}

/**
 * Get the unsubscribe link for the subscriber
 *
 * @since 0.1.0
 *
 * @param string $email  The email address of the subscriber.
 * @param string $code   The code associated with the subscriber.
 *
 * @return string The unsubscribe link.
 */

function fcnen_get_unsubscribe_link( $email, $code ) {
  // Setup
  $query_args = array(
    'fcnen' => 1,
    'fcnen-action' => 'unsubscribe',
    'fcnen-email' => urlencode( $email ),
    'fcnen-code' => urlencode( $code )
  );

  // Return link
  return add_query_arg( $query_args, home_url() );
}

// =======================================================================================
// SUBSCRIBERS
// =======================================================================================

/**
 * Check whether a subscriber is already in the database
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  The email address to check.
 *
 * @return bool Subscriber exists (true) or not (false).
 */

function fcnen_subscriber_exists( $email ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';

  // Query
  $query = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE email = %s", $email );
  $result = $wpdb->get_var( $query );

  // Result
  return (int) $result > 0;
}

/**
 * Get a subscriber by email
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  The email address of the subscriber.
 *
 * @return object|false The subscriber object if found, false if not.
 */

function fcnen_get_subscriber_by_email( $email ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';

  // Query
  $query = $wpdb->prepare( "SELECT * FROM $table_name WHERE email = %s", $email );
  $subscriber = $wpdb->get_row( $query );

  // Result
  return $subscriber ?: false;
}

/**
 * Get a subscriber by email and code
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  Email address of the subscriber.
 * @param string $code   Code of the subscriber.
 *
 * @return object|false The subscriber object if found, false otherwise.
 */

function fcnen_get_subscriber_by_email_and_code( $email, $code ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $email = sanitize_email( $email );
  $code = sanitize_text_field( $code );

  // Validate
  if ( empty( $email ) || empty( $code ) ) {
    return false;
  }

  // Query
  $query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE trashed = 0 AND email = %s AND code = %s AND trashed = 0",
    $email,
    $code
  );

  $subscriber = $wpdb->get_row( $query );

  // Failure?
  if ( empty( $subscriber ) ) {
    return false;
  }

  // Unserialize
  $subscriber->post_ids = maybe_unserialize( $subscriber->post_ids );
  $subscriber->post_types = maybe_unserialize( $subscriber->post_types );
  $subscriber->categories = maybe_unserialize( $subscriber->categories );
  $subscriber->tags = maybe_unserialize( $subscriber->tags );
  $subscriber->taxonomies = maybe_unserialize( $subscriber->taxonomies );

  // Return subscriber object
  return $subscriber;
}
