<?php

/**
 * Returns modal content
 *
 * @since 0.1.0
 *
 * @return string The HTML of the modal content.
 */

function fcncn_get_modal_content() {
  // Setup
  $advanced_mode = get_option( 'fcncn_advanced_mode' );
  $auth_email = $_POST['auth-email'] ?? 0;
  $auth_code = $_POST['auth-code'] ?? 0;
  $subscriber = fcncn_get_subscriber_by_email_and_code( $auth_email, $auth_code );
  $edit_mode = $advanced_mode && $subscriber;
  $button_label = $edit_mode ? __( 'Update', 'fcncn' ) : __( 'Subscribe', 'fcncn' );

  ob_start();
  // Start HTML ---> ?>
  <form method="post" id="fcncn-subscription-form">

    <?php if ( ! $subscriber ) : ?>
      <div class="fcncn-dialog-modal__auth-mode" data-target="auth-mode" hidden>

        <div class="dialog-modal__row">
          <p class="dialog-modal__description"><?php
            _e( 'Enter your email address and code (found in all emails) to edit your subscription, or <button type="button" class="fcncn-inline-button" data-click-action="submit-mode">go back</button> to subscribe with a new email address.', 'fcncn' );
          ?></p>
        </div>

        <div class="dialog-modal__row _no-top">
          <input type="email" name="auth-email" id="fcncn-modal-auth-email" class="fcncn-auth-email" placeholder="<?php esc_attr_e( 'Email Address', 'fcncn' ); ?>" value="" autocomplete="off" maxlength="191">
        </div>

        <div class="dialog-modal__row _no-top">
          <div class="fcncn-dialog-modal__input-button-pair">
            <input type="text" name="auth-code" id="fcncn-modal-auth-code" class="fcncn-auth-code" placeholder="<?php esc_attr_e( 'Code', 'fcncn' ); ?>" value="" autocomplete="off" maxlength="191">
            <button type="button" id="fcnes-modal-auth-button" class="button fcncn-button"><?php _e( 'Edit', 'fcncn' ); ?></button>
          </div>
        </div>

      </div>
    <?php endif; ?>

    <div class="fcncn-dialog-modal__submit-mode" data-target="submit-mode">

      <?php if ( $subscriber ) : ?>
        <input type="hidden" name="code" id="fcncn-modal-submit-code" value="<?php echo $auth_code; ?>" autocomplete="off">
        <div class="dialog-modal__row">
          <p class="dialog-modal__description"><?php
            _e( 'Update your subscription or <button type="button" class="fcncn-inline-button" data-click-action="fcncn-delete-subscription">delete</button> it.', 'fcncn' );
          ?></p>
        </div>
      <?php else : ?>
        <div class="dialog-modal__row">
          <p class="dialog-modal__description"><?php
            _e( 'Receive email notifications about new chapters on the site. You can <button type="button" class="fcncn-inline-button" data-click-action="auth-mode">edit or cancel</button> at any time.', 'fcncn' );
          ?></p>
        </div>
      <?php endif; ?>

      <div class="dialog-modal__row _no-top fcncn-dialog-modal__input-button-pair">
        <input type="email" name="email" id="fcncn-modal-submit-email" class="fcncn-email" placeholder="<?php esc_attr_e( 'Email Address', 'fcncn' ); ?>" value="<?php echo $subscriber ? $auth_email : ''; ?>" autocomplete="off" maxlength="191" required <?php echo $subscriber ? 'disabled' : ''; ?>>
        <?php if ( ! $subscriber || $advanced_mode ) : ?>
          <button type="button" id="fcnes-modal-submit-button" class="button fcncn-button"><?php echo $button_label; ?></button>
        <?php endif; ?>
      </div>

      <?php if ( $advanced_mode ) : ?>
        <div class="dialog-modal__row _no-top fcncn-dialog-modal__scopes">
          <div class="radio-label">
            <input type="radio" id="fcncn-modal-radio-scope-everything" name="scope" value="everything" checked>
            <label for="fcncn-modal-radio-scope-everything"><?php _e( 'Everything', 'fcncn' ); ?></label>
          </div>
          <div class="radio-label">
            <input type="radio" id="fcncn-modal-radio-scope-stories" name="scope" value="stories">
            <label for="fcncn-modal-radio-scope-stories"><?php _e( 'Stories', 'fcncn' ); ?></label>
          </div>
        </div>
      <?php endif; ?>

    </div>

  </form>
  <?php // <--- End HTML
  return ob_get_clean();
}
