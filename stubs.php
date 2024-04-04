<?php

/**
 * Minifies a HTML string
 *
 * This is not safe for `<pre>` or `<code>` tags!
 *
 * @since Fictioneer 5.4.0
 *
 * @param string $html  The HTML string to be minified.
 *
 * @return string The minified HTML string.
 */

function fictioneer_minify_html( $html ) {}

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
 * Updates post author user caches for a list of post objects.
 *
 * @since WP 6.1.0
 *
 * @param WP_Post[] $posts Array of post objects.
 */

function update_post_author_caches( $posts ) {}

/**
 * Prevents multi-fire in update hooks
 *
 * Unfortunately, the block editor always fires twice: once as REST request and
 * followed by WP_POST. Only the first will have the correct parameters such as
 * $update set, the second is technically no longer an update. Since blocking
 * the follow-up WP_POST would block programmatically triggered actions, there
 * is no other choice but to block the REST request and live with it.
 *
 * @since Fictioneer 5.5.2
 *
 * @param int $post_id  The ID of the updated post.
 *
 * @return boolean True if NOT allowed, false otherwise.
 */

function fictioneer_multi_save_guard( $post_id ) {}

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
 * Explodes string into an array
 *
 * Strips lines breaks, trims whitespaces, and removes empty elements.
 * Values might not be unique.
 *
 * @since Fictioneer 5.1.3
 *
 * @param string $string  The string to explode.
 *
 * @return array The string content as array.
 */

function fictioneer_explode_list( $value ) {}
