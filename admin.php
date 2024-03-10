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
  // Only on the plugin's admin pages
  if ( strpos( $_GET['page'] ?? '', 'fcncn-' ) === false ) {
    return;
  }

  // Styles
  wp_enqueue_style(
    'fcncn-admin-styles',
    plugin_dir_url( __FILE__ ) . '/css/fcncn-admin.css',
    [],
    FCNCN_VERSION
  );

  // Scripts
  wp_enqueue_script(
    'fcncn-admin-scripts',
    plugin_dir_url( __FILE__ ) . '/js/fcncn-admin.min.js',
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

/**
 * Adds removable query arguments (admin only)
 *
 * @since 0.1.0
 *
 * @param array $args  Array of removable query arguments.
 *
 * @return array Extended list of query args.
 */

function fcncn_add_removable_admin_args( $args ) {
  return array_merge( $args, ['fcncn-notice', 'fcncn-message'] );
}
add_filter( 'removable_query_args', 'fcncn_add_removable_admin_args' );

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
    case 'confirm-subscriber-success':
      $notice = sprintf( __( 'Subscriber (#%s) confirmed.', 'fcncn' ), $message ?: __( 'n/a', 'fcncn' ) );
      $class = 'notice-success';
      break;
    case 'confirm-subscriber-failure':
      $notice = sprintf(
        __( 'Error. Subscriber (#%s) could not be confirmed.', 'fcncn' ),
        $message ?: __( 'n/a', 'fcncn' )
      );
      $class = 'notice-error';
      break;
    case 'unconfirm-subscriber-success':
      $notice = sprintf( __( 'Subscriber (#%s) unconfirmed.', 'fcncn' ), $message ?: __( 'n/a', 'fcncn' ) );
      $class = 'notice-success';
      break;
    case 'unconfirm-subscriber-failure':
      $notice = sprintf(
        __( 'Error. Subscriber (#%s) could not be unconfirmed.', 'fcncn' ),
        $message ?: __( 'n/a', 'fcncn' )
      );
      $class = 'notice-error';
      break;
    case 'confirmation-email-resent':
      $notice = sprintf( __( 'Confirmation email resent to subscriber (#%s).', 'fcncn' ), $message ?: __( 'n/a', 'fcncn' ) );
      $class = 'notice-success';
      break;
  }

  // Render notice
  if ( ! empty( $notice ) ) {
    echo "<div class='notice {$class} is-dismissible'><p>{$notice}</p></div>";
  }
}
add_action( 'admin_notices', 'fcncn_admin_notices' );

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

  // Perform table actions
  $subscribers_table->perform_actions();
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
  <div id="fcncn-admin-page-subscribers" class="wrap fcncn-settings _subscribers">
    <h1 class="fcncn-settings__header"><?php echo esc_html__( 'Subscribers', 'fcncn' ); ?></h1>
    <hr class="wp-header-end">

    <div class="fcncn-settings__content">

      <div class="fcncn-settings__columns">

        <div class="fcncn-box">

          <div class="fcncn-box__header">
            <h2><?php _e( 'Add Subscriber', 'fcncn' ); ?></h2>
          </div>

          <div class="fcncn-box__body">
            <div class="fcncn-box__row">
              <form method="POST" action="<?php echo admin_url( 'admin-post.php?action=fcncn_submit_subscriber' ); ?>">

                <?php wp_nonce_field( 'submit_subscriber', 'fcncn-nonce' ); ?>

                <div class="fcncn-input-wrap">
                  <input type="email" name="email" id="fcncn-submit-subscriber-email" placeholder="<?php esc_attr_e( 'Email Address', 'fcncn' ); ?>" required>
                </div>

                <div class="fcncn-checkbox-wrap">
                  <input type="checkbox" name="confirmed" id="fcncn-submit-subscriber-confirm" value="1">
                  <label for="fcncn-submit-subscriber-confirm"><?php _e( 'Confirmed', 'fcncn' ); ?></label>
                </div>

                <div class="fcncn-submit-wrap">
                  <button type="submit" class="button button-primary"><?php _e( 'Submit Subscriber', 'fcncn' ); ?></button>
                </div>

              </form>
            </div>
          </div>

        </div>
      </div>

      <div class="fcncn-settings__table fcncn-subscribers-table-wrapper">
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
