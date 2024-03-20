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
 * Get the HTML body for the confirmation email
 *
 * @since 0.1.0
 *
 * @return string The email HTML.
 */

function fcnen_get_confirmation_email_body() {
  // Custom or default
  $body = get_option( 'fcnen_template_layout_confirmation' ) ?: FCNEN_DEFAULTS['layout_confirmation'];

  // Check for {{code}} presence
  if ( strpos( $body, '{{code}}' ) === false ) {
    $body = FCNEN_DEFAULTS['layout_confirmation'];
  }

  // Return HTML
  return $body;
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
 * Get the HTML body for the code email
 *
 * @since 0.1.0
 *
 * @return string The email HTML.
 */

function fcnen_get_code_email_body() {
  // Custom or default
  $body = get_option( 'fcnen_template_layout_code' ) ?: FCNEN_DEFAULTS['layout_code'];

  // Check for {{code}} presence
  if ( strpos( $body, '{{code}}' ) === false ) {
    $body = FCNEN_DEFAULTS['layout_code'];
  }

  // Return HTML
  return $body;
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
 * Get the HTML body for the edit email
 *
 * @since 0.1.0
 *
 * @return string The email HTML.
 */

function fcnen_get_edit_email_body() {
  // Return custom or default
  return get_option( 'fcnen_template_layout_edit' ) ?: FCNEN_DEFAULTS['layout_edit'];
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

  return "<li class='fcnen-dialog-modal__advanced-li _taxonomy' data-click-action='fcnen-add' data-name='{$name}[]' data-type='{$type}' data-compare='{$type}-{$id}' data-id='{$id}'><span class='fcnen-item-label'>{$label}</span> <span class='fcnen-item-name'>{$title}</span><i class='fa-solid fa-square-plus fcnen-icon'></i></li>";
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

  return "<li class='fcnen-dialog-modal__advanced-li _selected' data-click-action='fcnen-remove' data-type='{$type}' data-compare='{$type}-{$id}' data-id='{$id}'><span class='fcnen-item-label'>{$label}</span> <span class='fcnen-item-name'>{$title}</span><i class='fa-solid fa-square-minus fcnen-icon'></i><input type='hidden' name='{$name}[]' value='{$id}'></li>";
}

// =======================================================================================
// LOG
// =======================================================================================

/**
 * Logs a message to the plugin log file
 *
 * @since 0.1.0
 *
 * @param string $message  The message to log.
 */

function fcnen_log( $message ) {
  // Setup
  $current_user = wp_get_current_user();
  $log_file = plugin_dir_path( __FILE__ ) . 'fcnen-log.log';
  $log_limit = 5000;
  $date = date( 'Y-m-d H:i:s' );

  // Acting user?
  $user_id = $current_user ? $current_user->ID : '0';
  $username = $current_user ? $current_user->user_login : _x( 'Unknown', 'Log file.', 'fcnen' );

  if ( empty( $current_user ) && wp_doing_cron() ) {
    $username = _x( 'WP Cron', 'Log file.', 'fcnen' );
  }

  if ( empty( $current_user ) && wp_doing_ajax() ) {
    $username = _x( 'AJAX', 'Log file.', 'fcnen' );
  }

  // Make sure the log file exists
  if ( ! file_exists( $log_file ) ) {
    file_put_contents( $log_file, '' );
  }

  // Read
  $log_contents = file_get_contents( $log_file );

  // Parse
  $log_entries = explode( "\n", $log_contents );

  // Limit (if too large)
  $log_entries = array_slice( $log_entries, -( $log_limit + 1 ) );

  // Add new entry
  $log_entries[] = "[{$date}] [#{$user_id}|{$username}] $message";

  // Concatenate and save
  file_put_contents( $log_file, implode( "\n", $log_entries ) );
}

/**
 * Retrieves the log entries and returns an HTML representation
 *
 * @since 0.1.0
 *
 * @return string The HTML representation of the log entries.
 */

function fcnen_get_log() {
  // Setup
  $log_file = plugin_dir_path( __FILE__ ) . 'fcnen-log.log';

  // Check whether log file exists
  if ( ! file_exists( $log_file ) ) {
    return '<ul class="fcnen-log"><li>' . __( 'No log entries yet.', 'fcnen' ) . '</li></ul>';
  }

  // Read
  $log_contents = file_get_contents( $log_file );

  // Parse
  $log_entries = explode( "\n", $log_contents );

  // Limit display to 500
  $log_entries = array_slice( $log_entries, -500 );

  // Reverse
  $log_entries = array_reverse( $log_entries );

  // Build HTML
  $output = '<ul class="fcnen-log">';

  foreach ( $log_entries as $entry ) {
    $output .= '<li class="fcnen-log__item">' . $entry . '</li>';
  }

  $output .= '</ul>';

  // Return HTML
  return $output;
}

// =======================================================================================
// NOTIFICATIONS
// =======================================================================================

/**
 * Returns whether a notification is paused
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param int $post_id  Post ID of the notification.
 *
 * @return bool True if paused, false if not.
 */

function fcnen_notification_paused( $post_id ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';

  // Query
  $query = $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND paused = 1", $post_id );

  // Result
  return $wpdb->get_var( $query ) > 0;
}

/**
 * Returns whether a post can be sent as notification
 *
 * @since 0.1.0
 *
 * @param WP_Post|int $post          Post or post ID.
 * @param bool        $with_message  Whether to return the cause of failure.
 *
 * @return bool|array True or false, or an array with the result and message.
 */

function fcnen_post_sendable( $post, $with_message = false ) {
  // Resolve post ID
  if ( is_numeric( $post ) ) {
    $post = get_post( $post );
  }

  // Post not found?
  if ( ! ( $post instanceof WP_Post ) ) {
    if ( $with_message ) {
      return array( 'sendable' => false, 'message' => 'post-not-found' );
    } else {
      return false;
    }
  }

  // Setup
  $allowed_types = ['post', 'fcn_story', 'fcn_chapter'];
  $allow_password = get_option( 'fcnen_flag_allow_passwords' );
  $allow_hidden = get_option( 'fcnen_flag_allow_hidden' );

  // Reject non-published posts
  if ( $post->post_status !== 'publish' ) {
    if ( $with_message ) {
      return array( 'sendable' => false, 'message' => 'post-unpublished' );
    } else {
      return false;
    }
  }

  // Maybe reject password-protected posts
  if ( ! empty( $post->post_password ) && ! $allow_password ) {
    if ( $with_message ) {
      return array( 'sendable' => false, 'message' => 'post-protected' );
    } else {
      return false;
    }
  }

  // Reject invalid post types
  if ( ! in_array( $post->post_type, $allowed_types ) ) {
    if ( $with_message ) {
      return array( 'sendable' => false, 'message' => 'post-invalid-type' );
    } else {
      return false;
    }
  }

  // Reject excluded posts
  if ( get_post_meta( $post->ID, 'fcnen_exclude_from_notifications', true ) ) {
    if ( $with_message ) {
      return array( 'sendable' => false, 'message' => 'post-excluded' );
    } else {
      return false;
    }
  }

  // Maybe reject hidden posts
  $story_hidden = get_post_meta( $post->ID, 'fictioneer_story_hidden', true );
  $chapter_hidden = get_post_meta( $post->ID, 'fictioneer_chapter_hidden', true );

  if ( ! $allow_hidden && ( $story_hidden || $chapter_hidden ) ) {
    if ( $with_message ) {
      return array( 'sendable' => false, 'message' => 'post-hidden' );
    } else {
      return false;
    }
  }

  // All good
  if ( $with_message ) {
    return array( 'sendable' => true, 'message' => 'post-sendable' );
  } else {
    return true;
  }
}

/**
 * Checks whether a post is already enqueued as unset notification
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $post_id  The post ID to check.
 *
 * @return bool Unsent notification exists (true) or not (false).
 */

function fcnen_unsent_notification_exists( $post_id ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';

  // Query
  $query = $wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND last_sent IS NULL",
    $post_id
  );
  $result = $wpdb->get_var( $query );

  // Result
  return (int) $result > 0;
}
