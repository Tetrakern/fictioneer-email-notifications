<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// =======================================================================================
// SUBSCRIBERS
// =======================================================================================

/**
 * Add a subscriber and send activation email
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  The email address of the subscriber.
 * @param array  $args {
 *   Optional. An array of arguments. Default is an empty array.
 *
 *   @type array $created_at  Date of creation. Defaults to current 'mysql' time.
 *   @type array $updated_at  Date of last update. Defaults to current 'mysql' time.
 *   @type bool  $confirmed   Whether the subscriber is confirmed. Default false.
 * }
 *
 * @return int|false The ID of the inserted subscriber, false on failure.
 */

function fcncn_add_subscriber( $email, $args = [] ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcncn_subscribers';
  $subscriber_id = false;
  $email = sanitize_email( $email );

  // Valid and new email?
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) || fcncn_subscriber_exists( $email ) )  {
    return false;
  }

  // Defaults
  $defaults = array(
    'code' => wp_generate_password( 32, false ),
    'confirmed' => false,
    'trashed' => false,
    'created_at' => current_time( 'mysql' ),
    'updated_at' => current_time( 'mysql' )
  );

  // Merge provided data with defaults
  $args = array_merge( $defaults, $args );

  // Sanitize
  $args['confirmed'] = boolval( $args['confirmed'] ) ? 1 : 0;
  $args['trashed'] = boolval( $args['trashed'] ) ? 1 : 0;
  $created_at_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $args['created_at'] );
  $updated_at_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $args['created_at'] );

  if ( ! $created_at_date || $created_at_date->format( 'Y-m-d H:i:s' ) !== $args['created_at'] ) {
    $args['created_at'] = current_time( 'mysql' );
  }

  if ( ! $updated_at_date || $updated_at_date->format( 'Y-m-d H:i:s' ) !== $args['updated_at'] ) {
    $args['updated_at'] = current_time( 'mysql' );
  }

  // Prepare data
  $data = array(
    'email' => $email,
    'code' => $args['code'],
    'created_at' => $args['created_at'],
    'updated_at' => $args['updated_at'],
    'confirmed' => $args['confirmed'],
    'trashed' => $args['trashed']
  );

  // Insert into table and send activation mail if successful
  if ( $wpdb->insert( $table_name, $data, ['%s', '%s', '%s', '%s', '%d', '%d'] ) ) {
    $subscriber_id = $wpdb->insert_id;
  }

  // Return ID of the subscriber or false
  return $subscriber_id;
}
