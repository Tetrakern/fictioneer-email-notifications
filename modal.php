<?php

/**
 * Adds subscription modal to site
 *
 * @since 0.1.0
 */

function fcnen_subscription_modal() {
  // Start HTML ---> ?>
  <dialog id="fcnen-subscription-modal" class="dialog-modal fcnen-dialog-modal" data-nosnippet>
    <button class="dialog-modal__close" aria-label="<?php esc_attr_e( 'Close modal', 'fictioneer' ); ?>" data-click-action="close-dialog-modal" autofocus><?php fictioneer_icon( 'fa-xmark' ); ?></button>
    <h4 class="dialog-modal__header"><?php _e( 'Email Subscription', 'fcnen' ); ?></h4>
    <div class="fcnen-dialog-modal__ajax-target" data-target="fcnen-modal-loader">
      <div class="fcnen-modal-skeleton">
        <div class="shape" style="margin: 12px; height: 18px; max-width: min(500px, 70vw);"></div>
        <div class="shape" style="margin: 12px; height: 32px;"></div>
        <div class="shape" style="margin: 12px; height: 18px; max-width: min(600px, 50vw);"></div>
      </div>
    </div>
    <input name="nonce" type="hidden" autocomplete="off" value="<?php echo wp_create_nonce( 'fcnen-subscribe' ); ?>">
  </dialog>
  <?php // <--- End HTML
}
add_action( 'fictioneer_modals', 'fcnen_subscription_modal', 10 );

/**
 * Returns modal content
 *
 * @since 0.1.0
 *
 * @return string The HTML of the modal content.
 */

function fcnen_get_modal_content() {
  // Setup
  $advanced_mode = get_option( 'fcnen_advanced_mode' );
  $auth_email = $_POST['auth-email'] ?? 0;
  $auth_code = $_POST['auth-code'] ?? 0;
  $subscriber = fcnen_get_subscriber_by_email_and_code( $auth_email, $auth_code );
  $form_classes = ['fcnen-subscription-form'];
  $button_label = $subscriber ? __( 'Update', 'fcnen' ) : __( 'Subscribe', 'fcnen' );
  $check_everything = 1;
  $check_posts = 0;
  $check_content = 0;

  if ( $subscriber ) {
    $check_everything = $subscriber->everything;
    $check_posts = in_array( 'post', $subscriber->post_types );
    $check_content = in_array( 'fcn_story', $subscriber->post_types );
    $check_content = $check_content || in_array( 'fcn_chapter', $subscriber->post_types );
  }

  if ( ! $subscriber || $subscriber->everything ) {
    $form_classes[] = '_everything';
  }

  ob_start();
  // Start HTML ---> ?>
  <form method="post" id="fcnen-subscription-form" class="<?php echo implode( ' ', $form_classes ); ?>">

    <?php if ( ! $subscriber ) : ?>
      <div class="fcnen-dialog-modal__auth-mode" data-target="auth-mode" hidden>

        <div class="dialog-modal__row">
          <p class="dialog-modal__description"><?php
            _e( 'Enter your email address and code (found in all emails) to edit your subscription, or <button type="button" class="fcnen-inline-button" data-click-action="submit-mode">go back</button> to subscribe with a new email address.', 'fcnen' );
          ?></p>
        </div>

        <div class="dialog-modal__row _no-top">
          <input type="email" name="auth-email" id="fcnen-modal-auth-email" class="fcnen-auth-email" placeholder="<?php esc_attr_e( 'Email Address', 'fcnen' ); ?>" value="" autocomplete="off" maxlength="191">
        </div>

        <div class="dialog-modal__row _no-top">
          <div class="fcnen-dialog-modal__input-button-pair">
            <input type="text" name="auth-code" id="fcnen-modal-auth-code" class="fcnen-auth-code" placeholder="<?php esc_attr_e( 'Code', 'fcnen' ); ?>" value="" autocomplete="off" maxlength="191">
            <button type="button" id="fcnen-modal-auth-button" class="button fcnen-button"><?php _e( 'Edit', 'fcnen' ); ?></button>
          </div>
        </div>

      </div>
    <?php endif; ?>

    <div class="fcnen-dialog-modal__submit-mode" data-target="submit-mode">

      <?php if ( $subscriber ) : ?>
        <input type="hidden" name="code" id="fcnen-modal-submit-code" value="<?php echo $auth_code; ?>" autocomplete="off">
        <div class="dialog-modal__row">
          <p class="dialog-modal__description"><?php
            _e( 'Update your subscription or <button type="button" class="fcnen-inline-button" data-click-action="fcnen-delete-subscription">delete</button> it.', 'fcnen' );
          ?></p>
        </div>
      <?php else : ?>
        <div class="dialog-modal__row">
          <p class="dialog-modal__description"><?php
            _e( 'Receive email notifications about new content. You can <button type="button" class="fcnen-inline-button" data-click-action="auth-mode">edit or cancel</button> at any time.', 'fcnen' );
          ?></p>
        </div>
      <?php endif; ?>

      <div class="dialog-modal__row _no-top fcnen-dialog-modal__input-button-pair">
        <input type="email" name="email" id="fcnen-modal-submit-email" class="fcnen-email" placeholder="<?php esc_attr_e( 'Email Address', 'fcnen' ); ?>" value="<?php echo $subscriber ? $auth_email : ''; ?>" autocomplete="off" maxlength="191" required <?php echo $subscriber ? 'disabled' : ''; ?>>
        <button type="button" id="fcnen-modal-submit-button" class="button fcnen-button"><?php echo $button_label; ?></button>
      </div>

      <div class="dialog-modal__row _no-top fcnen-dialog-modal__scopes">
        <div class="checkbox-label _everything">
          <input type="hidden" name="scope-everything" value="0">
          <input type="checkbox" id="fcnen-modal-checkbox-scope-everything" name="scope-everything" value="1" <?php echo $check_everything ? 'checked' : ''; ?>>
          <label for="fcnen-modal-checkbox-scope-everything"><?php _e( 'Everything', 'fcnen' ); ?></label>
        </div>
        <div class="checkbox-label _posts">
          <input type="hidden" name="scope-posts" value="0">
          <input type="checkbox" id="fcnen-modal-checkbox-scope-posts" name="scope-posts" value="1" <?php echo $check_posts ? 'checked' : ''; ?>>
          <label for="fcnen-modal-checkbox-scope-posts"><?php _e( 'Blogs', 'fcnen' ); ?></label>
        </div>
        <div class="checkbox-label _content">
          <input type="hidden" name="scope-content" value="0">
          <input type="checkbox" id="fcnen-modal-checkbox-scope-content" name="scope-content" value="1" <?php echo $check_content ? 'checked' : ''; ?>>
          <label for="fcnen-modal-checkbox-scope-content"><?php _e( 'Stories & Chapters', 'fcnen' ); ?></label>
        </div>
      </div>

      <hr>

      <div class="dialog-modal__row fcnen-dialog-modal__advanced">
        <div class="fcnen-dialog-modal__advanced-search">
          <input type="search" id="fcnen-modal-search" class="fcnen-dialog-modal__advanced-search-string" placeholder="<?php _e( 'Type to search for anything…', 'fcnen' ); ?>" autocomplete="off" autocorrect="off" spellcheck="false" data-input-target="fcnen-search">
          <select class="fcnen-dialog-modal__advanced-search-select" id="fcnen-modal-search-select">
            <option value="fcn_story" selected><?php _e( 'Stories', 'fcnen' ); ?></option>
            <option value="taxonomies"><?php _e( 'Taxonomies', 'fcnen' ); ?></option>
          </select>
        </div>
        <div class="fcnen-dialog-modal__advanced-lists">
          <ol class="fcnen-dialog-modal__advanced-sources" data-target="fcnen-sources">
            <li class="fcnen-dialog-modal__advanced-li _disabled _no-match"><span><?php _e( 'No matches found.', 'fcnen' ); ?></span></li>
          </ol>
          <ol class="fcnen-dialog-modal__advanced-selection" data-target="fcnen-selection"></ol>
        </div>
        <template data-target="fcnen-spinner-template">
          <li class="fcnen-dialog-modal__advanced-li _disabled" data-target="fcnen-loading-observer">
            <i class="fa-solid fa-spinner fa-spin" style="--fa-animation-duration: .8s;"></i>
            <span><?php _e( 'Loading', 'fcnen' ); ?></span>
          </li>
        </template>
        <template data-target="fcnen-no-matches">
          <li class="fcnen-dialog-modal__advanced-li _disabled _no-match"><span><?php _e( 'No matches found.', 'fcnen' ); ?></span></li>
        </template>
        <template data-target="fcnen-selection-item">
          <li class="fcnen-dialog-modal__advanced-li _selected" data-type="" data-compare="" data-id=""><span></span><input type="hidden" name="" value=""></li>
        </template>
      </div>

    </div>

  </form>
  <?php // <--- End HTML
  return ob_get_clean();
}
