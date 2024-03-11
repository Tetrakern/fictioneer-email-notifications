<?php

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
  $edit_mode = $advanced_mode && $subscriber;
  $button_label = $edit_mode ? __( 'Update', 'fcnen' ) : __( 'Subscribe', 'fcnen' );

  ob_start();
  // Start HTML ---> ?>
  <form method="post" id="fcnen-subscription-form">

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
        <?php if ( ! $subscriber || $advanced_mode ) : ?>
          <button type="button" id="fcnen-modal-submit-button" class="button fcnen-button"><?php echo $button_label; ?></button>
        <?php endif; ?>
      </div>

      <?php if ( $advanced_mode ) : ?>
        <div class="dialog-modal__row _no-top fcnen-dialog-modal__scopes">
          <div class="radio-label">
            <input type="radio" id="fcnen-modal-radio-scope-everything" name="scope" value="everything" checked>
            <label for="fcnen-modal-radio-scope-everything"><?php _e( 'Everything', 'fcnen' ); ?></label>
          </div>
          <div class="radio-label">
            <input type="radio" id="fcnen-modal-radio-scope-stories" name="scope" value="stories">
            <label for="fcnen-modal-radio-scope-stories"><?php _e( 'Stories', 'fcnen' ); ?></label>
          </div>
        </div>
      <?php endif; ?>

    </div>

  </form>
  <?php // <--- End HTML
  return ob_get_clean();
}
