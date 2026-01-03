/**
 * AI CRM Form - Admin JavaScript
 * Professional Form Builder
 */

(function ($) {
	'use strict';

	// Form fields array
	let formFields = [];
	let editingFieldIndex = null;
	let draggedField = null;

	/**
	 * Initialize admin functionality.
	 */
	function init() {
		// Form Builder
		initFormBuilder();

		// AI Generator Modal
		initAIGenerator();

		// Field Editor Modal
		initFieldEditor();

		// Form Import
		initFormImport();

		// Collapsible Cards
		initCollapsibleCards();

		// Copy shortcode
		$(document).on('click', '.aicrmform-copy-btn, .aicrmform-copy-btn-sm', copyToClipboard);

		// Success modal
		$('#create-another-form').on('click', function () {
			$('#aicrmform-success-modal').hide();
			resetForm();
		});

		// Close modals on overlay click
		$('.aicrmform-modal-overlay').on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) {
				$(this).hide();
			}
		});

		// Form Management (for Forms list page)
		$(document).on('click', '.aicrmform-delete-form', deleteForm);
		$(document).on('click', '.aicrmform-preview-form', previewForm);
		$(document).on('click', '.aicrmform-edit-form', editForm);
		$(document).on('click', '.aicrmform-view-submission', viewSubmission);

		// Field group toggles (for any remaining field groups)
		$('.aicrmform-field-group-toggle').on('click', toggleFieldGroup);

		// Confirmation modal
		$('#aicrmform-confirm-cancel').on('click', hideConfirmModal);
		$('#aicrmform-confirm-ok').on('click', executeConfirm);

		// Color picker sync (for settings page)
		initColorPickers();
	}

	/**
	 * Initialize color pickers with text input sync.
	 */
	function initColorPickers() {
		// Sync color input with text input
		$('.aicrmform-color-input').on('input', function () {
			const textInput = $(this).siblings('.aicrmform-color-text');
			textInput.val($(this).val().toUpperCase());
		});

		// Sync text input with color input
		$('.aicrmform-color-text').on('input', function () {
			let val = $(this).val();
			if (!val.startsWith('#')) {
				val = '#' + val;
			}
			if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
				$(this).siblings('.aicrmform-color-input').val(val);
			}
		});
	}

	/**
	 * Initialize Form Builder.
	 */
	function initFormBuilder() {
		// Add field buttons
		$('#add-field-btn, #add-first-field').on('click', function () {
			openFieldEditor(-1); // -1 means new field
		});

		// Save form
		$('#save-form').on('click', saveForm);

		// Reset form
		$('#reset-form').on('click', function () {
			showConfirm('Clear Form', 'Are you sure you want to clear all fields?', resetForm);
		});

		// Field actions (edit/delete)
		$(document).on('click', '.edit-field-btn', function (e) {
			e.stopPropagation();
			const index = $(this).closest('.aicrmform-field-item').data('index');
			openFieldEditor(index);
		});

		$(document).on('click', '.delete-field-btn', function (e) {
			e.stopPropagation();
			const index = $(this).closest('.aicrmform-field-item').data('index');
			formFields.splice(index, 1);
			renderFields();
			updateLivePreview();
		});

		// Drag and drop for reordering
		initDragDrop();

		// Live preview updates
		$('#form-name, #submit-button-text').on('input', updateLivePreview);
		$(
			'#style-primary-color, #style-border-radius, #style-label-position, #style-button-width'
		).on('change', updateLivePreview);
	}

	/**
	 * Initialize drag and drop.
	 */
	function initDragDrop() {
		$(document).on('dragstart', '.aicrmform-field-item', function (e) {
			draggedField = this;
			$(this).addClass('dragging');
			e.originalEvent.dataTransfer.effectAllowed = 'move';
			e.originalEvent.dataTransfer.setData('text/plain', $(this).data('index'));
		});

		$(document).on('dragend', '.aicrmform-field-item', function () {
			$(this).removeClass('dragging');
			$('.aicrmform-field-item').removeClass('drag-over');
			draggedField = null;
		});

		$(document).on('dragover', '.aicrmform-field-item', function (e) {
			e.preventDefault();
			if (draggedField && draggedField !== this) {
				$(this).addClass('drag-over');
			}
		});

		$(document).on('dragleave', '.aicrmform-field-item', function () {
			$(this).removeClass('drag-over');
		});

		$(document).on('drop', '.aicrmform-field-item', function (e) {
			e.preventDefault();
			$(this).removeClass('drag-over');

			if (!draggedField || draggedField === this) return;

			const fromIndex = $(draggedField).data('index');
			const toIndex = $(this).data('index');

			const field = formFields.splice(fromIndex, 1)[0];
			formFields.splice(toIndex, 0, field);
			renderFields();
			updateLivePreview();
		});
	}

	/**
	 * Initialize AI Generator.
	 */
	function initAIGenerator() {
		// Open AI modal
		$('#open-ai-generator, .open-ai-modal').on('click', function () {
			$('#ai-generator-modal').show();
			$('#ai-prompt').focus();
		});

		// Close AI modal
		$('#close-ai-modal, #cancel-ai-generate').on('click', function () {
			$('#ai-generator-modal').hide();
		});

		// AI suggestions
		$('.aicrmform-ai-suggestion').on('click', function () {
			$('#ai-prompt').val($(this).data('prompt'));
		});

		// Generate with AI
		$('#generate-with-ai').on('click', generateWithAI);
	}

	/**
	 * Initialize Field Editor.
	 */
	function initFieldEditor() {
		// Close field modal
		$('#close-field-modal, #cancel-field-edit').on('click', closeFieldEditor);

		// Save field
		$('#save-field-edit').on('click', saveField);

		// Back to picker
		$('#back-to-picker').on('click', function () {
			showPickerView();
		});

		// Field picker - CRM preset fields
		$(document).on('click', '.aicrmform-picker-field[data-preset]', function () {
			const preset = $(this).data('preset');
			selectPresetField(preset);
		});

		// Field picker - basic field types
		$(document).on('click', '.aicrmform-picker-field[data-type]', function () {
			const type = $(this).data('type');
			selectBasicFieldType(type);
		});

		// Auto-generate field name from label
		$('#field-label').on('input', function () {
			if (editingFieldIndex === -1) {
				// Only for new fields
				const label = $(this).val();
				const name = label
					.toLowerCase()
					.replace(/[^a-z0-9\s]/g, '')
					.replace(/\s+/g, '_')
					.substring(0, 30);
				$('#field-name').val(name);
			}
		});

		// Show/hide options based on type
		$('#field-type').on('change', function () {
			const type = $(this).val();
			if (['select', 'checkbox', 'radio'].includes(type)) {
				$('#field-options-row').show();
			} else {
				$('#field-options-row').hide();
			}
		});
	}

	/**
	 * Initialize Form Import.
	 */
	function initFormImport() {
		// Open import modal
		$('#open-import-modal').on('click', function () {
			$('#import-form-modal').show();
			loadImportSources();
		});

		// Close import modal
		$('#import-form-modal .aicrmform-modal-close').on('click', function () {
			$('#import-form-modal').hide();
		});

		// Import form button click
		$(document).on('click', '.aicrmform-import-form-btn', function () {
			const $btn = $(this);
			const plugin = $btn.data('plugin');
			const formId = $btn.data('form-id');
			const formTitle = $btn.data('form-title');
			const useSameShortcode = $btn
				.closest('.aicrmform-import-form-item')
				.find('.use-same-shortcode')
				.is(':checked');

			importForm(plugin, formId, formTitle, useSameShortcode, $btn);
		});
	}

	/**
	 * Load import sources from API.
	 */
	function loadImportSources() {
		$('#import-loading').show();
		$('#import-content').hide();

		$.ajax({
			url: aicrmformAdmin.restUrl + 'import/sources',
			method: 'GET',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
		})
			.done(function (response) {
				$('#import-loading').hide();
				$('#import-content').show();

				if (response.success && response.sources) {
					renderImportSources(response.sources);
				}
			})
			.fail(function () {
				$('#import-loading').hide();
				$('#import-content').show();
				$('#import-no-plugins').show();
				$('#import-sources-list').hide();
			});
	}

	/**
	 * Render import sources.
	 */
	function renderImportSources(sources) {
		let hasActiveSources = false;
		let html = '';

		for (const [key, source] of Object.entries(sources)) {
			if (source.active && source.forms && source.forms.length > 0) {
				hasActiveSources = true;
				html += '<div class="aicrmform-import-source">';
				html +=
					'<h4><span class="dashicons dashicons-admin-plugins"></span> ' +
					escapeHtml(source.name) +
					'</h4>';
				html += '<div class="aicrmform-import-forms-list">';

				source.forms.forEach(function (form) {
					html += '<div class="aicrmform-import-form-item">';
					html += '<div class="aicrmform-import-form-info">';
					html += '<strong>' + escapeHtml(form.title) + '</strong>';
					html +=
						'<span class="aicrmform-import-form-fields">' +
						form.fields.length +
						' fields</span>';
					html += '</div>';
					html += '<div class="aicrmform-import-form-actions">';
					html += '<label class="aicrmform-import-same-shortcode">';
					html +=
						'<input type="checkbox" class="use-same-shortcode" data-form-id="' +
						form.id +
						'" checked>';
					html += '<span>Use same shortcode</span>';
					html += '</label>';
					html += '<button type="button" class="button aicrmform-import-form-btn" ';
					html += 'data-plugin="' + escapeHtml(key) + '" ';
					html += 'data-form-id="' + form.id + '" ';
					html += 'data-form-title="' + escapeHtml(form.title) + '">';
					html += '<span class="dashicons dashicons-download"></span> Import';
					html += '</button>';
					html += '</div>';
					html += '</div>';
				});

				html += '</div></div>';
			}
		}

		if (hasActiveSources) {
			$('#import-no-plugins').hide();
			$('#import-sources-list').html(html).show();
		} else {
			$('#import-no-plugins').show();
			$('#import-sources-list').hide();
		}
	}

	/**
	 * Import a form.
	 */
	function importForm(plugin, formId, formTitle, useSameShortcode, $btn) {
		const originalText = $btn.html();
		$btn.prop('disabled', true).html(
			'<span class="spinner is-active" style="float: none; margin: 0;"></span>'
		);

		$.ajax({
			url: aicrmformAdmin.restUrl + 'import',
			method: 'POST',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
			contentType: 'application/json',
			data: JSON.stringify({
				plugin: plugin,
				form_id: formId,
				use_same_shortcode: useSameShortcode,
			}),
		})
			.done(function (response) {
				if (response.success) {
					$btn.html('<span class="dashicons dashicons-yes"></span> Imported!').addClass(
						'button-primary'
					);

					let successMsg = 'Form "' + formTitle + '" imported successfully!';
					if (useSameShortcode) {
						successMsg += ' Existing shortcodes will now use this form.';
					}
					showToast(successMsg, 'success');

					// Offer to disable the source plugin
					setTimeout(function () {
						let confirmMsg = 'Would you like to disable Contact Form 7?';
						if (useSameShortcode) {
							confirmMsg +=
								' Your existing shortcodes will continue to work with the imported form.';
						} else {
							confirmMsg +=
								' You may need to update your shortcodes to [ai_crm_form id="' +
								response.form_id +
								'"]';
						}

						showConfirm(
							'Disable Contact Form 7?',
							confirmMsg,
							function () {
								// Deactivate the plugin
								deactivatePlugin(plugin);
							},
							function () {
								$('#import-form-modal').hide();
								window.location.href =
									aicrmformAdmin.adminUrl + '?page=ai-crm-form-forms';
							}
						);
					}, 1000);
				} else {
					$btn.prop('disabled', false).html(originalText);
					showToast(response.error || 'Failed to import form.', 'error');
				}
			})
			.fail(function () {
				$btn.prop('disabled', false).html(originalText);
				showToast('Failed to import form.', 'error');
			});
	}

	/**
	 * Deactivate a plugin.
	 */
	function deactivatePlugin(pluginKey) {
		showToast('Deactivating plugin...', 'info');

		$.ajax({
			url: aicrmformAdmin.restUrl + 'deactivate-plugin',
			method: 'POST',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
			contentType: 'application/json',
			data: JSON.stringify({ plugin: pluginKey }),
		})
			.done(function (response) {
				if (response.success) {
					showToast('Contact Form 7 has been deactivated!', 'success');
				} else {
					showToast(response.error || 'Could not deactivate plugin.', 'error');
				}
				$('#import-form-modal').hide();
				window.location.href = aicrmformAdmin.adminUrl + '?page=ai-crm-form-forms';
			})
			.fail(function () {
				showToast('Failed to deactivate plugin.', 'error');
				$('#import-form-modal').hide();
				window.location.href = aicrmformAdmin.adminUrl + '?page=ai-crm-form-forms';
			});
	}

	/**
	 * Field presets configuration.
	 */
	const fieldPresets = {
		first_name: {
			label: 'First Name',
			name: 'first_name',
			type: 'text',
			placeholder: 'Enter your first name',
			crm_mapping: 'first_name',
			required: true,
		},
		last_name: {
			label: 'Last Name',
			name: 'last_name',
			type: 'text',
			placeholder: 'Enter your last name',
			crm_mapping: 'last_name',
			required: true,
		},
		email: {
			label: 'Email Address',
			name: 'email',
			type: 'email',
			placeholder: 'Enter your email',
			crm_mapping: 'email',
			required: true,
		},
		phone_number: {
			label: 'Phone Number',
			name: 'phone_number',
			type: 'tel',
			placeholder: 'Enter your phone number',
			crm_mapping: 'phone_number',
			required: false,
		},
		mobile_phone: {
			label: 'Mobile Phone',
			name: 'mobile_phone',
			type: 'tel',
			placeholder: 'Enter your mobile number',
			crm_mapping: 'mobile_phone',
			required: false,
		},
		company_name: {
			label: 'Company Name',
			name: 'company_name',
			type: 'text',
			placeholder: 'Enter your company name',
			crm_mapping: 'company_name',
			required: false,
		},
		company_website: {
			label: 'Website',
			name: 'company_website',
			type: 'url',
			placeholder: 'https://example.com',
			crm_mapping: 'company_website',
			required: false,
		},
		message: {
			label: 'Message',
			name: 'message',
			type: 'textarea',
			placeholder: 'Enter your message',
			crm_mapping: 'message',
			required: false,
		},
		address_line1: {
			label: 'Address Line 1',
			name: 'address_line1',
			type: 'text',
			placeholder: 'Street address',
			crm_mapping: 'primary_address_line1',
			required: false,
		},
		address_line2: {
			label: 'Address Line 2',
			name: 'address_line2',
			type: 'text',
			placeholder: 'Apt, suite, etc.',
			crm_mapping: 'primary_address_line2',
			required: false,
		},
		city: {
			label: 'City',
			name: 'city',
			type: 'text',
			placeholder: 'City',
			crm_mapping: 'primary_address_city',
			required: false,
		},
		state: {
			label: 'State',
			name: 'state',
			type: 'text',
			placeholder: 'State',
			crm_mapping: 'primary_address_state',
			required: false,
		},
		postal_code: {
			label: 'Postal Code',
			name: 'postal_code',
			type: 'text',
			placeholder: 'ZIP / Postal code',
			crm_mapping: 'primary_address_postal',
			required: false,
		},
		country: {
			label: 'Country',
			name: 'country',
			type: 'text',
			placeholder: 'Country',
			crm_mapping: 'primary_address_country',
			required: false,
		},
		source_name: {
			label: 'How did you hear about us?',
			name: 'source_name',
			type: 'select',
			placeholder: '',
			crm_mapping: 'source_name',
			required: false,
			options: ['Google', 'Social Media', 'Friend/Referral', 'Other'],
		},
	};

	/**
	 * Select a preset CRM field.
	 */
	function selectPresetField(presetKey) {
		const preset = fieldPresets[presetKey];
		if (!preset) return;

		// Check if field with same name already exists
		const exists = formFields.some((f) => f.name === preset.name);
		if (exists) {
			showToast('This field already exists in your form.', 'warning');
			return;
		}

		// Populate the editor
		$('#field-label').val(preset.label);
		$('#field-name').val(preset.name);
		$('#field-type').val(preset.type).trigger('change');
		$('#field-placeholder').val(preset.placeholder || '');
		$('#field-options').val((preset.options || []).join('\n'));
		$('#field-crm-mapping').val(preset.crm_mapping || '');
		$('#field-required').prop('checked', preset.required);

		showEditorView();
	}

	/**
	 * Select a basic field type.
	 */
	function selectBasicFieldType(type) {
		// Clear and set defaults
		$('#field-label').val('');
		$('#field-name').val('');
		$('#field-type').val(type).trigger('change');
		$('#field-placeholder').val('');
		$('#field-options').val('');
		$('#field-crm-mapping').val('');
		$('#field-required').prop('checked', false);

		showEditorView();
		$('#field-label').focus();
	}

	/**
	 * Show picker view.
	 */
	function showPickerView() {
		$('#field-picker-view').show();
		$('#field-editor-view').hide();
		$('#back-to-picker').hide();
		$('#save-field-edit').hide();
		$('#field-editor-title').text('Add Field');
	}

	/**
	 * Show editor view.
	 */
	function showEditorView() {
		$('#field-picker-view').hide();
		$('#field-editor-view').show();
		$('#back-to-picker').show();
		$('#save-field-edit')
			.show()
			.text(editingFieldIndex === -1 ? 'Add Field' : 'Save Changes');
	}

	/**
	 * Initialize collapsible cards.
	 */
	function initCollapsibleCards() {
		$('.aicrmform-card-header-collapsible').on('click', function () {
			const $card = $(this).closest('.aicrmform-card');
			const $body = $card.find('.aicrmform-card-body');
			const isCollapsed = $(this).data('collapsed');

			if (isCollapsed) {
				$body.slideDown(200);
				$(this).data('collapsed', false);
				$(this)
					.find('.dashicons')
					.removeClass('dashicons-arrow-down-alt2')
					.addClass('dashicons-arrow-up-alt2');
			} else {
				$body.slideUp(200);
				$(this).data('collapsed', true);
				$(this)
					.find('.dashicons')
					.removeClass('dashicons-arrow-up-alt2')
					.addClass('dashicons-arrow-down-alt2');
			}
		});
	}

	/**
	 * Generate form with AI.
	 */
	function generateWithAI() {
		const prompt = $('#ai-prompt').val().trim();

		if (!prompt) {
			showToast('Please describe the form you want to create.', 'warning');
			$('#ai-prompt').focus();
			return;
		}

		const $btn = $('#generate-with-ai');
		$btn.prop('disabled', true).html(
			'<span class="aicrmform-spinner-small"></span> Generating...'
		);

		$.ajax({
			url: aicrmformAdmin.restUrl + 'generate',
			method: 'POST',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
			contentType: 'application/json',
			data: JSON.stringify({ prompt: prompt }),
		})
			.done(function (response) {
				if (response.success && response.form_config) {
					// Close modal
					$('#ai-generator-modal').hide();
					$('#ai-prompt').val('');

					// Load generated fields
					if (response.form_config.fields && Array.isArray(response.form_config.fields)) {
						formFields = response.form_config.fields.map((field) => ({
							name: field.name || '',
							label: field.label || '',
							type: field.type || 'text',
							placeholder: field.placeholder || '',
							required: field.required || false,
							options: field.options || [],
							crm_mapping: field.field_id || '',
						}));
					}

					// Set form name
					if (response.form_config.form_name) {
						$('#form-name').val(response.form_config.form_name);
					}

					// Set submit button text
					if (response.form_config.submit_button_text) {
						$('#submit-button-text').val(response.form_config.submit_button_text);
					}

					// Set messages
					if (response.form_config.success_message) {
						$('#success-message').val(response.form_config.success_message);
					}

					renderFields();
					updateLivePreview();
					showToast('Form generated successfully!', 'success');
				} else {
					showToast(response.error || 'Failed to generate form.', 'error');
				}
			})
			.fail(function (xhr) {
				showToast(xhr.responseJSON?.error || 'Failed to generate form.', 'error');
			})
			.always(function () {
				$btn.prop('disabled', false).html(
					'<span class="dashicons dashicons-admin-generic"></span> Generate Form'
				);
			});
	}

	/**
	 * Open field editor modal.
	 */
	function openFieldEditor(index) {
		editingFieldIndex = index;

		if (index === -1) {
			// New field - show picker
			$('#field-editor-title').text('Add Field');
			showPickerView();
		} else {
			// Edit existing field - show editor directly
			$('#field-editor-title').text('Edit Field');
			const field = formFields[index];
			$('#field-label').val(field.label);
			$('#field-name').val(field.name);
			$('#field-type').val(field.type).trigger('change');
			$('#field-placeholder').val(field.placeholder || '');
			$('#field-options').val((field.options || []).join('\n'));
			$('#field-crm-mapping').val(field.crm_mapping || '');
			$('#field-required').prop('checked', field.required);

			// Show editor view directly (no back button for editing)
			$('#field-picker-view').hide();
			$('#field-editor-view').show();
			$('#back-to-picker').hide();
			$('#save-field-edit').show().text('Save Changes');
		}

		$('#field-editor-modal').show();
		if (index !== -1) {
			$('#field-label').focus();
		}
	}

	/**
	 * Close field editor modal.
	 */
	function closeFieldEditor() {
		$('#field-editor-modal').hide();
		editingFieldIndex = null;
	}

	/**
	 * Save field from editor.
	 */
	function saveField() {
		const label = $('#field-label').val().trim();
		const name = $('#field-name').val().trim();

		if (!label || !name) {
			showToast('Label and Field ID are required.', 'warning');
			return;
		}

		// Check for duplicate names
		const isDuplicate = formFields.some((f, i) => i !== editingFieldIndex && f.name === name);
		if (isDuplicate) {
			showToast('Field ID must be unique.', 'warning');
			return;
		}

		const type = $('#field-type').val();
		const optionsText = $('#field-options').val().trim();
		const options = optionsText
			? optionsText
					.split('\n')
					.map((o) => o.trim())
					.filter((o) => o)
			: [];

		const field = {
			name: name,
			label: label,
			type: type,
			placeholder: $('#field-placeholder').val().trim(),
			required: $('#field-required').is(':checked'),
			options: options,
			crm_mapping: $('#field-crm-mapping').val(),
		};

		if (editingFieldIndex === -1) {
			formFields.push(field);
		} else {
			formFields[editingFieldIndex] = field;
		}

		closeFieldEditor();
		renderFields();
		updateLivePreview();
		showToast(editingFieldIndex === -1 ? 'Field added.' : 'Field updated.', 'success');
	}

	/**
	 * Render fields in the builder.
	 */
	function renderFields() {
		const $container = $('#form-fields-container');

		if (formFields.length === 0) {
			$container.html(`
				<div class="aicrmform-empty-fields">
					<div class="aicrmform-empty-fields-icon">
						<span class="dashicons dashicons-forms"></span>
					</div>
					<p>No fields yet. Add fields manually or generate with AI.</p>
					<div class="aicrmform-empty-fields-actions">
						<button type="button" id="add-first-field" class="button button-primary">
							<span class="dashicons dashicons-plus-alt2"></span>
							Add Field
						</button>
						<button type="button" class="button open-ai-modal">
							<span class="dashicons dashicons-superhero"></span>
							Generate with AI
						</button>
					</div>
				</div>
			`);
			return;
		}

		let html = '';
		formFields.forEach((field, index) => {
			const reqBadge = field.required
				? '<span class="aicrmform-field-badge required">Required</span>'
				: '';
			const crmBadge = field.crm_mapping
				? `<span class="aicrmform-field-badge crm">${escapeHtml(field.crm_mapping)}</span>`
				: '';

			html += `
				<div class="aicrmform-field-item" data-index="${index}" draggable="true">
					<div class="aicrmform-field-item-drag">
						<span class="dashicons dashicons-move"></span>
					</div>
					<div class="aicrmform-field-item-content">
						<div class="aicrmform-field-item-label">${escapeHtml(field.label)}</div>
						<div class="aicrmform-field-item-meta">
							<span class="aicrmform-field-badge type">${escapeHtml(field.type)}</span>
							${reqBadge}
							${crmBadge}
						</div>
					</div>
					<div class="aicrmform-field-item-actions">
						<button type="button" class="edit-field-btn" title="Edit">
							<span class="dashicons dashicons-edit"></span>
						</button>
						<button type="button" class="delete-field-btn" title="Delete">
							<span class="dashicons dashicons-trash"></span>
						</button>
					</div>
				</div>
			`;
		});

		$container.html(html);
	}

	/**
	 * Update live preview.
	 */
	function updateLivePreview() {
		const $container = $('#live-preview-container');

		if (formFields.length === 0) {
			$container.html(`
				<div class="aicrmform-preview-empty">
					<span class="dashicons dashicons-visibility"></span>
					<p>Your form preview will appear here</p>
				</div>
			`);
			return;
		}

		const primaryColor = $('#style-primary-color').val() || '#0073aa';
		const borderRadius = $('#style-border-radius').val() || '4px';
		const labelPosition = $('#style-label-position').val() || 'top';
		const buttonWidth = $('#style-button-width').val() || 'auto';
		const submitText = $('#submit-button-text').val() || 'Submit';

		let labelClass = '';
		if (labelPosition === 'hidden') labelClass = 'aicrmform-labels-hidden';
		if (labelPosition === 'left') labelClass = 'aicrmform-labels-left';

		let html = `<div class="aicrmform-preview-form ${labelClass}">`;

		formFields.forEach((field) => {
			html += renderPreviewField(field, borderRadius);
		});

		const btnStyle = `background-color: ${primaryColor}; border-radius: ${borderRadius}; ${buttonWidth === 'full' ? 'width: 100%;' : ''}`;
		html += `
			<div class="aicrmform-preview-field aicrmform-preview-submit">
				<button type="button" style="${btnStyle}">${escapeHtml(submitText)}</button>
			</div>
		`;

		html += '</div>';

		$container.html(html);
	}

	/**
	 * Render preview field.
	 */
	function renderPreviewField(field, borderRadius) {
		const label = escapeHtml(field.label);
		const reqMark = field.required ? '<span class="required">*</span>' : '';
		const placeholder = escapeHtml(field.placeholder || '');
		const inputStyle = `border-radius: ${borderRadius}`;

		let html = `<div class="aicrmform-preview-field aicrmform-preview-field-${field.type}">`;
		html += `<label>${label}${reqMark}</label>`;

		switch (field.type) {
			case 'textarea':
				html += `<textarea placeholder="${placeholder}" style="${inputStyle}" disabled></textarea>`;
				break;
			case 'select':
				html += `<select style="${inputStyle}" disabled>`;
				html += `<option value="">Select...</option>`;
				if (field.options && field.options.length) {
					field.options.forEach((opt) => {
						html += `<option value="${escapeHtml(opt)}">${escapeHtml(opt)}</option>`;
					});
				}
				html += `</select>`;
				break;
			case 'checkbox':
			case 'radio':
				const options = field.options?.length ? field.options : ['Option 1'];
				options.forEach((opt) => {
					html += `<label class="aicrmform-preview-option"><input type="${field.type}" disabled><span>${escapeHtml(opt)}</span></label>`;
				});
				break;
			default:
				html += `<input type="${field.type}" placeholder="${placeholder}" style="${inputStyle}" disabled>`;
		}

		html += '</div>';
		return html;
	}

	/**
	 * Save form.
	 */
	function saveForm() {
		const formName = $('#form-name').val().trim();
		const crmFormId = $('#crm-form-id').val().trim();

		if (!formName) {
			showToast('Please enter a form name.', 'warning');
			$('#form-name').focus();
			return;
		}

		if (!crmFormId) {
			showToast('Please enter a CRM Form ID.', 'warning');
			$('#crm-form-id').focus();
			return;
		}

		// Validate CRM Form ID format
		const pattern =
			/^FormConfigID-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;
		if (!pattern.test(crmFormId)) {
			showToast('Invalid CRM Form ID format.', 'error');
			$('#crm-form-id').focus();
			$('#crm-form-id-error')
				.text('Expected: FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
				.show();
			return;
		}

		if (formFields.length === 0) {
			showToast('Please add at least one field.', 'warning');
			return;
		}

		// Check for fields without CRM mapping
		const fieldsWithoutMapping = formFields.filter(
			(f) => !f.crm_mapping || f.crm_mapping.trim() === ''
		);
		if (fieldsWithoutMapping.length > 0 && !window.ignoreCrmMappingWarning) {
			const fieldNames = fieldsWithoutMapping.map((f) => f.label || f.name).join(', ');
			showCrmMappingWarning(fieldNames);
			return;
		}

		// Reset the ignore flag after use
		window.ignoreCrmMappingWarning = false;

		const $btn = $('#save-form');
		$btn.prop('disabled', true).html('<span class="aicrmform-spinner-small"></span> Saving...');

		const formConfig = {
			form_name: formName,
			fields: formFields.map((field) => ({
				name: field.name,
				label: field.label,
				type: field.type,
				placeholder: field.placeholder,
				required: field.required,
				options: field.options,
				field_id: field.crm_mapping || '',
			})),
			submit_button_text: $('#submit-button-text').val() || 'Submit',
			success_message: $('#success-message').val() || 'Thank you for your submission!',
			error_message: $('#error-message').val() || 'Something went wrong.',
			styles: {
				font_family: $('#style-font-family').val(),
				font_size: $('#style-font-size').val(),
				background_color: $('#style-background-color').val(),
				primary_color: $('#style-primary-color').val(),
				border_radius: $('#style-border-radius').val(),
				label_position: $('#style-label-position').val(),
				button_width: $('#style-button-width').val(),
			},
			custom_css: $('#custom-css').val() || '',
		};

		$.ajax({
			url: aicrmformAdmin.restUrl + 'forms',
			method: 'POST',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
			contentType: 'application/json',
			data: JSON.stringify({
				form_config: formConfig,
				crm_form_id: crmFormId,
				name: formName,
			}),
		})
			.done(function (response) {
				if (response.success) {
					$('#saved-form-shortcode').text(response.shortcode);
					$('#aicrmform-success-modal').show();
				} else {
					showToast(response.error || 'Failed to save form.', 'error');
				}
			})
			.fail(function (xhr) {
				showToast(xhr.responseJSON?.error || 'Failed to save form.', 'error');
			})
			.always(function () {
				$btn.prop('disabled', false).html(
					'<span class="dashicons dashicons-saved"></span> Save Form'
				);
			});
	}

	/**
	 * Reset form.
	 */
	function resetForm() {
		formFields = [];
		$('#form-name').val('');
		$('#crm-form-id-error').hide();
		$('#submit-button-text').val('Submit');
		renderFields();
		updateLivePreview();
	}

	// ==================== UTILITY FUNCTIONS ====================

	/**
	 * Show toast notification.
	 */
	function showToast(message, type = 'info', duration = 3000) {
		const $toast = $('#aicrmform-toast');
		$toast.text(message).removeClass('success error warning').addClass(type).addClass('show');

		setTimeout(function () {
			$toast.removeClass('show');
		}, duration);
	}

	/**
	 * Show alert modal (popup instead of JS alert).
	 */
	function showAlert(title, message, type = 'warning') {
		const $modal = $('#aicrmform-alert-modal');
		const $icon = $('#aicrmform-alert-icon');

		$('#aicrmform-alert-title').text(title);
		$('#aicrmform-alert-message').text(message);

		// Set icon based on type
		$icon.removeClass('warning error success info').addClass(type);
		const icons = {
			warning: 'dashicons-warning',
			error: 'dashicons-dismiss',
			success: 'dashicons-yes-alt',
			info: 'dashicons-info',
		};
		$icon.find('.dashicons').attr('class', 'dashicons ' + (icons[type] || 'dashicons-warning'));

		$modal.addClass('active');
	}

	/**
	 * Hide alert modal.
	 */
	function hideAlert() {
		$('#aicrmform-alert-modal').removeClass('active');
		// Reset to single button mode
		$('#aicrmform-alert-ignore').hide();
		$('#aicrmform-alert-ok').text('OK');
	}

	/**
	 * Show CRM mapping warning with ignore option.
	 */
	function showCrmMappingWarning(fieldNames) {
		const $modal = $('#aicrmform-alert-modal');
		const $icon = $('#aicrmform-alert-icon');

		$('#aicrmform-alert-title').text('CRM Mapping Missing');
		$('#aicrmform-alert-message').html(
			'The following fields do not have a CRM mapping: <strong>' +
				fieldNames +
				'</strong><br><br>' +
				'Data from these fields will not be synced to your CRM. You can still save the form, but consider adding mappings for full CRM integration.'
		);

		$icon.removeClass('warning error success info').addClass('warning');
		$icon.find('.dashicons').attr('class', 'dashicons dashicons-warning');

		// Show ignore button and change OK text
		$('#aicrmform-alert-ok').text('Go Back');
		$('#aicrmform-alert-ignore').show();

		$modal.addClass('active');
	}

	// Alert modal close handler
	$(document).on('click', '#aicrmform-alert-ok', hideAlert);
	$(document).on('click', '#aicrmform-alert-ignore', function () {
		hideAlert();
		window.ignoreCrmMappingWarning = true;
		$('#save-form').click(); // Retry save
	});
	$(document).on('click', '#aicrmform-alert-modal', function (e) {
		if ($(e.target).is('#aicrmform-alert-modal')) {
			hideAlert();
		}
	});

	/**
	 * Copy to clipboard.
	 */
	function copyToClipboard() {
		let text = $(this).data('copy');
		if (!text) {
			text =
				$(this).siblings('code').text() ||
				$(this).closest('.aicrmform-shortcode-box').find('code').text();
		}
		if (text) {
			navigator.clipboard
				.writeText(text)
				.then(function () {
					showToast('Copied to clipboard!', 'success');
				})
				.catch(function () {
					const textarea = document.createElement('textarea');
					textarea.value = text;
					document.body.appendChild(textarea);
					textarea.select();
					document.execCommand('copy');
					document.body.removeChild(textarea);
					showToast('Copied to clipboard!', 'success');
				});
		}
	}

	/**
	 * Escape HTML entities.
	 */
	function escapeHtml(text) {
		if (typeof text !== 'string') return text;
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// ==================== CONFIRMATION MODAL ====================

	let confirmCallback = null;

	function showConfirm(title, message, callback) {
		$('#aicrmform-confirm-title').text(title);
		$('#aicrmform-confirm-message').text(message);
		confirmCallback = callback;
		$('#aicrmform-confirm-modal').show();
	}

	function hideConfirmModal() {
		$('#aicrmform-confirm-modal').hide();
		confirmCallback = null;
	}

	function executeConfirm() {
		if (confirmCallback) {
			confirmCallback();
		}
		hideConfirmModal();
	}

	// ==================== FORMS LIST PAGE FUNCTIONS ====================

	function toggleFieldGroup() {
		const $toggle = $(this);
		const $content = $toggle.next('.aicrmform-field-group-content');
		const expanded = $toggle.attr('aria-expanded') === 'true';
		$toggle.attr('aria-expanded', !expanded);
		$content.slideToggle(200);
	}

	function deleteForm(e) {
		e.preventDefault();
		const $btn = $(this);
		const formId = $btn.data('form-id');
		const $card = $btn.closest('.aicrmform-form-card-pro');
		const formName = $card.find('h3').text();
		const isActive = $card.find('.aicrmform-status-active').length > 0;

		showConfirm(
			'Delete Form',
			'Are you sure you want to delete "' + formName + '"?',
			function () {
				$.ajax({
					url: aicrmformAdmin.restUrl + 'forms/' + formId,
					method: 'DELETE',
					headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
				})
					.done(function (response) {
						if (response.success) {
							$card.fadeOut(300, function () {
								$(this).remove();
								updateFormStats();
								checkEmptyState();
							});
							showToast('Form deleted.', 'success');
						} else {
							showToast(response.error || 'Failed to delete.', 'error');
						}
					})
					.fail(function () {
						showToast('Failed to delete form.', 'error');
					});
			}
		);
	}

	/**
	 * Update form stats after deletion.
	 */
	function updateFormStats() {
		const totalCount = $('.aicrmform-form-card-pro').length;
		const activeCount = $('.aicrmform-form-card-pro .aicrmform-status-active').length;

		$('#forms-total-count').text(totalCount);
		$('#forms-active-count').text(activeCount);
	}

	/**
	 * Check if forms list is empty and show empty state.
	 */
	function checkEmptyState() {
		const totalCount = $('.aicrmform-form-card-pro').length;

		if (totalCount === 0) {
			// Show empty state if it exists, or reload page
			if ($('.aicrmform-empty-state').length) {
				$('.aicrmform-forms-grid').hide();
				$('.aicrmform-stats-bar').hide();
				$('.aicrmform-empty-state').show();
			} else {
				// Reload page to show empty state
				window.location.reload();
			}
		}
	}

	function previewForm(e) {
		e.preventDefault();
		const formId = $(this).data('form-id');

		$.ajax({
			url: aicrmformAdmin.restUrl + 'forms/' + formId,
			method: 'GET',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
		}).done(function (response) {
			if (response.success && response.form) {
				showFormPreviewModal(response.form);
			}
		});
	}

	function showFormPreviewModal(form) {
		let html = '<div class="aicrmform-modal-overlay" id="form-preview-modal">';
		html += '<div class="aicrmform-modal aicrmform-modal-lg">';
		html += '<div class="aicrmform-modal-header"><h3>' + escapeHtml(form.name) + '</h3></div>';
		html += '<div class="aicrmform-modal-body">';
		html += '<div class="aicrmform-shortcode-box" style="margin-bottom: 20px;">';
		html += '<code>[ai_crm_form id="' + form.id + '"]</code>';
		html +=
			'<button type="button" class="aicrmform-copy-btn" data-copy=\'[ai_crm_form id="' +
			form.id +
			'"]\'><span class="dashicons dashicons-admin-page"></span></button>';
		html += '</div>';
		html += '<div class="aicrmform-preview-area" id="modal-form-preview"></div>';
		html += '</div>';
		html +=
			'<div class="aicrmform-modal-footer"><button type="button" class="button" onclick="jQuery(\'#form-preview-modal\').remove();">Close</button></div>';
		html += '</div></div>';

		const $modal = $(html);
		$('body').append($modal);

		if (form.form_config && form.form_config.fields) {
			let previewHtml = '<form class="aicrmform-form">';
			form.form_config.fields.forEach(function (field) {
				previewHtml += renderPreviewFieldStatic(field);
			});
			previewHtml +=
				'<div class="aicrmform-field aicrmform-submit"><button type="button" class="aicrmform-button">' +
				escapeHtml(form.form_config.submit_button_text || 'Submit') +
				'</button></div>';
			previewHtml += '</form>';
			$modal.find('#modal-form-preview').html(previewHtml);
		}

		$modal.on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) $modal.remove();
		});
	}

	function renderPreviewFieldStatic(field) {
		const label = escapeHtml(field.label || field.name);
		const reqMark = field.required ? '<span class="aicrmform-required">*</span>' : '';
		let html = '<div class="aicrmform-field"><label>' + label + reqMark + '</label>';

		switch (field.type) {
			case 'textarea':
				html += '<textarea disabled></textarea>';
				break;
			case 'select':
				html += '<select disabled><option>Select...</option></select>';
				break;
			default:
				html += '<input type="' + (field.type || 'text') + '" disabled>';
		}

		html += '</div>';
		return html;
	}

	function editForm(e) {
		e.preventDefault();
		const formId = $(this).data('form-id');

		$.ajax({
			url: aicrmformAdmin.restUrl + 'forms/' + formId,
			method: 'GET',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
		}).done(function (response) {
			if (response.success && response.form) {
				showFormEditModal(response.form);
			}
		});
	}

	function showFormEditModal(form) {
		const styles = form.form_config?.styles || {};
		const customCss = form.form_config?.custom_css || '';

		let html = '<div class="aicrmform-modal-overlay" id="form-edit-modal">';
		html += '<div class="aicrmform-modal aicrmform-modal-lg" style="max-width: 700px;">';
		html +=
			'<div class="aicrmform-modal-header"><h3>Edit: ' +
			escapeHtml(form.name) +
			'</h3></div>';
		html += '<div class="aicrmform-modal-body" style="max-height: 70vh; overflow-y: auto;">';

		// Basic Info Section
		html += '<h4 style="margin: 0 0 12px; color: #374151;">Basic Information</h4>';
		html +=
			'<div class="aicrmform-style-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">';
		html +=
			'<div class="aicrmform-form-row"><label>Form Name *</label><input type="text" id="edit-form-name" class="aicrmform-input" value="' +
			escapeHtml(form.name) +
			'"></div>';
		html +=
			'<div class="aicrmform-form-row"><label>Status</label><select id="edit-form-status" class="aicrmform-input"><option value="active"' +
			(form.status === 'active' ? ' selected' : '') +
			'>Active</option><option value="inactive"' +
			(form.status === 'inactive' ? ' selected' : '') +
			'>Inactive</option></select></div>';
		html += '</div>';
		html +=
			'<div class="aicrmform-form-row" style="margin-bottom: 20px;"><label>CRM Form ID *</label><input type="text" id="edit-crm-form-id" class="aicrmform-input" value="' +
			escapeHtml(form.crm_form_id) +
			'"></div>';

		// Styling Section
		html +=
			'<h4 style="margin: 0 0 12px; color: #374151; border-top: 1px solid #e5e7eb; padding-top: 20px;">Styling</h4>';
		html +=
			'<div class="aicrmform-style-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">';

		// Font Family
		html +=
			'<div class="aicrmform-form-row"><label>Font Family</label><select id="edit-font-family" class="aicrmform-input">';
		html +=
			'<option value=""' +
			(!styles.font_family ? ' selected' : '') +
			'>System Default</option>';
		html += '<optgroup label="Sans-serif">';
		[
			'Inter',
			'Roboto',
			'Open Sans',
			'Lato',
			'Poppins',
			'Montserrat',
			'Source Sans Pro',
			'Nunito',
		].forEach(function (font) {
			html +=
				'<option value="' +
				font +
				'"' +
				(styles.font_family === font ? ' selected' : '') +
				'>' +
				font +
				'</option>';
		});
		html += '</optgroup><optgroup label="Serif">';
		['Merriweather', 'Playfair Display', 'Lora'].forEach(function (font) {
			html +=
				'<option value="' +
				font +
				'"' +
				(styles.font_family === font ? ' selected' : '') +
				'>' +
				font +
				'</option>';
		});
		html += '</optgroup></select></div>';

		// Font Size
		html +=
			'<div class="aicrmform-form-row"><label>Font Size</label><select id="edit-font-size" class="aicrmform-input">';
		html +=
			'<option value="14px"' +
			(styles.font_size === '14px' ? ' selected' : '') +
			'>14px - Small</option>';
		html +=
			'<option value="16px"' +
			(!styles.font_size || styles.font_size === '16px' ? ' selected' : '') +
			'>16px - Default</option>';
		html +=
			'<option value="18px"' +
			(styles.font_size === '18px' ? ' selected' : '') +
			'>18px - Large</option>';
		html += '</select></div>';

		// Background Color
		html +=
			'<div class="aicrmform-form-row"><label>Background Color</label><input type="color" id="edit-background-color" class="aicrmform-color-input" value="' +
			(styles.background_color || '#ffffff') +
			'"></div>';

		// Button Color
		html +=
			'<div class="aicrmform-form-row"><label>Button Color</label><input type="color" id="edit-primary-color" class="aicrmform-color-input" value="' +
			(styles.primary_color || '#0073aa') +
			'"></div>';

		// Border Radius
		html +=
			'<div class="aicrmform-form-row"><label>Border Radius</label><select id="edit-border-radius" class="aicrmform-input">';
		html +=
			'<option value="0"' +
			(styles.border_radius === '0' ? ' selected' : '') +
			'>None</option>';
		html +=
			'<option value="4px"' +
			(!styles.border_radius || styles.border_radius === '4px' ? ' selected' : '') +
			'>Small</option>';
		html +=
			'<option value="8px"' +
			(styles.border_radius === '8px' ? ' selected' : '') +
			'>Medium</option>';
		html +=
			'<option value="12px"' +
			(styles.border_radius === '12px' ? ' selected' : '') +
			'>Large</option>';
		html += '</select></div>';

		// Label Position
		html +=
			'<div class="aicrmform-form-row"><label>Label Position</label><select id="edit-label-position" class="aicrmform-input">';
		html +=
			'<option value="top"' +
			(!styles.label_position || styles.label_position === 'top' ? ' selected' : '') +
			'>Top</option>';
		html +=
			'<option value="left"' +
			(styles.label_position === 'left' ? ' selected' : '') +
			'>Inline</option>';
		html +=
			'<option value="hidden"' +
			(styles.label_position === 'hidden' ? ' selected' : '') +
			'>Hidden</option>';
		html += '</select></div>';

		// Button Width
		html +=
			'<div class="aicrmform-form-row"><label>Button Width</label><select id="edit-button-width" class="aicrmform-input">';
		html +=
			'<option value="auto"' +
			(!styles.button_width || styles.button_width === 'auto' ? ' selected' : '') +
			'>Auto</option>';
		html +=
			'<option value="full"' +
			(styles.button_width === 'full' ? ' selected' : '') +
			'>Full Width</option>';
		html += '</select></div>';

		html += '</div>';

		// Custom CSS
		html +=
			'<div class="aicrmform-form-row" style="margin-top: 16px;"><label>Custom CSS</label><textarea id="edit-custom-css" class="aicrmform-textarea" rows="3" placeholder="/* Your custom styles */">' +
			escapeHtml(customCss) +
			'</textarea></div>';

		html += '</div>';
		html += '<div class="aicrmform-modal-footer">';
		html += '<button type="button" class="button" id="cancel-edit">Cancel</button>';
		html +=
			'<button type="button" class="button button-primary" id="save-edit" data-form-id="' +
			form.id +
			'">Save Changes</button>';
		html += '</div></div></div>';

		const $modal = $(html);
		$modal.data('form-config', form.form_config);
		$('body').append($modal);

		$modal.on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) $modal.remove();
		});
		$modal.find('#cancel-edit').on('click', function () {
			$modal.remove();
		});
		$modal.find('#save-edit').on('click', function () {
			const formId = $(this).data('form-id');
			let formConfig = $modal.data('form-config') || {};

			// Update styles in form config
			formConfig.styles = {
				font_family: $modal.find('#edit-font-family').val(),
				font_size: $modal.find('#edit-font-size').val(),
				background_color: $modal.find('#edit-background-color').val(),
				primary_color: $modal.find('#edit-primary-color').val(),
				border_radius: $modal.find('#edit-border-radius').val(),
				label_position: $modal.find('#edit-label-position').val(),
				button_width: $modal.find('#edit-button-width').val(),
			};
			formConfig.custom_css = $modal.find('#edit-custom-css').val();

			$.ajax({
				url: aicrmformAdmin.restUrl + 'forms/' + formId,
				method: 'PUT',
				headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
				contentType: 'application/json',
				data: JSON.stringify({
					form_config: formConfig,
					updates: {
						name: $modal.find('#edit-form-name').val(),
						crm_form_id: $modal.find('#edit-crm-form-id').val(),
						status: $modal.find('#edit-form-status').val(),
					},
				}),
			})
				.done(function (response) {
					if (response.success) {
						showToast('Form updated successfully!', 'success');
						$modal.remove();
						location.reload();
					} else {
						showToast('Failed to update form.', 'error');
					}
				})
				.fail(function () {
					showToast('Failed to update form.', 'error');
				});
		});
	}

	function viewSubmission(e) {
		e.preventDefault();
		const submissionId = $(this).data('submission-id');

		$.ajax({
			url: aicrmformAdmin.restUrl + 'submissions/' + submissionId,
			method: 'GET',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
		}).done(function (response) {
			if (response.success && response.submission) {
				const sub = response.submission;
				let html = '<div class="aicrmform-modal-overlay" id="submission-modal">';
				html += '<div class="aicrmform-modal aicrmform-modal-lg">';
				html +=
					'<div class="aicrmform-modal-header"><h3>Submission #' + sub.id + '</h3></div>';
				html += '<div class="aicrmform-modal-body">';
				html += '<table class="widefat" style="margin-bottom: 20px;">';
				if (sub.submission_data) {
					Object.entries(sub.submission_data).forEach(function ([key, value]) {
						html +=
							'<tr><td><strong>' +
							escapeHtml(key) +
							'</strong></td><td>' +
							escapeHtml(String(value)) +
							'</td></tr>';
					});
				}
				html += '</table>';
				html +=
					'<p><strong>Status:</strong> ' +
					sub.status +
					' | <strong>IP:</strong> ' +
					sub.ip_address +
					'</p>';
				html += '</div>';
				html +=
					'<div class="aicrmform-modal-footer"><button type="button" class="button" onclick="jQuery(\'#submission-modal\').remove();">Close</button></div>';
				html += '</div></div>';

				const $modal = $(html);
				$('body').append($modal);
				$modal.on('click', function (e) {
					if ($(e.target).hasClass('aicrmform-modal-overlay')) $modal.remove();
				});
			}
		});
	}

	// Initialize on document ready
	$(document).ready(init);
})(jQuery);
