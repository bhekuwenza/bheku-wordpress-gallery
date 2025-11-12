media_gallery
===============

Media Gallery metabox for WordPress.

Usage
-----

In your template inside a loop, grab the IDs of all the images with the following:

```php
$gallery_attachments = get_post_meta($post->ID, 'media_gallery', true);
```

Then you can loop through the IDs and call `wp_get_attachment_link` or `wp_get_attachment_image` to display the images with or without a link respectively:

```php
$gallery_attachments = get_post_meta($post->ID, 'media_gallery', true);
foreach ($gallery_attachments as $attachment) {
  echo wp_get_attachment_link($attachment, 'large');
  // echo wp_get_attachment_image($image, 'large');
}
```