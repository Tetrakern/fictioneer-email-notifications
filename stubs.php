<?php

/**
 * Outputs the HTML for an inline svg icon
 *
 * @since Fictioneer 4.0.0
 *
 * @param string $icon     Name of the icon that matches the svg.
 * @param string $classes  Optional. String of CSS classes.
 * @param string $id       Optional. An element ID.
 * @param string $inserts  Optional. Additional attributes.
 */

function fictioneer_icon( $icon ) {}

/**
 * Returns the sanitized title and accounts for empty strings
 *
 * @since Fictioneer 4.7.0
 * @since Fictioneer 5.12.0 - Added $context and $args parameters.
 * @link https://developer.wordpress.org/reference/functions/wp_strip_all_tags/
 *
 * @param int|WP_Post $post     The post or post ID to get the title for.
 * @param string|null $context  Optional. Context regarding where and how the title is used.
 * @param array       $args     Optional. Additional parameters.
 *
 * @return string The title, never empty.
 */

function fictioneer_get_safe_title( $post, $content = null ) {}

/**
 * Prevent unnecessary triggers of save hooks.
 *
 * @since 5.5.2
 * @since 5.30.0 - Refactored and renamed.
 *
 * @param int $post_id  The ID of the updated post.
 *
 * @return boolean True if NOT allowed, false otherwise.
 */

function fictioneer_save_guard( $post_id ) {}

/**
 * Returns the excerpt even if the post is protected
 *
 * @since Fictioneer 4.5.0
 *
 * @param int     $post_id  Post ID.
 * @param int     $limit    Maximum number of characters.
 * @param boolean $default  Whether to return the original excerpt if present.
 *
 * @return string The excerpt stripped of tags.
 */

function fictioneer_get_forced_excerpt( $post_id, $limit = 256, $default = false ) {}

/**
 * Render or return a frontend notice element
 *
 * @since Fictioneer 5.2.5
 *
 * @param string $message   The notice to show.
 * @param string $type      Optional. The notice type. Default 'warning'.
 * @param bool   $display   Optional. Whether to render or return. Default true.
 *
 * @return void|string The build HTML or nothing if rendered.
 */

function fictioneer_notice( $message, $type = 'warning', $display = true ) {}

/**
 * Returns saved random cache busting string
 *
 * @since Fictioneer 5.12.5
 *
 * @return string Cache busting string.
 */

function fictioneer_get_cache_bust() {}
