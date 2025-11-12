# Bheku Wordpress Gallery

Gallery Metabox for WordPress.

## Usage

In your template inside a loop, grab the IDs of all the images with the following:

```php
$gallery_attachments = get_post_meta($post->ID, 'media_gallery', true);
```

Example with loop:

```php
$gallery_attachments = get_post_meta($post->ID, 'media_gallery', true);
foreach ($gallery_attachments as $attachment) {
  echo wp_get_attachment_link($attachment, 'large');
  // echo wp_get_attachment_image($image, 'large');
}
```
