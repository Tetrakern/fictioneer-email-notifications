<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// =======================================================================================
// GENERAL
// =======================================================================================

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

function fcncn_replace_placeholders( $string, $extra = [] ) {
  // Setup
  $replacements = array_merge( [], $extra );

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

function fcncn_subscriber_exists( $email ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcncn_subscribers';

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

function fcncn_get_subscriber_by_email( $email ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcncn_subscribers';

  // Query
  $query = $wpdb->prepare( "SELECT * FROM $table_name WHERE email = %s", $email );
  $subscriber = $wpdb->get_row( $query );

  // Result
  return $subscriber ?: false;
}
