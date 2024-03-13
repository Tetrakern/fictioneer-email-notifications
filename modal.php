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
  $allow_stories = get_option( 'fcnen_flag_subscribe_to_stories' );
  $allow_taxonomies = get_option( 'fcnen_flag_subscribe_to_taxonomies' );
  $default_filter = $allow_stories ? 'story' : 'taxonomies';
  $auth_email = $_POST['auth-email'] ?? 0;
  $auth_code = $_POST['auth-code'] ?? 0;
  $subscriber = fcnen_get_subscriber_by_email_and_code( $auth_email, $auth_code );
  $form_classes = ['fcnen-subscription-form'];
  $button_label = $subscriber ? __( 'Update', 'fcnen' ) : __( 'Subscribe', 'fcnen' );
  $check_everything = 1;
  $check_posts = 0;
  $check_stories = 0;
  $check_chapters = 0;
  $post_ids = [];

  if ( $subscriber ) {
    $check_everything = $subscriber->everything;
    $check_posts = in_array( 'post', $subscriber->post_types );
    $check_stories = in_array( 'fcn_story', $subscriber->post_types );
    $check_chapters = in_array( 'fcn_chapter', $subscriber->post_types );
    $post_ids = $subscriber->post_ids;
  }

  if ( ! $subscriber || $subscriber->everything ) {
    $form_classes[] = '_everything';
  }

  // Validate stories
  if ( ! empty( $post_ids ) ) {
    $args = array(
      'post_type'=> 'fcn_story',
      'post_status'=> ['publish', 'private', 'future'],
      'posts_per_page' => -1,
      'post__in' => $post_ids,
      'orderby' => 'post__in',
      'update_post_term_cache' => true, // Improve performance
      'no_found_rows' => true // Improve performance
    );

    $stories = new WP_Query( $args );
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
        <div class="checkbox-label _stories">
          <input type="hidden" name="scope-stories" value="0">
          <input type="checkbox" id="fcnen-modal-checkbox-scope-stories" name="scope-stories" value="1" <?php echo $check_stories ? 'checked' : ''; ?>>
          <label for="fcnen-modal-checkbox-scope-stories"><?php _e( 'Stories', 'fcnen' ); ?></label>
        </div>
        <div class="checkbox-label _chapters">
          <input type="hidden" name="scope-chapters" value="0">
          <input type="checkbox" id="fcnen-modal-checkbox-scope-chapters" name="scope-chapters" value="1" <?php echo $check_chapters ? 'checked' : ''; ?>>
          <label for="fcnen-modal-checkbox-scope-chapters"><?php _e( 'Chapters', 'fcnen' ); ?></label>
        </div>
      </div>

      <?php if ( $allow_stories || $allow_taxonomies ) : ?>

        <div class="dialog-modal__row fcnen-dialog-modal__advanced">
          <div class="fcnen-dialog-modal__advanced-search">
            <input type="search" id="fcnen-modal-search" class="fcnen-dialog-modal__advanced-search-string" placeholder="<?php _e( 'Search for stories or taxonomies…', 'fcnen' ); ?>" autocomplete="off" autocorrect="off" spellcheck="false" data-input-target="fcnen-search" data-default-filter="<?php echo $default_filter; ?>">
            <?php if ( $allow_stories && $allow_taxonomies ) : ?>
              <select class="fcnen-dialog-modal__advanced-search-select" id="fcnen-modal-search-select">
                <option value="story" selected><?php _e( 'Stories', 'fcnen' ); ?></option>
                <option value="taxonomies"><?php _e( 'Taxonomies', 'fcnen' ); ?></option>
              </select>
            <?php endif; ?>
          </div>
          <div class="fcnen-dialog-modal__advanced-lists">
            <ol class="fcnen-dialog-modal__advanced-sources" data-target="fcnen-sources">
              <li class="fcnen-dialog-modal__advanced-li _disabled _no-match"><span><?php _e( 'No search query.', 'fcnen' ); ?></span></li>
            </ol>
            <ol class="fcnen-dialog-modal__advanced-selection" data-target="fcnen-selection"><?php
              if ( $allow_stories ) {
                foreach ( $stories->posts as $story ) {
                  $title = fictioneer_get_safe_title( $story->ID );

                  echo "<li class='fcnen-dialog-modal__advanced-li _selected' data-click-action='fcnen-remove' data-type='post_id' data-compare='story-{$story->ID}' data-id='{$story->ID}'><span class='fcnen-dialog-modal__advanced-li-label'>" . _x( 'Story', 'List item label.', 'fcnen' ) . "</span> <span>{$title}</span><input type='hidden' name='post_id[]' value='{$story->ID}'></li>";
                }
              }
            ?></ol>
          </div>
          <template data-target="fcnen-loader-item">
            <li class="fcnen-dialog-modal__advanced-li _disabled">
              <i class="fa-solid fa-spinner fa-spin" style="--fa-animation-duration: .8s;"></i>
              <span><?php _e( 'Loading…', 'fcnen' ); ?></span>
            </li>
          </template>
          <template data-target="fcnen-no-matches-item">
            <li class="fcnen-dialog-modal__advanced-li _disabled _no-match"><span><?php _e( 'No search query.', 'fcnen' ); ?></span></li>
          </template>
          <template data-target="fcnen-selection-item">
            <li class="fcnen-dialog-modal__advanced-li _selected" data-click-action="fcnen-remove" data-type="" data-compare="" data-id=""><span></span><input type="hidden" name="" value=""></li>
          </template>
          <template data-target="fcnen-error-item">
            <li class="fcnen-dialog-modal__advanced-li _error"><span></span></li>
          </template>
        </div>

      <?php endif; ?>

    </div>

  </form>
  <?php // <--- End HTML
  return ob_get_clean();
}
