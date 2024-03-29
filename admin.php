<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

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

if ( is_admin() ) {
  add_action( 'fictioneer_admin_settings_plugins', 'fcnen_settings_card' );
}

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

if ( is_admin() ) {
  add_action( 'after_setup_theme', 'fcnen_check_theme' );
}

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
// SETTINGS
// =======================================================================================

/**
 * Register settings for the plugin
 *
 * @since 0.1.0
 */

function fcnen_register_settings() {
  // General
  register_setting( 'fcnen_general_group', 'fcnen_from_email_address', 'sanitize_email' );
  register_setting( 'fcnen_general_group', 'fcnen_from_email_name', 'sanitize_text_field' );
  register_setting( 'fcnen_general_group', 'fcnen_flag_subscribe_to_stories', 'absint' );
  register_setting( 'fcnen_general_group', 'fcnen_flag_subscribe_to_taxonomies', 'absint' );
  register_setting( 'fcnen_general_group', 'fcnen_flag_allow_passwords', 'absint' );
  register_setting( 'fcnen_general_group', 'fcnen_flag_allow_hidden', 'absint' );
  register_setting( 'fcnen_general_group', 'fcnen_flag_purge_on_deactivation', 'absint' );
  register_setting( 'fcnen_general_group', 'fcnen_excerpt_length', 'absint' );
  register_setting( 'fcnen_general_group', 'fcnen_max_per_term', 'absint' );
  register_setting( 'fcnen_general_group', 'fcnen_service_provider', 'sanitize_text_field' );
  register_setting( 'fcnen_general_group', 'fcnen_api_key', 'sanitize_text_field' );
  register_setting( 'fcnen_general_group', 'fcnen_api_bulk_limit', 'absint' );

  // Templates
  register_setting( 'fcnen_template_group', 'fcnen_template_layout_confirmation', 'wp_kses_post' );
  register_setting( 'fcnen_template_group', 'fcnen_template_subject_confirmation', 'sanitize_text_field' );

  register_setting( 'fcnen_template_group', 'fcnen_template_layout_code', 'wp_kses_post' );
  register_setting( 'fcnen_template_group', 'fcnen_template_subject_code', 'sanitize_text_field' );

  register_setting( 'fcnen_template_group', 'fcnen_template_layout_edit', 'wp_kses_post' );
  register_setting( 'fcnen_template_group', 'fcnen_template_subject_edit', 'sanitize_text_field' );

  register_setting( 'fcnen_template_group', 'fcnen_template_layout_notification', 'wp_kses_post' );
  register_setting( 'fcnen_template_group', 'fcnen_template_subject_notification', 'sanitize_text_field' );

  register_setting( 'fcnen_template_group', 'fcnen_template_loop_part_post', 'wp_kses_post' );
  register_setting( 'fcnen_template_group', 'fcnen_template_loop_part_story', 'wp_kses_post' );
  register_setting( 'fcnen_template_group', 'fcnen_template_loop_part_chapter', 'wp_kses_post' );
}
add_action( 'admin_init', 'fcnen_register_settings' );

// =======================================================================================
// INCLUDES
// =======================================================================================

require_once( plugin_dir_path( __FILE__ ) . 'actions.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/class-subscribers-table.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/class-notifications-table.php' );

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

function fcnen_enqueue_admin_scripts( $hook_suffix ) {
  // Setup
  $screen = get_current_screen();

  // Plugin menus
  if ( strpos( $_GET['page'] ?? '', 'fcnen-' ) !== false ) {
    wp_enqueue_style( 'fcnen-admin-styles', plugin_dir_url( __FILE__ ) . '/css/fcnen-admin.css', [], FCNEN_VERSION );

    wp_enqueue_script(
      'fcnen-admin-scripts',
      plugin_dir_url( __FILE__ ) . '/js/fcnen-admin.min.js',
      ['fictioneer-utility-scripts'],
      FCNEN_VERSION,
      true
    );
  }

  // Post edit screens
  if ( $screen && in_array( $screen->post_type, ['post', 'fcn_story', 'fcn_chapter'] ) ) {
    wp_enqueue_style( 'fcnen-admin-styles', plugin_dir_url( __FILE__ ) . '/css/fcnen-admin.css', [], FCNEN_VERSION );
  }
}
add_action( 'admin_enqueue_scripts', 'fcnen_enqueue_admin_scripts' );

/**
 * Enqueues CodeMirror for the plugin's code editor
 *
 * @since 01.0
 */

function fcnen_enqueue_codemirror() {
  $cm_settings['codeEditor'] = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
  wp_localize_script( 'jquery', 'cm_settings', $cm_settings );
  wp_enqueue_script( 'wp-theme-plugin-editor' );
  wp_enqueue_style( 'wp-codemirror' );
}
add_action( 'admin_enqueue_scripts', 'fcnen_enqueue_codemirror' );

/**
 * Change text in bottom-left corner of the admin panel
 *
 * @since 0.1.0
 *
 * @param string $default  Default footer text.
 */

function fcnen_admin_footer_text( $default ) {
  if ( strpos( $_GET['page'] ?? '', 'fcnen-' ) !== false ) {
    return sprintf(
      _x( 'Fictioneer Email Notifications %s', 'Admin page footer text.', 'fcnen' ),
      FCNEN_VERSION
    );
  }

  return $default;
}
add_filter( 'admin_footer_text', 'fcnen_admin_footer_text' );

/**
 * Adds removable query arguments (admin only)
 *
 * @since 0.1.0
 *
 * @param array $args  Array of removable query arguments.
 *
 * @return array Extended list of query args.
 */

function fcnen_add_removable_admin_args( $args ) {
  return array_merge( $args, ['fcnen-notice', 'fcnen-message'] );
}
add_filter( 'removable_query_args', 'fcnen_add_removable_admin_args' );

// =======================================================================================
// NOTICES
// =======================================================================================

/**
 * Show plugin admin notices
 *
 * Displays an admin notice based on the query parameter 'fcnen-notice'
 * and optionally 'fcnen-message' for additional information.
 *
 * @since 0.1.0
 */

function fcnen_admin_notices() {
  // Setup
  $notice = '';
  $class = '';
  $message = sanitize_text_field( $_GET['fcnen-message'] ?? '' );
  $maybe_id = is_numeric( $message ) ? absint( $message ) : 0;
  $maybe_post = get_post( $maybe_id );
  $post_title = empty( $maybe_post ) ? __( 'UNAVAILABLE', 'fcnen' ) : $maybe_post->post_title;

  // Default notices
  if ( ( $_GET['settings-updated'] ?? 0 ) === 'true' ) {
    $notice = __( 'Settings saved' );
    $class = 'notice-success';
  }

  // FCNEN notices
  switch ( $_GET['fcnen-notice'] ?? 0 ) {
    case 'subscriber-already-exists':
      $notice = __( 'Error. Subscriber with that email address already exists.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'subscriber-adding-success':
      $notice = sprintf( __( '%s added.', 'fcnen' ), $message ?: __( 'Subscriber', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'subscriber-adding-failure':
      $notice = sprintf( __( 'Error. %s could not be added.', 'fcnen' ), $message ?: __( 'Subscriber', 'fcnen' ) );
      $class = 'notice-error';
      break;
    case 'confirm-subscriber-success':
      $notice = sprintf( __( 'Subscriber (#%s) confirmed.', 'fcnen' ), $message ?: __( 'n/a', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'confirm-subscriber-failure':
      $notice = sprintf(
        __( 'Error. Subscriber (#%s) could not be confirmed.', 'fcnen' ),
        $message ?: __( 'n/a', 'fcnen' )
      );
      $class = 'notice-error';
      break;
    case 'unconfirm-subscriber-success':
      $notice = sprintf( __( 'Subscriber (#%s) unconfirmed.', 'fcnen' ), $message ?: __( 'n/a', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'unconfirm-subscriber-failure':
      $notice = sprintf(
        __( 'Error. Subscriber (#%s) could not be unconfirmed.', 'fcnen' ),
        $message ?: __( 'n/a', 'fcnen' )
      );
      $class = 'notice-error';
      break;
    case 'trash-subscriber-success':
      $notice = sprintf( __( 'Subscriber (#%s) trashed.', 'fcnen' ), $message ?: __( 'n/a', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'trash-subscriber-failure':
      $notice = sprintf(
        __( 'Error. Subscriber (#%s) could not be trashed.', 'fcnen' ),
        $message ?: __( 'n/a', 'fcnen' )
      );
      $class = 'notice-error';
      break;
    case 'restore-subscriber-success':
      $notice = sprintf( __( 'Subscriber (#%s) restored.', 'fcnen' ), $message ?: __( 'n/a', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'restore-subscriber-failure':
      $notice = sprintf(
        __( 'Error. Subscriber (#%s) could not be restored.', 'fcnen' ),
        $message ?: __( 'n/a', 'fcnen' )
      );
      $class = 'notice-error';
      break;
    case 'delete-subscriber-success':
      $notice = sprintf( __( 'Subscriber (#%s) permanently deleted.', 'fcnen' ), $message ?: __( 'n/a', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'delete-subscriber-failure':
      $notice = sprintf(
        __( 'Error. Subscriber (#%s) could not be deleted.', 'fcnen' ),
        $message ?: __( 'n/a', 'fcnen' )
      );
      $class = 'notice-error';
      break;
    case 'bulk-confirm-subscribers-success':
      $notice = sprintf( __( 'Confirmed %s subscribers.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-confirm-subscribers-failure':
      $notice = __( 'Error. Could not confirm subscribers.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'bulk-unconfirm-subscribers-success':
      $notice = sprintf( __( 'Unconfirmed %s subscribers.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-unconfirm-subscribers-failure':
      $notice = __( 'Error. Could not unconfirm subscribers.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'bulk-trash-subscribers-success':
      $notice = sprintf( __( 'Trashed %s subscribers.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-trash-subscribers-failure':
      $notice = __( 'Error. Could not trash subscribers.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'bulk-restore-subscribers-success':
      $notice = sprintf( __( 'Restored %s subscribers.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-restore-subscribers-failure':
      $notice = __( 'Error. Could not restore subscribers.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'bulk-delete-subscribers-success':
      $notice = sprintf( __( 'Permanently deleted %s subscribers.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-delete-subscribers-failure':
      $notice = __( 'Error. Could not delete subscribers.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'confirmation-email-resent':
      $notice = sprintf( __( 'Confirmation email resent to subscriber (#%s).', 'fcnen' ), $message ?: __( 'n/a', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'code-email-sent':
      $notice = sprintf( __( 'Email with edit code sent to subscriber (#%s).', 'fcnen' ), $message ?: __( 'n/a', 'fcnen' ) );
      $class = 'notice-success';
      break;
    case 'emptied-trashed-subscribers':
      $notice = __( 'Emptied trash.', 'fcnen' );
      $class = 'notice-success';
      break;
    case 'csv-imported':
      $notice = sprintf( __( '%s subscriber(s) imported from CSV.', 'fcnen' ), $message ?: 0 );
      $class = 'notice-success';
      break;
    case 'delete-notification-success':
      $notice = sprintf( __( 'Deleted notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-success';
      break;
    case 'delete-notification-failure':
      $notice = sprintf( __( 'Error. Could not delete notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-error';
      break;
    case 'paused-notification-success':
      $notice = sprintf( __( 'Paused notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-success';
      break;
    case 'paused-notification-failure':
      $notice = sprintf( __( 'Error. Could not pause notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-error';
      break;
    case 'unpaused-notification-success':
      $notice = sprintf( __( 'Unpaused notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-success';
      break;
    case 'unpaused-notification-failure':
      $notice = sprintf( __( 'Error. Could not unpause notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-error';
      break;
    case 'unsent-notification-success':
      $notice = sprintf( __( 'Notification for "%s" (#%s) marked as unsent.', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-success';
      break;
    case 'unsent-notification-failure':
      $notice = sprintf( __( 'Error. Could not mark notification for "%s" (#%s) as unsent.', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-error';
      break;
    case 'bulk-delete-notifications-success':
      $notice = sprintf( __( 'Deleted %s notifications.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-delete-notifications-failure':
      $notice = __( 'Error. Could not delete notifications.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'bulk-unsent-notifications-success':
      $notice = sprintf( __( 'Marked %s notifications as unsent.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-unsent-notifications-failure':
      $notice = __( 'Error. Could not mark notifications as unsent.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'bulk-pause-notifications-success':
      $notice = sprintf( __( 'Paused %s notifications.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-pause-notifications-failure':
      $notice = __( 'Error. Could not pause notifications.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'bulk-unpause-notifications-success':
      $notice = sprintf( __( 'Unpaused %s notifications.', 'fcnen' ), $message ?: '0' );
      $class = 'notice-success';
      break;
    case 'bulk-unpause-notifications-failure':
      $notice = __( 'Error. Could not unpause notifications.', 'fcnen' );
      $class = 'notice-error';
      break;
    case 'submit-notification-successful':
      $notice = sprintf( __( 'Added notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-success';
      break;
    case 'submit-notification-failure':
      $notice = sprintf( __( 'Error. Could not add notification for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-error';
      break;
    case 'submit-notification-duplicate':
      $notice = sprintf( __( 'Error. There is already an unsent notification enqueued for "%s" (#%s).', 'fcnen' ), $post_title, $maybe_id );
      $class = 'notice-error';
      break;
    case 'submit-notification-post-not-found':
      $notice = sprintf( __( 'Error. Post #%s not found.', 'fcnen' ), $maybe_id );
      $class = 'notice-error';
      break;
    case 'submit-notification-post-unpublished':
      $notice = sprintf( __( 'Error. Post #%s is not published.', 'fcnen' ), $maybe_id );
      $class = 'notice-error';
      break;
    case 'submit-notification-post-protected':
      $notice = sprintf( __( 'Error. Post #%s is protected.', 'fcnen' ), $maybe_id );
      $class = 'notice-error';
      break;
    case 'submit-notification-post-invalid-type':
      $notice = sprintf( __( 'Error. Post #%s is of an invalid type.', 'fcnen' ), $maybe_id );
      $class = 'notice-error';
      break;
    case 'submit-notification-post-excluded':
      $notice = sprintf( __( 'Error. Post #%s is excluded from email notifications.', 'fcnen' ), $maybe_id );
      $class = 'notice-error';
      break;
    case 'submit-notification-post-hidden':
      $notice = sprintf( __( 'Error. Post #%s is hidden.', 'fcnen' ), $maybe_id );
      $class = 'notice-error';
      break;
  }

  // Render notice
  if ( ! empty( $notice ) ) {
    echo "<div class='notice {$class} is-dismissible'><p>{$notice}</p></div>";
  }
}
add_action( 'admin_notices', 'fcnen_admin_notices' );

// =======================================================================================
// SAVE SCREEN OPTIONS
// =======================================================================================

/**
 * Save custom screen options values
 *
 * @since 0.1.0
 *
 * @param bool   $status  The current status of the screen option saving.
 * @param string $option  The name of the screen option being saved.
 * @param mixed  $value   The value of the screen option being saved.
 *
 * @return bool The updated status of the screen option saving.
 */

function fcnen_save_screen_options( $status, $option, $value ) {
  // Subscribers per page
  if ( $option === 'fcnen_subscribers_per_page' ) {
    update_user_meta( get_current_user_id(), $option, $value );
  }

  // Updated per page
  if ( $option === 'fcnen_notifications_per_page' ) {
    update_user_meta( get_current_user_id(), $option, $value );
  }

  return $status;
}
add_filter( 'set-screen-option', 'fcnen_save_screen_options', 10, 3 );

// =======================================================================================
// ADMIN NOTIFICATIONS PAGE
// =======================================================================================

/**
 * Add notifications admin menu page
 *
 * @since 0.1.0
 */

function fcnen_add_notifications_menu_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // Add admin page
  $hook = add_menu_page(
    'Email Notifications',
    'Notifications',
    'manage_options',
    'fcnen-notifications',
    'fcnen_notifications_page',
    'dashicons-email-alt'
  );

  // Add screen options
  if ( $hook ) {
    add_action( "load-{$hook}", 'fcnen_notifications_table_screen_options' );
  }
}
add_action( 'admin_menu', 'fcnen_add_notifications_menu_page' );

/**
 * Configure the screen options for the notifications page
 *
 * @since 0.1.0
 * @global WP_List_Table $notifications_table  The notifications table instance.
 */

function fcnen_notifications_table_screen_options() {
  global $notifications_table;

  // Add pagination option
	$args = array(
		'label' => __( 'Notifications per page', 'fcnen' ),
		'default' => 25,
		'option' => 'fcnen_notifications_per_page'
	);
	add_screen_option( 'per_page', $args );

  // Setup table
  $notifications_table = new FCNEN_Notifications_Table();

  // Perform table actions
  $notifications_table->perform_actions();
}

/**
 * Callback for the notifications menu page
 *
 * @since 0.1.0
 */

function fcnen_notifications_page() {
  global $notifications_table;

  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnen' ) );
  }

  // Setup
  $notifications_table->prepare_items();

  // Start HTML ---> ?>
  <div id="fcnen-admin-page-notifications" class="wrap fcnen-settings _notifications">
    <h1 class="wp-heading-inline"><?php _e( 'Notifications', 'fcnen' ); ?></h1>
    <button class="page-title-action" data-fcnen-open-modal="fcnen-modal-add-notification"><?php _e( 'Add Notification', 'fcnen' ); ?></button>
    <hr class="wp-header-end">
    <div class="fcnen-settings__content">
      <div class="fcnen-settings__table fcnen-notifications-table-wrapper">
        <?php $notifications_table->display_views(); ?>
        <form method="post"><?php
          $notifications_table->search_box( 'Search Notifications', 'search-notifications' );
          $notifications_table->display();
        ?></form>
      </div>
    </div>
  </div>

  <dialog class="fcnen-modal" id="fcnen-modal-add-notification">
    <form method="POST" action="<?php echo admin_url( 'admin-post.php?action=fcnen_submit_notification' ); ?>" class="fcnen-modal__wrapper">
      <?php wp_nonce_field( 'submit-notification', 'fcnen-nonce' ); ?>
      <div class="fcnen-modal__header"><?php _e( 'Add Notification', 'fcnen' ); ?></div>
      <div class="fcnen-modal__content">
        <p><?php _e( 'You can add blog posts, stories, and chapters by ID (you can find that in the URL). Make sure the post is not excluded, private, duplicate, and so forth.', 'fcnen' ); ?></p>
        <div class="fcnen-input-wrap">
          <input type="text" name="post_id" id="fcnen-add-notification-post-id" placeholder="<?php echo esc_attr_x( 'Post ID', 'Add notification input placeholder.', 'fcnen' ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore required>
        </div>
      </div>
      <div class="fcnen-modal__actions">
        <button value="cancel" formmethod="dialog" class="button" formnovalidate><?php _e( 'Cancel', 'fcnen' ); ?></button>
        <button type="submit" class="button button-primary"><?php _e( 'Add', 'fcnen' ); ?></button>
      </div>
    </form>
  </dialog>
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

function fcnen_add_subscribers_menu_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // Add admin page
  $hook = add_submenu_page(
    'fcnen-notifications',
    'Subscribers',
    'Subscribers',
    'manage_options',
    'fcnen-subscribers',
    'fcnen_subscribers_page'
  );

  // Add screen options
  if ( $hook ) {
    add_action( "load-{$hook}", 'fcnen_subscribers_table_screen_options' );
  }
}
add_action( 'admin_menu', 'fcnen_add_subscribers_menu_page' );

/**
 * Configure the screen options for the subscribers page
 *
 * @since 0.1.0
 * @global WP_List_Table $subscribers_table  The subscribers table instance.
 */

function fcnen_subscribers_table_screen_options() {
  global $subscribers_table;

  // Add pagination option
	$args = array(
		'label' => __( 'Subscribers per page', 'fcnen' ),
		'default' => 25,
		'option' => 'fcnen_subscribers_per_page'
	);
	add_screen_option( 'per_page', $args );

  // Setup table
  $subscribers_table = new FCNEN_Subscribers_Table();

  // Perform table actions
  $subscribers_table->perform_actions();
}

/**
 * Callback for the subscribers submenu page
 *
 * @since 0.1.0
 */

function fcnen_subscribers_page() {
  global $subscribers_table;

  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnen' ) );
  }

  // Setup
  $subscribers_table->prepare_items();

  // Start HTML ---> ?>
  <div id="fcnen-admin-page-subscribers" class="wrap fcnen-settings _subscribers">
    <h1 class="wp-heading-inline"><?php _e( 'Subscribers', 'fcnen' ); ?></h1>
    <button class="page-title-action" data-fcnen-open-modal="fcnen-modal-add-subscriber"><?php _e( 'Add Subscriber', 'fcnen' ); ?></button>
    <hr class="wp-header-end">
    <div class="fcnen-settings__content">
      <div class="fcnen-settings__table fcnen-subscribers-table-wrapper">
        <?php $subscribers_table->display_views(); ?>
        <form method="post"><?php
          $subscribers_table->search_box( 'Search Emails', 'search_id' );
          $subscribers_table->display();
        ?></form>
      </div>
    </div>
  </div>

  <dialog class="fcnen-modal" id="fcnen-modal-add-subscriber">
    <form method="POST" action="<?php echo admin_url( 'admin-post.php?action=fcnen_submit_subscriber' ); ?>" class="fcnen-modal__wrapper">
      <?php wp_nonce_field( 'submit-subscriber', 'fcnen-nonce' ); ?>
      <div class="fcnen-modal__header"><?php _e( 'Add Subscriber', 'fcnen' ); ?></div>
      <div class="fcnen-modal__content">
        <p><?php _e( 'Add a subscriber, optionally already confirmed.', 'fcnen' ); ?></p>
        <div class="fcnen-input-wrap">
          <input type="email" name="email" id="fcnen-submit-subscriber-email" placeholder="<?php esc_attr_e( 'Email Address', 'fcnen' ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore required>
        </div>
        <div class="fcnen-horizontal-wrap">
          <div class="fcnen-checkbox-wrap">
            <input type="checkbox" name="confirmed" id="fcnen-submit-subscriber-confirm" value="1">
            <label for="fcnen-submit-subscriber-confirm"><?php _e( 'Confirmed', 'fcnen' ); ?></label>
          </div>
          <div class="fcnen-checkbox-wrap">
            <input type="checkbox" name="everything" id="fcnen-submit-subscriber-everything" value="1" checked>
            <label for="fcnen-submit-subscriber-everything"><?php _e( 'Everything', 'fcnen' ); ?></label>
          </div>
          <div class="fcnen-checkbox-wrap">
            <input type="checkbox" name="posts" id="fcnen-submit-subscriber-posts" value="1">
            <label for="fcnen-submit-subscriber-posts"><?php _e( 'Blogs', 'fcnen' ); ?></label>
          </div>
          <div class="fcnen-checkbox-wrap">
            <input type="checkbox" name="stories" id="fcnen-submit-subscriber-stories" value="1">
            <label for="fcnen-submit-subscriber-stories"><?php _e( 'Stories', 'fcnen' ); ?></label>
          </div>
          <div class="fcnen-checkbox-wrap">
            <input type="checkbox" name="chapters" id="fcnen-submit-subscriber-chapters" value="1">
            <label for="fcnen-submit-subscriber-chapters"><?php _e( 'Chapters', 'fcnen' ); ?></label>
          </div>
        </div>
      </div>
      <div class="fcnen-modal__actions">
        <button value="cancel" formmethod="dialog" class="button" formnovalidate><?php _e( 'Cancel', 'fcnen' ); ?></button>
        <button type="submit" class="button button-primary"><?php _e( 'Add', 'fcnen' ); ?></button>
      </div>
    </form>
  </dialog>

  <dialog class="fcnen-modal" id="fcnen-modal-import-csv">
    <form method="POST" action="<?php echo admin_url( 'admin-post.php?action=fcnen_import_subscribers_csv' ); ?>" enctype="multipart/form-data" class="fcnen-modal__wrapper">
      <?php wp_nonce_field( 'fcnen-import-csv', 'fcnen-nonce' ); ?>
      <div class="fcnen-modal__header"><?php _e( 'Import CSV', 'fcnen' ); ?></div>
      <div class="fcnen-modal__content">
        <p><?php _e( 'Import subscribers from a CSV file. Keep in mind that scopes are saved as IDs and may not match if the associated terms have changed.', 'fcnen' ); ?></p>
        <div class="fcnen-input-wrap _file">
          <input type="file" name="csv-file" id="fcnen-import-csv-file" hidden required>
          <label for="fcnen-import-csv-file" class="fcnen-input-wrap__file-button"><?php _e( 'Choose File', 'fcnen' ); ?></label>
          <label for="fcnen-import-csv-file" class="fcnen-input-wrap__file-field"><?php _e( 'No file chosen', 'fcnen' ); ?></label>
        </div>
        <div class="fcnen-checkbox-wrap">
          <input type="checkbox" name="reset-scopes" id="fcnen-import-csv-reset-scopes" value="1">
          <label for="fcnen-import-csv-reset-scopes"><?php _e( 'Reset Scopes', 'fcnen' ); ?></label>
        </div>
      </div>
      <div class="fcnen-modal__actions">
        <button value="cancel" formmethod="dialog" class="button" formnovalidate><?php _e( 'Cancel', 'fcnen' ); ?></button>
        <button type="submit" class="button button-primary"><?php _e( 'Import', 'fcnen' ); ?></button>
      </div>
    </form>
  </dialog>
  <?php // <--- End HTML
}

// =======================================================================================
// TEMPLATES PAGE
// =======================================================================================

/**
 * Add settings admin submenu page
 *
 * @since 0.1.0
 */

function fcnen_add_templates_menu_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // Add admin page
  add_submenu_page(
    'fcnen-notifications',
    'Templates',
    'Templates',
    'manage_options',
    'fcnen-templates',
    'fcnen_templates_page'
  );
}
add_action( 'admin_menu', 'fcnen_add_templates_menu_page' );

/**
 * Callback for the templates submenu page
 *
 * @since 0.1.0
 */

function fcnen_templates_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnen' ) );
  }

  // Setup
  $layout_confirmation = get_option( 'fcnen_template_layout_confirmation', FCNEN_DEFAULTS['layout_confirmation'] ?? '' );
  $subject_confirmation = get_option( 'fcnen_template_subject_confirmation' );

  $layout_code = get_option( 'fcnen_template_layout_code', FCNEN_DEFAULTS['layout_code'] ?? '' );
  $subject_code = get_option( 'fcnen_template_subject_code' );

  $layout_edit = get_option( 'fcnen_template_layout_edit', FCNEN_DEFAULTS['layout_edit'] ?? '' );
  $subject_edit = get_option( 'fcnen_template_subject_edit' );

  $layout_notification = get_option( 'fcnen_template_layout_notification', FCNEN_DEFAULTS['layout_notification'] ?? '' );
  $subject_notification = get_option( 'fcnen_template_subject_notification' );

  $loop_part_post = get_option( 'fcnen_template_loop_part_post', FCNEN_DEFAULTS['loop_part_post'] ?? '' );
  $loop_part_story = get_option( 'fcnen_template_loop_part_story', FCNEN_DEFAULTS['loop_part_story'] ?? '' );
  $loop_part_chapter = get_option( 'fcnen_template_loop_part_chapter', FCNEN_DEFAULTS['loop_part_chapter'] ?? '' );

  // Preview replacements
  $preview_replacements = array(
    '{{id}}' => '####',
    '{{updates}}' => $loop_part_post . "\n" . $loop_part_story . "\n" . $loop_part_chapter, // Do this first!
    '{{site_name}}' => get_bloginfo( 'name' ),
    '{{author}}' => _x( 'Author', 'Preview replacement string.', 'fcnen' ),
    '{{title}}' => _x( 'Preview Title', 'Preview replacement string.', 'fcnen' ),
    '{{story_title}}' => _x( 'Preview Story Title', 'Preview replacement string.', 'fcnen' ),
    '{{excerpt}}' => _x( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec vel lacus luctus, laoreet augue vitae, dignissim arcu. Curabitur fermentum euismod justo et luctus. Cras sit amet gravida libero.', 'Preview replacement string.', 'fcnen' ),
    '{{date}}' => date( get_option( 'date_format' ) ),
    '{{time}}' => date( get_option( 'time_format' ) ),
    '{{code}}' => wp_generate_password( 32, false ),
    '{{email}}' => _x( 'subscriber@email.com', 'Preview replacement string.', 'fcnen' ),
    '{{type}}' => _x( 'Type', 'Preview replacement string.', 'fcnen' ),
    '{{scope_post_types}}' => _x( 'Blogs, Stories, Chapters', 'Preview replacement string.', 'fcnen' ),
    '{{scope_stories}}' => _x( 'Dracula, Sherlock Holmes, My Immortal', 'Preview replacement string.', 'fcnen' ),
    '{{scope_categories}}' => _x( 'External, Wholesome, Creepy', 'Preview replacement string.', 'fcnen' ),
    '{{scope_tags}}' => _x( 'Amnesia, Dystopian, Magical Girls', 'Preview replacement string.', 'fcnen' ),
    '{{scope_genres}}' => _x( 'Science Fiction, Solarpunk, Cosmic Horror', 'Preview replacement string.', 'fcnen' ),
    '{{scope_fandoms}}' => _x( 'Original, Sol Bianca, Little Witch Academia', 'Preview replacement string.', 'fcnen' ),
    '{{scope_characters}}' => _x( 'Twilight Sparkle, Luz Noceda, Rebecca', 'Preview replacement string.', 'fcnen' ),
    '{{scope_warnings}}' => _x( 'Profanity, Violence, Gore', 'Preview replacement string.', 'fcnen' ),
    '{{site_link}}' => '#',
    '{{activation_link}}' => '#',
    '{{unsubscribe_link}}' => '#',
    '{{edit_link}}' => '#',
    '{{story_link}}' => '#',
    '{{thumbnail}}' => "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='300'><rect width='100%' height='100%' fill='%23333'/></svg>"
  );

  // Start HTML ---> ?>
  <script><?php echo 'var fcnen_preview_replacements = ' . json_encode( $preview_replacements ) . ';'; ?></script>
  <div id="fcnen-admin-page-settings" class="wrap fcnen-settings _settings">
    <h1 class="wp-heading-inline"><?php _e( 'Templates', 'fcnen' ); ?></h1>
    <hr class="wp-header-end">
    <p class="fcnen-replacement-tokens"><?php _e( 'You can edit the subject, layout, and style of emails. Make sure to always include the necessary replacement tokens. Use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the token is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the token <em>is empty.</em>', 'fcnen' ); ?></p>
    <div class="fcnen-settings__content">
      <form method="POST" action="options.php">
        <?php
          settings_fields( 'fcnen_template_group' );
          do_settings_sections( 'fcnen_template_group' );
        ?>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <td class="td-full">
                <select id="fcnen-select-template">
                  <option value=""><?php _e( '— Select a template to edit —', 'fcnen' ); ?></option>
                  <option value="layout-confirmation"><?php _e( 'Confirmation Layout', 'fcnen' ); ?></option>
                  <option value="layout-code"><?php _e( 'Code Layout', 'fcnen' ); ?></option>
                  <option value="layout-edit"><?php _e( 'Edit Layout', 'fcnen' ); ?></option>
                  <option value="layout-notification"><?php _e( 'Notification Layout', 'fcnen' ); ?></option>
                  <option value="loop-part-post"><?php _e( 'Post Loop Partial', 'fcnen' ); ?></option>
                  <option value="loop-part-story"><?php _e( 'Story Loop Partial', 'fcnen' ); ?></option>
                  <option value="loop-part-chapter"><?php _e( 'Chapter Loop Partial', 'fcnen' ); ?></option>
                </select>
                <div class="fcnen-template-wrapper hidden" id="layout-confirmation">
                  <p class="fcnen-replacement-tokens"><?php _e( 'This email is sent when a new subscription is submitted, prompting the subscriber to confirm as security measure against fraudulent submissions. Anyone could enter anyone’s email address, after all. If not confirmed within 24 hours, the subscription and all data will be deleted once the cleanup cron job runs (every 12 hours).', 'fcnen' ); ?></p>
                  <div class="fcnen-left-right-wrap">
                    <label for="fcnen-template-subject-confirmation" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
                    <div class="fcnen-input-wrap">
                      <input type="text" name="fcnen_template_subject_confirmation" id="fcnen-template-subject-confirmation" placeholder="<?php echo FCNEN_DEFAULTS['subject_confirmation']; ?>" value="<?php echo $subject_confirmation; ?>">
                    </div>
                  </div>
                  <textarea name="fcnen_template_layout_confirmation" id="fcnen-template-layout-confirmation" class="fcnen-codemirror"><?php echo esc_textarea( $layout_confirmation ); ?></textarea>
                  <p class="fcnen-replacement-tokens">
                    <code>{{site_name}}</code>
                    <code>{{site_link}}</code>
                    <code>{{activation_link}}</code>
                    <code>{{unsubscribe_link}}</code>
                    <code>{{edit_link}}</code>
                    <code>{{email}}</code>
                    <code>{{code}}</code>
                    <code>{{id}}</code>
                  </p>
                  <details class="fcnen-default-code">
                    <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
                    <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_confirmation'] ?? '' ); ?></code></pre>
                  </details>
                  <div class="fcnen-action-wrap">
                    <?php submit_button( __( 'Save Templates', 'fcnen' ), 'primary', 'submit', false ); ?>
                  </div>
                </div>
                <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="layout-code">
                  <p class="fcnen-replacement-tokens"><?php _e( 'This email can be manually triggered in the subscriber list, sending the edit code to a confirmed subscriber along with an edit link for convenience. This should normally not be required since the code should be included in all other emails anyway, but people are people.', 'fcnen' ); ?></p>
                  <div class="fcnen-left-right-wrap">
                    <label for="fcnen-template-subject-code" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
                    <div class="fcnen-input-wrap">
                      <input type="text" name="fcnen_template_subject_code" id="fcnen-template-subject-code" placeholder="<?php echo FCNEN_DEFAULTS['subject_code']; ?>" value="<?php echo $subject_code; ?>">
                    </div>
                  </div>
                  <textarea name="fcnen_template_layout_code" id="fcnen-template-layout-code" class="fcnen-codemirror"><?php echo esc_textarea( $layout_code ); ?></textarea>
                  <p class="fcnen-replacement-tokens">
                    <code>{{site_name}}</code>
                    <code>{{site_link}}</code>
                    <code>{{activation_link}}</code>
                    <code>{{unsubscribe_link}}</code>
                    <code>{{edit_link}}</code>
                    <code>{{email}}</code>
                    <code>{{code}}</code>
                    <code>{{id}}</code>
                  </p>
                  <details class="fcnen-default-code">
                    <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
                    <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_code'] ?? '' ); ?></code></pre>
                  </details>
                  <div class="fcnen-action-wrap">
                    <?php submit_button( __( 'Save Templates', 'fcnen' ), 'primary', 'submit', false ); ?>
                  </div>
                </div>
                <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="layout-edit">
                  <p class="fcnen-replacement-tokens"><?php _e( 'This email is sent whenever a subscriber updates their preferences, both as confirmation and security notification about the change. Just in case a malicious actor managed to acquire both their email address and code.', 'fcnen' ); ?></p>
                  <div class="fcnen-left-right-wrap">
                    <label for="fcnen-template-subject-edit" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
                    <div class="fcnen-input-wrap">
                      <input type="text" name="fcnen_template_subject_edit" id="fcnen-template-subject-edit" placeholder="<?php echo FCNEN_DEFAULTS['subject_edit']; ?>" value="<?php echo $subject_edit; ?>">
                    </div>
                  </div>
                  <textarea name="fcnen_template_layout_edit" id="fcnen-template-layout-edit" class="fcnen-codemirror"><?php echo esc_textarea( $layout_edit ); ?></textarea>
                  <p class="fcnen-replacement-tokens">
                    <code>{{site_name}}</code>
                    <code>{{site_link}}</code>
                    <code>{{activation_link}}</code>
                    <code>{{unsubscribe_link}}</code>
                    <code>{{edit_link}}</code>
                    <code>{{email}}</code>
                    <code>{{code}}</code>
                    <code>{{id}}</code>
                    <code>{{scope_everything}}</code>
                    <code>{{scope_post_types}}</code>
                    <code>{{scope_stories}}</code>
                    <code>{{scope_categories}}</code>
                    <code>{{scope_tags}}</code>
                    <code>{{scope_fandoms}}</code>
                    <code>{{scope_characters}}</code>
                    <code>{{scope_warnings}}</code>
                  </p>
                  <details class="fcnen-default-code">
                    <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
                    <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_edit'] ?? '' ); ?></code></pre>
                  </details>
                  <div class="fcnen-action-wrap">
                    <?php submit_button( __( 'Save Templates', 'fcnen' ), 'primary', 'submit', false ); ?>
                  </div>
                </div>
                <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="layout-notification">
                  <p class="fcnen-replacement-tokens"><?php _e( 'This email is sent for actual update notifications and consists of multiple parts: layout and loop partials. While this layout provides the surrounding body with some nice text of your choice (and the code, really important), the loop partials render the individual content matching the subscriber’s preferences.', 'fcnen' ); ?></p>
                  <div class="fcnen-left-right-wrap">
                    <label for="fcnen-template-subject-notification" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
                    <div class="fcnen-input-wrap">
                      <input type="text" name="fcnen_template_subject_notification" id="fcnen-template-subject-notification" placeholder="<?php echo FCNEN_DEFAULTS['subject_notification']; ?>" value="<?php echo $subject_notification; ?>">
                    </div>
                  </div>
                  <textarea name="fcnen_template_layout_notification" id="fcnen-template-layout-notification" class="fcnen-codemirror"><?php echo esc_textarea( $layout_notification ); ?></textarea>
                  <p class="fcnen-replacement-tokens">
                    <code>{{site_name}}</code>
                    <code>{{site_link}}</code>
                    <code>{{unsubscribe_link}}</code>
                    <code>{{edit_link}}</code>
                    <code>{{email}}</code>
                    <code>{{code}}</code>
                    <code>{{id}}</code>
                    <code>{{updates}}</code>
                  </p>
                  <details class="fcnen-default-code">
                    <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
                    <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_notification'] ?? '' ); ?></code></pre>
                  </details>
                  <div class="fcnen-action-wrap">
                    <?php submit_button( __( 'Save Templates', 'fcnen' ), 'primary', 'submit', false ); ?>
                  </div>
                </div>
                <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="loop-part-post">
                  <p class="fcnen-replacement-tokens"><?php _e( 'This partial renders post updates in notification emails inside the <code>{{updates}}</code> replacement token.', 'fcnen' ); ?></p>
                  <textarea name="fcnen_template_loop_part_post" id="fcnen-template-loop-part-post" class="fcnen-codemirror"><?php echo esc_textarea( $loop_part_post ); ?></textarea>
                  <p class="fcnen-replacement-tokens">
                    <code>{{type}}</code>
                    <code>{{title}}</code>
                    <code>{{link}}</code>
                    <code>{{author}}</code>
                    <code>{{author_link}}</code>
                    <code>{{excerpt}}</code>
                    <code>{{date}}</code>
                    <code>{{time}}</code>
                    <code>{{thumbnail}}</code>
                    <code>{{categories}}</code>
                    <code>{{tags}}</code>
                    <code>{{site_name}}</code>
                    <code>{{site_link}}</code>
                  </p>
                  <details class="fcnen-default-code">
                    <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
                    <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['loop_part_post'] ?? '' ); ?></code></pre>
                  </details>
                  <div class="fcnen-action-wrap">
                    <?php submit_button( __( 'Save Templates', 'fcnen' ), 'primary', 'submit', false ); ?>
                  </div>
                </div>
                <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="loop-part-story">
                  <p class="fcnen-replacement-tokens"><?php _e( 'This partial renders story updates in notification emails inside the <code>{{updates}}</code> replacement token.', 'fcnen' ); ?></p>
                  <textarea name="fcnen_template_loop_part_story" id="fcnen-template-loop-part-story" class="fcnen-codemirror"><?php echo esc_textarea( $loop_part_story ); ?></textarea>
                  <p class="fcnen-replacement-tokens">
                    <code>{{type}}</code>
                    <code>{{title}}</code>
                    <code>{{link}}</code>
                    <code>{{author}}</code>
                    <code>{{author_link}}</code>
                    <code>{{excerpt}}</code>
                    <code>{{date}}</code>
                    <code>{{time}}</code>
                    <code>{{thumbnail}}</code>
                    <code>{{categories}}</code>
                    <code>{{tags}}</code>
                    <code>{{genres}}</code>
                    <code>{{fandoms}}</code>
                    <code>{{characters}}</code>
                    <code>{{warnings}}</code>
                    <code>{{all_terms}}</code>
                    <code>{{site_name}}</code>
                    <code>{{site_link}}</code>
                  </p>
                  <details class="fcnen-default-code">
                    <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
                    <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['loop_part_story'] ?? '' ); ?></code></pre>
                  </details>
                  <div class="fcnen-action-wrap">
                    <?php submit_button( __( 'Save Templates', 'fcnen' ), 'primary', 'submit', false ); ?>
                  </div>
                </div>
                <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="loop-part-chapter">
                  <p class="fcnen-replacement-tokens"><?php _e( 'This partial renders chapter updates in notification emails inside the <code>{{updates}}</code> replacement token.', 'fcnen' ); ?></p>
                  <textarea name="fcnen_template_loop_part_chapter" id="fcnen-template-loop-part-chapter" class="fcnen-codemirror"><?php echo esc_textarea( $loop_part_chapter ); ?></textarea>
                  <p class="fcnen-replacement-tokens">
                    <code>{{type}}</code>
                    <code>{{title}}</code>
                    <code>{{link}}</code>
                    <code>{{author}}</code>
                    <code>{{author_link}}</code>
                    <code>{{excerpt}}</code>
                    <code>{{date}}</code>
                    <code>{{time}}</code>
                    <code>{{thumbnail}}</code>
                    <code>{{categories}}</code>
                    <code>{{tags}}</code>
                    <code>{{genres}}</code>
                    <code>{{fandoms}}</code>
                    <code>{{characters}}</code>
                    <code>{{warnings}}</code>
                    <code>{{all_terms}}</code>
                    <code>{{story_title}}</code>
                    <code>{{story_link}}</code>
                    <code>{{site_name}}</code>
                    <code>{{site_link}}</code>
                  </p>
                  <details class="fcnen-default-code">
                    <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
                    <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['loop_part_chapter'] ?? '' ); ?></code></pre>
                  </details>
                  <div class="fcnen-action-wrap">
                    <?php submit_button( __( 'Save Templates', 'fcnen' ), 'primary', 'submit', false ); ?>
                  </div>
                </div>
              </td>
            </tr>
            <tr>
              <td class="td-full fcnen-template-preview-wrapper hidden" id="fcnen-preview">
                <h2 class="title"><?php _e( 'Preview', 'fcnen' ); ?></h2>
                <div>
                  <iframe id="fcnen-preview-iframe"></iframe>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
  </div>
  <?php // <--- End HTML
}

// =======================================================================================
// ADMIN SETTINGS PAGE
// =======================================================================================

/**
 * Add settings admin submenu page
 *
 * @since 0.1.0
 */

function fcnen_add_settings_menu_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // Add admin page
  add_submenu_page(
    'fcnen-notifications',
    'Settings',
    'Settings',
    'manage_options',
    'fcnen-settings',
    'fcnen_settings_page'
  );
}
add_action( 'admin_menu', 'fcnen_add_settings_menu_page' );

/**
 * Callback for the settings submenu page
 *
 * @since 0.1.0
 */

function fcnen_settings_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnen' ) );
  }

  // Setup
  $from = fcnen_get_from_email_address();
  $name = fcnen_get_from_email_name();
  $excerpt_length = absint( get_option( 'fcnen_excerpt_length', 256 ) );
  $max_per_term = absint( get_option( 'fcnen_max_per_term', 10 ) );

  // $provider = get_option( 'fcnen_service_provider' );
  $api_key = get_option( 'fcnen_api_key' );
  $api_bulk_limit = get_option( 'fcnen_api_bulk_limit', 300 );

  // Start HTML ---> ?>
  <div id="fcnen-admin-page-settings" class="wrap fcnen-settings _settings">
    <h1 class="wp-heading-inline"><?php _e( 'Settings', 'fcnen' ); ?></h1>
    <hr class="wp-header-end">
    <div class="fcnen-settings__content">
      <form method="post" action="options.php" novalidate="novalidate">
        <?php
          settings_fields( 'fcnen_general_group' );
          do_settings_sections( 'fcnen_general_group' );
        ?>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">
                <label for="fcnen-from-email-address"><?php _e( 'Sender Email', 'fcnen' ); ?></label>
              </th>
              <td>
                <input type="email" name="fcnen_from_email_address" id="fcnen-from-email-address" class="regular-text ltr" placeholder="<?php _ex( 'noreply@your-site.com', 'From email address placeholder.', 'fcnen' ); ?>" value="<?php echo esc_attr( $from ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore>
                <p class="description"><?php _e( 'The sender email address of all outgoing notifications. Defaults to noreply@* or admin email address.', 'fcnen' ); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="fcnen-from-email-name"><?php _e( 'Sender Name', 'fcnen' ); ?></label>
              </th>
              <td>
                <input type="text" name="fcnen_from_email_name" id="fcnen-from-email-name" class="regular-text" placeholder="<?php _ex( 'Your Site', 'From email name placeholder.', 'fcnen' ); ?>" value="<?php echo esc_attr( $name ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore>
                <p class="description"><?php _e( 'The sender name of all outgoing notifications. Defaults to site name.', 'fcnen' ); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e( 'Options', 'fcnen' ); ?></th>
              <td>
                <fieldset>
                  <label for="fcnen-flag-stories">
                    <input type="hidden" name="fcnen_flag_subscribe_to_stories" value="0">
                    <input type="checkbox" name="fcnen_flag_subscribe_to_stories" id="fcnen-flag-stories" value="1" autocomplete="off" <?php echo checked( 1, get_option( 'fcnen_flag_subscribe_to_stories' ), false ); ?>>
                    <?php _e( 'Allow subscriptions to stories', 'fcnen' ); ?>
                  </label>
                  <br>
                  <label for="fcnen-flag-taxonomies">
                    <input type="hidden" name="fcnen_flag_subscribe_to_taxonomies" value="0">
                    <input type="checkbox" name="fcnen_flag_subscribe_to_taxonomies" id="fcnen-flag-taxonomies" value="1" autocomplete="off" <?php echo checked( 1, get_option( 'fcnen_flag_subscribe_to_taxonomies' ), false ); ?>>
                    <?php _e( 'Allow subscriptions to taxonomies', 'fcnen' ); ?>
                  </label>
                  <br>
                  <label for="fcnen-flag-allow-passwords">
                    <input type="hidden" name="fcnen_flag_allow_passwords" value="0">
                    <input type="checkbox" name="fcnen_flag_allow_passwords" id="fcnen-flag-allow-passwords" value="1" autocomplete="off" <?php echo checked( 1, get_option( 'fcnen_flag_allow_passwords' ), false ); ?>>
                    <?php _e( 'Allow notifications for protected posts', 'fcnen' ); ?>
                  </label>
                  <br>
                  <label for="fcnen-flag-allow-hidden">
                    <input type="hidden" name="fcnen_flag_allow_hidden" value="0">
                    <input type="checkbox" name="fcnen_flag_allow_hidden" id="fcnen-flag-allow-hidden" value="1" autocomplete="off" <?php echo checked( 1, get_option( 'fcnen_flag_allow_hidden' ), false ); ?>>
                    <?php _e( 'Allow notifications for hidden posts', 'fcnen' ); ?>
                  </label>
                </fieldset>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="fcnen-excerpt-length"><?php _e( 'Excerpts', 'fcnen' ); ?></label>
              </th>
              <td>
                <input type="number" name="fcnen_excerpt_length" id="fcnen-excerpt-length" class="small-text" placeholder="256" value="<?php echo esc_attr( $excerpt_length ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore>
                <p class="description"><?php _e( 'Maximum number of characters for generated excerpts. Custom excerpts are unaffected.', 'fcnen' ); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="fcnen-max-per-term"><?php _e( 'Maximums', 'fcnen' ); ?></label>
              </th>
              <td>
                <input type="number" name="fcnen_max_per_term" id="fcnen-max-per-term" class="small-text" placeholder="10" value="<?php echo esc_attr( $max_per_term ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore>
                <p class="description"><?php _e( 'Maximum subscription items per category, tag, and taxonomies. Disable with 0.', 'fcnen' ); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="fcnen-flag-purge-on-deactivation"><?php _e( 'Deactivation', 'fcnen' ); ?></label>
              </th>
              <td>
                <label for="fcnen-flag-purge-on-deactivation">
                  <input type="hidden" name="fcnen_flag_purge_on_deactivation" value="0">
                  <input type="checkbox" name="fcnen_flag_purge_on_deactivation" id="fcnen-flag-purge-on-deactivation" value="1" autocomplete="off" <?php echo checked( 1, get_option( 'fcnen_flag_purge_on_deactivation' ), false ); ?>>
                  <?php _e( 'Delete all plugin data on deactivation (irreversible)', 'fcnen' ); ?>
                </label>
              </td>
            </tr>
          </tbody>
        </table>
        <h2 class="title"><?php _e( 'Email Service', 'fcnen' ); ?></h2>
        <p><?php _e( 'You require an external account with an email service provider. The plugin composes the emails and pushes them in batches to the service, which in turn will send the email notifications.', 'fcnen' ); ?></p>
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">
                <label for="fcnen-select-service-provider"><?php _e( 'Provider', 'fcnen' ); ?></label>
              </th>
              <td>
                <select name="fcnen_service_provider" id="fcnen-select-service-provider" disabled>
                  <option value="mailersend" disabled selected><?php _e( 'MailerSend', 'fcnen' ); ?></option>
                </select>
                <p class="description"><?php _e( 'Currently only MailerSend available.', 'fcnen' ); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="fcnen-api-key"><?php _e( 'API Key', 'fcnen' ); ?></label>
              </th>
              <td>
                <input type="text" name="fcnen_api_key" id="fcnen-api-key" class="regular-text" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore>
                <p class="description"><?php _e( 'You can get that from your provider account.', 'fcnen' ); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="fcnen-api-bulk-limit"><?php _e( 'Batch Limit', 'fcnen' ); ?></label>
              </th>
              <td>
                <input type="number" name="fcnen_api_bulk_limit" id="fcnen-api-bulk-limit" class="small-text" value="<?php echo esc_attr( $api_bulk_limit ); ?>" autocomplete="off" spellcheck="false" autocorrect="off" data-1p-ignore>
                <p class="description"><?php _e( 'Emails per request.', 'fcnen' ); ?></p>
              </td>
            </tr>
          </tbody>
        </table>
        <p class="submit"><?php submit_button( __( 'Save Changes', 'fcnen' ), 'primary', 'submit', false ); ?></p>
      </form>
    </div>
  </div>
  <?php // <--- End HTML
}

// =======================================================================================
// ADMIN LOG PAGE
// =======================================================================================

/**
 * Add log admin submenu page
 *
 * @since 0.1.0
 */

function fcnen_add_log_menu_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // Add admin page
  $fcnen_admin_page_logs = add_submenu_page(
    'fcnen-notifications',
    'Log',
    'Log',
    'manage_options',
    'fcnen-log',
    'fcnen_log_page'
  );
}
add_action( 'admin_menu', 'fcnen_add_log_menu_page' );

/**
 * Callback for the log submenu page
 *
 * @since 0.1.0
 */

function fcnen_log_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'fcnen' ) );
  }

  // Start HTML ---> ?>
  <div id="fcnen-admin-page-log" class="wrap fcnen-settings _log">
    <h1 class="wp-heading-inline"><?php _e( 'Log', 'fcnen' ); ?></h1>
    <hr class="wp-header-end">
    <p><?php
      printf(
        __( 'These are the most recent 200 log items. Up to %s items are saved in the actual log file in the wp-content directory. Note that due to privacy concerns, only administrative and system actions are logged and only with IDs instead of email addresses. Datetime in GMT/UTC.', 'fcnen' ),
        FCNEN_LOG_LIMIT
      );
    ?></p>
    <div class="fcnen-log-wrapper" id="fcnen-log">
      <?php echo fcnen_get_log(); ?>
    </div>
  </div>
  <?php // <--- End HTML
}

// =======================================================================================
// POST META
// =======================================================================================

/**
 * Register metabox
 *
 * @since 0.1.0
 */

function fcnen_register_metabox() {
  add_meta_box(
    'fcnen-email-notifications',
    __( 'Email Notifications', 'fcnen' ),
    'fcnen_render_metabox',
    ['post', 'fcn_story', 'fcn_chapter'],
    'side',
    'high'
  );
}
add_action( 'add_meta_boxes', 'fcnen_register_metabox' );

/**
 * Add classes to metabox
 *
 * @since 0.1.0
 *
 * @param array $classes  An array of postbox classes.
 *
 * @return array The modified array of postbox classes.
 */

function fcnen_add_metabox_classes( $classes ) {
  // Add class
  $classes[] = 'fcnen-metabox';

  // Return with added class
  return $classes;
}
add_filter( 'postbox_classes_post_fcnen-email-notifications', 'fcnen_add_metabox_classes' );
add_filter( 'postbox_classes_fcn_story_fcnen-email-notifications', 'fcnen_add_metabox_classes' );
add_filter( 'postbox_classes_fcn_chapter_fcnen-email-notifications', 'fcnen_add_metabox_classes' );

/**
 * Render the metabox
 *
 * @since 0.1.0
 *
 * @param WP_Post $post  The current post object.
 */

function fcnen_render_metabox( $post ) {
  // Setup
  $nonce = wp_create_nonce( 'fcnen-metabox-nonce' );
  $meta = fcnen_get_meta( $post->ID );
  $excluded = $meta['excluded'] ?? 0;
  $dates = [];
  $notification = fcnen_get_notification( $post->ID );
  $added_at = null;

  if ( $meta['sent'] ?? 0 ) {
    foreach ( $meta['sent'] as $date ) {
      $dates[] = get_date_from_gmt(
        $date,
        sprintf(
          _x( '%1$s \a\t %2$s', 'Time format string.', 'fcnen' ),
          get_option( 'date_format' ),
          get_option( 'time_format' )
        )
      );
    }
  }

  if ( $notification ) {
    $added_at = get_date_from_gmt(
      $notification->added_at,
      sprintf(
        _x( '%1$s \a\t %2$s', 'Time format string.', 'fcnen' ),
        get_option( 'date_format' ),
        get_option( 'time_format' )
      )
    );
  }

  // Start HTML ---> ?>
  <input type="hidden" name="fcnen-nonce" value="<?php echo esc_attr( $nonce ); ?>" autocomplete="off">
  <?php if ( ! empty( $dates ) ) : ?>
    <p class="fcnen-metabox-date-info"><?php
      printf( __( '<strong>Mailed on</strong>%s', 'fcnen' ), implode( '<br>', $dates ) );
    ?></p>
  <?php endif; ?>
  <?php if ( $added_at ) : ?>
    <p class="fcnen-metabox-date-info"><?php
      if ( empty( $dates ) ) {
        printf( __( '<strong>Enqueued on</strong>%s', 'fcnen' ), $added_at );
      } else {
        printf( __( '<strong>Enqueued again on</strong>%s', 'fcnen' ), $added_at );
      }
    ?></p>
  <?php endif; ?>
  <label class="fictioneer-meta-checkbox">
    <div class="fictioneer-meta-checkbox__checkbox">
      <input type="checkbox" id="fcnen-enqueue-on-update" name="fcnen_enqueue_on_update" value="1" autocomplete="off">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>
    </div>
    <div class="fictioneer-meta-checkbox__label"><?php _e( 'Enqueue on update', 'fcnen' ); ?></div>
  </label>
  <label class="fictioneer-meta-checkbox">
    <div class="fictioneer-meta-checkbox__checkbox">
      <input type="checkbox" id="fcnen-exclude-from-notifications" name="fcnen_exclude_from_notifications" value="1" autocomplete="off" <?php checked( $excluded, 1 ); ?>>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>
    </div>
    <div class="fictioneer-meta-checkbox__label"><?php _e( 'Exclude from notifications', 'fcnen' ); ?></div>
  </label>
  <?php // <--- End HTML
}

/**
 * Save the metabox data
 *
 * @since 0.1.0
 *
 * @param int $post_id  The ID of the post being saved.
 */

function fcnen_save_metabox( $post_id ) {
  // Verify request
  if ( ! wp_verify_nonce( $_POST['fcnen-nonce'] ?? '', 'fcnen-metabox-nonce' ) ) {
    return;
  }

  // Setup
  $meta = fcnen_get_meta( $post_id );

  // Exclude from queue
  if ( isset( $_POST['fcnen_exclude_from_notifications'] ) ) {
    $meta['excluded'] = 1;
  } else {
    $meta['excluded'] = 0;
  }

  // Prepare sent dates if not set
  if ( empty( $meta['sent'] ?? 0 ) ) {
    $meta['sent'] = [];
  }

  // Save
  fcnen_set_meta( $post_id, $meta );
}
add_action( 'save_post_post', 'fcnen_save_metabox', 5 );
add_action( 'save_post_fcn_story', 'fcnen_save_metabox', 5 );
add_action( 'save_post_fcn_chapter', 'fcnen_save_metabox', 5 );
