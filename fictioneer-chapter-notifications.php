<?php
/**
 * Plugin Name: Fictioneer Chapter Notifications
 * Description: Subscribe to chapter updates via email.
 * Version: 1.0.0
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Author: Tetrakern
 * Author URI: https://github.com/Tetrakern
 * Text Domain: fcncn
 */

// No direct access!
defined( 'ABSPATH' ) OR exit;

// Version
define( 'FCNCN_VERSION', '1.0.0' );

// =======================================================================================
// INSTALLATION
// =======================================================================================



// =======================================================================================
// REGISTER WITH THEME
// =======================================================================================

/**
 * Adds plugin card to theme settings plugin tab
 *
 * @since 1.0.0
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
 * @since 1.0.0
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
 * @since 1.0.0
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
 * @since 1.0.0
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
 * @since 1.0.0
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

/**
 * Enqueues styles and scripts in the admin
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix  The current admin page.
 */

function fcncn_enqueue_admin_scripts( $hook_suffix ) {
  // Only on the theme's plugin settings tab
  if ( $hook_suffix !== 'fictioneer_page_fictioneer_plugins' ) {
    return;
  }

  // Styles
  wp_enqueue_style(
    'fcncn-admin-styles',
    plugin_dir_url( __FILE__ ) . '/css/fcncn-admin.css',
    ['fictioneer-admin-panel'],
    FCNCN_VERSION
  );

  // Scripts
  wp_enqueue_script(
    'fcncn-admin-scripts',
    plugin_dir_url( __FILE__ ) . '/js/fcncn-admin.js',
    ['fictioneer-utility-scripts'],
    FCNCN_VERSION,
    true
  );
}
add_action( 'admin_enqueue_scripts', 'fcncn_enqueue_admin_scripts' );
