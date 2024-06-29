<?php

// =============================================================================
// FRONTEND
// =============================================================================

/**
 * AJAX callback to retrieve the modal content
 *
 * Note: The "fictioneer_ajax_" prefix enables the plugin skipping
 * in the theme's must-use-plugin.
 *
 * @since 0.1.0
 */

function fictioneer_ajax_fcnen_get_form_content() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcnen' ) );
  }

  // Get form
  $html = fictioneer_minify_html( fcnen_get_modal_content() );

  // Response
  wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_fictioneer_ajax_fcnen_get_form_content', 'fictioneer_ajax_fcnen_get_form_content' );
add_action( 'wp_ajax_nopriv_fictioneer_ajax_fcnen_get_form_content', 'fictioneer_ajax_fcnen_get_form_content' );

/**
 * AJAX callback to subscribe
 *
 * Note: The "fictioneer_ajax_" prefix enables the plugin skipping
 * in the theme's must-use-plugin.
 *
 * @since 0.1.0
 */

function fictioneer_ajax_fcnen_subscribe_or_update() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcnen' ) );
  }

  if ( ! check_ajax_referer( 'fcnen-subscribe', 'nonce', false ) ) {
    wp_send_json_error(
      array( 'notice' => __( 'Nonce verification failed. Please reload and try again.', 'fcnen' ) )
    );
  }

  // Setup
  $email = sanitize_email( $_POST['email'] ?? '' );
  $code = sanitize_text_field( $_POST['code'] ?? '' );
  $scope_everything = boolval( absint( $_POST['scope-everything'] ?? 1 ) );
  $scope_posts = boolval( absint( $_POST['scope-posts'] ?? 0 ) );
  $scope_stories = boolval( absint( $_POST['scope-stories'] ?? 0 ) );
  $scope_chapters = boolval( absint( $_POST['scope-chapters'] ?? 0 ) );
  $post_ids = fcnen_get_array_from_post_string( 'post_id' );
  $categories = fcnen_get_array_from_post_string( 'categories' );
  $tags = fcnen_get_array_from_post_string( 'tags' );
  $taxonomies = fcnen_get_array_from_post_string( 'taxonomies' );
  $default_notice = __( 'Submission successful. If everything is in order, you will get an email.', 'fcnen' );
  $result = false;

  // Validate email
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) )  {
    wp_send_json_error( array( 'notice' => __( 'Invalid email address.', 'fcnen' ) ) );
  }

  // Sanitize
  if ( get_option( 'fcnen_flag_subscribe_to_stories' ) ) {
    $post_ids = fcnen_prepare_id_array( $post_ids );
  } else {
    $post_ids = [];
  }

  if ( get_option( 'fcnen_flag_subscribe_to_taxonomies' ) ) {
    $categories = fcnen_prepare_id_array( $categories );
    $tags = fcnen_prepare_id_array( $tags );
    $taxonomies = fcnen_prepare_id_array( $taxonomies );
  } else {
    $categories = [];
    $tags = [];
    $taxonomies = [];
  }

  // Arguments
  $args = array(
    'scope-everything' => $scope_everything,
    'scope-posts' => $scope_posts,
    'scope-stories' => $scope_stories,
    'scope-chapters' => $scope_chapters,
    'post_ids' => $post_ids,
    'categories' => $categories,
    'tags' => $tags,
    'taxonomies' => $taxonomies
  );

  // New or update?
  $is_new_subscriber = ! fcnen_subscriber_exists( $email );

  // New subscriber!
  if ( $is_new_subscriber ) {
    $result = fcnen_add_subscriber( $email, $args );
  }

  // Update subscriber!
  if ( ! $is_new_subscriber ) {
    // Code?
    if ( ! $code ) {
      $notice = WP_DEBUG ? __( 'Code missing.', 'fcnen' ) : $default_notice;
      wp_send_json_error( array( 'notice' => $notice ) );
    }

    // Query subscriber
    $subscriber = fcnen_get_subscriber_by_email_and_code( $email, $code );

    // Code did not match email
    if ( empty( $subscriber ) ) {
      $notice = WP_DEBUG ? __( 'Code did not match email.', 'fcnen' ) : $default_notice;
      wp_send_json_error( array( 'notice' => $notice ) );
    }

    // Update
    $result = fcnen_update_subscriber( $email, $args );
  }

  // Response
  if ( $result ) {
    wp_send_json_success( array( 'notice' => $default_notice ) );
  } else {
    $notice = WP_DEBUG ? __( 'Could not create or update subscription.', 'fcnen' ) : $default_notice;

    // Do not expose informative errors to strangers
    wp_send_json_success( array( 'notice' => $notice ) );
  }
}
add_action( 'wp_ajax_fictioneer_ajax_fcnen_subscribe_or_update', 'fictioneer_ajax_fcnen_subscribe_or_update' );
add_action( 'wp_ajax_nopriv_fictioneer_ajax_fcnen_subscribe_or_update', 'fictioneer_ajax_fcnen_subscribe_or_update' );

/**
 * AJAX callback to unsubscribe
 *
 * Note: The "fictioneer_ajax_" prefix enables the plugin skipping
 * in the theme's must-use-plugin.
 *
 * @since 0.1.0
 */

function fictioneer_ajax_fcnen_unsubscribe() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcnen' ) );
  }

  if ( ! check_ajax_referer( 'fcnen-subscribe', 'nonce', false ) ) {
    wp_send_json_error(
      array( 'notice' => __( 'Nonce verification failed. Please reload and try again.', 'fcnen' ) )
    );
  }

  // Setup
  $email = sanitize_email( $_POST['email'] ?? '' );
  $code = sanitize_text_field( $_POST['code'] ?? '' );
  $default_notice = __( 'Successfully unsubscribed.', 'fcnen' );
  $result = false;

  // Validate email
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) )  {
    wp_send_json_error( array( 'notice' => __( 'Invalid email address.', 'fcnen' ) ) );
  }

  // Email and code present?
  if ( empty( $email ) || empty( $code ) ) {
    wp_send_json_error( array( 'notice' => __( 'Email or code missing.', 'fcnen' ) ) );
  }

  // Query subscriber
  $subscriber = fcnen_get_subscriber_by_email_and_code( $email, $code );

  // Match found...
  if ( ! $subscriber ) {
    // ... no match
    $notice = WP_DEBUG ? __( 'No matching subscription found.', 'fcnen' ) : $default_notice;

    // Do not expose informative errors to strangers
    wp_send_json_success( array( 'notice' => $notice ) );
  } else {
    // ... found
    $result = fcnen_delete_subscriber( $email );
  }

  // Response
  if ( $result ) {
    wp_send_json_success( array( 'notice' => $default_notice ) );
  } else {
    $notice = WP_DEBUG ? __( 'Could not delete subscription.', 'fcnen' ) : $default_notice;

    // Do not expose informative errors to strangers
    wp_send_json_success( array( 'notice' => $notice ) );
  }
}
add_action( 'wp_ajax_fictioneer_ajax_fcnen_unsubscribe', 'fictioneer_ajax_fcnen_unsubscribe' );
add_action( 'wp_ajax_nopriv_fictioneer_ajax_fcnen_unsubscribe', 'fictioneer_ajax_fcnen_unsubscribe' );

/**
 * AJAX callback to search content
 *
 * Note: The "fictioneer_ajax_" prefix enables the plugin skipping
 * in the theme's must-use-plugin.
 *
 * @since 0.1.0
 */

function fictioneer_ajax_fcnen_search_content() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error( __( 'Invalid request.', 'fcnen' ) );
  }

  if ( ! check_ajax_referer( 'fcnen-subscribe', 'nonce', false ) ) {
    wp_send_json_error(
      array( 'notice' => __( 'Nonce verification failed. Please reload and try again.', 'fcnen' ) )
    );
  }

  // Setup
  $filter = sanitize_text_field( $_REQUEST['filter'] ?? '' );
  $search = sanitize_text_field( $_REQUEST['search'] ?? '' );
  $page = absint( $_REQUEST['page'] ?? 1 );
  $stories = null;
  $terms = null;
  $output = [];

  // Query stories
  if ( $filter === 'story' && get_option( 'fcnen_flag_subscribe_to_stories' ) ) {
    $search_args = array(
      'post_type' => 'fcn_story',
      'post_status' => 'publish',
      'orderby' => 'relevance modified',
      'order' => 'desc',
      'posts_per_page' => 25,
      'paged' => $page,
      's' => $search,
      'update_post_meta_cache' => true, // We might need that
      'update_post_term_cache' => false // Improve performance
    );

    if ( ! $search ) {
      $search_args['orderby'] = 'modified';
    }

    $stories = new WP_Query( $search_args );

    // Build and add items
    foreach ( $stories->posts as $item ) {
      // Add to output
      $output[] = fcnen_get_source_node(
        array(
          'name' => 'post_id',
          'type' => 'fcn_story',
          'id' => $item->ID,
          'label' => _x( 'Story', 'List item label.', 'fcnen' ),
          'title' => fictioneer_get_safe_title( $item, 'fcnen-search-stories' )
        )
      );
    }
  }

  // Query taxonomies
  if ( $filter === 'taxonomies' && get_option( 'fcnen_flag_subscribe_to_taxonomies' ) ) {
    $terms = get_terms(
      array(
        'taxonomy' => ['category', 'post_tag', 'fcn_genre', 'fcn_fandom', 'fcn_character', 'fcn_content_warning'],
        'name__like' => $search,
        'hide_empty' => false,
        'number' => 51, // Paginate (if >50, this means there are still terms to query)
        'offset' => ( $page - 1 ) * 50, // Paginate
        'update_term_meta_cache' => false // Improve performance
      )
    );

    // Build and add items
    foreach ( $terms as $term ) {
      $taxonomy = fcnen_get_term_html_attribute( $term->taxonomy );

      // Add to output
      $output[] = fcnen_get_source_node(
        array(
          'name' => $taxonomy,
          'type' => $taxonomy,
          'id' => $term->term_id,
          'label' => fcnen_get_term_label( $term->taxonomy ),
          'title' => $term->name
        )
      );
    }
  }

  // Add observer?
  if (
    ( $stories && $page < $stories->max_num_pages ) ||
    ( $terms && count( $terms ) > 50 )
  ) {
    $page++;

    $observer = '<li class="fcnen-dialog-modal__advanced-li _observer" data-target="fcnen-observer-item" data-page="' . $page . '"><i class="fa-solid fa-spinner fa-spin" style="--fa-animation-duration: .8s;"></i> ' . __( 'Loading…', 'fcnen' ) . '<span></span></li>';

    $output[] = $observer;
  }

  // No results?
  if ( empty( $output ) ) {
    $no_matches = '<li class="fcnen-dialog-modal__advanced-li _disabled _no-match"><span>' . __( 'No matches found.', 'fcnen' ) . '</span></li>';

    $output[] = $no_matches;
  }

  // Response
  wp_send_json_success(
    array(
      'html' => implode( '', $output )
    )
  );
}
add_action( 'wp_ajax_fictioneer_ajax_fcnen_search_content', 'fictioneer_ajax_fcnen_search_content' );
add_action( 'wp_ajax_nopriv_fictioneer_ajax_fcnen_search_content', 'fictioneer_ajax_fcnen_search_content' );

// =============================================================================
// ADMIN
// =============================================================================

/**
 * AJAX callback to process email queue
 *
 * Note: The "fictioneer_ajax_" prefix enables the plugin skipping
 * in the theme's must-use-plugin.
 *
 * @since 0.1.0
 */

function fictioneer_ajax_fcnen_process_email_queue() {
  // Verify
  if ( ! wp_doing_ajax() ) {
    wp_send_json_error(
      array( 'error' => __( 'Invalid request.', 'fcnen' ) )
    );
  }

  if ( ! check_ajax_referer( 'fcnen-process-email-queue', 'fcnen_queue_nonce', false ) ) {
    wp_send_json_error(
      array( 'error' => __( 'Nonce verification failed. Please reload and try again.', 'fcnen' ) )
    );
  }

  if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error(
      array( 'error' => __( 'Insufficient permissions.', 'fcnen' ) )
    );
  }

  // Setup
  $index = absint( $_REQUEST['index'] ?? 0 );
  $new = absint( $_REQUEST['new'] ?? 0 );

  // Process
  $result = fcnen_process_email_queue( $index, $new );

  // Response
  wp_send_json_success( $result );
}
add_action( 'wp_ajax_fictioneer_ajax_fcnen_process_email_queue', 'fictioneer_ajax_fcnen_process_email_queue' );

// =============================================================================
// FOLLOWS
// =============================================================================

/**
 * AJAX Hook: Toggle story subscription by Follow
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param int  $story_id  ID of the toggled story Follow.
 * @param bool $force     Whether the Follow was added or removed.
 */

function fcnen_subscribe_by_follow( $story_id, $force ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $user = wp_get_current_user();
  $auth_email = get_user_meta( $user->ID, 'fcnen_subscription_email', true );
  $auth_code = get_user_meta( $user->ID, 'fcnen_subscription_code', true );

  // Subscription linked in profile?
  if ( ! $auth_email || ! $auth_code || ! get_user_meta( $user->ID, 'fcnen_enable_subscribe_by_follow', true ) ) {
    return;
  }

  // Valid subscriber?
  $subscriber = fcnen_get_subscriber_by_email_and_code( $auth_email, $auth_code );

  if ( ! $subscriber ) {
    return;
  }

  // Update subscription post IDs
  $stories = maybe_unserialize( $subscriber->post_ids );

  if ( $force ) {
    // Add story
    $stories[] = strval( $story_id );
    $stories = array_unique( $stories );
  } else {
    // Remove story (if set)
    if ( ( $key = array_search( $story_id, $stories ) ) !== false ) {
      unset( $stories[ $key ] );
    }
  }

  // Update database
  $wpdb->update(
    $table_name,
    array( 'post_ids' => serialize( $stories ) ),
    array( 'email' => $auth_email ),
    ['%s'],
    ['%s']
  );
}

if ( get_option( 'fictioneer_enable_follows' ) ) {
  add_action( 'fictioneer_toggled_follow', 'fcnen_subscribe_by_follow', 10, 2 );
}
