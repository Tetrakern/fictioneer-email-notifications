<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

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

  // Provider
  register_setting( 'fcnen_provider_group', 'fcnen_service_provider', 'sanitize_text_field' );
  register_setting( 'fcnen_provider_group', 'fcnen_api_key', 'sanitize_text_field' );
  register_setting( 'fcnen_provider_group', 'fcnen_api_bulk_limit', 'absint' );

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
  // Only on the plugin's admin pages
  if ( strpos( $_GET['page'] ?? '', 'fcnen-' ) === false ) {
    return;
  }

  // Styles
  wp_enqueue_style(
    'fcnen-admin-styles',
    plugin_dir_url( __FILE__ ) . '/css/fcnen-admin.css',
    [],
    FCNEN_VERSION
  );

  // Scripts
  wp_enqueue_script(
    'fcnen-admin-scripts',
    plugin_dir_url( __FILE__ ) . '/js/fcnen-admin.min.js',
    ['fictioneer-utility-scripts'],
    FCNEN_VERSION,
    true
  );
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
      _x( 'Fictioneer Email Notifications %s', 'Admin page footer text.', 'fcnes' ),
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

  // Default notices
  if ( ( $_GET['settings-updated'] ?? 0 ) === 'true' ) {
    $notice = __( 'Settings saved' );
    $class = 'notice-success';
  }

  // FCNES notices
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
      $notice = sprintf( __( 'Error. %s could not be added.', 'fcnes' ), $message ?: __( 'Subscriber', 'fcnen' ) );
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
  $notifications = add_menu_page(
    'Email Notifications',
    'Notifications',
    'manage_options',
    'fcnen-notifications',
    'fcnen_notifications_page',
    'dashicons-email-alt'
  );
}
add_action( 'admin_menu', 'fcnen_add_notifications_menu_page' );

/**
 * Callback for the notifications menu page
 *
 * @since 0.1.0
 */

function fcnen_notifications_page() {
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

function fcnen_add_subscribers_menu_page() {
  // Guard
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // Add admin page
  $fcnen_admin_page_subscribers = add_submenu_page(
    'fcnen-notifications',
    'Subscribers',
    'Subscribers',
    'manage_options',
    'fcnen-subscribers',
    'fcnen_subscribers_page'
  );

  // Add screen options
  if ( $fcnen_admin_page_subscribers ) {
    add_action( "load-{$fcnen_admin_page_subscribers}", 'fcnen_subscribers_table_screen_options' );
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
    wp_die( __( 'You do not have permission to access this page.', 'fcnes' ) );
  }

  // Setup
  $subscribers_table->prepare_items();

  // Start HTML ---> ?>
  <div id="fcnen-admin-page-subscribers" class="wrap fcnen-settings _subscribers">
    <h1 class="fcnen-settings__header"><?php echo esc_html__( 'Subscribers', 'fcnen' ); ?></h1>
    <hr class="wp-header-end">

    <div class="fcnen-settings__content">

      <div class="fcnen-settings__columns _stretch">

        <div class="fcnen-box">
          <div class="fcnen-box__header">
            <h2><?php _e( 'Add Subscriber', 'fcnen' ); ?></h2>
          </div>
          <div class="fcnen-box__body">
            <div class="fcnen-box__row"><p class="fcnen-box__description"><?php _e( 'Add a subscriber, optionally without confirmation. Duplicate emails will be ignored.', 'fcnen' ); ?></p></div>
            <div class="fcnen-box__row">
              <form method="POST" action="<?php echo admin_url( 'admin-post.php?action=fcnen_submit_subscriber' ); ?>" class="fcnen-box__vertical">
                <?php wp_nonce_field( 'submit_subscriber', 'fcnen-nonce' ); ?>
                <div class="fcnen-input-wrap">
                  <input type="email" name="email" id="fcnen-submit-subscriber-email" placeholder="<?php esc_attr_e( 'Email Address', 'fcnen' ); ?>" required>
                </div>
                <div class="fcnen-box__horizontal">
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
                <div class="fcnen-action-wrap">
                  <button type="submit" class="button button-primary"><?php _e( 'Add Subscriber', 'fcnen' ); ?></button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="fcnen-box">
          <div class="fcnen-box__header">
            <h2><?php _e( 'Import CSV', 'fcnen' ); ?></h2>
          </div>
          <div class="fcnen-box__body">
            <div class="fcnen-box__row"><p class="fcnen-box__description"><?php _e( 'Import subscribers from a CSV file. Duplicate emails will be ignored. Keep in mind that scopes are saved as IDs and may not match if the associated terms have changed.', 'fcnen' ); ?></p></div>
            <div class="fcnen-box__row">
              <form method="POST" action="<?php echo admin_url( 'admin-post.php?action=fcnen_import_subscribers_csv' ); ?>" enctype="multipart/form-data" class="fcnen-box__vertical">
                <?php wp_nonce_field( 'fcnen-import-csv', 'fcnen-nonce' ); ?>
                <div class="fcnen-box__horizontal">
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
                <div class="fcnen-action-wrap">
                  <button type="submit" class="button button-primary"><?php _e( 'Import CSV', 'fcnen' ); ?></button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div>

      <div class="fcnen-settings__table fcnen-subscribers-table-wrapper">
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
  $fcnen_admin_page_settings = add_submenu_page(
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
    wp_die( __( 'You do not have permission to access this page.', 'fcnes' ) );
  }

  // Setup
  $from = fcnen_get_from_email_address();
  $name = fcnen_get_from_email_name();

  // $provider = get_option( 'fcnen_service_provider' );
  $api_key = get_option( 'fcnen_api_key' );
  $api_bulk_limit = get_option( 'fcnen_api_bulk_limit', 400 );

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
  <script><?php echo 'var fcnes_preview_replacements = ' . json_encode( $preview_replacements ) . ';'; ?></script>
  <div id="fcnen-admin-page-subscribers" class="wrap fcnen-settings _settings">
    <h1 class="fcnen-settings__header"><?php echo esc_html__( 'Settings', 'fcnen' ); ?></h1>
    <hr class="wp-header-end">

    <div class="fcnen-settings__content">

      <div class="fcnen-settings__columns _stretch">

        <div class="fcnen-box">
          <div class="fcnen-box__header">
            <h2><?php _e( 'General', 'fcnen' ); ?></h2>
          </div>
          <div class="fcnen-box__body">
            <div class="fcnen-box__row">
              <form method="POST" action="options.php" class="fcnen-box__vertical">
                <?php
                  settings_fields( 'fcnen_general_group' );
                  do_settings_sections( 'fcnen_general_group' );
                ?>
                <div class="fcnen-left-right-wrap">
                  <label for="fcnen-from-email-address" class="offset-top"><?php _e( 'From', 'fcnen' ); ?></label>
                  <div class="fcnen-input-wrap">
                    <input type="email" name="fcnen_from_email_address" id="fcnen-from-email-address" placeholder="<?php _ex( 'noreply@your-site.com', 'From email address placeholder.', 'fcnen' ); ?>" value="<?php echo esc_attr( $from ); ?>" required>
                    <p class="fcnen-input-wrap__sub-label"><?php _e( 'Defaults to noreply@* or admin email address.', 'fcnen' ); ?></p>
                  </div>
                </div>
                <div class="fcnen-left-right-wrap">
                  <label for="fcnen-from-email-name" class="offset-top"><?php _e( 'Name', 'fcnen' ); ?></label>
                  <div class="fcnen-input-wrap">
                    <input type="text" name="fcnen_from_email_name" id="fcnen-from-email-name" placeholder="<?php _ex( 'Your Site', 'From email name placeholder.', 'fcnen' ); ?>" value="<?php echo esc_attr( $name ); ?>" required>
                    <p class="fcnen-input-wrap__sub-label"><?php _e( 'Defaults to site name.', 'fcnen' ); ?></p>
                  </div>
                </div>
                <div class="fcnen-left-right-wrap">
                  <span><?php _e( 'Flags', 'fcnen' ); ?></span>
                  <div>
                    <div class="fcnen-checkbox-wrap">
                      <input type="hidden" name="fcnen_flag_subscribe_to_stories" value="0">
                      <input type="checkbox" name="fcnen_flag_subscribe_to_stories" id="fcnen-flag-stories" value="1" autocomplete="off" <?php echo checked( 1, get_option( 'fcnen_flag_subscribe_to_stories' ), false ); ?>>
                      <label for="fcnen-flag-stories"><?php _e( 'Allow subscriptions to stories', 'fcnen' ); ?></label>
                    </div>
                    <div class="fcnen-checkbox-wrap" style="margin-top: 14px;">
                      <input type="hidden" name="fcnen_flag_subscribe_to_taxonomies" value="0">
                      <input type="checkbox" name="fcnen_flag_subscribe_to_taxonomies" id="fcnen-flag-taxonomies" value="1" autocomplete="off" <?php echo checked( 1, get_option( 'fcnen_flag_subscribe_to_taxonomies' ), false ); ?>>
                      <label for="fcnen-flag-taxonomies"><?php _e( 'Allow subscriptions to taxonomies', 'fcnen' ); ?></label>
                    </div>
                  </div>
                </div>
                <div class="fcnen-action-wrap">
                  <?php submit_button( __( 'Save Changes', 'fcnes' ), 'primary', 'submit', false ); ?>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="fcnen-box">
          <div class="fcnen-box__header">
            <h2><?php _e( 'Service Provider API', 'fcnen' ); ?></h2>
          </div>
          <div class="fcnen-box__body">
            <div class="fcnen-box__row">
              <form method="POST" action="options.php" class="fcnen-box__vertical">
                <?php
                  settings_fields( 'fcnen_provider_group' );
                  do_settings_sections( 'fcnen_provider_group' );
                ?>
                <div class="fcnen-left-right-wrap">
                  <label for="fcnen-select-service-provider" class="offset-top"><?php _e( 'Provider', 'fcnen' ); ?></label>
                  <div class="fcnen-input-wrap">
                    <select name="fcnen_service_provider" id="fcnen-select-service-provider">
                      <option value="mailersend" disabled selected><?php _e( 'MailerSend', 'fcnen' ); ?></option>
                    </select>
                    <p class="fcnen-input-wrap__sub-label"><?php _e( 'Currently, only MailerSend is available.', 'fcnen' ); ?></p>
                  </div>
                </div>
                <div class="fcnen-left-right-wrap">
                  <label for="fcnen-api-key" class="offset-top"><?php _e( 'API Key', 'fcnen' ); ?></label>
                  <div class="fcnen-input-wrap">
                    <input type="text" name="fcnen_api_key" id="fcnen-api-key" value="<?php echo esc_attr( $api_key ); ?>" required>
                    <p class="fcnen-input-wrap__sub-label"><?php _e( 'You can get that from your provider account.', 'fcnen' ); ?></p>
                  </div>
                </div>
                <div class="fcnen-left-right-wrap">
                  <label for="fcnen-api-bulk-limit" class="offset-top"><?php _e( 'Limit', 'fcnen' ); ?></label>
                  <div class="fcnen-input-wrap">
                    <input type="text" name="fcnen_api_bulk_limit" id="fcnen-api-bulk-limit" value="<?php echo esc_attr( $api_bulk_limit ); ?>" style="max-width: 100px;" required>
                    <p class="fcnen-input-wrap__sub-label"><?php _e( 'Emails per request.', 'fcnen' ); ?></p>
                  </div>
                </div>
                <div class="fcnen-action-wrap">
                  <?php submit_button( __( 'Save Changes', 'fcnes' ), 'primary', 'submit', false ); ?>
                  <button type="subuttonbmit" id="fcnes-test-api" class="button"><?php _e( 'Test', 'fcnen' ); ?></button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div>

      <div class="fcnen-box">
        <div class="fcnen-box__header">
          <h2><?php _e( 'Email Templates', 'fcnen' ); ?></h2>
        </div>
        <form method="POST" action="options.php" class="fcnen-box__body">
          <?php
            settings_fields( 'fcnen_template_group' );
            do_settings_sections( 'fcnen_template_group' );
          ?>

          <div class="fcnen-box__row">
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
          </div>

          <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="layout-confirmation">
            <div class="fcnen-replacement-tokens"><?php _e( 'This email is sent when a new subscription is submitted, prompting the subscriber to confirm it as security measure against fraudulent submissions. Anyone could enter anyone’s email address, after all. If not confirmed within 24 hours, the subscription and all data will be deleted once the cron job runs (every 12 hours). Make sure to always include the necessary replacement tokens. You can use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the replacement is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the replacement <em>is empty.</em>', 'fcnen' ); ?></div>
            <div class="fcnen-spacer"></div>
            <div class="fcnen-left-right-wrap">
              <label for="fcnen-template-subject-confirmation" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
              <div class="fcnen-input-wrap">
                <input type="text" name="fcnen_template_subject_confirmation" id="fcnen-template-subject-confirmation" placeholder="<?php echo FCNEN_DEFAULTS['subject_confirmation']; ?>" value="<?php echo $subject_confirmation; ?>">
              </div>
            </div>
            <textarea name="fcnen_template_layout_confirmation" id="fcnen-template-layout-confirmation" class="fcnen-codemirror"><?php echo esc_textarea( $layout_confirmation ); ?></textarea>
            <div class="fcnen-placeholders">
              <code>{{site_name}}</code>
              <code>{{site_link}}</code>
              <code>{{activation_link}}</code>
              <code>{{unsubscribe_link}}</code>
              <code>{{edit_link}}</code>
              <code>{{email}}</code>
              <code>{{code}}</code>
            </div>
            <details class="fcnen-default-code">
              <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
              <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_confirmation'] ?? '' ); ?></code></pre>
            </details>
            <div class="fcnen-action-wrap">
              <?php submit_button( __( 'Save Templates', 'fcnes' ), 'primary', 'submit', false ); ?>
            </div>
          </div>

          <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="layout-code">
            <div class="fcnen-replacement-tokens"><?php _e( 'This email can be manually triggered in the subscriber list, sending the edit code to a confirmed subscriber along with an edit link for convenience. This should normally not be required since the code should be included in all other emails anyway, but people are people. Make sure to always include the necessary replacement tokens. You can use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the replacement is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the replacement <em>is empty.</em>', 'fcnen' ); ?></div>
            <div class="fcnen-spacer"></div>
            <div class="fcnen-left-right-wrap">
              <label for="fcnen-template-subject-code" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
              <div class="fcnen-input-wrap">
                <input type="text" name="fcnen_template_subject_code" id="fcnen-template-subject-code" placeholder="<?php echo FCNEN_DEFAULTS['subject_code']; ?>" value="<?php echo $subject_code; ?>">
              </div>
            </div>
            <textarea name="fcnen_template_layout_code" id="fcnen-template-layout-code" class="fcnen-codemirror"><?php echo esc_textarea( $layout_code ); ?></textarea>
            <div class="fcnen-placeholders">
              <code>{{site_name}}</code>
              <code>{{site_link}}</code>
              <code>{{activation_link}}</code>
              <code>{{unsubscribe_link}}</code>
              <code>{{edit_link}}</code>
              <code>{{email}}</code>
              <code>{{code}}</code>
            </div>
            <details class="fcnen-default-code">
              <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
              <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_code'] ?? '' ); ?></code></pre>
            </details>
            <div class="fcnen-action-wrap">
              <?php submit_button( __( 'Save Templates', 'fcnes' ), 'primary', 'submit', false ); ?>
            </div>
          </div>

          <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="layout-edit">
            <div class="fcnen-replacement-tokens"><?php _e( 'This email is sent whenever a subscriber updates their preferences, both as confirmation and security notification about the change. Just in case a malicious actor managed to acquire both their email address and code. Make sure to always include the necessary replacement tokens. You can use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the replacement is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the replacement <em>is empty.</em>', 'fcnen' ); ?></div>
            <div class="fcnen-spacer"></div>
            <div class="fcnen-left-right-wrap">
              <label for="fcnen-template-subject-edit" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
              <div class="fcnen-input-wrap">
                <input type="text" name="fcnen_template_subject_edit" id="fcnen-template-subject-edit" placeholder="<?php echo FCNEN_DEFAULTS['subject_edit']; ?>" value="<?php echo $subject_edit; ?>">
              </div>
            </div>
            <textarea name="fcnen_template_layout_edit" id="fcnen-template-layout-edit" class="fcnen-codemirror"><?php echo esc_textarea( $layout_edit ); ?></textarea>
            <div class="fcnen-placeholders">
              <code>{{site_name}}</code>
              <code>{{site_link}}</code>
              <code>{{activation_link}}</code>
              <code>{{unsubscribe_link}}</code>
              <code>{{edit_link}}</code>
              <code>{{email}}</code>
              <code>{{code}}</code>
              <code>{{scope_everything}}</code>
              <code>{{scope_post_types}}</code>
              <code>{{scope_stories}}</code>
              <code>{{scope_categories}}</code>
              <code>{{scope_tags}}</code>
              <code>{{scope_fandoms}}</code>
              <code>{{scope_characters}}</code>
              <code>{{scope_warnings}}</code>
            </div>
            <details class="fcnen-default-code">
              <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
              <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_edit'] ?? '' ); ?></code></pre>
            </details>
            <div class="fcnen-action-wrap">
              <?php submit_button( __( 'Save Templates', 'fcnes' ), 'primary', 'submit', false ); ?>
            </div>
          </div>

          <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="layout-notification">
            <div class="fcnen-replacement-tokens"><?php _e( 'This email is sent for actual update notifications and consists of multiple parts: layout and loop partials. While this layout provides the surrounding body with some nice text of your choice (and the code, really important), the loop partials render the individual content matching the subscriber’s preferences. Make sure to always include the necessary replacement tokens. You can use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the replacement is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the replacement <em>is empty.</em>', 'fcnen' ); ?></div>
            <div class="fcnen-spacer"></div>
            <div class="fcnen-left-right-wrap">
              <label for="fcnen-template-subject-notification" class="offset-top"><?php _e( 'Subject', 'fcnen' ); ?></label>
              <div class="fcnen-input-wrap">
                <input type="text" name="fcnen_template_subject_notification" id="fcnen-template-subject-notification" placeholder="<?php echo FCNEN_DEFAULTS['subject_notification']; ?>" value="<?php echo $subject_notification; ?>">
              </div>
            </div>
            <textarea name="fcnen_template_layout_notification" id="fcnen-template-layout-notification" class="fcnen-codemirror"><?php echo esc_textarea( $layout_notification ); ?></textarea>
            <div class="fcnen-placeholders">
              <code>{{site_name}}</code>
              <code>{{site_link}}</code>
              <code>{{activation_link}}</code>
              <code>{{unsubscribe_link}}</code>
              <code>{{edit_link}}</code>
              <code>{{email}}</code>
              <code>{{code}}</code>
              <code>{{updates}}</code>
            </div>
            <details class="fcnen-default-code">
              <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
              <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['layout_notification'] ?? '' ); ?></code></pre>
            </details>
            <div class="fcnen-action-wrap">
              <?php submit_button( __( 'Save Templates', 'fcnes' ), 'primary', 'submit', false ); ?>
            </div>
          </div>

          <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="loop-part-post">
            <div class="fcnen-replacement-tokens"><?php _e( 'This partial renders post updates in notification emails inside the <code>{{updates}}</code> replacement token. You can use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the replacement is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the replacement <em>is empty.</em>', 'fcnen' ); ?></div>
            <div class="fcnen-spacer"></div>
            <textarea name="fcnen_template_loop_part_post" id="fcnen-template-loop-part-post" class="fcnen-codemirror"><?php echo esc_textarea( $loop_part_post ); ?></textarea>
            <div class="fcnen-placeholders">
              <code>{{type}}</code>
              <code>{{title}}</code>
              <code>{{link}}</code>
              <code>{{author}}</code>
              <code>{{excerpt}}</code>
              <code>{{date}}</code>
              <code>{{time}}</code>
              <code>{{thumbnail}}</code>
              <code>{{categories}}</code>
              <code>{{tags}}</code>
              <code>{{site_name}}</code>
              <code>{{site_link}}</code>
            </div>
            <details class="fcnen-default-code">
              <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
              <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['loop_part_post'] ?? '' ); ?></code></pre>
            </details>
            <div class="fcnen-action-wrap">
              <?php submit_button( __( 'Save Templates', 'fcnes' ), 'primary', 'submit', false ); ?>
            </div>
          </div>

          <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="loop-part-story">
            <div class="fcnen-replacement-tokens"><?php _e( 'This partial renders story updates in notification emails inside the <code>{{updates}}</code> replacement token. You can use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the replacement is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the replacement <em>is empty.</em>', 'fcnen' ); ?></div>
            <div class="fcnen-spacer"></div>
            <textarea name="fcnen_template_loop_part_story" id="fcnen-template-loop-part-story" class="fcnen-codemirror"><?php echo esc_textarea( $loop_part_story ); ?></textarea>
            <div class="fcnen-placeholders">
              <code>{{type}}</code>
              <code>{{title}}</code>
              <code>{{link}}</code>
              <code>{{author}}</code>
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
              <code>{{site_name}}</code>
              <code>{{site_link}}</code>
            </div>
            <details class="fcnen-default-code">
              <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
              <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['loop_part_story'] ?? '' ); ?></code></pre>
            </details>
            <div class="fcnen-action-wrap">
              <?php submit_button( __( 'Save Templates', 'fcnes' ), 'primary', 'submit', false ); ?>
            </div>
          </div>

          <div class="fcnen-box__row fcnen-box__vertical fcnen-template-wrapper hidden" id="loop-part-chapter">
            <div class="fcnen-replacement-tokens"><?php _e( 'This partial renders chapter updates in notification emails inside the <code>{{updates}}</code> replacement token. You can use <code>{{#token}}</code>content<code>{{/token}}</code> to only render the middle part if the replacement is <em>not</em> empty, and  <code>{{^token}}</code>content<code>{{/token}}</code> for when the replacement <em>is empty.</em>', 'fcnen' ); ?></div>
            <div class="fcnen-spacer"></div>
            <textarea name="fcnen_template_loop_part_chapter" id="fcnen-template-loop-part-chapter" class="fcnen-codemirror"><?php echo esc_textarea( $loop_part_chapter ); ?></textarea>
            <div class="fcnen-placeholders">
              <code>{{type}}</code>
              <code>{{title}}</code>
              <code>{{link}}</code>
              <code>{{author}}</code>
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
              <code>{{story_title}}</code>
              <code>{{story_link}}</code>
              <code>{{site_name}}</code>
              <code>{{site_link}}</code>
            </div>
            <details class="fcnen-default-code">
              <summary><?php _e( 'Default HTML', 'fcnen' ); ?></summary>
              <pre><code><?php echo esc_textarea( FCNEN_DEFAULTS['loop_part_chapter'] ?? '' ); ?></code></pre>
            </details>
            <div class="fcnen-action-wrap">
              <?php submit_button( __( 'Save Templates', 'fcnes' ), 'primary', 'submit', false ); ?>
            </div>
          </div>

        </form>
      </div>

      <div class="fcnen-box">
        <div class="fcnen-box__header">
          <h2><?php _e( 'Preview', 'fcnen' ); ?></h2>
        </div>
        <div class="fcnen-box__body">
          <div class="fcnen-box__row" id="fcnen-preview-notice">
            <?php _e( 'No template selected.', 'fcnen' ); ?>
          </div>
          <div class="fcnen-box__row _iframe hidden" id="fcnen-preview">
            <iframe id="fcnen-preview-iframe"></iframe>
          </div>
        </div>
      </div>

    </div>
  </div>
  <?php // <--- End HTML
}
