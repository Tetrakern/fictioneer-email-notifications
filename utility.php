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

/**
 * Extracts an array from $_POST
 *
 * @since 0.1.0
 *
 * @param string $key  The key to extract from $_POST.
 *
 * @return array The extracted array or an empty array.
 */

function fcnen_get_array_from_post_string( $key ) {
  return ( $_POST[ $key ] ?? 0 ) ? explode( ',', $_POST[ $key ] ) : [];
}

/**
 * Return label of a taxonomy
 *
 * @since 0.1.0
 *
 * @param string $term_name  Name of the taxonomy.
 *
 * @return string The taxonomy label.
 */

function fcnen_get_term_label( $term_name ) {
  $term_labels = array(
    'category' => _x( 'Cat', 'List item term label.', 'fcnen' ),
    'post_tag' => _x( 'Tag', 'List item term label.', 'fcnen' ),
    'fcn_genre' => _x( 'Genre', 'List item term label.', 'fcnen' ),
    'fcn_fandom' => _x( 'Fandom', 'List item term label.', 'fcnen' ),
    'fcn_character' => _x( 'Character', 'List item term label.', 'fcnen' ),
    'fcn_content_warning' => _x( 'Warning', 'List item term label.', 'fcnen' )
  );

  $label = $term_labels[ $term_name ] ?? _x( 'Tax', 'Default term label.', 'fcnen' );

  return $label;
}

/**
 * Return attribute of a taxonomy for HTML elements
 *
 * @since 0.1.0
 *
 * @param string $term_name  Name of the taxonomy.
 *
 * @return string The taxonomy attribute.
 */

function fcnen_get_term_html_attribute( $term_name ) {
  $attribute = 'taxonomies';

  switch ( $term_name ) {
    case 'category':
      $attribute = 'categories';
      break;
    case 'post_tag':
      $attribute = 'tags';
      break;
    default:
      $attribute = 'taxonomies';
      break;
  }

  return $attribute;
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
 * Get the subject for the confirmation email
 *
 * @since 0.1.0
 *
 * @return string The subject.
 */

function fcnen_get_confirmation_email_subject() {
  // Return custom subject if set
  $subject = get_option( 'fcnen_template_subject_confirmation' );

  if ( $subject ) {
    return $subject;
  }

  // Return default otherwise
  return FCNEN_DEFAULTS['subject_confirmation'];
}

/**
 * Get the subject for the code email
 *
 * @since 0.1.0
 *
 * @return string The subject.
 */

function fcnen_get_code_email_subject() {
  // Return custom subject if set
  $subject = get_option( 'fcnen_template_subject_code' );

  if ( $subject ) {
    return $subject;
  }

  // Return default otherwise
  return FCNEN_DEFAULTS['subject_code'];
}

/**
 * Get the subject for the edit email
 *
 * @since 0.1.0
 *
 * @return string The subject.
 */

function fcnen_get_edit_email_subject() {
  // Return custom subject if set
  $subject = get_option( 'fcnen_template_subject_edit' );

  if ( $subject ) {
    return $subject;
  }

  // Return default otherwise
  return FCNEN_DEFAULTS['subject_edit'];
}

/**
 * Get the subject for the edit email
 *
 * @since 0.1.0
 *
 * @return string The subject.
 */

function fcnen_get_notification_email_subject() {
  // Return custom subject if set
  $subject = get_option( 'fcnen_template_subject_notification' );

  if ( $subject ) {
    return $subject;
  }

  // Return default otherwise
  return FCNEN_DEFAULTS['subject_notification'];
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

/**
 * Get the edit link for the subscriber
 *
 * @since 0.1.0
 *
 * @param string $email  The email address of the subscriber.
 * @param string $code   The code associated with the subscriber.
 *
 * @return string The edit link.
 */

function fcnen_get_edit_link( $email, $code ) {
  // Setup
  $query_args = array(
    'fcnen' => 1,
    'fcnen-action' => 'edit',
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

/**
 * Get subscriber's scopes as token replacement values
 *
 * @since 0.1.0
 *
 * @param object $subscriber  The subscriber from the database.
 *
 * @return array The subscriber's scopes.
 */

function fcnen_get_subscriber_scopes( $subscriber ) {
  // Setup
  $post_ids = maybe_unserialize( $subscriber->post_ids ?? 'a:0:{}' );
  $post_types = maybe_unserialize( $subscriber->post_types ?? 'a:0:{}' );
  $categories = maybe_unserialize( $subscriber->categories ?? 'a:0:{}' );
  $tags = maybe_unserialize( $subscriber->tags ?? 'a:0:{}' );
  $taxonomies = maybe_unserialize( $subscriber->taxonomies ?? 'a:0:{}' );

  // Everything
  $scope_everything = $subscriber->everything ? _x( 'Everything', 'Subscription scope.', 'fcnen' ) : '';

  // Stories
  $story_names = [];
  $stories = get_posts(
    array(
      'post_type'=> 'fcn_story',
      'post_status'=> ['publish', 'private', 'future'],
      'posts_per_page' => -1,
      'post__in' => $post_ids,
      'orderby' => 'post__in',
      'update_post_meta_cache' => false, // Improve performance
      'update_post_term_cache' => true, // Improve performance
      'no_found_rows' => true // Improve performance
    )
  );

  foreach ( $stories as $story ) {
    $story_names[] = fictioneer_get_safe_title( $story->ID, 'fcnen-edit-email' );
  }

  // Post types
  $post_type_names = array(
    'post' => _x( 'Blogs', 'Subscription scope.', 'fcnen' ),
    'fcn_story' => _x( 'Stories', 'Subscription scope.', 'fcnen' ),
    'fcn_chapter' => _x( 'Chapters', 'Subscription scope.', 'fcnen' ),
  );
  $scope_post_types = [];

  foreach ( $post_type_names as $type => $name ) {
    if ( in_array( $type, $post_types ) ) {
      $scope_post_types[] = $name;
    }
  }

  // Terms
  $all_term_ids = array_merge( $categories, $tags, $taxonomies );
  $category_names = [];
  $tag_names = [];
  $genre_names = [];
  $fandom_names = [];
  $character_names = [];
  $warning_names = [];

  $terms = get_terms(
    array(
      'taxonomy' => ['category', 'post_tag', 'fcn_genre', 'fcn_fandom', 'fcn_character', 'fcn_content_warning'],
      'include' => $all_term_ids,
      'hide_empty' => false
    )
  );

  foreach ( $terms as $term ) {
    switch ( $term->taxonomy ) {
      case 'category':
        $category_names[] = $term->name;
        break;
      case 'post_tag':
        $tag_names[] = $term->name;
        break;
      case 'fcn_genre':
        $genre_names[] = $term->name;
        break;
      case 'fcn_fandom':
        $fandom_names[] = $term->name;
        break;
      case 'fcn_character':
        $character_names[] = $term->name;
        break;
      case 'fcn_content_warning':
        $warning_names[] = $term->name;
        break;
    }
  }

  // Return replacement values
  return array(
    'scope_everything' => $scope_everything,
    'scope_post_types' => $scope_post_types,
    'scope_stories' => $story_names,
    'scope_categories' => $category_names,
    'scope_tags' => $tag_names,
    'scope_genres' => $genre_names,
    'scope_fandoms' => $fandom_names,
    'scope_characters' => $character_names,
    'scope_warnings' => $warning_names
  );
}

// =======================================================================================
// SANITIZATION
// =======================================================================================

/**
 * Returns database-ready array of IDs
 *
 * Note: Saving ID as string instead of integer is better for SQL
 * queries, because serialized arrays can also us integers as key
 * and that creates problems for certain matching operations.
 *
 * @since 0.1.0
 *
 * @param array $ids  The IDs to prepare.
 *
 * @return array Sanitized and stringified IDs.
 */

function fcnen_prepare_id_array( $ids ) {
  if ( empty( $ids ) ) {
    return [];
  }

  $ids = array_map( 'absint', $ids );
  $ids = array_unique( $ids );

  return array_map( 'strval', $ids );
}

/**
 * Returns sanitized array of story-type post IDs
 *
 * @since 0.1.0
 *
 * @param array $post_ids  Post IDs that should be stories.
 *
 * @return array Array of post IDs of type fcn_story.
 */

function fcnen_sanitize_post_ids( $post_ids ) {
  return empty( $post_ids ) ? [] : get_posts(
    array(
      'post_type'=> 'fcn_story',
      'post_status'=> ['publish', 'private', 'future'],
      'posts_per_page' => -1,
      'post__in' => $post_ids,
      'orderby' => 'post__in',
      'fields' => 'ids',
      'update_post_meta_cache' => false, // Improve performance
      'update_post_term_cache' => true, // Improve performance
      'no_found_rows' => true // Improve performance
    )
  );
}

/**
 * Returns sanitized array of term IDs
 *
 * @since 0.1.0
 *
 * @param array $term_ids  IDs that should be terms.
 *
 * @return array Array of term IDs.
 */

function fcnen_sanitize_term_ids( $term_ids ) {
  return empty( $term_ids ) ? [] : get_terms(
    array(
      'taxonomy' => ['category', 'post_tag', 'fcn_genre', 'fcn_fandom', 'fcn_character', 'fcn_content_warning'],
      'include' => $term_ids,
      'fields' => 'ids',
      'hide_empty' => false,
      'update_term_meta_cache' => false // Improve performance
    )
  );
}

// =======================================================================================
// HTML
// =======================================================================================

/**
 * Returns HTML for source list item
 *
 * @since 0.1.0
 *
 * @param array $args  Arguments for the HTML. Default empty.
 *
 * @return string The list item HTML.
 */

function fcnen_get_source_node( $args = [] ) {
  $name = $args['name'] ?? '';
  $type = $args['type'] ?? '';
  $id = $args['id'] ?? '';
  $label = $args['label'] ?? '';
  $title = $args['title'] ?? '';

  return "<li class='fcnen-dialog-modal__advanced-li _taxonomy' data-click-action='fcnen-add' data-name='{$name}[]' data-type='{$type}' data-compare='{$type}-{$id}' data-id='{$id}'><span class='fcnen-item-label'>{$label}</span> <span class='fcnen-item-name'>{$title}</span></li>";
}

/**
 * Returns HTML for selection list item
 *
 * @since 0.1.0
 *
 * @param array $args  Arguments for the HTML. Default empty.
 *
 * @return string The list item HTML.
 */

function fcnen_get_selection_node( $args = [] ) {
  $name = $args['name'] ?? '';
  $type = $args['type'] ?? '';
  $id = $args['id'] ?? '';
  $label = $args['label'] ?? '';
  $title = $args['title'] ?? '';

  return "<li class='fcnen-dialog-modal__advanced-li _selected' data-click-action='fcnen-remove' data-type='{$type}' data-compare='{$type}-{$id}' data-id='{$id}'><span class='fcnen-item-label'>{$label}</span> <span class='fcnen-item-name'>{$title}</span><input type='hidden' name='{$name}[]' value='{$id}'></li>";
}
