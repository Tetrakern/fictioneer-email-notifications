<?php
/**
 * Plugin Name: Fictioneer Chapter Notifications
 * Description: Subscribe to chapter updates via email.
 * Version: 0.1.0
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Author: Tetrakern
 * Author URI: https://github.com/Tetrakern
 * Text Domain: fcncn
 */

// No direct access!
defined( 'ABSPATH' ) OR exit;

// Version
define( 'FCNCN_VERSION', '0.1.0' );

// =======================================================================================
// INCLUDES & REQUIRES
// =======================================================================================

require_once plugin_dir_path( __FILE__ ) . 'utility.php';
require_once plugin_dir_path( __FILE__ ) . 'actions.php';

if ( is_admin() ) {
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

function fcncn_create_subscribers_table() {
  global $wpdb;

  if ( ! function_exists( 'dbDelta' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  }

  // Setup
  $table_name = $wpdb->prefix . 'fcncn_subscribers';
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
    confirmed TINYINT(1) NOT NULL DEFAULT 0,
    trashed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (email)
  ) $charset_collate;";

  dbDelta( $sql );
}
register_activation_hook( __FILE__, 'fcncn_create_subscribers_table' );

// =======================================================================================
// REGISTER WITH THEME
// =======================================================================================

/**
 * Adds plugin card to theme settings plugin tab
 *
 * @since 0.1.0
 */

function fcncn_settings_card() {
  // Start HTML ---> ?>
  <div class="fictioneer-card fictioneer-card--plugin">
    <div class="fictioneer-card__wrapper">
      <h3 class="fictioneer-card__header"><?php _e( 'Fictioneer Chapter Notifications', 'fcncn' ); ?></h3>
      <div class="fictioneer-card__content">

        <div class="fictioneer-card__row">
          <p><?php
            _e( 'Subscribe to stories for chapter updates via email.', 'fcncn' );
          ?></p>
        </div>

        <div class="fictioneer-card__row fictioneer-card__row--meta">
          <?php printf( __( 'Version %s', 'fcncn' ), FCNCN_VERSION ); ?>
          |
          <?php printf( __( 'By <a href="%s">Tetrakern</a>', 'fcncn' ), 'https://github.com/Tetrakern' ); ?>
        </div>

      </div>
    </div>
  </div>
  <?php // <--- End HTML
}
add_action( 'fictioneer_admin_settings_plugins', 'fcncn_settings_card' );

/**
 * Checks for the Fictioneer (parent) theme, deactivates plugin if not found
 *
 * @since 0.1.0
 */

function fcncn_check_theme() {
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
    add_action( 'admin_notices', 'fcncn_admin_notice_wrong_theme' );
  }
}
add_action( 'after_setup_theme', 'fcncn_check_theme' );

/**
 * Show admin notice if plugin has been deactivated due to wrong theme
 *
 * @since 0.1.0
 */

function fcncn_admin_notice_wrong_theme() {
  // Start HTML ---> ?>
  <div class="notice notice-error is-dismissible">
    <p><?php
      _e( 'Fictioneer Chapter Notifications requires the Fictioneer theme or a child theme. The plugin has been deactivated.', 'fcncn' );
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

function fcncn_compare_wp_version( $version, $operator = '>=' ) {
  global $wp_version;

  return version_compare( $wp_version, $version, $operator );
}

/**
 * Enqueue frontend scripts and styles for the plugin
 *
 * @since 0.1.0
 */

function fcncn_enqueue_frontend_scripts() {
  // Setup
  $strategy = fcncn_compare_wp_version( '6.3' ) ? array( 'strategy'  => 'defer' ) : true; // Defer or load in footer

  // Styles
  wp_enqueue_style(
    'fcncn-frontend-styles',
    plugin_dir_url( __FILE__ ) . '/css/fcncn-frontend.css',
    ['fictioneer-application'],
    FCNCN_VERSION
  );

  // Scripts
  wp_enqueue_script(
    'fcncn-frontend-scripts',
    plugin_dir_url( __FILE__ ) . 'js/fcncn-frontend.min.js',
    [],
    FCNCN_VERSION,
    $strategy
  );
}
add_action( 'wp_enqueue_scripts', 'fcncn_enqueue_frontend_scripts' );

// =======================================================================================
// NOTICES
// =======================================================================================

/**
 * Show plugin admin notices
 *
 * Displays an admin notice based on the query parameter 'fcncn-notice'
 * and optionally 'fcncn-message' for additional information.
 *
 * @since 0.1.0
 */

function fcncn_admin_notices() {
  // Setup
  $notice = '';
  $class = '';
  $message = sanitize_text_field( $_GET['fcncn-message'] ?? '' );

  // Default notices
  if ( ( $_GET['settings-updated'] ?? 0 ) === 'true' ) {
    $notice = __( 'Settings saved' );
    $class = 'notice-success';
  }

  // FCNES notices
  switch ( $_GET['fcncn-notice'] ?? 0 ) {
    case 'subscriber-already-exists':
      $notice = __( 'Error. Subscriber with that email address already exists.', 'fcncn' );
      $class = 'notice-error';
      break;
    case 'subscriber-adding-success':
      $notice = sprintf( __( '%s added.', 'fcncn' ), $message ?: __( 'Subscriber', 'fcncn' ) );
      $class = 'notice-success';
      break;
    case 'subscriber-adding-failure':
      $notice = sprintf( __( 'Error. %s could not be added.', 'fcnes' ), $message ?: __( 'Subscriber', 'fcncn' ) );
      $class = 'notice-error';
      break;
  }

  // Render notice
  if ( ! empty( $notice ) ) {
    echo "<div class='notice {$class} is-dismissible'><p>{$notice}</p></div>";
  }
}
add_action( 'admin_notices', 'fcncn_admin_notices' );

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

    if ( ! $args['confirmed'] ) {
      fcncn_send_confirmation_email(
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

function fcncn_send_transactional_email( $args, $subject, $body ) {
  global $wpdb;

  // Setup
  $table_name = $wpdb->prefix . 'fcncn_subscribers';
  $from = 'no-reply@foobar.de';
  $name = get_bloginfo( 'name' );
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
  $replacements = array(
    '{{activation_link}}' => esc_url( fcncn_get_activation_link( $subscriber_email, $subscriber_code ) ),
    '{{unsubscribe_link}}' => esc_url( fcncn_get_unsubscribe_link( $subscriber_email, $subscriber_code ) ),
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
    fcncn_replace_placeholders( $subject ),
    fcncn_replace_placeholders( $body, $replacements ),
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
 *   @type string $email  Email address of the subscriber.
 *   @type string $code   Code of the subscriber.
 * }
 */

function fcncn_send_confirmation_email( $args ) {
  // Setup
  $subject = 'Please confirm your subscription';
  $body = __( '<p>Thank you for subscribing to <a href="{{site_link}}" target="_blank">{{site_name}}</a>. Please click the following link within 24 hours to confirm your subscription: <a href="{{activation_link}}">Activate Subscription</a>.<br><br>Your edit code is <strong>{{code}}</strong>, which will also be included in any future emails.<br><br>If someone has subscribed you against your will or you reconsidered, worry not! Without confirmation, your subscription and email address will be deleted after 24-36 hours (depending on the worker schedule).</p>', 'fcncn' );

  // Send
  fcncn_send_transactional_email( $args, $subject, $body );
}
