<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// =======================================================================================
// SETUP
// =======================================================================================

/**
 * Enqueues styles and scripts in the admin
 *
 * @since 0.1.0
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

// =======================================================================================
// ADMIN NOTIFICATIONS PAGE
// =======================================================================================

/**
 * Add notifications admin menu page
 *
 * @since 0.1.0
 */

function fcncn_add_notifications_menu_page() {
  // Guard
  if ( ! current_user_can( 'administrator' ) ) {
    return;
  }

  // Add admin page
  $notifications = add_menu_page(
    'Chapter Notifications',
    'Notifications',
    'manage_options',
    'fcncn-notifications',
    'fcncn_notifications_page',
    'dashicons-email-alt'
  );
}
add_action( 'admin_menu', 'fcncn_add_notifications_menu_page' );

/**
 * Callback for the notifications menu page
 *
 * @since 0.1.0
 */

function fcncn_notifications_page() {
  // Guard
  if ( ! current_user_can( 'administrator' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnes' ) );
  }

  // Start HTML ---> ?>
  <?php // <--- End HTML
}

// =======================================================================================
// ADMIN SUBSCRIBERS PAGE
// =======================================================================================

/**
 * Add subscribers admin submenu page
 *
 * @since 0.1.0
 */

function fcncn_add_subscribers_menu_page() {
  // Guard
  if ( ! current_user_can( 'administrator' ) ) {
    return;
  }

  // Add admin page
  $notifications = add_submenu_page(
    'fcncn-notifications',
    'Subscribers',
    'Subscribers',
    'manage_options',
    'fcncn-subscribers',
    'fcncn_subscribers_page'
  );
}
add_action( 'admin_menu', 'fcncn_add_subscribers_menu_page' );

/**
 * Callback for the subscribers submenu page
 *
 * @since 0.1.0
 */

function fcncn_subscribers_page() {
  // Guard
  if ( ! current_user_can( 'administrator' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnes' ) );
  }

  // Start HTML ---> ?>
  <?php // <--- End HTML
}
