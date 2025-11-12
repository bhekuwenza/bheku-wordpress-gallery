document.addEventListener('DOMContentLoaded', () => {
	// State management for file frame
	let fileFrame = null;

	// Helper function to get image URL from attachment
	const getImageUrl = (attachment) => {
		if (!attachment || !attachment.sizes) return '#';
		return (
			attachment.sizes.thumbnail?.url ||
			attachment.sizes.medium?.url ||
			attachment.sizes.large?.url ||
			attachment.sizes.full?.url ||
			'#'
		);
	};

	// Reset indices for gallery items
	const resetIndex = () => {
		document.querySelectorAll('#gallery-metabox-list li').forEach((item, i) => {
			const input = item.querySelector('input[type="hidden"]');
			if (input) {
				input.name = `media_gallery[${i}]`;
			}
		});
	};

	// Create and configure file frame
	const createFileFrame = (options = {}) => {
		// Close existing frame if it exists
		if (fileFrame) {
			fileFrame.close();
		}

		// Default configuration
		const defaultOptions = {
			frame: 'select',
			title: 'Select Media',
			multiple: false,
			library: {
				order: 'DESC',
				orderby: 'menuOrder',
				type: 'image',
				search: null,
				uploadedTo: null,
			},
			button: {
				text: 'Select',
			},
		};

		// Merge default options with provided options
		const frameOptions = { ...defaultOptions, ...options };

		// Create new media frame
		fileFrame = wp.media(frameOptions);

		return fileFrame;
	};

	// Resolve a location ID to its human-readable label from the modal select
	const getLocationLabel = (id) => {
		if (id === undefined || id === null || id === '') return '';
		const select = document.getElementById('gallery_attachment_location');
		if (!select || !select.options) return String(id);
		const opts = select.options;
		for (let i = 0; i < opts.length; i++) {
			if (String(opts[i].value) === String(id)) {
				return (opts[i].textContent || '').trim();
			}
		}
		return String(id);
	};

	// Make gallery sortable
	const makeSortable = () => {
		const list = document.getElementById('gallery-metabox-list');
		if (!list || !window.Sortable) return;

		new Sortable(list, {
			animation: 150,
			easing: 'cubic-bezier(1, 0, 0, 1)',
			onEnd: resetIndex,
		});
	};

	// Create gallery item HTML - Make consistent with PHP markup
	const createGalleryItem = (attachment, index) => {
		const attrs = attachment.attributes || attachment;
		const imageURL = getImageUrl(attrs);
		const location = attrs.meta?.gallery_attachment_location || '';
		const locationLabel = getLocationLabel(location);

		return `
    <li data-id="${attrs.id}" data-location-id="${location}">
    <input type="hidden" name="media_gallery[${index}]" value="${attrs.id}">
    <img class="image-preview" src="${imageURL}">
    <div class="image-details">
      ${attrs.title ? `<div class="image-title">${attrs.title}</div>` : ''}
      ${
				attrs.caption ? `<div class="image-caption">${attrs.caption}</div>` : ''
			}
      ${location ? `<div class="image-location">${locationLabel}</div>` : ''}
    </div>
    <footer>
      <a class="edit-image button" href="#" data-parent-id="${attrs.id}">
      <svg class="screen-reader-text" xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 0 256 256"><rect width="256" height="256" fill="none" /><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" /><path d="M120,120a8,8,0,0,1,8,8v40a8,8,0,0,0,8,8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" /><circle cx="124" cy="84" r="12" /></svg>
      <span class="">Edit</span>
      </a>
      <a class="remove-image" href="#" title="Delete Image">
      <svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="216" y1="56" x2="40" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="104" y1="104" x2="104" y2="168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="152" y1="104" x2="152" y2="168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M200,56V208a8,8,0,0,1-8,8H64a8,8,0,0,1-8-8V56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M168,56V40a16,16,0,0,0-16-16H104A16,16,0,0,0,88,40V56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
      <span class="screen-reader-text">Delete Image</span>
      </a>
    </footer>
    </li>`;
	};

	// Add Gallery Images
	document.addEventListener('click', (e) => {
		const target = e.target.closest('a.gallery-add');
		if (!target) return;
		e.preventDefault();

		const frame = createFileFrame({
			title: target.dataset.uploaderTitle,
			multiple: true,
			button: {
				text: target.dataset.uploaderButtonText,
			},
		});

		frame.on('select', () => {
			const galleryList = document.getElementById('gallery-metabox-list');
			if (!galleryList) return;

			// Remove placeholder if it exists
			const placeholder = galleryList.querySelector(
				'.gallery-metabox-placeholder'
			);
			if (placeholder) {
				placeholder.remove();
			}

			const selection = frame.state().get('selection');
			const currentItems = galleryList.querySelectorAll(
				'li:not(.gallery-metabox-placeholder)'
			);
			const startIndex = currentItems.length;

			// Create document fragment for better performance
			const fragment = document.createRange().createContextualFragment(
				Array.from(selection.models)
					.map((attachment, i) => createGalleryItem(attachment, startIndex + i))
					.join('')
			);

			// Append new items to the gallery
			galleryList.appendChild(fragment);

			// Reset indices and make sortable
			resetIndex();
			makeSortable();
		});

		frame.on('open', () => {
			// We're intentionally NOT preselecting existing images here
			// This prevents the duplication issue when adding new images

			// If you need to keep track of selected IDs for other purposes,
			// you can still collect them, but don't add them to the selection
			const selectedIds = Array.from(
				document.querySelectorAll('#gallery-metabox-list input')
			).map((input) => input.value);

			// We're not adding them to the selection to prevent duplicates
		});

		frame.open();
	});

	// Remove Image
	document.addEventListener('click', (e) => {
		const target = e.target.closest('a.remove-image');
		if (!target) return;
		e.preventDefault();

		const listItem = target.closest('li');
		if (!listItem) return;

		listItem.style.opacity = 0;
		setTimeout(() => {
			listItem.remove();
			resetIndex();

			// Add placeholder if gallery is empty
			const galleryList = document.getElementById('gallery-metabox-list');
			if (galleryList && !galleryList.querySelectorAll('li').length) {
				galleryList.innerHTML = `
      <li class="gallery-metabox-placeholder">
      <svg xmlns="http://www.w3.org/2000/svg" width="192" height="192" fill="#000000" viewBox="0 0 256 256">
        <rect width="256" height="256" fill="none"></rect>
        <rect x="32" y="48" width="192" height="160" rx="8" stroke-width="16" stroke="#f1f1f1" stroke-linecap="round" stroke-linejoin="round" fill="none"></rect>
        <path d="M32,167.99982l50.343-50.343a8,8,0,0,1,11.31371,0l44.68629,44.6863a8,8,0,0,0,11.31371,0l20.68629-20.6863a8,8,0,0,1,11.31371,0L223.99982,184" fill="none" stroke="#f1f1f1" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path>
        <circle cx="156" cy="100" r="12" fill="#f1f1f1"></circle>
      </svg>
      </li>`;
			}
		}, 200);
	});

	// Modal Container
	const modalContainer = document.querySelector(
		'.gallery-metabox-attachment-edit'
	);

	// Current attachment being edited
	let currentAttachmentId = null;
	let currentAttachmentElement = null;

	// Edit Image with custom modal
	document.addEventListener('click', async (e) => {
		const target = e.target.closest('a.edit-image');
		if (!target) return;
		e.preventDefault();

		const imageId = target.dataset.parentId;
		if (!imageId) return;

		currentAttachmentId = imageId;
		currentAttachmentElement = target.closest('li');

		// Show modal
		modalContainer.classList.add('active');

		// Add loading state
		const modalBody = modalContainer.querySelector(
			'.gallery-metabox-attachment-body'
		);
		const formContent = modalBody.innerHTML;
		modalBody.innerHTML = '<span class="spinner is-active"></span>';

		try {
			// Get attachment data directly from WordPress media library
			const attachment = wp.media.attachment(imageId);
			await attachment.fetch();

			// Restore form
			modalBody.innerHTML = formContent;

			// Get new references to the form inputs
			const titleInput = modalBody.querySelector('#gallery_attachment_title');
			const captionInput = modalBody.querySelector(
				'#gallery_attachment_caption'
			);
			const locationInput = modalBody.querySelector(
				'#gallery_attachment_location'
			);

			// Set form values (handle string or {raw, rendered})
			const getText = (v) => {
				if (!v) return '';
				if (typeof v === 'string') return v;
				if (typeof v === 'object') return v.raw || v.rendered || '';
				return '';
			};
			if (titleInput) titleInput.value = getText(attachment.get('title'));
			if (captionInput) captionInput.value = getText(attachment.get('caption'));

			const meta = attachment.get('meta') || {};
			let locationValue = meta.gallery_attachment_location || '';
			// Prefer the value stored on the current list item if present
			const liDatasetValue =
				currentAttachmentElement && currentAttachmentElement.dataset
					? currentAttachmentElement.dataset.locationId
					: '';
			if (liDatasetValue) {
				locationValue = liDatasetValue;
			}
			if (locationInput) locationInput.value = String(locationValue || '');
		} catch (error) {
			console.error('Error loading attachment data:', error);
			modalBody.innerHTML = `
        <p>Error loading attachment data. Please try again.</p>
        <button type="button" class="button close-modal">Close</button>
      `;
		}
	});

	// Handle save button click
	document.addEventListener('click', async (e) => {
		if (e.target.id === 'gallery-save-attachment') {
			e.preventDefault();

			if (!currentAttachmentId) {
				modalContainer.classList.remove('active');
				return;
			}

			// Show loading state
			const submitBtn = e.target;
			const originalBtnText = submitBtn.textContent;
			submitBtn.textContent = 'Saving...';
			submitBtn.disabled = true;

			try {
				// Use the direct media API
				await saveAttachmentDirectly();

				// Close modal
				modalContainer.classList.remove('active');
			} catch (error) {
				console.error('Error saving attachment:', error);
				alert('Failed to save changes. Please try again.');
			} finally {
				submitBtn.textContent = originalBtnText;
				submitBtn.disabled = false;
			}
		}
	});

	const update_attachment_frontend = (
		id,
		title,
		caption,
		locationId,
		locationLabel
	) => {
		const attachment = document.querySelector('[data-id="' + id + '"]');

		if (!attachment) return;

		const wrapEl = attachment.querySelector('.image-details');

		if (!wrapEl) return;

		// wrapEl.replaceChildren()

		let str = '';

		if (title) {
			str += '<div class="image-title">' + title + '</div>';
		}

		if (caption) {
			str += '<div class="image-caption">' + caption + '</div>';
		}

		if (locationLabel) {
			str += '<div class="image-location">' + locationLabel + '</div>';
		}

		wrapEl.innerHTML = str;
		// persist latest location on the list item for next edit open
		attachment.dataset.locationId = String(locationId || '');
	};

	const saveAttachmentDirectly = async () => {
		const form = document.getElementById('gallery-attachment-edit-form');
		if (!form || !currentAttachmentId) return;

		const new_title = form.querySelector('#gallery_attachment_title').value;
		const new_caption = form.querySelector('#gallery_attachment_caption').value;
		const new_location = form.querySelector(
			'#gallery_attachment_location'
		).value;
		const new_location_label = getLocationLabel(new_location);

		const nonce = (window.wpMediaSettings && wpMediaSettings.nonce) || '';

		return new Promise((resolve, reject) => {
			wp.ajax
				.post('update-attachment', {
					nonce,
					id: currentAttachmentId,
					changes: {
						title: new_title,
						caption: new_caption,
						location: new_location,
					},
				})
				.done(() => {
					update_attachment_frontend(
						currentAttachmentId,
						new_title,
						new_caption,
						new_location,
						new_location_label
					);
					// Keep the WP media attachment model in sync
					try {
						const attModel = wp.media.attachment(currentAttachmentId);
						const prevMeta = attModel.get('meta') || {};
						attModel.set({
							title: new_title,
							caption: new_caption,
							meta: Object.assign({}, prevMeta, {
								gallery_attachment_location: new_location,
							}),
						});
					} catch (e) {
						// Non-fatal: model may not be available
					}
					resolve();
				})
				.fail((err) => {
					console.error('Error saving attachment via AJAX:', err);
					reject(err);
				});
		});
	};

	// Close modal when clicking close or cancel buttons
	document.addEventListener('click', (e) => {
		// Check if the clicked element or its parent is a close button
		const closeButton = e.target.closest('.close-modal, .cancel-edit');

		if (closeButton || e.target === modalContainer) {
			e.preventDefault();
			e.stopPropagation(); // Stop the event from bubbling up
			modalContainer.classList.remove('active');
			currentAttachmentId = null;
			currentAttachmentElement = null;
		}
	});

	// Initialize sortable functionality
	makeSortable();
});
