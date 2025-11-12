# Bheku Wordpress Gallery

Gallery Metabox for WordPress.

## Usage

To get the IDs of all the images:

```php
$media_attachments = get_post_meta($post->ID, 'media_gallery', true);
```

Using loop:

```php
$media_attachments = get_post_meta($post->ID, 'media_gallery', true);
foreach ($media_attachments as $attachment) {
  echo wp_get_attachment_link($attachment, 'large');
  // echo wp_get_attachment_image($image, 'large');
}
```
