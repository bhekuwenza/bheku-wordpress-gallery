<div class="gallery-metabox">

  <div class="gallery-metabox-header">
    <ul class="description">
      <li>Entry Imagery, Profile Picture, Artwork, Installation Views, etc</li>
      <li>* The first slide will be used as the main thumbnail.</li>
    </ul>
  </div>

  <div class="gallery-metabox-list-wrap">

    <ul id="gallery-metabox-list" class="gallery-metabox-list">
      <?php if (!empty($gallery_data)): ?>
        <?php foreach ($gallery_data as $key => $attachment_id): ?>
          <?php
          $attachment = get_post($attachment_id);

          if (! $attachment) continue;

          $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
          $title = get_the_title($attachment_id);
          $caption = wp_get_attachment_caption($attachment_id);
          ?>
          <li data-id="<?= esc_attr($attachment_id) ?>">

            <input
              type="hidden"
              name="media_gallery[<?= esc_attr($key) ?>]"
              value="<?= esc_attr($attachment_id) ?>">

            <?php if ($image_url) : ?>
              <img class="image-preview" src="<?php echo esc_url($image_url); ?>" alt="">
            <?php endif; ?>

            <div class="image-details">
              <?php if ($title): ?>
                <div class="image-title"><?= esc_html($title) ?></div>
              <?php endif; ?>
              <?php if ($caption): ?>
                <div class="image-caption"><?= esc_html($caption) ?></div>
              <?php endif; ?>
            </div>

            <footer>
              <a href="#" class="edit-image button" data-parent-id="<?= esc_attr($attachment_id) ?>">
                <svg class="screen-reader-text" xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 0 256 256">
                  <rect width="256" height="256" fill="none" />
                  <circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                  <path d="M120,120a8,8,0,0,1,8,8v40a8,8,0,0,0,8,8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                  <circle cx="124" cy="84" r="12" />
                </svg>
                <span class="">Edit</span>
              </a>
              <a href="#" class="remove-image" title="Delete Image">
                <svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 0 256 256">
                  <rect width="256" height="256" fill="none" />
                  <line x1="216" y1="56" x2="40" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                  <line x1="104" y1="104" x2="104" y2="168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                  <line x1="152" y1="104" x2="152" y2="168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                  <path d="M200,56V208a8,8,0,0,1-8,8H64a8,8,0,0,1-8-8V56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                  <path d="M168,56V40a16,16,0,0,0-16-16H104A16,16,0,0,0,88,40V56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                </svg>
                <span class="screen-reader-text">Delete Image</span>
              </a>
            </footer>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="gallery-metabox-placeholder">
          <svg xmlns="http://www.w3.org/2000/svg" width="192" height="192" fill="#000000" viewBox="0 0 256 256">
            <rect width="256" height="256" fill="none"></rect>
            <rect x="32" y="48" width="192" height="160" rx="8" stroke-width="16" stroke="#f1f1f1" stroke-linecap="round" stroke-linejoin="round" fill="none"></rect>
            <path d="M32,167.99982l50.343-50.343a8,8,0,0,1,11.31371,0l44.68629,44.6863a8,8,0,0,0,11.31371,0l20.68629-20.6863a8,8,0,0,1,11.31371,0L223.99982,184" fill="none" stroke="#f1f1f1" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path>
            <circle cx="156" cy="100" r="12" fill="#f1f1f1"></circle>
          </svg>
        </li>
      <?php endif; ?>
    </ul>

    <div class="gallery-metabox-attachment-edit">
      <div class="gallery-metabox-attachment-wrap">
        <div class="gallery-metabox-attachment-header">
          <span class="gallery-metabox-attachment-title">
            Image Info
          </span>
          <button type="button" class="close-modal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
              <rect width="32" height="32" fill="transparent" />
              <line x1="8" y1="8" x2="24" y2="24" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
              <line x1="24" y1="8" x2="8" y2="24" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
          </button>
        </div>
        <div class="gallery-metabox-attachment-body">
          <div id="gallery-attachment-edit-form" class="gallery-attachment-form">
            <div class="form-field">
              <label for="gallery_attachment_title">Title/Caption</label>
              <textarea id="gallery_attachment_title" name="gallery_attachment_title" class="widefat"></textarea>
            </div>
            <div class="form-field">
              <label for="gallery_attachment_caption">Credit</label>
              <input type="text" id="gallery_attachment_caption" name="gallery_attachment_caption" class="widefat">
            </div>
          </div>
        </div>
        <div class="gallery-metabox-attachment-footer">
          <button type="button" id="gallery-save-attachment" class="button button-primary">Save Changes</button>&nbsp;&nbsp;<button type="button" class="button cancel-edit">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <div class="metabox-footer">
    <a href="#" class="gallery-add button button-primary button-large"
      data-uploader-title="<?php esc_attr_e('Select Image(s) to gallery *ctrl + click to multi-select', 'gallery-metabox'); ?>"
      data-uploader-button-text="<?php esc_attr_e('Add to Gallery', 'gallery-metabox'); ?>">
      <?php esc_html_e('Add Image(s)', 'gallery-metabox'); ?>
    </a>
  </div>

</div>