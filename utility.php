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

/**
 * Check whether the right set has a key of the left set
 *
 * @since 0.1.0
 *
 * @param array $left_set   Array set where the keys are the values.
 * @param array $right_set  Array set where the keys are the values.
 *
 * @return bool True if there is a match of keys, false if not.
 */

function fcnen_match_sets( $left_set, $right_set ) {
  foreach ( $left_set as $term_id => $value ) {
    if ( isset( $right_set[ $term_id ] ) ) {
      return true;
    }
  }

  return false;
}

/**
 * Get basic plugin info
 *
 * @since 0.1.0
 *
 * @return array Associative array with plugin info.
 */

function fcnen_get_plugin_info() {
  // Setup
  $info = get_option( 'fcnen_plugin_info' ) ?: [];

  // Merge with defaults (in case of incomplete data)
  $info = array_merge(
    array(
      'install_date' => current_time( 'mysql', 1 ),
      'last_update_check' => current_time( 'mysql', 1 ),
      'found_update_version' => '',
      'last_sent' => ''
    ),
    $info
  );

  // Return info
  return $info;
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
 * Get a subscriber by email (unserialized)
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

  // Failure?
  if ( empty( $subscriber ) ) {
    return false;
  }

  // Unserialize
  $subscriber->post_ids = maybe_unserialize( $subscriber->post_ids ?? 'a:0:{}' );
  $subscriber->post_types = maybe_unserialize( $subscriber->post_types ?? 'a:0:{}' );
  $subscriber->categories = maybe_unserialize( $subscriber->categories ?? 'a:0:{}' );
  $subscriber->tags = maybe_unserialize( $subscriber->tags ?? 'a:0:{}' );
  $subscriber->taxonomies = maybe_unserialize( $subscriber->taxonomies ?? 'a:0:{}' );

  // Return subscriber object
  return $subscriber;
}

/**
 * Get a subscriber by email and code (unserialized)
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
  $subscriber->post_ids = maybe_unserialize( $subscriber->post_ids ?? 'a:0:{}' );
  $subscriber->post_types = maybe_unserialize( $subscriber->post_types ?? 'a:0:{}' );
  $subscriber->categories = maybe_unserialize( $subscriber->categories ?? 'a:0:{}' );
  $subscriber->tags = maybe_unserialize( $subscriber->tags ?? 'a:0:{}' );
  $subscriber->taxonomies = maybe_unserialize( $subscriber->taxonomies ?? 'a:0:{}' );

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

/**
 * Get array of subscriber objects (unserialized)
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param bool $confirmed  Whether the subscriber must be confirmed. Default true.
 * @param bool $trashed    Whether the subscriber must be trashed. Default false.
 *
 * @return array Array of subscriber objects.
 */

function fcnen_get_subscribers( $confirmed = true, $trashed = false ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $sql = "SELECT * FROM {$table_name} WHERE confirmed = %d AND trashed = %d";

  // Query
  $subscribers = $wpdb->get_results( $wpdb->prepare( $sql, $confirmed ? 1 : 0, $trashed ? 1 : 0 ) );

  // Failure or empty?
  if ( empty( $subscribers ) ) {
    return [];
  }

  // Unserialize
  foreach ( $subscribers as $subscriber ) {
    $subscriber->post_ids = maybe_unserialize( $subscriber->post_ids ?? 'a:0:{}' );
    $subscriber->post_types = maybe_unserialize( $subscriber->post_types ?? 'a:0:{}' );
    $subscriber->categories = maybe_unserialize( $subscriber->categories ?? 'a:0:{}' );
    $subscriber->tags = maybe_unserialize( $subscriber->tags ?? 'a:0:{}' );
    $subscriber->taxonomies = maybe_unserialize( $subscriber->taxonomies ?? 'a:0:{}' );
  }

  // Return result
  return $subscribers;
}

/**
 * Get array of email-ready subscribers
 *
 * @since 0.1.0
 *
 * @param array|null $subscribers  Array of subscriber objects. Defaults to
 *                                 all confirmed, non-trashed subscribers.
 *
 * @return array Array of prepared associative subscriber arrays.
 */

function fcnen_get_email_subscribers( $subscribers = null ) {
  // Setup
  $prepared = [];
  $subscribers = $subscribers ? $subscribers : fcnen_get_subscribers();

  // Prepare subscribers
  foreach ( $subscribers as $subscriber ) {
    $data = array(
      'id' => $subscriber->id,
      'email' => $subscriber->email,
      'code' => $subscriber->code,
      'everything' => $subscriber->everything,
      'post_ids' => maybe_unserialize( $subscriber->post_ids ?? 'a:0:{}' ),
      'post_types' => maybe_unserialize( $subscriber->post_types ?? 'a:0:{}' ),
      'categories' => maybe_unserialize( $subscriber->categories ?? 'a:0:{}' ),
      'tags' => maybe_unserialize( $subscriber->tags ?? 'a:0:{}' ),
      'taxonomies' => maybe_unserialize( $subscriber->taxonomies ?? 'a:0:{}' ),
      'confirmed' => $subscriber->confirmed,
      'trashed' => $subscriber->trashed,
    );

    $prepared[ $subscriber->email ] = $data;
  }

  // Return result
  return $prepared;
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

  return "<li class='fcnen-dialog-modal__advanced-li _taxonomy' data-click-action='fcnen-add' data-name='{$name}[]' data-type='{$type}' data-compare='{$type}-{$id}' data-id='{$id}'><span class='fcnen-item-label'>{$label}</span> <span class='fcnen-item-name'>{$title}</span><i class='fa-solid fa-plus fcnen-icon'></i></li>";
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

  return "<li class='fcnen-dialog-modal__advanced-li _selected' data-click-action='fcnen-remove' data-type='{$type}' data-compare='{$type}-{$id}' data-id='{$id}'><span class='fcnen-item-label'>{$label}</span> <span class='fcnen-item-name'>{$title}</span><i class='fa-solid fa-minus fcnen-icon'></i><input type='hidden' name='{$name}[]' value='{$id}'></li>";
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
  $log_file = WP_CONTENT_DIR . '/fcnen-log.log';
  $date = current_time( 'mysql', 1 );

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
  $log_entries = array_slice( $log_entries, -( FCNEN_LOG_LIMIT + 1 ) );

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
  $log_file = WP_CONTENT_DIR . '/fcnen-log.log';

  // Check whether log file exists
  if ( ! file_exists( $log_file ) ) {
    return '<ul class="fcnen-log"><li>' . __( 'No log entries yet.', 'fcnen' ) . '</li></ul>';
  }

  // Read
  $log_contents = file_get_contents( $log_file );

  // Parse
  $log_entries = explode( "\n", $log_contents );

  // Limit display to 200
  $log_entries = array_slice( $log_entries, -200 );

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
 * Returns the number of ready notifications
 *
 * Note: This does include blocked notifications!
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @return bool True if paused, false if not.
 */

function fcnen_notification_ready_count() {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';

  // Return count
  return $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE paused = 0 AND last_sent IS NULL" ) ?? 0;
}

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
  $meta = fcnen_get_meta( $post->ID );

  if ( $meta['excluded'] ?? 0 ) {
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
 * Checks whether a post is already enqueued as unsent notification
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

/**
 * Marks a single notification as 'sent' with date
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $post_id  The post ID of the notification.
 *
 * @return int|false The number of rows updated, or false on error.
 */

function fcnen_mark_notification_as_sent( $post_id ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';

  // Update DB
  $result = $wpdb->update(
    $table_name,
    array( 'last_sent' => current_time( 'mysql', 1 ) ),
    array( 'post_id' => $post_id )
  );

  // Return result
  return $result;
}

/**
 * Return a single notification based on a post ID
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $post_id  The post ID to query for.
 *
 * @return object|null The notification object or null on failure.
 */

function fcnen_get_notification( $post_id ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';

  // Prepare
  $sql = $wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE post_id = %d AND last_sent IS NULL LIMIT 1",
    $post_id
  );

  // Query and return
  return $wpdb->get_row( $sql );
}

/**
 * Get array of notification objects (unserialized)
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param bool $paused  Whether the notification must be paused. Default false.
 * @param bool $sent    Whether the notification must have been sent. Default false.
 *
 * @return array Array of notification objects.
 */

function fcnen_get_notifications( $paused = false, $sent = false ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';
  $sql = "SELECT * FROM {$table_name} WHERE paused = %d AND last_sent " . ( $sent ? 'IS NOT NULL' : 'IS NULL' );

  // Query
  $notifications = $wpdb->get_results( $wpdb->prepare( $sql, $paused ? 1 : 0 ) );

  // Return result
  if ( ! empty( $notifications ) ) {
    return $notifications;
  } else {
    return [];
  }
}

/**
 * Get array of WP_Post objects for ready notifications
 *
 * @since 0.1.0
 *
 * @param array|null $notifications  Array of notifications objects. Defaults to
 *                                   all ready, unsent notifications.
 *
 * @return array The WP_Post objects.
 */

function fcnen_get_email_posts( $notifications = null ) {
  // Setup
  $notifications = $notifications ? $notifications : fcnen_get_notifications();
  $post_ids = [];

  // Collect post IDs
  foreach ( $notifications as $notification ) {
    $post_ids[ $notification->post_id ] = $notification->post_id;

    if ( $notification->story_id ?? 0 ) {
      $post_ids[ $notification->story_id ] = $notification->story_id;
    }
  }

  // Empty IDs?
  if ( empty( $post_ids ) ) {
    return [];
  }

  // Get posts
  $posts = get_posts(
    array(
      'post_type' => ['post', 'fcn_story', 'fcn_chapter'],
      'post__in' => array_unique( $post_ids ),
      'numberposts' => -1,
      'update_post_meta_cache' => true,
      'update_post_term_cache' => true,
      'no_found_rows' => true
    )
  );

  // Prime author cache
  if ( function_exists( 'update_post_author_caches' ) ) {
    update_post_author_caches( $posts );
  }

  // Filter posts
  $sendable_posts = array_filter( $posts, function( $post ) {
    return fcnen_post_sendable( $post->ID );
  });

  // Return result
  if ( empty( $sendable_posts ) ) {
    return [];
  } else {
    return $sendable_posts;
  }
}

// =======================================================================================
// POST META
// =======================================================================================

/**
 * Get un-serialized meta array for post ID
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param int $post_id  The ID of the post.
 *
 * @return array The meta array of an empty array if not found.
 */

function fcnen_get_meta( $post_id ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_meta';
  $default = array(
    'excluded' => false,
    'sent' => []
  );

  // Query
  $meta = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT meta FROM {$table_name} WHERE post_id = %d",
      $post_id
    )
  );

  // Exists?
  if ( $meta === null || $meta === false ) {
    return $default;
  }

  // Unserialize
  $meta = maybe_unserialize( $meta );

  // Return
  if ( is_array( $meta ) ) {
    return array_merge( $default, $meta );
  } else {
    return $default;
  }
}

/**
 * Save serialized meta array for post ID
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param int   $post_id     The ID of the post.
 * @param array $meta_array  The meta array to store.
 *
 * @return bool True on success, false on failure.
 */

function fcnen_set_meta( $post_id, $meta_array ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_meta';

  // Array?
  if ( ! is_array( $meta_array ) ) {
    return false;
  }

  // Serialize
  $meta_serialized = maybe_serialize( $meta_array );

  // Exists?
  $exists = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
      $post_id
    )
  );

  // Insert or update
  if ( $exists ) {
    $result = $wpdb->update(
      $table_name,
      array( 'meta' => $meta_serialized ),
      array( 'post_id' => $post_id )
    );
  } else {
    $result = $wpdb->insert(
      $table_name,
      array(
        'post_id' => $post_id,
        'meta' => $meta_serialized
      )
    );
  }

  // Return success or failure
  return $result !== false;
}

/**
 * Delete meta for post ID
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param int $post_id  The ID of the post.
 */

function fcnen_delete_meta( $post_id ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_meta';

  // Delete meta
  $sql = $wpdb->prepare( "DELETE FROM {$table_name} WHERE post_id = %d", $post_id );
  $wpdb->query( $sql );
}

// =======================================================================================
// QUEUE
// =======================================================================================

/**
 * Returns HTML for the sending queue
 *
 * @since 0.1.0
 *
 * @param $array $batches  Batches of bulk email payloads and their status.
 *
 * @return string The HTML.
 */

function fcnen_build_queue_html( $batches ) {
  // Setup
  $html = '';
  $translations = array(
    'pending' => _x( 'Pending', 'Email queue status.', 'fcnen' ),
    'transmitted' => _x( 'Transmitted', 'Email queue status.', 'fcnen' ),
    'working' => _x( 'Working', 'Email queue status.', 'fcnen' ),
    'error' => _x( 'Error', 'Email queue status.', 'fcnen' ),
    'failure' => _x( 'Failure', 'Email queue status.', 'fcnen' )
  );

  // Build HTML
  foreach ( $batches as $key => $batch ) {
    $status = $batch['status'];
    $email_count = count( $batch['payload'] );
    $icon = '';

    $html .= "<div class='fcnen-queue-batch' data-batch-id='{$key}' data-status='{$status}'>";
    $html .= '<span class="fcnen-queue-batch__id">' . sprintf( __( 'Batch #%s', 'fcnen' ), $key + 1 ) . '</span> | ';
    $html .= '<span class="fcnen-queue-batch__items">' . sprintf( __( '%s Emails', 'fcnen' ), $email_count ) . '</span> | ';

    if ( $status === 'working' ) {
      $icon = ' <i class="fa-solid fa-spinner fa-spin" style="--fa-animation-duration: .8s;"></i>';
    }

    $html .= '<span class="fcnen-queue-batch__status"><span>' . $translations[ $status ] . '</span>' . $icon . '</span>';

    if ( $batch['code'] ?? 0 ) {
      $html .= ' | <span class="fcnen-queue-batch__code">' . sprintf( __( 'Code: %s', 'fcnen' ), $batch['code'] ) . '</span>';
    }

    if ( $batch['response'] ?? 0 ) {
      $html .= ' | <span class="fcnen-queue-batch__response">' . $batch['response'] . '</span>';
    }

    if ( $batch['date'] ?? 0 ) {
      $html .= ' | <span class="fcnen-queue-batch__date">' . $batch['date'] . '</span>';
    }

    $html .= '</div>';
  }

  // Return HTML
  return $html;
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

/**
 * Get matching notification content for each subscriber
 *
 * @since 0.1.0
 *
 * @param array|null $subscribers  Optional. Array of prepared subscribers.
 *
 * @return array Associated array of posts, subscribers and matched IDs.
 */

function fcnen_get_notification_contents( $subscribers = null ) {
  // Setup
  $subscribers = $subscribers ?? fcnen_get_email_subscribers();
  $posts = fcnen_get_email_posts();
  $post_terms = [];
  $contents = array(
    'posts' => array_column( $posts, null, 'ID' ), // Use IDs as keys
    'subscribers' => []
  );

  // Prepare terms
  foreach ( $posts as $post ) {
    $term_ids = wp_get_post_terms(
      $post->ID,
      ['category', 'post_tag', 'fcn_genre', 'fcn_fandom', 'fcn_character', 'fcn_content_warning'],
      array( 'fields' => 'ids' )
    );

    if ( ! is_wp_error( $term_ids ) && ! empty( $term_ids ) ) {
      $post_terms[ $post->ID ] = array_flip( $term_ids ); // Values become keys
    }
  }

  // Match notifications to subscriber scopes
  foreach ( $subscribers as $subscriber ) {
    // Collect matches
    $matches = [];

    // Everything?
    if ( $subscriber['everything'] ?? 0 ) {
      $contents['subscribers'][ $subscriber['email'] ] = array(
        'subscriber' => $subscriber,
        'post_ids' => array_column( $posts, 'ID', 'ID' ) // Use IDs as keys and only keep IDs
      );
      continue;
    }

    // Match posts...
    foreach ( $posts as $post ) {
      // Match post type
      if ( in_array( $post->post_type, $subscriber['post_types'] ?? [] ) ) {
        $matches[ $post->ID ] = $post->ID;
        continue;
      }

      // Match post ID
      if ( in_array( $post->ID, $subscriber['post_ids'] ?? [] ) ) {
        $matches[ $post->ID ] = $post->ID;
        continue;
      }

      // Match parent story ID (if any)
      if ( $post->post_type === 'fcn_chapter' ) {
        $story_id = get_post_meta( $post->ID, 'fictioneer_chapter_story', true );

        if ( in_array( $story_id, $subscriber['post_ids'] ?? [] ) ) {
          $matches[ $post->ID ] = $post->ID;
          continue;
        }
      }

      // Match terms (post terms are flipped)
      $post_terms_set = $post_terms[ $post->ID ] ?? [];

      if (
        fcnen_match_sets( $post_terms_set, array_flip( $subscriber['tags'] ) ) ||
        fcnen_match_sets( $post_terms_set, array_flip( $subscriber['taxonomies'] ) ) ||
        fcnen_match_sets( $post_terms_set, array_flip( $subscriber['categories'] ) )
      ) {
        $matches[ $post->ID ] = $post->ID;
      }
    }

    // Append subscriber
    if ( ! empty( $matches ) ) {
      $contents['subscribers'][ $subscriber['email'] ] = array(
        'subscriber' => $subscriber,
        'post_ids' => $matches
      );
    }
  }

  // Return result
  return $contents;
}

/**
 * Get notification email bodies for subscribers
 *
 * @since 0.1.0
 *
 * @param array $args {
 *   Array of optional arguments.
 *
 *   @type array $subscribers  Array of prepared email subscribers. Defaults
 *                             to return value of fcnen_get_email_subscribers().
 *   @type bool  $preview      Optional. Whether this is for an email preview.
 * }
 *
 * @return array Associated array of posts and email bodies.
 */

function fcnen_get_notification_emails( $args = [] ) {
  // Setup
  $contents = fcnen_get_notification_contents( $args['subscribers'] ?? null );
  $posts = $contents['posts'];
  $subscribers = $contents['subscribers'];
  $time_format = get_option( 'time_format' );
  $date_format = get_option( 'date_format' );
  $is_preview = $args['preview'] ?? 0;
  $cached_partials = [];
  $email_bodies = [];

  // No notifications or subscribers?
  if ( empty( $posts ) || empty( $subscribers ) ) {
    return array(
      'email_bodies' => [],
      'posts' => []
    );
  }

  // Translations
  $type_names = array(
    'post' => __( 'Post', 'fcnen' ),
    'fcn_story' => __( 'Story', 'fcnen' ),
    'fcn_chapter' => __( 'Chapter', 'fcnen' )
  );

  // Templates
  $templates = array(
    'notification' => get_option( 'fcnen_template_layout_notification' ) ?: FCNEN_DEFAULTS['layout_notification'],
    'post' => get_option( 'fcnen_template_loop_part_post' ) ?: FCNEN_DEFAULTS['loop_part_post'],
    'fcn_story' => get_option( 'fcnen_template_loop_part_story' ) ?: FCNEN_DEFAULTS['loop_part_story'],
    'fcn_chapter' => get_option( 'fcnen_template_loop_part_chapter' ) ?: FCNEN_DEFAULTS['loop_part_chapter']
  );

  // Remove special characters from templates
  $templates = array_map( function( $a ) { return preg_replace( '/[\x00-\x1F\x7F\xA0]/u', '', $a ); }, $templates );

  // Loop over subscribers...
  foreach ( $subscribers as $email => $data ) {
    $post_ids = $data['post_ids'];
    $subscriber = $data['subscriber'];
    $partials = []; // Holds post, story, and chapter HTML snippets

    // Loop over matched post IDs...
    foreach ( $post_ids as $post_id ) {
      // Look for already prepared partial
      if ( isset( $cached_partials[ $post_id ] ) ) {
        $partials[] = $cached_partials[ $post_id ];
        continue;
      }

      // Prepare replacement content
      $post = $posts[ $post_id ]; // WP_Post object
      $categories = get_the_category( $post_id );
      $tags = get_the_tags( $post_id );
      $genres = get_the_terms( $post_id, 'fcn_genre' );
      $fandoms = get_the_terms( $post_id, 'fcn_fandom' );
      $characters = get_the_terms( $post_id, 'fcn_character' );
      $warnings = get_the_terms( $post_id, 'fcn_content_warning' );

      if ( is_wp_error( $tags ) || ! $tags ) {
        $tags = [];
      }

      if ( is_wp_error( $genres ) || ! $genres ) {
        $genres = [];
      }

      if ( is_wp_error( $fandoms ) || ! $fandoms ) {
        $fandoms = [];
      }

      if ( is_wp_error( $characters ) || ! $characters ) {
        $characters = [];
      }

      if ( is_wp_error( $warnings ) || ! $warnings ) {
        $warnings = [];
      }

      $all_terms = array_merge( $categories, $tags, $genres, $fandoms, $characters, $warnings );

      $extra_replacements = array(
        '{{type}}' => $type_names[ $post->post_type ], // Post, Story, or Chapter
        '{{title}}' => fictioneer_get_safe_title( $post ),
        '{{link}}' => esc_url( get_the_permalink( $post ) ?: '' ),
        '{{excerpt}}' => fictioneer_get_forced_excerpt( $post_id, absint( get_option( 'fcnen_excerpt_length', 256 ) ) ),
        '{{date}}' => get_the_date( $date_format, $post ),
        '{{time}}' => get_the_time( $time_format, $post ),
        '{{author}}' => get_the_author_meta( 'display_name', $post->post_author ?? 0 ) ?: __( 'Unknown Author', 'fcnen' ),
        '{{author_link}}' => esc_url( get_author_posts_url( $post->post_author ?? 0 ) ?: get_home_url() ),
        '{{thumbnail}}' => esc_url( get_the_post_thumbnail_url( $post, 'cover' ) ?: '' ),
        '{{categories}}' => implode( ', ', wp_list_pluck( $categories, 'name' ) ) ?: '',
        '{{tags}}' => implode( ', ', wp_list_pluck( $tags, 'name' ) ) ?: '',
        '{{genres}}' => implode( ', ', wp_list_pluck( $genres, 'name' ) ) ?: '',
        '{{fandoms}}' => implode( ', ', wp_list_pluck( $fandoms, 'name' ) ) ?: '',
        '{{characters}}' => implode( ', ', wp_list_pluck( $characters, 'name' ) ) ?: '',
        '{{warnings}}' => implode( ', ', wp_list_pluck( $warnings, 'name' ) ) ?: '',
        '{{all_terms}}' => implode( ', ', wp_list_pluck( $all_terms, 'name' ) ),
        '{{story_title}}' => '', // Empty will not be rendered
        '{{story_link}}' => '' // Empty will not be rendered
      );

      // Chapter?
      if ( $post->post_type === 'fcn_chapter' ) {
        $story_id = get_post_meta( $post_id, 'fictioneer_chapter_story', true );

        if ( $story_id ) {
          $story = get_post( $story_id );

          $extra_replacements['{{story_title}}'] = fictioneer_get_safe_title( $story );
          $extra_replacements['{{story_link}}'] = esc_url( get_the_permalink( $story ) ?: '' );
        }
      }

      // Replace placeholders in loop template and cache for next iteration
      $cached_partials[ $post_id ] = fcnen_replace_placeholders( $templates[ $post->post_type ], $extra_replacements );
      $partials[] = $cached_partials[ $post_id ];
    }

    // Replace placeholders in notification template
    $email_bodies[ $email ] = fcnen_replace_placeholders(
      $templates[ 'notification' ],
      array(
        '{{email}}' => $email,
        '{{code}}' => $subscriber['code'],
        '{{id}}' => $subscriber['id'],
        '{{updates}}' => implode( '', $partials ),
        '{{edit_link}}' => $is_preview ? '#' : esc_url( fcnen_get_edit_link( $email, $subscriber['code'] ) ),
        '{{unsubscribe_link}}' => $is_preview ? '#' : esc_url( fcnen_get_unsubscribe_link( $email, $subscriber['code'] ) )
      )
    );
  }

  // Return ready email HTML for each subscriber
  return array(
    'email_bodies' => $email_bodies,
    'posts' => $posts
  );
}

/**
 * Get the MailerSend payload for bulk emails
 *
 * @since 0.1.0
 *
 * @param array|null $email_bodies  Optional. Array of email addresses and bodies. Defaults
 *                                  to the return of fcnen_get_notification_emails().
 *
 * @return array The MailerSend payload.
 */

function fcnen_get_mailersend_payload( $email_bodies = null ) {
  // Setup
  $email_bodies = $email_bodies ?? fcnen_get_notification_emails()['email_bodies'] ?? [];
  $from = fcnen_get_from_email_address();
  $name = fcnen_get_from_email_name();
  $subject = fcnen_replace_placeholders( fcnen_get_notification_email_subject() );
  $payload = [];

  // Prepare payload
  foreach ( $email_bodies as $email => $body ) {
    $payload[] = array(
      'from' => array( 'email' => $from, 'name' => $name ),
      'to' => array(
        array( 'email' => $email )
      ),
      'subject' => $subject,
      'html' => $body
    );
  }

  // Return result
  return $payload;
}

// =======================================================================================
// QUEUE
// =======================================================================================

/**
 * Checks whether a set of payload batches has been completely processed
 *
 * @since 0.1.0
 *
 * @param array $batches  Batches of email payloads.
 *
 * @return bool True if completely processed, false otherwise.
 */

function fcnen_batches_completed( $batches ) {
  // Setup
  $complete = true;

  // Test
  foreach ( $batches as $batch ) {
    if ( ! $batch['success'] ) {
      $complete = false;
    }
  }

  // Return result
  return $complete;
}

/**
 * Get the prepared email queue for the current provider
 *
 * @since 0.1.0
 *
 * @return array The email queue with date and batches.
 */

function fcnen_get_email_queue() {
  // Extend this to cover multiple possible providers
  // $provider = get_option( 'fcnen_service_provider', 'mailersend' );

  return fcnen_get_mailersend_email_queue();
}

/**
 * Get the prepared email queue for MailerSend
 *
 * @since 0.1.0
 *
 * @return array The email queue with date and batches.
 */

function fcnen_get_mailersend_email_queue() {
  // Setup
  $api_bulk_limit = max( absint( get_option( 'fcnen_api_bulk_limit', 300 ) ), 1 );
  $emails = fcnen_get_notification_emails();
  $payload = fcnen_get_mailersend_payload( $emails['email_bodies'] ?? [] );
  $chunks = array_chunk( $payload, $api_bulk_limit );
  $queue = array(
    'provider' => 'mailersend',
    'date' => current_time( 'mysql', 1 ),
    'count' => count( $payload ),
    'post_ids' => array_keys( $emails['posts'] ?? [] ),
    'batches' => []
  );

  // Prepare batches
  foreach ( $chunks as $chunk ) {
    $queue['batches'][] = array(
      'date' => null,
      'success' => false,
      'status' => 'pending',
      'payload' => $chunk,
      'attempts' => 0,
      'code' => null,
      'response' => null
    );
  }

  // Return batched queue
  return $queue;
}
