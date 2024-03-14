<?php
/**
 * Plugin Name: Fictioneer Email Notifications
 * Description: Subscribe to updates via email.
 * Version: 0.1.0
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Author: Tetrakern
 * Author URI: https://github.com/Tetrakern
 * Text Domain: fcnen
 */

// No direct access!
defined( 'ABSPATH' ) OR exit;

// Version
define( 'FCNEN_VERSION', '0.1.0' );

// =======================================================================================
// STUBS
// =======================================================================================

/**
 * Stubs for theme functions to make editor shut up
 *
 * @since 0.1.0
 */

function fcnen_load_stubs() {
  if ( ! function_exists( 'fictioneer_minify_html' ) ) {
    function fictioneer_minify_html( $html ) {}
  }

  if ( ! function_exists( 'fictioneer_icon' ) ) {
    function fictioneer_icon( $icon ) {}
  }

  if ( ! function_exists( 'fictioneer_get_safe_title' ) ) {
    function fictioneer_get_safe_title( $post, $content = null ) {}
  }
}
add_action( 'wp_loaded', 'fcnen_load_stubs' );

// =======================================================================================
// DEFAULTS
// =======================================================================================

define(
  'FCNEN_API',
  array(
    'mailersend' => array(
      'quota' => 'https://api.mailersend.com/v1/api-quota',
      'bulk' => 'https://api.mailersend.com/v1/bulk-email',
      'bulk_status' => 'https://api.mailersend.com/v1/bulk-email/{bulk_email_id}'
    )
  )
);

define(
  'FCNEN_DEFAULTS',
  array(
    'subject_confirmation' => _x( 'Please confirm your subscription', 'Email subject', 'fcnen' ),
    'subject_code' => _x( 'Your subscription code', 'Email subject', 'fcnen' ),
    'subject_edit' => _x( 'Your subscription has been updated', 'Email subject', 'fcnen' ),
    'subject_notification' => _x( 'New content on {{site_name}}', 'Email subject', 'fcnen' ),
    'layout_confirmation' => __( '<p>Thank you for subscribing to <a href="{{site_link}}" target="_blank">{{site_name}}</a>.</p>' . "\n\n" . '<p>Please click the following link within 24 hours to confirm your subscription: <a href="{{activation_link}}">Activate Subscription</a>.</p>' . "\n\n" . '<p>Your edit code is <strong>{{code}}</strong>, which will also be included in any future emails. In case your code ever gets compromised, just delete your subscription and submit a new one.</p>' . "\n\n" . '<p>If someone has subscribed you against your will or you reconsidered, worry not! Without confirmation, your subscription and email address will be deleted after 24 hours. You can also immediately <a href="{{unsubscribe_link}}">delete it with this link</a>.</p>', 'fcnen' ),
    'layout_code' => __( '<p>Following is the edit code for your email subscription on <a href="{{site_link}}" target="_blank">{{site_name}}</a>. Do not share it. If compromised, just delete your subscription and submit a new one.</p>' . "\n\n" . '<p><strong>{{code}}</strong></p>' . "\n\n" . '<p>You can also directly edit your subscription with this <a href="{{edit_link}}" target="_blank">link</a>.</p>', 'fcnen' ),
    'layout_edit' => '',
    'layout_notification' => '',
    'loop_part_post' => '',
    'loop_part_story' => '',
    'loop_part_chapter' => ''
  )
);

// =======================================================================================
// INCLUDES & REQUIRES
// =======================================================================================

require_once plugin_dir_path( __FILE__ ) . 'utility.php';
require_once plugin_dir_path( __FILE__ ) . 'ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'modal.php';

if ( is_admin() ) {
  require_once plugin_dir_path( __FILE__ ) . 'actions.php';
  require_once plugin_dir_path( __FILE__ ) . 'admin.php';
}

// =======================================================================================
// INSTALLATION
// =======================================================================================

/**
 * Create the subscriber database table
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 */

function fcnen_create_subscribers_table() {
  global $wpdb;

  if ( ! function_exists( 'dbDelta' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $charset_collate = $wpdb->get_charset_collate();

  // Skip if the table already exists
  if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
    return;
  }

  // Table creation query
  $sql = "CREATE TABLE $table_name (
    id INT NOT NULL AUTO_INCREMENT,
    email VARCHAR(191) NOT NULL,
    code VARCHAR(32) NOT NULL,
    everything TINYINT(1) NOT NULL DEFAULT 1,
    post_ids LONGTEXT NOT NULL,
    post_types LONGTEXT NOT NULL,
    categories LONGTEXT NOT NULL,
    tags LONGTEXT NOT NULL,
    taxonomies LONGTEXT NOT NULL,
    pending_changes LONGTEXT NOT NULL,
    confirmed TINYINT(1) NOT NULL DEFAULT 0,
    trashed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (email)
  ) $charset_collate;";

  dbDelta( $sql );
}
register_activation_hook( __FILE__, 'fcnen_create_subscribers_table' );

// =======================================================================================
// CRON JOBS
// =======================================================================================

/**
 * Schedules event to delete unconfirmed subscribers when the plugin is activated
 *
 * @since 0.1.0
 */

function fcnen_schedule_delete_expired_subscribers() {
  if ( ! wp_next_scheduled( 'fcnen_delete_expired_subscribers_event' ) ) {
    wp_schedule_event( time(), 'twicedaily', 'fcnen_delete_expired_subscribers_event' );
  }
}
register_activation_hook( __FILE__, 'fcnen_schedule_delete_expired_subscribers' );

/**
 * Clears event to delete unconfirmed subscribers when the plugin is deactivated
 *
 * @since 0.1.0
 */

function fcnen_remove_delete_expired_subscribers() {
  wp_clear_scheduled_hook( 'fcnen_delete_expired_subscribers_event' );
}
register_deactivation_hook( __FILE__, 'fcnen_remove_delete_expired_subscribers' );

/**
 * Deletes unconfirmed subscribers that are older than 24 hours
 *
 * @since 0.1.0
 */

function fcnen_delete_expired_subscribers() {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';

  // Delete unconfirmed subscribers older than 24 hours
  $wpdb->query(
    $wpdb->prepare(
      "DELETE FROM $table_name WHERE confirmed = %d AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)",
      0
    )
  );
}
add_action( 'fcnen_delete_expired_subscribers_event', 'fcnen_delete_expired_subscribers' );

// =======================================================================================
// REGISTER WITH THEME
// =======================================================================================

/**
 * Adds plugin card to theme settings plugin tab
 *
 * @since 0.1.0
 */

function fcnen_settings_card() {
  // Start HTML ---> ?>
  <div class="fictioneer-card fictioneer-card--plugin">
    <div class="fictioneer-card__wrapper">
      <h3 class="fictioneer-card__header"><?php _e( 'Fictioneer Email Notifications', 'fcnen' ); ?></h3>
      <div class="fictioneer-card__content">

        <div class="fictioneer-card__row">
          <p><?php
            _e( 'Subscribe to updates via email.', 'fcnen' );
          ?></p>
        </div>

        <div class="fictioneer-card__row fictioneer-card__row--meta">
          <?php printf( __( 'Version %s', 'fcnen' ), FCNEN_VERSION ); ?>
          |
          <?php printf( __( 'By <a href="%s">Tetrakern</a>', 'fcnen' ), 'https://github.com/Tetrakern' ); ?>
        </div>

      </div>
    </div>
  </div>
  <?php // <--- End HTML
}
add_action( 'fictioneer_admin_settings_plugins', 'fcnen_settings_card' );

/**
 * Checks for the Fictioneer (parent) theme, deactivates plugin if not found
 *
 * @since 0.1.0
 */

function fcnen_check_theme() {
  // Setup
  $current_theme = wp_get_theme();

  // Child or parent theme?
  if ( $current_theme->parent() ) {
    $theme_name = $current_theme->parent()->get( 'Name' );
  } else {
    $theme_name = $current_theme->get( 'Name' );
  }

  // Theme name must be Fictioneer!
  if ( $theme_name !== 'Fictioneer' ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    // Deactivate plugin
    deactivate_plugins( plugin_basename( __FILE__ ) );

    // Display an admin notice
    add_action( 'admin_notices', 'fcnen_admin_notice_wrong_theme' );
  }
}
add_action( 'after_setup_theme', 'fcnen_check_theme' );

/**
 * Show admin notice if plugin has been deactivated due to wrong theme
 *
 * @since 0.1.0
 */

function fcnen_admin_notice_wrong_theme() {
  // Start HTML ---> ?>
  <div class="notice notice-error is-dismissible">
    <p><?php
      _e( 'Fictioneer Email Notifications requires the Fictioneer theme or a child theme. The plugin has been deactivated.', 'fcnen' );
    ?></p>
  </div>
  <?php // <--- End HTML
}

// =======================================================================================
// SETUP
// =======================================================================================

/**
 * Compare installed WordPress version against version string
 *
 * @since 0.1.0
 * @global wpdb $wp_version  Current WordPress version string.
 *
 * @param string $version   The version string to test against.
 * @param string $operator  Optional. How to compare. Default '>='.
 *
 * @return boolean True or false.
 */

function fcnen_compare_wp_version( $version, $operator = '>=' ) {
  global $wp_version;

  return version_compare( $wp_version, $version, $operator );
}

/**
 * Enqueue frontend scripts and styles for the plugin
 *
 * @since 0.1.0
 */

function fcnen_enqueue_frontend_scripts() {
  // Setup
  $strategy = fcnen_compare_wp_version( '6.3' ) ? array( 'strategy'  => 'defer' ) : true; // Defer or load in footer

  // Styles
  wp_enqueue_style(
    'fcnen-frontend-styles',
    plugin_dir_url( __FILE__ ) . '/css/fcnen-frontend.css',
    ['fictioneer-application'],
    FCNEN_VERSION
  );

  // Scripts
  wp_enqueue_script(
    'fcnen-frontend-scripts',
    plugin_dir_url( __FILE__ ) . 'js/fcnen-frontend.min.js',
    [],
    FCNEN_VERSION,
    $strategy
  );
}
add_action( 'wp_enqueue_scripts', 'fcnen_enqueue_frontend_scripts' );

/**
 * Add removable query args (frontend only)
 *
 * @since 0.1.0
 *
 * @param array $args  Array of removable query arguments.
 *
 * @return array Extended list of query args.
 */

function fcnen_add_removable_frontend_query_args( $args ) {
  return array_merge( $args, ['fcnen-notice', 'fcnen-message'] );
}
add_filter( 'fictioneer_filter_removable_query_args', 'fcnen_add_removable_frontend_query_args' );

// =======================================================================================
// FRONTEND
// =======================================================================================

/**
 * Returns HTML for the subscription button
 *
 * @since 0.1.0
 *
 * @param int|null $post_id  Optional. The post ID to subscribe to.
 */

function fcnen_get_subscription_button( $post_id = null ) {
  // Setup
  $attributes = '';

  // Story ID
  if ( $post_id && get_post_type( $post_id ) === 'fcn_story' ) {
    $attributes = "data-story-id='{$post_id}'";
  }

  return '<button type="button" data-click-target="#fcnen-subscription-modal" data-click-action="open-dialog-modal fcnen-load-modal-form" class="_align-left" tabindex="0" ' . $attributes . '><i class="fa-solid fa-envelope"></i> <span>' . __( 'Email Subscription', 'fcnen' ) . '</span></button>';
}

/**
 * Adds button to subscribe popup
 *
 * @since 0.1.0
 *
 * @param array $buttons  Array of subscribe buttons.
 *
 * @return array Updated array of subscribe buttons.
 */

function fcnen_filter_extend_subscribe_buttons( $buttons, $post_id ) {
  // Add to first place
  array_splice( $buttons, 0, 0, fcnen_get_subscription_button( $post_id ) );

  // Continue filter
  return $buttons;
}
add_filter( 'fictioneer_filter_subscribe_buttons', 'fcnen_filter_extend_subscribe_buttons', 20, 2 );

// =======================================================================================
// SUBSCRIBERS
// =======================================================================================

/**
 * Adds a subscriber and maybe send activation email
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  Email address of the subscriber.
 * @param array  $args {
 *   Optional array of arguments. Default empty.
 *
 *   @type bool   'scope-everything'         True or false. Default true.
 *   @type bool   'scope-posts'              True or false. Default false.
 *   @type bool   'scope-stories'            True or false. Default false.
 *   @type bool   'scope-chapters'           True or false. Default false.
 *   @type array  'post_ids'                 Array of post IDs to subscribe to. Default empty.
 *   @type array  'post_types'               Array of post types to subscribe to. Default empty.
 *   @type array  'categories'               Array of category IDs to subscribe to. Default empty.
 *   @type array  'tags'                     Array of tag IDs to subscribe to. Default empty.
 *   @type array  'taxonomies'               Array of taxonomy IDs to subscribe to. Default empty.
 *   @type array  'created_at'               Date of creation. Defaults to current 'mysql' time.
 *   @type array  'updated_at'               Date of last update. Defaults to current 'mysql' time.
 *   @type bool   'confirmed'                Whether the subscriber is confirmed. Default false.
 *   @type bool   'skip-confirmation-email'  Whether to skip the confirmation email. Default false.
 * }
 *
 * @return int|false The ID of the inserted subscriber, false on failure.
 */

function fcnen_add_subscriber( $email, $args = [] ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $subscriber_id = false;
  $email = sanitize_email( $email );

  // Valid and new email?
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) || fcnen_subscriber_exists( $email ) )  {
    return false;
  }

  // Defaults
  $defaults = array(
    'code' => wp_generate_password( 32, false ),
    'scope-everything' => 1,
    'scope-posts' => 0,
    'scope-stories' => 0,
    'scope-chapters' => 0,
    'post_ids' => [],
    'post_types' => [],
    'categories' => [],
    'tags' => [],
    'taxonomies' => [],
    'confirmed' => 0,
    'trashed' => 0,
    'created_at' => current_time( 'mysql' ),
    'updated_at' => current_time( 'mysql' ),
    'skip-confirmation-email' => 0
  );

  // Merge provided args with defaults
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

  // Scopes
  if ( $args['scope-posts'] ) {
    $args['post_types'][] = 'post';
  }

  if ( $args['scope-stories'] ) {
    $args['post_types'][] = 'fcn_story';
  }

  if ( $args['scope-chapters'] ) {
    $args['post_types'][] = 'fcn_chapter';
  }

  // Sanitize post IDs
  if ( ! empty( $args['post_ids'] ) && get_option( 'fcnen_flag_subscribe_to_stories' ) ) {
    $args['post_ids'] = fcnen_sanitize_post_ids( $args['post_ids'] );
  } else {
    $args['post_ids'] = [];
  }

  // Sanitize taxonomies
  if ( get_option( 'fcnen_flag_subscribe_to_taxonomies' ) ) {
    $args['categories'] = fcnen_sanitize_term_ids( $args['categories'] );
    $args['tags'] = fcnen_sanitize_term_ids( $args['tags'] );
    $args['taxonomies'] = fcnen_sanitize_term_ids( $args['taxonomies'] );
  } else {
    $args['categories'] = [];
    $args['tags'] = [];
    $args['taxonomies'] = [];
  }

  // Prepare data
  $data = array(
    'email' => $email,
    'code' => $args['code'],
    'everything' => $args['scope-everything'],
    'post_types' => serialize( array_map( 'strval', $args['post_types'] ) ),
    'post_ids' => serialize( array_map( 'strval', $args['post_ids'] ) ),
    'categories' => serialize( array_map( 'strval', $args['categories'] ) ),
    'tags' => serialize( array_map( 'strval', $args['tags'] ) ),
    'taxonomies' => serialize( array_map( 'strval', $args['taxonomies'] ) ),
    'pending_changes' => serialize( [] ),
    'created_at' => $args['created_at'],
    'updated_at' => $args['updated_at'],
    'confirmed' => $args['confirmed'],
    'trashed' => $args['trashed']
  );

  // Insert into table and send activation mail if successful (and required)
  if ( $wpdb->insert( $table_name, $data, ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'] ) ) {
    $subscriber_id = $wpdb->insert_id;

    if ( ! $args['confirmed'] && ! $args['skip-confirmation-email'] ) {
      fcnen_send_confirmation_email(
        array(
          'email' => $email,
          'code' => $args['code']
        )
      );
    }
  }

  // Return ID of the subscriber or false
  return $subscriber_id;
}

/**
 * Updates an existing subscriber
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  Email address of the subscriber.
 * @param array  $args {
 *   Optional array of arguments. Default empty.
 *
 *   @type bool   'scope-everything'  True or false. Default true.
 *   @type bool   'scope-posts'       True or false. Default false.
 *   @type bool   'scope-stories'     True or false. Default false.
 *   @type bool   'scope-chapters'    True or false. Default false.
 *   @type array  'post_ids'          Array of post IDs to subscribe to. Default empty.
 *   @type array  'post_types'        Array of post types to subscribe to. Default empty.
 *   @type array  'categories'        Array of category IDs to subscribe to. Default empty.
 *   @type array  'tags'              Array of tag IDs to subscribe to. Default empty.
 *   @type array  'taxonomies'        Array of taxonomy IDs to subscribe to. Default empty.
 * }
 *
 * @return bool Whether the subscriber was successfully updated.
 */

function fcnen_update_subscriber( $email, $args = [] ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $email = sanitize_email( $email );

  // Valid email?
  if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
    return false;
  }

  // Get subscriber
  $subscriber = fcnen_get_subscriber_by_email( $email );

  // Make sure subscriber exists and is not trashed
  if ( ! $subscriber || $subscriber->trashed ) {
    return false;
  }

  // Defaults
  $defaults = array(
    'scope-everything' => 1,
    'scope-posts' => 0,
    'scope-stories' => 0,
    'scope-chapters' => 0,
    'post_ids' => [],
    'post_types' => [],
    'categories' => [],
    'tags' => [],
    'taxonomies' => []
  );

  // Merge provided args with defaults
  $args = array_merge( $defaults, $args );

  // Scopes
  if ( $args['scope-posts'] ) {
    $args['post_types'][] = 'post';
  }

  if ( $args['scope-stories'] ) {
    $args['post_types'][] = 'fcn_story';
  }

  if ( $args['scope-chapters'] ) {
    $args['post_types'][] = 'fcn_chapter';
  }

  // Sanitize post IDs
  if ( ! empty( $args['post_ids'] ) && get_option( 'fcnen_flag_subscribe_to_stories' ) ) {
    $args['post_ids'] = fcnen_sanitize_post_ids( $args['post_ids'] );
  } else {
    $args['post_ids'] = [];
  }

  // Sanitize taxonomies
  if ( get_option( 'fcnen_flag_subscribe_to_taxonomies' ) ) {
    $args['categories'] = fcnen_sanitize_term_ids( $args['categories'] );
    $args['tags'] = fcnen_sanitize_term_ids( $args['tags'] );
    $args['taxonomies'] = fcnen_sanitize_term_ids( $args['taxonomies'] );
  } else {
    $args['categories'] = [];
    $args['tags'] = [];
    $args['taxonomies'] = [];
  }

  // Prepare data
  $data = array(
    'everything' => $args['scope-everything'],
    'post_types' => serialize( array_map( 'strval', $args['post_types'] ) ),
    'post_ids' => serialize( array_map( 'strval', $args['post_ids'] ) ),
    'categories' => serialize( array_map( 'strval', $args['categories'] ) ),
    'tags' => serialize( array_map( 'strval', $args['tags'] ) ),
    'taxonomies' => serialize( array_map( 'strval', $args['taxonomies'] ) )
  );

  // Update
  $result = $wpdb->update(
    $table_name,
    $data,
    array( 'email' => $email ),
    ['%d', '%s', '%s', '%s', '%s', '%s'],
    ['%s']
  );

  // Return result
  return ( $result !== false );
}

/**
 * Deletes a subscriber
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  Email address of the subscriber.
 *
 * @return bool Whether the subscriber was successfully deleted.
 */

function fcnen_delete_subscriber( $email ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $email = sanitize_email( $email );

  // Delete subscriber
  $result = $wpdb->delete( $table_name, array( 'email' => $email ), ['%s'] );

  // Return success/failure
  return (bool) $result;
}

/**
 * Activates the subscriber based on the provided email and code
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param string $email  Email address of the subscriber.
 * @param string $code   Code of the subscriber.
 *
 * @return boolean Whether the activation was successful or not.
 */

function fcnen_activate_subscriber( $email, $code ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $email = sanitize_email( $email );
  $code = sanitize_text_field( $code );

  // Update confirmation status (the WHERE clause doubles as validation)
  $result = $wpdb->update(
    $table_name,
    array( 'confirmed' => 1 ),
    array( 'email' => $email, 'code' => $code ),
    array( '%d' ),
    array( '%s', '%s' )
  );

  // Return success/failure
  return (bool) $result;
}

/**
 * Handle the activation link
 *
 * @since 0.1.0
 */

function fcnen_handle_activation_link() {
  // Check URI
  if (
    ! isset( $_GET['fcnen'], $_GET['fcnen-action'], $_GET['fcnen-email'], $_GET['fcnen-code'] ) ||
    $_GET['fcnen-action'] !== 'activation'
  ) {
    return;
  }

  // Setup
  $email = urldecode( $_GET['fcnen-email'] ?? '' );
  $code = urldecode( $_GET['fcnen-code'] ?? '' );

  // Secondary check
  if ( empty( $email ) || empty( $code ) ) {
    return;
  }

  // Try to activate subscriber
  $result = fcnen_activate_subscriber( $email, $code );

  // Check result and redirect...
  if ( $result ) {
    $notice = __( 'Subscription has been confirmed.', 'fcnen' );
    wp_safe_redirect( add_query_arg( array( 'fictioneer-notice' => $notice, 'success' => 1 ), home_url() ) );
  } else {
    $notice = __( 'Subscription not found or already confirmed.', 'fcnen' );
    wp_safe_redirect( add_query_arg( array( 'fictioneer-notice' => $notice, 'failure' => 1 ), home_url() ) );
  }
}
add_action( 'template_redirect', 'fcnen_handle_activation_link' );

/**
 * Handle the unsubscribe link
 *
 * @since 0.1.0
 */

function fcnen_handle_unsubscribe_link() {
  // Check URI
  if (
    ! isset( $_GET['fcnen'], $_GET['fcnen-action'], $_GET['fcnen-email'], $_GET['fcnen-code'] ) ||
    $_GET['fcnen-action'] !== 'unsubscribe'
  ) {
    return;
  }

  // Setup
  $email = urldecode( $_GET['fcnen-email'] ?? '' );
  $code = urldecode( $_GET['fcnen-code'] ?? '' );

  // Secondary check
  if ( empty( $email ) || empty( $code ) ) {
    return;
  }

  // Try to delete subscriber
  $result = fcnen_delete_subscriber( $email, $code );

  // Check result and redirect...
  if ( $result ) {
    $notice = __( 'Subscription has been deleted.', 'fcnen' );
    wp_safe_redirect( add_query_arg( array( 'fictioneer-notice' => $notice, 'success' => 1 ), home_url() ) );
  } else {
    $notice = __( 'Subscription not found.', 'fcnen' );
    wp_safe_redirect( add_query_arg( array( 'fictioneer-notice' => $notice, 'failure' => 1 ), home_url() ) );
  }
}
add_action( 'template_redirect', 'fcnen_handle_unsubscribe_link' );

// =======================================================================================
// EMAILS
// =======================================================================================

/**
 * Sends a transactional email to a subscriber
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param array $args {
 *   Array of arguments.
 *
 *   @type int    $id      ID of the subscriber.
 *   @type string $email   Email address of the subscriber.
 *   @type string $code    Code of the subscriber.
 * }
 * @param string $subject  Subject of the email.
 * @param string $body     Body of the email.
 */

function fcnen_send_transactional_email( $args, $subject, $body ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_subscribers';
  $from = fcnen_get_from_email_address();
  $name = fcnen_get_from_email_name();
  $subscriber_email = $args['email'] ?? 0;
  $subscriber_code = $args['code'] ?? 0;

  // Query database
  if ( ( $args['id'] ?? 0 ) && ( ! $subscriber_email || ! $subscriber_code )  ) {
    $query = $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $args['id'] );
    $subscriber = $wpdb->get_row( $query, ARRAY_A );
    $subscriber_email = $subscriber['email'];
    $subscriber_code = $subscriber['code'];
  }

  // Guard
  if ( empty( $subscriber_email ) || empty( $subscriber_code ) ) {
    return;
  }

  // Prepare replacements
  $extra_replacements = array(
    '{{activation_link}}' => esc_url( fcnen_get_activation_link( $subscriber_email, $subscriber_code ) ),
    '{{unsubscribe_link}}' => esc_url( fcnen_get_unsubscribe_link( $subscriber_email, $subscriber_code ) ),
    '{{edit_link}}' => esc_url( fcnen_get_edit_link( $subscriber_email, $subscriber_code ) ),
    '{{email}}' => $subscriber_email,
    '{{code}}' => $subscriber_code
  );

  // Headers
  $headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . trim( $name )  . ' <'  . trim( $from ) . '>'
  );

  // Send the email
  wp_mail(
    $subscriber_email,
    fcnen_replace_placeholders( $subject, $extra_replacements ),
    fcnen_replace_placeholders( $body, $extra_replacements ),
    $headers
  );
}

/**
 * Sends a confirmation email to a subscriber
 *
 * @since 0.1.0
 *
 * @param array $args {
 *   Array of optional arguments. Passed on to next function.
 *
 *   @type int|null    $id     ID of the subscriber.
 *   @type string|null $email  Email address of the subscriber.
 *   @type string|null $code   Code of the subscriber.
 * }
 */

function fcnen_send_confirmation_email( $args ) {
  // Setup
  $subject = fcnen_get_confirmation_email_subject();
  $body = FCNEN_DEFAULTS['layout_confirmation'];

  // Customized?
  $body = get_option( 'fcnen_template_layout_confirmation' ) ?: $body;

  // Send
  fcnen_send_transactional_email( $args, $subject, $body );
}

/**
 * Send the edit code to a subscriber
 *
 * @since 0.1.0
 *
 * @param array  $args    {
 *   Array of arguments. Passed on to next function.
 *
 *   @type int $id  ID of the subscriber.
 * }
 */

function fcnen_send_code_email( $args ) {
  // Setup
  $subject = fcnen_get_code_email_subject();
  $body = FCNEN_DEFAULTS['layout_code'];

  // Customized?
  $custom_body = get_option( 'fcnen_template_layout_code' ) ?: $body;

  // Check for {{code}} presence
  if ( strpos( $custom_body, '{{code}}' ) !== false ) {
    $body = $custom_body;
  }

  // Send
  fcnen_send_transactional_email( $args, $subject, $body );
}
