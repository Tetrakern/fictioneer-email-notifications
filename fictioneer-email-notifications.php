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
// CONSTANTS & DEFAULTS
// =======================================================================================

if ( ! defined( 'FCNEN_LOG_LIMIT' ) ) {
  define( 'FCNEN_LOG_LIMIT', 2000 );
}

if ( ! defined( 'FCNEN_API_LIMIT' ) ) {
  define( 'FCNEN_API_LIMIT', 10 );
}

if ( ! defined( 'FCNEN_API_INTERVAL' ) ) {
  define( 'FCNEN_API_INTERVAL', 60000 );
}

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

/**
 * Returns all options and defaults or one specific default
 *
 * @since 0.1.0
 *
 * @param string|null $option  Optional. Name of the option to get the default for.
 *
 * @return array|mixed Returns an associative array of default options and values or the
 *                     default value of the passed option (empty string if not defined).
 */

function fcnen_option_defaults( $option = null ) {
  static $defaults = null;

  if ( $defaults === null ) {
    $defaults = array(
      'fcnen_from_email_address' => '',
      'fcnen_from_email_name' => '',
      'fcnen_flag_subscribe_to_stories' => 0,
      'fcnen_flag_subscribe_to_taxonomies' => 0,
      'fcnen_flag_allow_passwords' => 0,
      'fcnen_flag_allow_hidden' => 0,
      'fcnen_flag_purge_on_deactivation' => 0,
      'fcnen_excerpt_length' => 256,
      'fcnen_max_per_term' => 10,
      'fcnen_excluded_posts' => [],
      'fcnen_excluded_authors' => [],
      'fcnen_api_key' => '',
      'fcnen_api_bulk_limit' => 300,
      'fcnen_template_subject_confirmation' => _x( 'Please confirm your subscription', 'Email subject', 'fcnen' ),
      'fcnen_template_subject_code' => _x( 'Your subscription code', 'Email subject', 'fcnen' ),
      'fcnen_template_subject_edit' => _x( 'Your subscription has been updated', 'Email subject', 'fcnen' ),
      'fcnen_template_subject_notification' => _x( 'Updates on {{site_name}}', 'Email subject', 'fcnen' ),
      'fcnen_template_layout_confirmation' =>
<<<EOT
<p>Thank you for subscribing to <a href="{{site_link}}" target="_blank">{{site_name}}</a>.</p>

<p>Please click the following link within 24 hours to confirm your subscription: <a href="{{activation_link}}">Activate Subscription</a>.</p>

<p>Your edit code is <strong>{{code}}</strong>, which will also be included in any future emails. In case your code ever gets compromised, just delete your subscription and submit a new one.</p>

<p>If someone has subscribed you against your will or you reconsidered, worry not! Without confirmation, your subscription and email address will be deleted after 24 hours. You can also immediately <a href="{{unsubscribe_link}}" data-id="{{id}}">delete it with this link</a>.</p>
EOT,
      'fcnen_template_layout_code' =>
<<<EOT
<p>Following is the edit code for your email subscription on <a href="{{site_link}}" target="_blank">{{site_name}}</a>. Do not share it. If compromised, just delete your subscription and submit a new one.</p>

<p><strong>{{code}}</strong></p>

<p>You can also directly edit your subscription with this <a href="{{edit_link}}" target="_blank" data-id="{{id}}">link</a>.</p>
EOT,
      'fcnen_template_layout_edit' =>
<<<EOT
<p>Your subscription preferences on <a href="{{site_link}}" target="_blank">{{site_name}}</a> have been updated to:</p>

<ul style="padding-left: 20px; margin: 20px 0;">

  {{#scope_everything}}<li>Everything</li>{{/scope_everything}}

  {{^scope_everything}}
  {{#scope_post_types}}<li><strong>Post Types:</strong> {{scope_post_types}}</li>{{/scope_post_types}}
  {{#scope_stories}}<li><strong>Stories:</strong> {{scope_stories}}</li>{{/scope_stories}}
  {{#scope_categories}}<li><strong>Categories:</strong> {{scope_categories}}</li>{{/scope_categories}}
  {{#scope_tags}}<li><strong>Tags:</strong> {{scope_tags}}</li>{{/scope_tags}}
  {{#scope_genres}}<li><strong>Genres:</strong> {{scope_genres}}</li>{{/scope_genres}}
  {{#scope_fandoms}}<li><strong>Fandoms:</strong> {{scope_fandoms}}</li>{{/scope_fandoms}}
  {{#scope_characters}}<li><strong>Characters:</strong> {{scope_characters}}</li>{{/scope_characters}}
  {{#scope_warnings}}<li><strong>Warnings:</strong> {{scope_warnings}}</li>{{/scope_warnings}}
  {{/scope_everything}}

</ul>

<p>If that was not you, please <a href="{{unsubscribe_link}}" target="_blank" data-id="{{id}}">delete<a> and renew your subscription. Also make sure your email account is not compromised and never share your code.</p>
EOT,
      'fcnen_template_layout_notification' =>
<<<EOT
<p>Hello,<br><br>There are new updates on <a href="{{site_link}}" target="_blank">{{site_name}}</a> matching your preferences. You are receiving this email because you subscribed to content updates. You can <a href="{{edit_link}}" target="_blank">edit</a> your subscription at any time. If you no longer want to receive updates, you can <a href="{{unsubscribe_link}}" target="_blank" data-id="{{id}}">unsubscribe</a>.</p>

<div>{{updates}}</div>

<hr style="border: 0; border-top: 1px solid #ccc;">

<div style="font-size: 75%;">Your edit code is <strong>{{code}}</strong>.</div>
EOT,
      'fcnen_template_loop_part_post' =>
<<<EOT
<fieldset style="padding: 10px; margin: 20px 0; border: 1px solid #ccc;">
  <div>
    <div style="font-size: 14px;">
      <strong>Blog: <a href="{{link}}" style="text-decoration: none;">{{title}}</a></strong>
    </div>
    <div style="font-size: 11px; margin-top: 5px;">by {{author}}</div>
  </div>
  <div style="margin-top: 10px">{{excerpt}}</div>
</fieldset>
EOT,
      'fcnen_template_loop_part_story' =>
<<<EOT
<fieldset style="padding: 10px; margin: 20px 0; border: 1px solid #ccc;">
  <div>
    <div style="font-size: 14px;">
      <strong>Story: <a href="{{link}}" style="text-decoration: none;">{{title}}</a></strong>
    </div>
    <div style="font-size: 11px; margin-top: 5px;">by {{author}}</div>
  </div>
  <div style="margin-top: 10px">{{excerpt}}</div>
</fieldset>
EOT,
      'fcnen_template_loop_part_chapter' =>
<<<EOT
<fieldset style="padding: 10px; margin: 20px 0; border: 1px solid #ccc;">
  <div>
    <div style="font-size: 14px;">
      <strong>Chapter: <a href="{{link}}" style="text-decoration: none;">{{title}}</a></strong>
    </div>
    <div style="font-size: 11px; margin-top: 5px;">by {{author}}{{#story_title}} in <a href="{{story_link}}" style="text-decoration: none;">{{story_title}}</a>{{/story_title}}</div>
  </div>
  <div style="margin-top: 10px">{{excerpt}}</div>
</fieldset>
EOT
    );
  }

  return $option ? ( $defaults[ $option ] ?? '' ) : $defaults;
}

// =======================================================================================
// INCLUDES & REQUIRES
// =======================================================================================

require_once plugin_dir_path( __FILE__ ) . 'utility.php';
require_once plugin_dir_path( __FILE__ ) . 'modal.php';

if ( is_admin() ) {
  require_once plugin_dir_path( __FILE__ ) . 'actions.php';
  require_once plugin_dir_path( __FILE__ ) . 'admin.php';
  require_once plugin_dir_path( __FILE__ ) . 'ajax.php';
}

// =======================================================================================
// INSTALLATION
// =======================================================================================

/**
 * Add default options to database table
 *
 * Note: These options are not required on every page load
 * and should therefore not be auto-loaded.
 *
 * @since 0.1.0
 */

function fcnen_add_default_options() {
  // Setup
  $default_options = fcnen_option_defaults();

  // Options
  foreach ( $default_options as $option => $default ) {
    if ( ! get_option( $option ) ) {
      add_option( $option, $default, '', 'no' );
    }
  }

  // Plugin info
  if ( ! get_option( 'fcnen_plugin_info' ) ) {
    $info = array(
      'install_date' => current_time( 'mysql', 1 ),
      'last_update_check' => current_time( 'mysql', 1 ),
      'found_update_version' => '',
      'last_sent' => ''
    );

    add_option( 'fcnen_plugin_info', $info );
  }
}
register_activation_hook( __FILE__, 'fcnen_add_default_options' );

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
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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

/**
 * Create the notification database table
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 */

function fcnen_create_notification_table() {
  global $wpdb;

  if ( ! function_exists( 'dbDelta' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';
  $charset_collate = $wpdb->get_charset_collate();

  // Skip if the table already exists
  if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
    return;
  }

  // Table creation query
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED DEFAULT NULL,
    post_title TEXT NOT NULL,
    post_type varchar(20) NOT NULL,
    post_author BIGINT UNSIGNED NOT NULL DEFAULT 0,
    paused TINYINT(1) NOT NULL DEFAULT 0,
    added_at DATETIME NOT NULL,
    last_sent DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX post_id_index (post_id)
  ) $charset_collate;";

  dbDelta( $sql );
}
register_activation_hook( __FILE__, 'fcnen_create_notification_table' );

/**
 * Create the meta database table
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 */

function fcnen_create_meta_table() {
  global $wpdb;

  if ( ! function_exists( 'dbDelta' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_meta';
  $charset_collate = $wpdb->get_charset_collate();

  // Skip if the table already exists
  if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
    return;
  }

  // Table creation query
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id BIGINT UNSIGNED NOT NULL,
    meta LONGTEXT NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    INDEX post_id_index (post_id)
  ) $charset_collate;";

  dbDelta( $sql );
}
register_activation_hook( __FILE__, 'fcnen_create_meta_table' );

// =======================================================================================
// DEACTIVATION
// =======================================================================================

/**
 * Clean up when the plugin is deactivated
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 */

function fcnen_deactivation() {
  global $wpdb;

  // Guard
  if ( ! get_option( 'fcnen_flag_purge_on_deactivation' ) ) {
    return;
  }

  // Setup
  $default_options = fcnen_option_defaults();

  // Delete options
  foreach ( $default_options as $option => $values ) {
    delete_option( $option );
  }

  delete_option( 'fcnen_plugin_info' );

  // Drop tables
  $tables = array(
    $wpdb->prefix . 'fcnen_subscribers',
    $wpdb->prefix . 'fcnen_notifications',
    $wpdb->prefix . 'fcnen_meta'
  );

  foreach ($tables as $table) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
  }

  // Delete log file
  $log_file = WP_CONTENT_DIR . '/fcnen-log.log';

  if ( file_exists( $log_file ) ) {
    unlink( $log_file );
  }
}
register_deactivation_hook( __FILE__, 'fcnen_deactivation' );

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
  $cache_bust = fictioneer_get_cache_bust();
  $strategy = fcnen_compare_wp_version( '6.3' ) ? array( 'strategy'  => 'defer' ) : true; // Defer or load in footer

  // Styles
  wp_enqueue_style(
    'fcnen-frontend-styles',
    plugin_dir_url( __FILE__ ) . '/css/fcnen-frontend.css',
    get_option( 'fictioneer_bundle_stylesheets' ) ? ['fictioneer-complete'] : ['fictioneer-application'],
    $cache_bust
  );

  // Scripts
  wp_enqueue_script(
    'fcnen-frontend-scripts',
    plugin_dir_url( __FILE__ ) . 'js/fcnen-frontend.min.js',
    [],
    $cache_bust,
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

/**
 * Load plugin textdomain
 *
 * @since 0.1.0
 */

function fcnen_load_textdomain() {
  load_plugin_textdomain( 'fcnen', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'fcnen_load_textdomain' );

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

  // Story
  if ( $post_id && get_post_type( $post_id ) === 'fcn_story' ) {
    $attributes .= " data-story-id='{$post_id}'";
    $attributes .= " data-story-title='" . esc_attr( get_the_title( $post_id ) ) . "'";
  }

  // Chapter
  if ( $post_id && get_post_type( $post_id ) === 'fcn_chapter' ) {
    $story_id = get_post_meta( $post_id, 'fictioneer_chapter_story', true );

    if ( $story_id ) {
      $attributes .= " data-story-id='{$story_id}'";
      $attributes .= " data-story-title='" . esc_attr( get_the_title( $story_id ) ) . "'";
    }
  }

  // Story Page template
  if ( $post_id && is_page_template( 'singular-story.php' ) ) {
    $story_id = get_post_meta( $post_id, 'fictioneer_template_story_id', true );

    if ( $story_id ) {
      $attributes .= " data-story-id='{$story_id}'";
      $attributes .= " data-story-title='" . esc_attr( get_the_title( $story_id ) ) . "'";
    }
  }

  // Build and return HTML
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

/**
 * Adds subscription button to user menu
 *
 * @since 0.1.0
 *
 * @param array $items  The user menu items.
 *
 * @return array The updated user menu items.
 */

function fcnen_add_user_menu_subscription_button( $items ) {
  // Setup
  $html = '<li class="menu-item"><a data-click-target="#fcnen-subscription-modal" data-click-action="open-dialog-modal fcnen-load-modal-form" class="_align-left" tabindex="0">' . __( 'Subscription', 'fcnen' ) . '</a></li>';

  // Insert in second to last place
  array_splice( $items, count( $items ) - 1, 0, $html );

  // Continue filter
  return $items;
}
add_action( 'fictioneer_filter_user_menu_items', 'fcnen_add_user_menu_subscription_button', 10 );

/**
 * Adds subscription button to mobile menu
 *
 * @since 0.1.0
 *
 * @param array $items  The mobile user menu items.
 *
 * @return array The updated mobile user menu items.
 */

function fcnen_add_mobile_subscription_button( $items ) {
  // Setup
  $html = '<a data-click-target="#fcnen-subscription-modal" data-click-action="open-dialog-modal fcnen-load-modal-form"><i class="fa-solid fa-envelope mobile-menu__item-icon"></i> ' . __( 'Subscription', 'fcnen' ) . '</a>';

  // Insert in second to last place
  array_splice( $items, count( $items ) - 1, 0, $html );

  // Continue filter
  return $items;
}
add_action( 'fictioneer_filter_mobile_user_menu_items', 'fcnen_add_mobile_subscription_button', 10 );

/**
 * Outputs the HTML for the account profile section
 *
 * @since 0.1.0
 *
 * @param WP_User $args['user']          Current user.
 * @param boolean $args['is_admin']      True if the user is an administrator.
 * @param boolean $args['is_author']     True if the user is an author (by capabilities).
 * @param boolean $args['is_editor']     True if the user is an editor.
 * @param boolean $args['is_moderator']  True if the user is a moderator (by capabilities).
 */

function fcnen_account_profile_section( $args ) {
  // Setup
  $current_user = $args['user'];
  $email = get_user_meta( $current_user->ID, 'fcnen_subscription_email', true ) ?: '';
  $code = get_user_meta( $current_user->ID, 'fcnen_subscription_code', true ) ?: '';
  $subscription = null;
  $link_status = null;
  $action_url = esc_url( admin_url( 'admin-post.php?action=fcnen_update_profile' ) );

  // Subscription?
  if ( ! empty( $email ) && ! empty( $code ) ) {
    $subscription = fcnen_get_subscriber_by_email_and_code( $email, $code );
  }

  // Linked?
  if ( $subscription === false ) {
    $link_status = 'mismatch';
  } elseif ( ! empty( $subscription ) ) {
    $link_status = 'linked';
  }

  // Start HTML ---> ?>
  <h3 id="fcnen" class="profile__account-headline"><?php _e( 'Email Subscription', 'fcnen' ) ?></h3>

  <p class="profile__description"><?php
    _e( 'Your email subscription for selected content updates is kept separate from your account, meaning you can use a different email address. But you also need to authenticate with your code every time you wish to view or update your subscription. For convenience, you can link your subscription here.', 'fcnen' );
  ?></p>

  <?php
    if ( $link_status === 'mismatch' ) {
      fictioneer_notice( __( 'No matching subscription found.', 'fcnen' ) );
    }
  ?>

  <form method="post" action="<?php echo $action_url; ?>" class="profile__fcnen profile__segment">
    <?php wp_nonce_field( 'fcnen-update-profile', 'fcnen-nonce' ); ?>
    <input name="user_id" type="hidden" value="<?php echo $current_user->ID; ?>">

    <div class="profile__input-group">
      <div class="profile__input-label">
        <?php _ex( 'Subscription Email Address', 'Profile label for subscription email address.', 'fcnen' ) ?>
      </div>
      <div class="profile__input-wrapper _checkmark">
        <?php
          if ( $link_status === 'linked' ) {
            echo '<i class="fa-solid fa-circle-check checkmark"></i>';
          }
        ?>
        <input type="email" maxlength="191" name="fcnen-email" value="<?php echo esc_attr( $email ); ?>" class="profile__input-field profile__fcnen-email">
        <p class="profile__input-note"><?php _e( 'The email address used for your subscription.', 'fcnen' ) ?></p>
      </div>
    </div>

    <div class="profile__input-group">
      <div class="profile__input-label">
        <?php _ex( 'Subscription Code', 'Profile label for subscription code.', 'fcnen' ) ?>
      </div>
      <div class="profile__input-wrapper">
        <?php if ( $link_status === 'linked' ) : ?>
          <i class="fa-solid fa-circle-check checkmark"></i>
        <?php endif; ?>
        <input type="text" name="fcnen-code" value="<?php echo esc_attr( $code ); ?>" class="profile__input-field profile__fcnen-code">
        <p class="profile__input-note"><?php _e( 'Found in notification emails. If compromised, delete and renew subscription.', 'fcnen' ) ?></p>
      </div>
    </div>

    <div class="profile__actions">
      <input name="submit" type="submit" value="<?php esc_attr_e( 'Save', 'fictioneer' ) ?>" class="button">
      <button type="button" class="button _secondary" data-click-target="#fcnen-subscription-modal" data-click-action="open-dialog-modal fcnen-load-modal-form"><?php _e( 'Open Modal', 'fcnen' ); ?></button>
    </div>

  </form>
  <?php // <--- End HTML
}
add_action( 'fictioneer_account_content', 'fcnen_account_profile_section', 25 );

/**
 * Shortcode to show subscription form (opens modal)
 *
 * @since 0.1.0
 *
 * @param string|null $attr['placeholder']  Optional. Placeholder text override.
 *
 * @return string The shortcode HTML.
 */

function fcnen_shortcode_subscription( $attr ) {
  // Setup
  $placeholder = sanitize_text_field( $attr['placeholder'] ?? __( 'Subscribe for email updates…', 'fcnen' ) );

  // Build and return
  return '<div class="fcnen-subscription-shortcode" data-click-target="#fcnen-subscription-modal" data-click-action="open-dialog-modal fcnen-load-modal-form fcnen-input-modal-toggle" tabindex="0"><input type="email" class="fcnen-subscription-shortcode__input" autocomplete="off" autocorrect="off" tabindex="-1" placeholder="' . $placeholder . '"></div>';
}
add_shortcode( 'fictioneer_email_subscription', 'fcnen_shortcode_subscription' );

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
  $max_per_term = get_option( 'fcnen_max_per_term', 10 );

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
    'created_at' => current_time( 'mysql', 1 ),
    'updated_at' => current_time( 'mysql', 1 ),
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
    $args['created_at'] = current_time( 'mysql', 1 );
  }

  if ( ! $updated_at_date || $updated_at_date->format( 'Y-m-d H:i:s' ) !== $args['updated_at'] ) {
    $args['updated_at'] = current_time( 'mysql', 1 );
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

  // Limit items
  if ( $max_per_term > 0 ) {
    $args['categories'] = array_slice( $args['categories'], 0, $max_per_term );
    $args['tags'] = array_slice( $args['tags'], 0, $max_per_term );
    $args['taxonomies'] = array_slice( $args['taxonomies'], 0, $max_per_term );
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
          'code' => $args['code'],
          'id' => $subscriber_id
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
  $max_per_term = get_option( 'fcnen_max_per_term', 10 );

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

  // Limit items
  if ( $max_per_term > 0 ) {
    $args['categories'] = array_slice( $args['categories'], 0, $max_per_term );
    $args['tags'] = array_slice( $args['tags'], 0, $max_per_term );
    $args['taxonomies'] = array_slice( $args['taxonomies'], 0, $max_per_term );
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

  // Edit notification email
  if ( $result ) {
    fcnen_send_edit_email(
      array(
        'email' => $email,
        'code' => $subscriber->code,
        'id' => $subscriber->id
      )
    );
  }

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
// NOTIFICATIONS
// =======================================================================================

/**
 * Adds a notification
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param int $post_id  The ID of the post to add as notification.
 *
 * @return int|false The ID of the inserted notification, false on failure.
 */

function fcnen_add_notification( $post_id ) {
  global $wpdb;

  // Check for unsent duplicate
  if ( fcnen_unsent_notification_exists( $post_id ) ) {
    return false;
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';
  $post = get_post( $post_id );
  $allowed_types = ['post', 'fcn_story', 'fcn_chapter'];
  $story_id = null;
  $excluded_posts = get_option( 'fcnen_excluded_posts', [] ) ?: [];
  $excluded_authors = get_option( 'fcnen_excluded_authors', [] ) ?: [];

  // Post not found
  if ( ! $post ) {
    return false;
  }

  // Excluded author ID?
  if ( in_array( $post->post_author, $excluded_authors ) ) {
    return false;
  }

  // Wrong post type
  if ( ! in_array( $post->post_type, $allowed_types ) ) {
    return false;
  }

  // Chapter?
  if ( $post->post_type === 'fcn_chapter' ) {
    $story_id = get_post_meta( $post->ID, 'fictioneer_chapter_story', true ) ?: null;
  }

  // Excluded post ID?
  if ( in_array( $post_id, $excluded_posts ) || in_array( $story_id ?? 0, $excluded_posts ) ) {
    return false;
  }

  // Insert into table
  $result = $wpdb->insert(
    $table_name,
    array(
      'post_id' => $post->ID,
      'story_id' => $story_id,
      'post_title' => $post->post_title,
      'post_type' => $post->post_type,
      'post_author' => $post->post_author,
      'added_at' => current_time( 'mysql', 1 )
    ),
    array( '%d', '%s', '%s', '%s', '%d', '%s' )
  );

  // Return ID of the notification or false
  if ( $result ) {
    return $wpdb->insert_id;
  } else {
    return false;
  }
}

/**
 * Track updates and add notifications
 *
 * @since 0.1.0
 *
 * @param int     $post_id  The ID of the post being saved.
 * @param WP_Post $post     The post object being saved.
 */

function fcnen_track_posts( $post_id, $post ) {
  // Prevent miss-fire
  if (
    fictioneer_multi_save_guard( $post_id ) ||
    $post->post_status !== 'publish'
  ) {
    return;
  }

  // Setup
  $meta = fcnen_get_meta( $post_id );
  $dates = $meta['sent'] ?? [];
  $on_update = $_POST['fcnen_enqueue_on_update'] ?? 0;
  $current_time = current_datetime()->format( 'U' );
  $publish_time = get_post_time( 'U', false, $post );
  $is_new = $current_time - $publish_time < 30 && empty( $dates );
  $allow_password = get_option( 'fcnen_flag_allow_passwords' );
  $allow_hidden = get_option( 'fcnen_flag_allow_hidden' );

  // Excluded?
  if ( $meta['excluded'] ?? 0 ) {
    return;
  }

  // New or enqueued on update?
  if ( ! $is_new && ! $on_update ) {
    return;
  }

  // Ignore disallowed
  if ( get_option( 'fcnen_flag_disable_blocked_enqueue' ) ) {
    // Password?
    if ( ! $allow_password && ! empty( $post->post_password ) ) {
      return;
    }

    // Hidden?
    $story_hidden = $_POST['fictioneer_story_hidden'] ?? 0;
    $chapter_hidden = $_POST['fictioneer_chapter_hidden'] ?? 0;

    if ( ! $allow_hidden && ( $story_hidden || $chapter_hidden ) ) {
      return;
    }
  }

  // Add notification
  fcnen_add_notification( $post_id );
}
add_action( 'save_post_post', 'fcnen_track_posts', 20, 2 );
add_action( 'save_post_fcn_story', 'fcnen_track_posts', 20, 2 );
add_action( 'save_post_fcn_chapter', 'fcnen_track_posts', 20, 2 );

/**
 * Delete related data on post deletion
 *
 * @since 0.1.0
 * @global wpdb $wpdb  The WordPress database object.
 *
 * @param int $post_id  The ID of the post.
 */

function fcnen_cleanup_on_post_delete( $post_id ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcnen_notifications';

  // Delete meta
  fcnen_delete_meta( $post_id );

  // Delete notifications
  $wpdb->delete( $table_name, array( 'post_id' => $post_id ), ['%d'] );
}
add_action( 'before_delete_post', 'fcnen_cleanup_on_post_delete' );

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
    '{{id}}' => $args['id'] ?? __( '####', 'fcnen' ),
    '{{activation_link}}' => esc_url( fcnen_get_activation_link( $subscriber_email, $subscriber_code ) ),
    '{{unsubscribe_link}}' => esc_url( fcnen_get_unsubscribe_link( $subscriber_email, $subscriber_code ) ),
    '{{edit_link}}' => esc_url( fcnen_get_edit_link( $subscriber_email, $subscriber_code ) ),
    '{{email}}' => $subscriber_email,
    '{{code}}' => $subscriber_code,
    '{{scope_everything}}' => $args['scope_everything'] ?? 0,
    '{{scope_stories}}' => implode( ', ', $args['scope_stories'] ?? [] ),
    '{{scope_post_types}}' => implode( ', ', $args['scope_post_types'] ?? [] ),
    '{{scope_categories}}' => implode( ', ', $args['scope_categories'] ?? [] ),
    '{{scope_tags}}' => implode( ', ', $args['scope_tags'] ?? [] ),
    '{{scope_genres}}' => implode( ', ', $args['scope_genres'] ?? [] ),
    '{{scope_fandoms}}' => implode( ', ', $args['scope_fandoms'] ?? [] ),
    '{{scope_characters}}' => implode( ', ', $args['scope_characters'] ?? [] ),
    '{{scope_warnings}}' => implode( ', ', $args['scope_warnings'] ?? [] )
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
  fcnen_send_transactional_email(
    $args,
    fcnen_get_confirmation_email_subject(),
    fcnen_get_confirmation_email_body()
  );
}

/**
 * Sends the edit code to a subscriber
 *
 * @since 0.1.0
 *
 * @param array $args {
 *   Array of arguments. Passed on to next function.
 *
 *   @type int $id  ID of the subscriber.
 * }
 */

function fcnen_send_code_email( $args ) {
  fcnen_send_transactional_email(
    $args,
    fcnen_get_code_email_subject(),
    fcnen_get_code_email_body()
  );
}

/**
 * Sends the current subscription preferences to a subscriber
 *
 * @since 0.1.0
 *
 * @param array $args {
 *   Array of arguments. Passed on to next function.
 *
 *   @type string $email  Email address of the subscriber.
 *   @type string $code   Code of the subscriber.
 * }
 */

function fcnen_send_edit_email( $args ) {
  // Subscriber
  $updated_subscriber = fcnen_get_subscriber_by_email( $args['email'] );

  // Subscriber valid??
  if ( ! $updated_subscriber || $updated_subscriber->trashed ) {
    return;
  }

  // Setup
  $subject = fcnen_get_edit_email_subject();
  $body = fcnen_get_edit_email_body();

  // Prepare scopes
  $args = array_merge( $args, fcnen_get_subscriber_scopes( $updated_subscriber ) );

  // Send
  fcnen_send_transactional_email( $args, $subject, $body );
}

// =======================================================================================
// QUEUE & BULK EMAILS
// =======================================================================================

/**
 * Process email queue and post bulk emails to provider
 *
 * @since 0.1.0
 *
 * @param int  $index  Index of current batch. Default 0.
 * @param bool $fresh  Whether to start from the top. Default false.
 *
 * @return array Response data for use in AJAX requests.
 */

function fcnen_process_email_queue( $index = 0, $new = false ) {
  // Setup
  $queue = get_transient( 'fcnen_request_queue' ) ?: fcnen_get_email_queue();
  $batch_count = count( $queue['batches'] );
  $current_batch = $queue['batches'][ $index ] ?? null;
  $next_index = ( $batch_count - 1 > $index ) ? $index + 1 : -1;

  // Empty?
  if ( empty( $queue ) || empty( $queue['batches'] ) ) {
    // Response
    return array(
      'result' => 'empty',
      'message' => __( 'Queue is empty.', 'fcnen' ),
      'index' => $index
    );
  }

  // Complete?
  if ( fcnen_are_batches_completed( $queue['batches'] ) ) {
    delete_transient( 'fcnen_request_queue' );

    // Response
    return array(
      'result' => 'complete',
      'index' => $index,
      'next' => -1,
      'count' => $batch_count,
      'html' => fcnen_build_queue_html( $queue['batches'] )
    );
  }

  // End?
  if ( $index > $batch_count - 1 ) {
    // Response
    return array(
      'result' => 'finished',
      'index' => $index,
      'next' => -1,
      'count' => $batch_count,
      'html' => fcnen_build_queue_html( $queue['batches'] )
    );
  }

  // New
  if ( $new ) {
    // Response
    return array(
      'result' => 'new',
      'index' => 0,
      'next' => 0,
      'count' => $batch_count,
      'html' => fcnen_build_queue_html( $queue['batches'], 0 )
    );
  }

  // Once per queue run...
  if ( $index < 1 ) {
    // Update plugin info
    update_option( 'fcnen_last_sent', current_time( 'mysql', 1 ), 'no' );

    // Mark unsent notifications and posts as 'sent'
    foreach ( $queue['post_ids'] as $post_id ) {
      if ( fcnen_unsent_notification_exists( $post_id ) ) {
        // Update notification
        fcnen_mark_notification_as_sent( $post_id );

        // Update fcnen post meta
        $meta = fcnen_get_meta( $post_id );
        $meta['sent'][] = current_time( 'mysql', 1 );
        fcnen_set_meta( $post_id, $meta );
      }
    }
  }

  // Current batch already completed?
  if ( $current_batch['success'] ?? 0 ) {
    $current_batch = null;

    // Response data
    return array(
      'result' => 'skipped_successful',
      'index' => $index,
      'next' => $next_index,
      'count' => $batch_count,
      'html' => fcnen_build_queue_html( $queue['batches'], $next_index )
    );
  }

  // Request
  $response = fcnen_send_bulk_notifications( $current_batch['payload'] );

  // Update queue state
  if ( ! is_wp_error( $response ) ) {
    $response_body = wp_remote_retrieve_body( $response );
    $response_code = wp_remote_retrieve_response_code( $response );

    $queue['batches'][ $index ]['response'] = $response_body;
    $queue['batches'][ $index ]['code'] = $response_code;

    if ( $response_code >= 200 && $response_code < 300 ) {
      $queue['batches'][ $index ]['success'] = true;
      $queue['batches'][ $index ]['status'] = 'transmitted';
    } else {
      $queue['batches'][ $index ]['success'] = false;
      $queue['batches'][ $index ]['status'] = 'failure';
    }

    fcnen_log( sprintf( __( 'Sending Response: Status %s | %s', 'fcnen' ), $response_code, $response_body ) );
  } else {
    $queue['batches'][ $index ]['success'] = false;
    $queue['batches'][ $index ]['status'] = 'error';

    fcnen_log( sprintf( __( 'Sending Error: %s', 'fcnen' ), $response->get_error_message() ) );
  }

  $queue['batches'][ $index ]['date'] = current_time( 'mysql', 1 );
  $queue['batches'][ $index ]['attempts'] += 1;

  // Update or delete Transient
  if ( fcnen_are_batches_completed( $queue['batches'] ) ) {
    delete_transient( 'fcnen_request_queue' );
  } else {
    set_transient( 'fcnen_request_queue', $queue, DAY_IN_SECONDS );
  }

  // Response
  return array(
    'result' => 'processed',
    'index' => $index,
    'next' => $next_index,
    'count' => $batch_count,
    'html' => fcnen_build_queue_html( $queue['batches'], $next_index )
  );
}

/**
 * Send bulk email request for payload
 *
 * @since 0.1.0
 *
 * @param array $payload  Emails to be sent.
 *
 * @return array|WP_Error The response or WP_Error on failure.
 */

function fcnen_send_bulk_notifications( $payload ) {
  // Setup
  $api_key = get_option( 'fcnen_api_key' ) ?: 0;

  // API key missing
  if ( empty( $api_key ) ) {
    return new WP_Error( 'api_key_missing', __( 'API key missing.', 'fcnen' ) );
  }

  // Send and return response
  return wp_remote_post(
    FCNEN_API['mailersend']['bulk'],
    array(
      'headers' => array(
        'Authorization' => "Bearer {$api_key}",
        'Content-Type' => 'application/json'
      ),
      'body' => json_encode( $payload )
    )
  );
}
