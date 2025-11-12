<?php

/**
 * Plugin Name: Bheku - Wordpress Gallery
 * Plugin URI: https://ngqabutho.com
 * Description: A WordPress gallery manager for posts.
 * Version: 1.0.0
 * Author: Ngqabutho Zondo
 * Author URI: https://ngqabutho.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bheku-wordpress-gallery
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Define plugin constants.
 */
define('BHEKU_WP_GALLERY_VERSION', '1.0.0');
define('BHEKU_WP_GALLERY_DIR', plugin_dir_path(__FILE__));
define('BHEKU_WP_GALLERY_URL', plugin_dir_url(__FILE__));

// Includes
require_once BHEKU_WP_GALLERY_DIR . 'admin_functions.php';
require_once BHEKU_WP_GALLERY_DIR . 'admin.php';

/**
 * Add Gallery Metabox
 * +
 * Render Gallery Metabox
 */
function gallery_metabox_add()
{
  $post_types = ['post', 'page', 'exhibition', 'place', 'space', 'person'];

  foreach ($post_types as $type) {
    add_meta_box(
      'gallery-metabox',
      __('Gallery', 'gallery-metabox'),
      'gallery_metabox_callback',
      $type,
      'normal',
      'default'
    );
  }
}
add_action('add_meta_boxes', 'gallery_metabox_add');

function gallery_metabox_callback(WP_Post $post): void
{
  wp_nonce_field('gallery_metabox_nonce', 'gallery_metabox_nonce');
  $gallery_data = get_post_meta($post->ID, 'media_gallery', true);
  if (!is_array($gallery_data)) {
    $gallery_data = [];
  }

  include BHEKU_WP_GALLERY_DIR . 'admin_render.php';
}

/**
 * Saves the gallery metabox data
 */
function gallery_metabox_save(int $post_id): void
{
  if (! isset($_POST['gallery_metabox_nonce']) || ! wp_verify_nonce($_POST['gallery_metabox_nonce'], 'gallery_metabox_nonce')) {
    return;
  }
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  $filtered_ids = [];

  // Handle gallery saving
  if (isset($_POST['media_gallery']) && is_array($_POST['media_gallery'])) {
    $gallery_ids = array_map(
      fn($id) => filter_var($id, FILTER_VALIDATE_INT),
      $_POST['media_gallery']
    );

    $filtered_ids = array_values(array_filter($gallery_ids));

    $update = update_post_meta($post_id, 'media_gallery', $filtered_ids);

    update_media_rgb($filtered_ids);
  } else {
    delete_post_meta($post_id, 'media_gallery');
  }
}
add_action('save_post', 'gallery_metabox_save', 20); // runs after SCF


/**
 * Add proper AJAX handler for attachment updates
 */
add_action('admin_init', function () {
  add_action('wp_ajax_update-attachment', 'custom_update_attachment_ajax');
});

/**
 * Custom function to handle attachment updates via AJAX
 */
function custom_update_attachment_ajax()
{
  // Check nonce
  $nonce = isset($_POST['nonce'])
    ? sanitize_text_field($_POST['nonce'])
    : '';

  if (!wp_verify_nonce($nonce, 'update-attachment')) {
    wp_send_json_error(['message' => 'Security check failed'], 403);
    return;
  }

  // Check permissions
  if (!current_user_can('upload_files') && !current_user_can('edit_posts')) {
    wp_send_json_error(['message' => 'Permission denied'], 403);
    return;
  }

  // Get attachment ID
  $attachment_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  if (!$attachment_id) {
    wp_send_json_error(['message' => 'Invalid attachment ID'], 400);
    return;
  }

  // Check if attachment exists and user can edit it
  $attachment = get_post($attachment_id);
  if (!$attachment || $attachment->post_type !== 'attachment' || !current_user_can('edit_post', $attachment_id)) {
    wp_send_json_error(['message' => 'Cannot edit this attachment'], 403);
    return;
  }

  // Get changes
  $changes = isset($_POST['changes']) ? $_POST['changes'] : [];
  if (empty($changes) || !is_array($changes)) {
    wp_send_json_error(['message' => 'No changes specified'], 400);
    return;
  }

  $attachment_data = [
    'ID' => $attachment_id  // Only set the ID, we'll add only what we need
  ];

  // Only update fields that are explicitly in our changes array
  if (isset($changes['title'])) {
    $attachment_data['post_title'] = sanitize_text_field($changes['title']);
  }

  if (isset($changes['caption'])) {
    $attachment_data['post_excerpt'] = sanitize_text_field($changes['caption']);
  }

  if (isset($changes['description'])) {
    $attachment_data['post_content'] = sanitize_text_field($changes['description']);
  }

  // Alt text is handled separately
  if (isset($changes['alt'])) {
    update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($changes['alt']));
  }

  // Only update post if we have fields to update
  if (count($attachment_data) > 1) { // More than just ID
    $result = wp_update_post($attachment_data, true);

    if (is_wp_error($result)) {
      wp_send_json_error([
        'message' => 'Failed to update attachment',
        'error' => $result->get_error_message()
      ], 500);
      return;
    }
  }

  // Return success
  wp_send_json_success([
    'message' => 'Attachment updated successfully',
    'id' => $attachment_id
  ]);
}

/**
 * Enqueues necessary scripts and styles for the gallery metabox
 */
function gallery_metabox_admin_scripts(?string $hook): void
{
  if (
    'post.php' !== $hook &&
    'post-new.php' !== $hook
  ) {
    return;
  }

  wp_enqueue_media();

  wp_enqueue_script(
    'sortable-js',
    BHEKU_WP_GALLERY_URL . 'Sortable/Sortable.min.js',
    [],
    null,
    true
  );

  wp_enqueue_script(
    'gallery-metabox',
    BHEKU_WP_GALLERY_URL . 'script.js',
    ['jquery', 'sortable-js', 'wp-util'], // wp-util for wp.template() and wp.ajax
    null,
    true
  );

  wp_localize_script(
    'gallery-metabox',
    'myAjax',
    ['ajaxurl' => admin_url('admin-ajax.php'),]
  );

  wp_localize_script(
    'gallery-metabox',
    'wpMediaSettings',
    ['nonce' => wp_create_nonce('update-attachment'),]
  );

  wp_enqueue_style(
    'gallery-metabox',
    BHEKU_WP_GALLERY_URL . 'style.css'
  );
}
add_action('admin_enqueue_scripts', 'gallery_metabox_admin_scripts');
