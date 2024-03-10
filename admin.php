<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// =======================================================================================
// INCLUDES
// =======================================================================================

require_once( plugin_dir_path( __FILE__ ) . 'actions.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/class-subscribers-table.php' );

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

/**
 * Change text in bottom-left corner of the admin panel
 *
 * @since 0.1.0
 *
 * @param string $default  Default footer text.
 */

function fcncn_admin_footer_text( $default ) {
  if ( strpos( $_GET['page'] ?? '', 'fcncn-' ) !== false ) {
    return sprintf(
      _x( 'Fictioneer Chapter Notifications %s', 'Admin page footer text.', 'fcnes' ),
      FCNCN_VERSION
    );
  }

  return $default;
}
add_filter( 'admin_footer_text', 'fcncn_admin_footer_text' );

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
  if ( ! current_user_can( 'manage_options' ) ) {
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
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // Add admin page
  $fcncn_admin_page_subscribers = add_submenu_page(
    'fcncn-notifications',
    'Subscribers',
    'Subscribers',
    'manage_options',
    'fcncn-subscribers',
    'fcncn_subscribers_page'
  );

  // Add screen options
  if ( $fcncn_admin_page_subscribers ) {
    add_action( "load-{$fcncn_admin_page_subscribers}", 'fcncn_subscribers_table_screen_options' );
  }
}
add_action( 'admin_menu', 'fcncn_add_subscribers_menu_page' );

/**
 * Configure the screen options for the subscribers page
 *
 * @since 0.1.0
 * @global WP_List_Table $subscribers_table  The subscribers table instance.
 */

function fcncn_subscribers_table_screen_options() {
  global $subscribers_table;

  // Add pagination option
	$args = array(
		'label' => __( 'Subscribers per page', 'fcncn' ),
		'default' => 25,
		'option' => 'fcncn_subscribers_per_page'
	);
	add_screen_option( 'per_page', $args );

  // Setup table
  $subscribers_table = new FCNCN_Subscribers_Table();
}

/**
 * Callback for the subscribers submenu page
 *
 * @since 0.1.0
 */

function fcncn_subscribers_page() {
  global $subscribers_table;

  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnes' ) );
  }

  // Setup
  $subscribers_table->prepare_items();

  // Start HTML ---> ?>
  <div id="fcncn-admin-page-subscribers" class="wrap fcncn-settings">
    <h1 class="fcncn-settings__header"><?php echo esc_html__( 'Subscribers', 'fcncn' ); ?></h1>
    <hr class="wp-header-end">

    <div class="fcncn-settings__content">

      <div class="fcncn-settings__table">
        <?php $subscribers_table->display_views(); ?>
        <form method="post"><?php
          $subscribers_table->search_box( 'Search Emails', 'search_id' );
          $subscribers_table->display();
        ?></form>
      </div>

    </div>
</div>
  <?php // <--- End HTML
}
