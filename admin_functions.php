<?php
function update_media_rgb(array|int $filtered_ids)
{

  if (is_array($filtered_ids)) {
    foreach ($filtered_ids as $attachment_id) {
      if ($rgb = bheku_generate_image_colour($attachment_id)) {
        $update = update_post_meta($attachment_id, 'rgb', $rgb);

        if (!$update) {
          error_log('Could not save rgb for attachment: ' . $attachment_id);
        } else {
          error_log("Saved $rgb for attachment: $attachment_id");
        }
      }
    }
  }
}

// Add new column to Media Library
add_filter('manage_upload_columns', function ($columns) {
  $columns['rgb'] = 'RGB';
  return $columns;
});

// Populate the RGB column
add_action('manage_media_custom_column', function ($column_name, $post_id) {
  if ($column_name === 'rgb') {
    $rgb = get_post_meta($post_id, 'rgb', true);
    echo $rgb ? esc_html($rgb) : '—';
  }
}, 10, 2);

/**
 * 
 */
function bheku_process_colour_generation_batch()
{
  // Get all image attachments
  $attachments = get_posts([
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
    'post_mime_type' => 'image',
    'fields'         => 'ids',
  ]);

  $success = 0;
  $fail = 0;
  $total = count($attachments);

  foreach ($attachments as $count => $attachment_id) {

    delete_post_meta($attachment_id, 'rgb');

    $rgb = bheku_generate_image_colour($attachment_id);

    if ($rgb && update_post_meta($attachment_id, 'rgb', $rgb)) {
      $success++;
    } else {
      $fail++;
    }
  }

  return [
    'total'   => $total,
    'success' => $success,
    'fail'    => $fail,
  ];
}

add_action('init', function () {
  if (current_user_can('manage_options') && isset($_GET['run_colour_batch'])) {
    $result = bheku_process_colour_generation_batch();

    $message = sprintf(
      'Color generation complete: %d total images, %d successful, %d failed',
      $result['total'],
      $result['success'],
      $result['fail']
    );

    error_log($message);
    wp_die($message);
  }
});

/**
 * bheku_generate_image_colour.
 * 
 * @param int $attachment_id The attachment ID to process
 * @return string|null string hex code on success, null on failure
 */
function bheku_generate_image_colour(
  int $attachment_id,
  string $size = 'medium'
) {
  return get_average_colour(
    get_attached_file($attachment_id, $size)
  );
}

/**
 * Get the average pixel colour from the given file using Image Magick
 * Author: Paul Ferrett
 * 
 * @param string $filename
 * @param bool $as_hex Set to true, the function will return the 6 character HEX value of the colour.  
 * If false, an array will be returned with r, g, b components.
 */
function get_average_colour(
  string $filename,
  bool $as_hex_string = false,
  string $default = '#151621'
): string|array {
  if (!file_exists($filename)) {
    return $default;
  }
  if (!extension_loaded('imagick') || !class_exists('Imagick')) {
    error_log('Imagick extension is not installed or loaded.');
    return $default;
  }
  try {
    $image = new Imagick($filename);
    $image->scaleImage(100, 100);
    $width = $image->getImageWidth();
    $height = $image->getImageHeight();
    $rTotal = $gTotal = $bTotal = $alphaTotal = 0;
    $count = 0;
    $step = 5;

    for ($x = 0; $x < $width; $x += $step) {
      for ($y = 0; $y < $height; $y += $step) {
        $pixel = $image->getImagePixelColor($x, $y);
        $rgba = $pixel->getColor(true);
        $alpha = $rgba['a']; // 1 = opaque, 0 = transparent

        // Blend with white background: final = foreground * alpha + background * (1 - alpha)
        $rTotal += $rgba['r'] * $alpha + 1 * (1 - $alpha);
        $gTotal += $rgba['g'] * $alpha + 1 * (1 - $alpha);
        $bTotal += $rgba['b'] * $alpha + 1 * (1 - $alpha);
        $alphaTotal += $alpha;
        $count++;
      }
    }

    $r = (int) round(($rTotal / $count) * 255);
    $g = (int) round(($gTotal / $count) * 255);
    $b = (int) round(($bTotal / $count) * 255);
    $avgAlpha = $alphaTotal / $count;

    if ($as_hex_string) {
      return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
    return "rgba($r, $g, $b, " . round($avgAlpha, 2) . ")";
  } catch (ImagickException | Exception $e) {
    if ($as_hex_string) {
      return $default;
    }
    return 'rgb(245,245,245)';
  }
}
