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

	// CRM Field ID to friendly name mapping (reverse lookup) - must match class-field-mapping.php
	const crmFieldIdToName = {
		// Contact Fields
		'FieldID-211d398a-d905-43f8-a8e2-c1c952d4f5cc': 'first_name',
		'FieldID-a1c64750-0a8e-457b-8575-bcada69db4a1': 'last_name',
		'FieldID-1bcd68e1-2234-4a0c-9c70-21d9a6ff0ce8': 'phone_number',
		'FieldID-d2fea894-1e23-4bf6-b488-2cd7a8359dde': 'email',
		'FieldID-a7b68b66-6820-4a44-9717-f8f4e1d399ce': 'tags',
		// Standard Contact Fields
		'FieldID-c11b2495-6f02-4386-9fe0-141067c63c14': 'mobile_phone',
		'FieldID-6e3bb5c2-8baf-4f94-8b61-9b8a3d3b7a5c': 'additional_emails',
		'FieldID-e4d82132-ed77-4a2f-8625-1163a04efba1': 'primary_company_id',
		'FieldID-59dc9adf-6401-4af4-91cb-666ce81acc37': 'primary_address_line1',
		'FieldID-623fb8a0-c224-4952-af4c-a4e1e3bd5b70': 'primary_address_line2',
		'FieldID-4fe28279-6e83-4e7c-b310-9474dcd5034d': 'primary_address_city',
		'FieldID-a215813c-8225-4c21-b646-01b370eefd47': 'primary_address_state',
		'FieldID-3ba73c58-3fa9-4fbf-b28b-8709837685bc': 'primary_address_postal',
		'FieldID-aa50a455-c00b-4d6b-98f7-f833332b1b5c': 'primary_address_country',
		'FieldID-0a2e9d97-3d09-4b7d-a5fd-7308fa8066e1': 'message',
		'FieldID-d80132b7-4fbe-45dd-92d3-a313286567ac': 'source_name',
		'FieldID-78e702fa-84c1-46c3-84d9-c59b9f83db88': 'original_source',
		// UTM Fields
		'FieldID-2b0c575e-4a6e-4646-bd3d-0e43fa341c9a': 'utm_medium',
		'FieldID-7edd3d42-41ab-4b53-9141-6f0113046ad5': 'utm_source',
		'FieldID-dd36880f-73a9-46fc-a254-a9259341df79': 'utm_campaign',
		'FieldID-31553fd6-ea45-4420-ba1f-9f5eb51956a1': 'utm_term',
		'FieldID-7df2ac1d-8405-477c-9ae7-98a176c64c10': 'utm_content',
		'FieldID-a90f2d07-a40c-4c9f-a9f4-20122437beaa': 'gclid',
		'FieldID-59d59574-50da-4f67-a2fa-f99cf077a6b0': 'fbclid',
		'FieldID-52ba3729-fe15-41f7-ac87-662d23656c85': 'msclkid',
		// Consent Fields
		'FieldID-7e76c322-bf7c-47ed-817a-dc4e2d60402f': 'marketing_email_consent',
		'FieldID-7c8eda3b-b1b9-44b1-8f88-adff9dbc61fb': 'sms_consent',
		// Company Fields
		'FieldID-b2a5d86e-f9ae-11ed-be56-0242ac120002': 'company_name',
		'FieldID-36785fbc-9c40-4938-92e3-b262d40ef6bb': 'company_address_line1',
		'FieldID-ac4c2ea1-f8d1-4bdd-8a42-a1ad4127eef6': 'company_address_line2',
		'FieldID-33b7b0a0-82ed-471b-baa1-87ab958f90d2': 'company_address_city',
		'FieldID-184fe9c2-b3a8-47c5-85dc-7c3a285c17ac': 'company_address_state',
		'FieldID-b0e75bc5-59f7-4c54-98a8-510b9ec982ec': 'company_address_postal',
		'FieldID-daa0c91f-8a0e-4c90-be9c-e53a73789edc': 'company_address_country',
		'FieldID-f25628fb-eeb3-4d99-8737-44b403758837': 'company_website',
		'FieldID-fc7ff4ae-b753-4650-9961-42b07bcc6ac6': 'company_phone',
	};

	/**
	 * Get friendly name from CRM field ID or mapping.
	 */
	function getCrmFriendlyName(mapping) {
		if (!mapping) return '';

		// If it's already a friendly name (not a FieldID), return as-is but uppercase
		if (!mapping.startsWith('FieldID-')) {
			return mapping.toUpperCase().replace(/_/g, ' ');
		}

		// Look up in reverse mapping (case-insensitive)
		const lowerMapping = mapping.toLowerCase();
		for (const [fieldId, name] of Object.entries(crmFieldIdToName)) {
			if (fieldId.toLowerCase() === lowerMapping) {
				return name.toUpperCase().replace(/_/g, ' ');
			}
		}

		// If not found, show shortened version of FieldID
		return mapping.substring(0, 20) + '...';
	}

	/**
	 * Get CRM mapping key (for dropdown selection) from FieldID or mapping.
	 */
	function getCrmMappingKey(mapping) {
		if (!mapping) return '';

		// If it's already a friendly name (not a FieldID), return as-is
		if (!mapping.startsWith('FieldID-')) {
			return mapping.toLowerCase();
		}

		// Look up in reverse mapping (case-insensitive)
		const lowerMapping = mapping.toLowerCase();
		for (const [fieldId, name] of Object.entries(crmFieldIdToName)) {
			if (fieldId.toLowerCase() === lowerMapping) {
				return name;
			}
		}

		// If not found, return empty
		return '';
	}

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

		// Theme Styling Toggle
		initThemeStylingToggle();

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
		$(document).on('click', '.aicrmform-repair-mappings', repairMappings);

		// Submissions page functionality
		initSubmissionsPage();

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

		// Field actions (edit/delete) - Only for the form builder, not the edit modal
		$(document).on('click', '#form-fields-container .edit-field-btn', function (e) {
			e.stopPropagation();
			const index = $(this).closest('.aicrmform-field-item').data('index');
			openFieldEditor(index);
		});

		$(document).on('click', '#form-fields-container .delete-field-btn', function (e) {
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
	 * Initialize theme styling toggle functionality.
	 */
	function initThemeStylingToggle() {
		const $toggle = $('#use-theme-styling');
		const $cardBody = $toggle.closest('.aicrmform-card-body');

		if (!$toggle.length) return;

		// Handle toggle change - add class to parent to hide options via CSS
		$toggle.on('change', function () {
			if ($(this).is(':checked')) {
				$cardBody.addClass('aicrmform-theme-styling-active');
			} else {
				$cardBody.removeClass('aicrmform-theme-styling-active');
			}
			updateLivePreview();
		});

		// Also handle in edit form modal
		$(document).on('change', '#edit-use-theme-styling', function () {
			const $tabContent = $(this).closest('.aicrmform-edit-tab-content');
			const $note = $tabContent.find('#edit-theme-styling-note');
			const $styleOptions = $tabContent.find('#edit-style-options');
			if ($(this).is(':checked')) {
				$tabContent.addClass('aicrmform-theme-styling-active');
				$note.show();
				$styleOptions.hide();
			} else {
				$tabContent.removeClass('aicrmform-theme-styling-active');
				$note.hide();
				$styleOptions.show();
			}
		});
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

	// Track imported plugins for the disable dialog
	let pendingImportDialogTimeout = null;
	let importedPlugins = {}; // Track all plugins imported in this session

	/**
	 * Initialize Form Import.
	 */
	function initFormImport() {
		// Open import modal (works on both Forms page and Form Builder page)
		$('#open-import-modal, #import-form-btn').on('click', function () {
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

		// Import All button click (per plugin)
		$(document).on('click', '.aicrmform-import-all-btn', function () {
			const $btn = $(this);
			const plugin = $btn.data('plugin');
			const pluginName = $btn.data('plugin-name');
			const formCount = $btn.data('form-count');
			const useSameShortcode = $('.use-same-shortcode-all[data-plugin="' + plugin + '"]').is(
				':checked'
			);

			// Confirm before importing all
			showConfirm(
				'Import All Forms?',
				'Are you sure you want to import all ' +
					formCount +
					' forms from ' +
					pluginName +
					'? This will create ' +
					formCount +
					' new forms in AI CRM Form.',
				function () {
					importAllForms(plugin, pluginName, useSameShortcode, $btn);
				}
			);
		});

		// Import All from All Plugins button click
		$(document).on('click', '.aicrmform-import-all-global-btn', function () {
			const $btn = $(this);
			const totalForms = $btn.data('total-forms');

			// Confirm before importing all
			showConfirm(
				'Import All Forms from All Plugins?',
				'Are you sure you want to import all ' +
					totalForms +
					' forms from all plugins? This will create ' +
					totalForms +
					' new forms in AI CRM Form.\n\nNote: Each plugin must have a CRM Form ID configured.',
				function () {
					importAllFormsFromAllPlugins($btn);
				}
			);
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
		let totalForms = 0;
		let activePlugins = [];

		// Get default CRM Form ID from settings or form builder page if available
		const defaultCrmFormId = $('#crm-form-id').val() || aicrmformAdmin.defaultCrmFormId || '';

		// First pass: count total forms and collect active plugins
		for (const [key, source] of Object.entries(sources)) {
			if (source.active && source.forms && source.forms.length > 0) {
				totalForms += source.forms.length;
				activePlugins.push({ key: key, name: source.name, formCount: source.forms.length });
			}
		}

		// Add global "Import All from All Plugins" section if multiple plugins or many forms
		if (activePlugins.length > 0 && totalForms > 0) {
			// Build plugin summary list
			const pluginSummary = activePlugins
				.map(function (p) {
					return p.name + ' (' + p.formCount + ')';
				})
				.join(' • ');

			html +=
				'<div class="aicrmform-import-all-global" style="margin-bottom: 24px; padding: 20px; background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); border-radius: 10px; color: #fff; box-shadow: 0 4px 15px rgba(30, 58, 95, 0.3);">';
			html +=
				'<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">';
			html += '<div style="flex: 1; min-width: 200px;">';
			html +=
				'<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">';
			html +=
				'<span class="dashicons dashicons-database-import" style="font-size: 24px; width: 24px; height: 24px; background: rgba(255,255,255,0.2); padding: 8px; border-radius: 8px;"></span>';
			html +=
				'<h4 style="margin: 0; font-size: 18px; font-weight: 600; color: #fff;">Import All Forms</h4>';
			html += '</div>';
			html +=
				'<p style="margin: 0 0 6px 0; font-size: 14px; color: rgba(255,255,255,0.95);"><strong>' +
				totalForms +
				' forms</strong> available from <strong>' +
				activePlugins.length +
				' plugin' +
				(activePlugins.length > 1 ? 's' : '') +
				'</strong></p>';
			html +=
				'<p style="margin: 0; font-size: 12px; color: rgba(255,255,255,0.7);">' +
				escapeHtml(pluginSummary) +
				'</p>';
			html += '</div>';
			html +=
				'<button type="button" class="button aicrmform-import-all-global-btn" style="background: #fff; color: #1e3a5f; border: none; font-weight: 600; padding: 12px 24px; font-size: 14px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: transform 0.2s, box-shadow 0.2s;" ';
			html += 'data-total-forms="' + totalForms + '">';
			html +=
				'<span class="dashicons dashicons-download" style="margin-right: 6px; margin-top: 3px;"></span>Import All ' +
				totalForms +
				' Forms';
			html += '</button>';
			html += '</div>';
			html += '</div>';
		}

		for (const [key, source] of Object.entries(sources)) {
			if (source.active && source.forms && source.forms.length > 0) {
				hasActiveSources = true;
				html += '<div class="aicrmform-import-source">';
				html +=
					'<h4><span class="dashicons dashicons-admin-plugins"></span> ' +
					escapeHtml(source.name) +
					'</h4>';

				// Add CRM Form ID input at the top of each source
				html +=
					'<div class="aicrmform-import-crm-id-row" style="margin-bottom: 16px; padding: 12px; background: #f0f6fc; border-radius: 6px; border: 1px solid #c3d9ed;">';
				html +=
					'<label style="display: block; margin-bottom: 6px; font-weight: 500; color: #1e3a5f;">';
				html +=
					'<span class="dashicons dashicons-cloud" style="color: #2271b1; margin-right: 4px;"></span>';
				html += 'CRM Form ID <span style="color: #d63638;">*</span></label>';
				html +=
					'<input type="text" class="aicrmform-import-crm-form-id aicrmform-input" data-plugin="' +
					escapeHtml(key) +
					'" ';
				html += 'value="' + escapeHtml(defaultCrmFormId) + '" ';
				html += 'placeholder="FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" ';
				html += 'style="width: 100%; margin-bottom: 4px;">';
				html +=
					'<p style="margin: 0; font-size: 12px; color: #50575e;">Required for CRM integration. Get this from your CRM dashboard.</p>';
				html += '</div>';

				// Add Import All button
				html +=
					'<div class="aicrmform-import-all-row" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">';
				html += '<div style="display: flex; align-items: center; gap: 12px;">';
				html += '<label class="aicrmform-import-same-shortcode" style="margin: 0;">';
				html +=
					'<input type="checkbox" class="use-same-shortcode-all" data-plugin="' +
					escapeHtml(key) +
					'" checked>';
				html += '<span>Use same shortcodes for all</span>';
				html += '</label>';
				html += '</div>';
				html +=
					'<button type="button" class="button button-primary aicrmform-import-all-btn" ';
				html += 'data-plugin="' + escapeHtml(key) + '" ';
				html += 'data-plugin-name="' + escapeHtml(source.name) + '" ';
				html += 'data-form-count="' + source.forms.length + '">';
				html +=
					'<span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Import All (' +
					source.forms.length +
					' forms)';
				html += '</button>';
				html += '</div>';

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
		// Get the CRM Form ID from the input field
		const crmFormId = $('.aicrmform-import-crm-form-id[data-plugin="' + plugin + '"]')
			.val()
			.trim();

		// Validate CRM Form ID
		if (!crmFormId) {
			showToast('CRM Form ID is required to import forms.', 'error');
			$('.aicrmform-import-crm-form-id[data-plugin="' + plugin + '"]').focus();
			return;
		}

		// Validate format
		const pattern =
			/^FormConfigID-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;
		if (!pattern.test(crmFormId)) {
			showToast(
				'Invalid CRM Form ID format. Expected: FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
				'error'
			);
			$('.aicrmform-import-crm-form-id[data-plugin="' + plugin + '"]').focus();
			return;
		}

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
				crm_form_id: crmFormId,
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

					// Get the source plugin name
					const pluginNames = {
						cf7: 'Contact Form 7',
						gravity: 'Gravity Forms',
						wpforms: 'WPForms',
					};
					const pluginDisplayName = pluginNames[plugin] || plugin;

					// If a confirm dialog is currently showing, close it (we'll show a new one with all plugins)
					$('#aicrmform-confirm-modal').hide();

					// Track this plugin as imported
					importedPlugins[plugin] = {
						key: plugin,
						displayName: pluginDisplayName,
						formId: response.form_id,
						useSameShortcode: useSameShortcode,
					};

					// Clear any pending dialog timeout - we'll reset it
					if (pendingImportDialogTimeout) {
						clearTimeout(pendingImportDialogTimeout);
						pendingImportDialogTimeout = null;
					}

					// Offer to disable the source plugin(s) (delayed to allow for multiple imports)
					// Each new import resets the timer, so dialog shows 2s after LAST import
					pendingImportDialogTimeout = setTimeout(function () {
						showImportCompleteDialog();
					}, 2000);
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
	 * Import all forms from a plugin.
	 *
	 * @param {string} plugin Plugin key.
	 * @param {string} pluginName Plugin display name.
	 * @param {boolean} useSameShortcode Whether to use same shortcodes.
	 * @param {jQuery} $btn The button element.
	 */
	function importAllForms(plugin, pluginName, useSameShortcode, $btn) {
		// Get the CRM Form ID from the input field
		const crmFormId = $('.aicrmform-import-crm-form-id[data-plugin="' + plugin + '"]')
			.val()
			.trim();

		// Validate CRM Form ID
		if (!crmFormId) {
			showToast('CRM Form ID is required to import forms.', 'error');
			$('.aicrmform-import-crm-form-id[data-plugin="' + plugin + '"]').focus();
			return;
		}

		// Validate format
		const pattern =
			/^FormConfigID-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;
		if (!pattern.test(crmFormId)) {
			showToast(
				'Invalid CRM Form ID format. Expected: FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
				'error'
			);
			$('.aicrmform-import-crm-form-id[data-plugin="' + plugin + '"]').focus();
			return;
		}

		// Collect all form IDs for this plugin
		const $formButtons = $('.aicrmform-import-form-btn[data-plugin="' + plugin + '"]');
		const forms = [];
		$formButtons.each(function () {
			const $formBtn = $(this);
			// Skip already imported forms
			if ($formBtn.hasClass('button-primary')) {
				return;
			}
			forms.push({
				id: $formBtn.data('form-id'),
				title: $formBtn.data('form-title'),
				$btn: $formBtn,
			});
		});

		if (forms.length === 0) {
			showToast('All forms from ' + pluginName + ' have already been imported!', 'info');
			return;
		}

		const originalText = $btn.html();
		const $source = $btn.closest('.aicrmform-import-source');

		// Add progress indicator
		$source.find('.aicrmform-plugin-progress').remove();
		$btn.after(
			'<div class="aicrmform-plugin-progress" style="margin-top: 8px; margin-bottom: 8px;">' +
				'<div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 12px; color: #666;">' +
				'<span class="progress-text">Starting...</span>' +
				'<span class="progress-count">0 / ' +
				forms.length +
				'</span>' +
				'</div>' +
				'<div style="background: #e0e0e0; border-radius: 3px; height: 6px; overflow: hidden;">' +
				'<div class="progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s ease;"></div>' +
				'</div>' +
				'</div>'
		);

		$btn.prop('disabled', true).html(
			'<span class="spinner is-active" style="float: none; margin: 0;"></span> Importing...'
		);

		// Disable all individual import buttons
		$formButtons.prop('disabled', true);

		let completed = 0;
		let successCount = 0;
		let errorCount = 0;
		const totalForms = forms.length;

		// Update progress UI
		function updatePluginProgress(current, formTitle) {
			const percent = Math.round((current / totalForms) * 100);
			$source.find('.aicrmform-plugin-progress .progress-bar').css('width', percent + '%');
			$source
				.find('.aicrmform-plugin-progress .progress-count')
				.text(current + ' / ' + totalForms);
			$source
				.find('.aicrmform-plugin-progress .progress-text')
				.text('Importing: ' + formTitle);
		}

		// Import forms sequentially to avoid overwhelming the server
		function importNext(index) {
			if (index >= forms.length) {
				// Update progress to complete
				$source.find('.aicrmform-plugin-progress .progress-bar').css('width', '100%');
				$source.find('.aicrmform-plugin-progress .progress-text').text('Complete!');
				$source
					.find('.aicrmform-plugin-progress .progress-count')
					.text(successCount + ' imported');

				// All done
				$btn.html(
					'<span class="dashicons dashicons-yes"></span> Imported ' +
						successCount +
						' forms'
				);

				// Track plugin as imported
				const pluginNames = {
					cf7: 'Contact Form 7',
					gravity: 'Gravity Forms',
					wpforms: 'WPForms',
				};
				importedPlugins[plugin] = {
					key: plugin,
					displayName: pluginNames[plugin] || pluginName,
					formId: 'multiple',
					useSameShortcode: useSameShortcode,
				};

				// Close modal and show deactivate dialog
				if (pendingImportDialogTimeout) {
					clearTimeout(pendingImportDialogTimeout);
				}
				pendingImportDialogTimeout = setTimeout(function () {
					// Hide import modal first
					$('#import-form-modal').hide();

					if (errorCount > 0) {
						showToast(
							'Imported ' +
								successCount +
								' of ' +
								totalForms +
								' forms. ' +
								errorCount +
								' failed.',
							'warning'
						);
					} else {
						showToast(
							'Successfully imported all ' +
								successCount +
								' forms from ' +
								pluginName +
								'!',
							'success'
						);
					}

					showImportCompleteDialog();
				}, 1000);

				return;
			}

			const form = forms[index];
			const $formBtn = form.$btn;

			// Update progress
			updatePluginProgress(index + 1, form.title);

			$formBtn.html(
				'<span class="spinner is-active" style="float: none; margin: 0;"></span>'
			);

			// Update main button progress
			$btn.html(
				'<span class="spinner is-active" style="float: none; margin: 0;"></span> ' +
					(index + 1) +
					'/' +
					totalForms
			);

			$.ajax({
				url: aicrmformAdmin.restUrl + 'import',
				method: 'POST',
				headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
				contentType: 'application/json',
				data: JSON.stringify({
					plugin: plugin,
					form_id: form.id,
					use_same_shortcode: useSameShortcode,
					crm_form_id: crmFormId,
				}),
			})
				.done(function (response) {
					if (response.success) {
						successCount++;
						$formBtn
							.html('<span class="dashicons dashicons-yes"></span> Imported!')
							.addClass('button-primary');
					} else {
						errorCount++;
						$formBtn
							.html('<span class="dashicons dashicons-no"></span> Failed')
							.addClass('button-link-delete');
					}
				})
				.fail(function () {
					errorCount++;
					$formBtn
						.html('<span class="dashicons dashicons-no"></span> Failed')
						.addClass('button-link-delete');
				})
				.always(function () {
					completed++;
					// Import next form
					importNext(index + 1);
				});
		}

		// Start importing
		importNext(0);
	}

	/**
	 * Import all forms from all plugins.
	 *
	 * @param {jQuery} $btn The global import button element.
	 */
	function importAllFormsFromAllPlugins($btn) {
		const pattern =
			/^FormConfigID-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;

		// Collect all plugins and their forms with CRM Form IDs
		const allPluginForms = [];
		const pluginsWithoutCrmId = [];
		const allAvailablePlugins = {}; // Track all plugins for deactivation offer

		$('.aicrmform-import-source').each(function () {
			const $source = $(this);
			const $crmInput = $source.find('.aicrmform-import-crm-form-id');
			const plugin = $crmInput.data('plugin');
			const crmFormId = $crmInput.val().trim();
			const useSameShortcode = $source.find('.use-same-shortcode-all').is(':checked');

			// Get plugin name from header
			const pluginName = $source.find('h4').text().trim();

			// Track this plugin as available for deactivation
			const pluginDisplayNames = {
				cf7: 'Contact Form 7',
				gravity: 'Gravity Forms',
				wpforms: 'WPForms',
			};
			allAvailablePlugins[plugin] = {
				key: plugin,
				displayName: pluginDisplayNames[plugin] || pluginName,
				formId: 'multiple',
				useSameShortcode: useSameShortcode,
			};

			// Validate CRM Form ID
			if (!crmFormId || !pattern.test(crmFormId)) {
				pluginsWithoutCrmId.push(pluginName);
				return;
			}

			// Get all form buttons for this plugin
			$source.find('.aicrmform-import-form-btn').each(function () {
				const $formBtn = $(this);
				// Skip already imported forms
				if ($formBtn.hasClass('button-primary')) {
					return;
				}
				allPluginForms.push({
					plugin: plugin,
					pluginName: pluginName,
					formId: $formBtn.data('form-id'),
					formTitle: $formBtn.data('form-title'),
					crmFormId: crmFormId,
					useSameShortcode: useSameShortcode,
					$btn: $formBtn,
				});
			});
		});

		// Check if any plugins are missing CRM Form ID
		if (pluginsWithoutCrmId.length > 0) {
			showToast(
				'Missing or invalid CRM Form ID for: ' +
					pluginsWithoutCrmId.join(', ') +
					'. Please configure CRM Form IDs for all plugins.',
				'error'
			);
			return;
		}

		if (allPluginForms.length === 0) {
			showToast('All forms have already been imported!', 'info');
			return;
		}

		const originalText = $btn.html();
		const $globalSection = $btn.closest('.aicrmform-import-all-global');

		// Add progress indicator below the button
		$globalSection.find('.aicrmform-import-progress').remove();
		$globalSection.append(
			'<div class="aicrmform-import-progress" style="margin-top: 12px;">' +
				'<div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; color: rgba(255,255,255,0.9);">' +
				'<span class="progress-text">Starting import...</span>' +
				'<span class="progress-count">0 / ' +
				allPluginForms.length +
				'</span>' +
				'</div>' +
				'<div style="background: rgba(255,255,255,0.3); border-radius: 4px; height: 8px; overflow: hidden;">' +
				'<div class="progress-bar" style="background: #fff; height: 100%; width: 0%; transition: width 0.3s ease;"></div>' +
				'</div>' +
				'</div>'
		);

		$btn.prop('disabled', true).html(
			'<span class="spinner is-active" style="float: none; margin: 0; vertical-align: middle;"></span> Importing...'
		);

		// Disable all import buttons
		$('.aicrmform-import-form-btn, .aicrmform-import-all-btn').prop('disabled', true);

		let successCount = 0;
		let errorCount = 0;
		const totalForms = allPluginForms.length;

		// Update progress UI
		function updateProgress(current, formTitle) {
			const percent = Math.round((current / totalForms) * 100);
			$globalSection.find('.progress-bar').css('width', percent + '%');
			$globalSection.find('.progress-count').text(current + ' / ' + totalForms);
			$globalSection.find('.progress-text').text('Importing: ' + formTitle);
		}

		// Import forms sequentially
		function importNext(index) {
			if (index >= allPluginForms.length) {
				// All done - update progress to complete
				$globalSection.find('.progress-bar').css('width', '100%');
				$globalSection.find('.progress-text').text('Import complete!');
				$globalSection.find('.progress-count').text(successCount + ' imported');

				$btn.html(
					'<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span> Imported ' +
						successCount +
						' forms'
				);

				// Track ALL available plugins for deactivation (not just newly imported)
				for (const pluginKey in allAvailablePlugins) {
					importedPlugins[pluginKey] = allAvailablePlugins[pluginKey];
				}

				// Close the import modal and show deactivate dialog
				if (pendingImportDialogTimeout) {
					clearTimeout(pendingImportDialogTimeout);
				}
				pendingImportDialogTimeout = setTimeout(function () {
					// Hide import modal first
					$('#import-form-modal').hide();

					if (errorCount > 0) {
						showToast(
							'Imported ' +
								successCount +
								' of ' +
								totalForms +
								' forms. ' +
								errorCount +
								' failed.',
							'warning'
						);
					} else {
						showToast(
							'Successfully imported all ' + successCount + ' forms!',
							'success'
						);
					}

					// Show the deactivate plugins dialog
					showImportCompleteDialog();
				}, 1000);

				return;
			}

			const formData = allPluginForms[index];
			const $formBtn = formData.$btn;

			// Update progress UI
			updateProgress(index + 1, formData.formTitle);

			$formBtn.html(
				'<span class="spinner is-active" style="float: none; margin: 0;"></span>'
			);

			// Update main button progress
			$btn.html(
				'<span class="spinner is-active" style="float: none; margin: 0; vertical-align: middle;"></span> ' +
					(index + 1) +
					'/' +
					totalForms
			);

			$.ajax({
				url: aicrmformAdmin.restUrl + 'import',
				method: 'POST',
				headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
				contentType: 'application/json',
				data: JSON.stringify({
					plugin: formData.plugin,
					form_id: formData.formId,
					use_same_shortcode: formData.useSameShortcode,
					crm_form_id: formData.crmFormId,
				}),
			})
				.done(function (response) {
					if (response.success) {
						successCount++;
						$formBtn
							.html('<span class="dashicons dashicons-yes"></span> Imported!')
							.addClass('button-primary');
					} else {
						errorCount++;
						$formBtn
							.html('<span class="dashicons dashicons-no"></span> Failed')
							.addClass('button-link-delete');
					}
				})
				.fail(function () {
					errorCount++;
					$formBtn
						.html('<span class="dashicons dashicons-no"></span> Failed')
						.addClass('button-link-delete');
				})
				.always(function () {
					// Import next form
					importNext(index + 1);
				});
		}

		// Start importing
		importNext(0);
	}

	/**
	 * Show the import complete dialog with option to disable plugin(s).
	 */
	function showImportCompleteDialog() {
		pendingImportDialogTimeout = null;

		// Get list of imported plugins
		const pluginKeys = Object.keys(importedPlugins);

		if (pluginKeys.length === 0) {
			return;
		}

		// Build list of plugin names
		const pluginDisplayNames = pluginKeys.map(function (key) {
			return importedPlugins[key].displayName;
		});

		let title, confirmMsg;

		if (pluginKeys.length === 1) {
			// Single plugin imported
			const pluginInfo = importedPlugins[pluginKeys[0]];
			title = 'Disable ' + pluginInfo.displayName + '?';
			confirmMsg = 'Would you like to disable ' + pluginInfo.displayName + '?';
			if (pluginInfo.useSameShortcode) {
				confirmMsg +=
					' Your existing shortcodes will continue to work with the imported form.';
			} else {
				confirmMsg +=
					' You may need to update your shortcodes to [ai_crm_form id="' +
					pluginInfo.formId +
					'"]';
			}
		} else {
			// Multiple plugins imported
			title = 'Disable Source Plugins?';
			confirmMsg =
				'Would you like to disable the following plugins?\n\n• ' +
				pluginDisplayNames.join('\n• ') +
				'\n\nYour existing shortcodes will continue to work with the imported forms.';
		}

		// Store the plugins to deactivate (capture in closure)
		const pluginsToDeactivate = Object.assign({}, importedPlugins);

		showConfirm(
			title,
			confirmMsg,
			function () {
				// User clicked Yes - clear tracking and deactivate all imported plugins
				importedPlugins = {};
				deactivateMultiplePlugins(pluginsToDeactivate);
			},
			function () {
				// User clicked No - clear tracking and go to forms page
				importedPlugins = {};
				$('#import-form-modal').hide();
				window.location.href = aicrmformAdmin.adminUrl + '?page=ai-crm-form-forms';
			}
		);
	}

	/**
	 * Deactivate multiple plugins.
	 *
	 * @param {Object} plugins Object of plugin info to deactivate.
	 */
	function deactivateMultiplePlugins(plugins) {
		const pluginKeys = Object.keys(plugins);
		const pluginNames = pluginKeys.map(function (key) {
			return plugins[key].displayName;
		});

		showToast('Deactivating ' + pluginNames.join(' and ') + '...', 'info');

		// Deactivate plugins in parallel
		let completed = 0;
		let errors = [];

		pluginKeys.forEach(function (pluginKey) {
			$.ajax({
				url: aicrmformAdmin.restUrl + 'deactivate-plugin',
				method: 'POST',
				headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
				contentType: 'application/json',
				data: JSON.stringify({ plugin: pluginKey }),
			})
				.done(function (response) {
					if (!response.success) {
						errors.push(plugins[pluginKey].displayName);
					}
				})
				.fail(function () {
					errors.push(plugins[pluginKey].displayName);
				})
				.always(function () {
					completed++;
					if (completed === pluginKeys.length) {
						// All requests completed
						if (errors.length === 0) {
							showToast(
								pluginNames.join(' and ') + ' deactivated successfully!',
								'success'
							);
						} else {
							showToast('Failed to deactivate: ' + errors.join(', '), 'error');
						}
						$('#import-form-modal').hide();
						window.location.href = aicrmformAdmin.adminUrl + '?page=ai-crm-form-forms';
					}
				});
		});
	}

	/**
	 * Deactivate a plugin.
	 *
	 * @param {string} pluginKey Plugin key (e.g., 'cf7', 'gravity').
	 * @param {string} pluginDisplayName Human-readable plugin name.
	 */
	function deactivatePlugin(pluginKey, pluginDisplayName) {
		pluginDisplayName = pluginDisplayName || pluginKey;
		showToast('Deactivating ' + pluginDisplayName + '...', 'info');

		$.ajax({
			url: aicrmformAdmin.restUrl + 'deactivate-plugin',
			method: 'POST',
			headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
			contentType: 'application/json',
			data: JSON.stringify({ plugin: pluginKey }),
		})
			.done(function (response) {
				if (response.success) {
					showToast(pluginDisplayName + ' has been deactivated!', 'success');
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
	 * Field presets configuration - All CRM mapped fields.
	 */
	const fieldPresets = {
		// Contact Fields
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
		additional_emails: {
			label: 'Additional Emails',
			name: 'additional_emails',
			type: 'email',
			placeholder: 'Enter additional email',
			crm_mapping: 'additional_emails',
			required: false,
		},
		tags: {
			label: 'Tags',
			name: 'tags',
			type: 'text',
			placeholder: 'Enter tags (comma separated)',
			crm_mapping: 'tags',
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

		// Address Fields
		primary_address_line1: {
			label: 'Address Line 1',
			name: 'primary_address_line1',
			type: 'text',
			placeholder: 'Street address',
			crm_mapping: 'primary_address_line1',
			required: false,
		},
		primary_address_line2: {
			label: 'Address Line 2',
			name: 'primary_address_line2',
			type: 'text',
			placeholder: 'Apt, suite, etc.',
			crm_mapping: 'primary_address_line2',
			required: false,
		},
		primary_address_city: {
			label: 'City',
			name: 'primary_address_city',
			type: 'text',
			placeholder: 'City',
			crm_mapping: 'primary_address_city',
			required: false,
		},
		primary_address_state: {
			label: 'State',
			name: 'primary_address_state',
			type: 'text',
			placeholder: 'State',
			crm_mapping: 'primary_address_state',
			required: false,
		},
		primary_address_postal: {
			label: 'Postal Code',
			name: 'primary_address_postal',
			type: 'text',
			placeholder: 'ZIP / Postal code',
			crm_mapping: 'primary_address_postal',
			required: false,
		},
		primary_address_country: {
			label: 'Country',
			name: 'primary_address_country',
			type: 'text',
			placeholder: 'Country',
			crm_mapping: 'primary_address_country',
			required: false,
		},

		// Company Fields
		company_name: {
			label: 'Company Name',
			name: 'company_name',
			type: 'text',
			placeholder: 'Enter your company name',
			crm_mapping: 'company_name',
			required: false,
		},
		company_website: {
			label: 'Company Website',
			name: 'company_website',
			type: 'url',
			placeholder: 'https://example.com',
			crm_mapping: 'company_website',
			required: false,
		},
		company_phone: {
			label: 'Company Phone',
			name: 'company_phone',
			type: 'tel',
			placeholder: 'Company phone number',
			crm_mapping: 'company_phone',
			required: false,
		},
		company_address_line1: {
			label: 'Company Address',
			name: 'company_address_line1',
			type: 'text',
			placeholder: 'Company street address',
			crm_mapping: 'company_address_line1',
			required: false,
		},
		company_address_city: {
			label: 'Company City',
			name: 'company_address_city',
			type: 'text',
			placeholder: 'Company city',
			crm_mapping: 'company_address_city',
			required: false,
		},
		company_address_state: {
			label: 'Company State',
			name: 'company_address_state',
			type: 'text',
			placeholder: 'Company state',
			crm_mapping: 'company_address_state',
			required: false,
		},
		company_address_postal: {
			label: 'Company Postal Code',
			name: 'company_address_postal',
			type: 'text',
			placeholder: 'Company postal code',
			crm_mapping: 'company_address_postal',
			required: false,
		},
		company_address_country: {
			label: 'Company Country',
			name: 'company_address_country',
			type: 'text',
			placeholder: 'Company country',
			crm_mapping: 'company_address_country',
			required: false,
		},
		company_linkedin_url: {
			label: 'LinkedIn URL',
			name: 'company_linkedin_url',
			type: 'url',
			placeholder: 'https://linkedin.com/company/...',
			crm_mapping: 'company_linkedin_url',
			required: false,
		},
		company_facebook_url: {
			label: 'Facebook URL',
			name: 'company_facebook_url',
			type: 'url',
			placeholder: 'https://facebook.com/...',
			crm_mapping: 'company_facebook_url',
			required: false,
		},
		company_instagram_url: {
			label: 'Instagram URL',
			name: 'company_instagram_url',
			type: 'url',
			placeholder: 'https://instagram.com/...',
			crm_mapping: 'company_instagram_url',
			required: false,
		},
		company_twitter_url: {
			label: 'Twitter URL',
			name: 'company_twitter_url',
			type: 'url',
			placeholder: 'https://twitter.com/...',
			crm_mapping: 'company_twitter_url',
			required: false,
		},

		// Lead & Source Fields
		source_name: {
			label: 'How did you hear about us?',
			name: 'source_name',
			type: 'select',
			placeholder: '',
			crm_mapping: 'source_name',
			required: false,
			options: ['Google', 'Social Media', 'Friend/Referral', 'Advertisement', 'Other'],
		},
		original_source: {
			label: 'Original Source',
			name: 'original_source',
			type: 'text',
			placeholder: 'Original source',
			crm_mapping: 'original_source',
			required: false,
		},
		lead_score: {
			label: 'Lead Score',
			name: 'lead_score',
			type: 'number',
			placeholder: 'Lead score',
			crm_mapping: 'lead_score',
			required: false,
		},
		lead_quality: {
			label: 'Lead Quality',
			name: 'lead_quality',
			type: 'select',
			placeholder: '',
			crm_mapping: 'lead_quality',
			required: false,
			options: ['Hot', 'Warm', 'Cold'],
		},

		// UTM Fields
		utm_source: {
			label: 'UTM Source',
			name: 'utm_source',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'utm_source',
			required: false,
		},
		utm_medium: {
			label: 'UTM Medium',
			name: 'utm_medium',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'utm_medium',
			required: false,
		},
		utm_campaign: {
			label: 'UTM Campaign',
			name: 'utm_campaign',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'utm_campaign',
			required: false,
		},
		utm_term: {
			label: 'UTM Term',
			name: 'utm_term',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'utm_term',
			required: false,
		},
		utm_content: {
			label: 'UTM Content',
			name: 'utm_content',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'utm_content',
			required: false,
		},
		gclid: {
			label: 'Google Click ID',
			name: 'gclid',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'gclid',
			required: false,
		},
		fbclid: {
			label: 'Facebook Click ID',
			name: 'fbclid',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'fbclid',
			required: false,
		},
		msclkid: {
			label: 'Microsoft Click ID',
			name: 'msclkid',
			type: 'hidden',
			placeholder: '',
			crm_mapping: 'msclkid',
			required: false,
		},

		// Consent Fields
		marketing_email_consent_status: {
			label: 'Email Marketing Consent',
			name: 'marketing_email_consent_status',
			type: 'checkbox',
			placeholder: '',
			crm_mapping: 'marketing_email_consent_status',
			required: false,
			options: ['I agree to receive marketing emails'],
		},
		sms_consent_status: {
			label: 'SMS Consent',
			name: 'sms_consent_status',
			type: 'checkbox',
			placeholder: '',
			crm_mapping: 'sms_consent_status',
			required: false,
			options: ['I agree to receive SMS messages'],
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

			// Convert FieldID to dropdown key if needed
			const crmMappingKey = getCrmMappingKey(field.crm_mapping);
			$('#field-crm-mapping').val(crmMappingKey);

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

		// Detect duplicate CRM mappings
		const mappingCounts = {};
		formFields.forEach((field) => {
			if (field.crm_mapping) {
				const key = field.crm_mapping.toLowerCase();
				mappingCounts[key] = (mappingCounts[key] || 0) + 1;
			}
		});

		let html = '';
		formFields.forEach((field, index) => {
			const reqBadge = field.required
				? '<span class="aicrmform-field-badge required">Required</span>'
				: '';
			const crmFriendlyName = getCrmFriendlyName(field.crm_mapping);

			// Check for duplicate mapping
			const isDuplicate =
				field.crm_mapping && mappingCounts[field.crm_mapping.toLowerCase()] > 1;
			const duplicateClass = isDuplicate ? ' duplicate' : '';
			const duplicateTitle = isDuplicate
				? ' title="Warning: This CRM field is mapped to multiple form fields"'
				: '';

			const crmBadge = crmFriendlyName
				? `<span class="aicrmform-field-badge crm${duplicateClass}"${duplicateTitle}>${escapeHtml(crmFriendlyName)}${isDuplicate ? ' <span class="dashicons dashicons-warning"></span>' : ''}</span>`
				: '';

			html += `
				<div class="aicrmform-field-item${isDuplicate ? ' has-warning' : ''}" data-index="${index}" draggable="true">
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
				use_theme_styling: $('#use-theme-styling').is(':checked'),
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
	let confirmCancelCallback = null;

	function showConfirm(title, message, callback, cancelCallback) {
		$('#aicrmform-confirm-title').text(title);
		$('#aicrmform-confirm-message').text(message);
		confirmCallback = callback;
		confirmCancelCallback = cancelCallback || null;
		$('#aicrmform-confirm-modal').show();
	}

	function hideConfirmModal() {
		$('#aicrmform-confirm-modal').hide();
		// Call cancel callback if provided
		if (confirmCancelCallback) {
			confirmCancelCallback();
		} else {
			// Default behavior for import dialogs: redirect to forms page
			if (Object.keys(importedPlugins).length > 0) {
				importedPlugins = {};
				$('#import-form-modal').hide();
				window.location.href = aicrmformAdmin.adminUrl + '?page=ai-crm-form-forms';
			}
		}
		confirmCallback = null;
		confirmCancelCallback = null;
	}

	function executeConfirm() {
		const callback = confirmCallback;
		$('#aicrmform-confirm-modal').hide();
		confirmCallback = null;
		confirmCancelCallback = null;
		// Execute the callback
		if (callback) {
			callback();
		}
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
	 * Repair form field mappings.
	 */
	function repairMappings(e) {
		e.preventDefault();
		e.stopPropagation();

		const $btn = $(this);
		const formId = $btn.data('form-id');

		if (!formId) {
			console.error('AI CRM Form: No form ID found for repair button');
			showToast('Error: Form ID not found', 'error');
			return;
		}

		const $card = $btn.closest('.aicrmform-form-card-pro');
		const formName = $card.find('h3').text() || 'this form';

		// Show confirmation with AI option
		showRepairConfirm(
			'Repair Field Mappings',
			'This will regenerate CRM field mappings for "' +
				formName +
				'".\n\nWould you like to use AI to help with ambiguous mappings?',
			formId
		);
	}

	/**
	 * Show repair confirmation dialog with AI option.
	 */
	function showRepairConfirm(title, message, formId) {
		const hasAiKey = typeof aicrmformAdmin !== 'undefined' && aicrmformAdmin.hasAiKey;
		const aiDisabled = hasAiKey ? '' : 'disabled';
		const aiLabel = hasAiKey ? '' : '(AI not configured)';

		const modalHtml = `
			<div id="repair-confirm-modal" class="aicrmform-modal-overlay">
				<div class="aicrmform-modal aicrmform-modal-md">
					<div class="aicrmform-modal-header">
						<h3>${title}</h3>
						<button type="button" class="aicrmform-modal-close" data-action="cancel">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
					<div class="aicrmform-modal-body">
						<p>${message}</p>
						<div class="aicrmform-form-row" style="margin-top: 15px;">
							<label class="aicrmform-checkbox-label">
								<input type="checkbox" id="repair-use-ai" ${aiDisabled}>
								<span>Use AI for better mapping suggestions ${aiLabel}</span>
							</label>
						</div>
					</div>
					<div class="aicrmform-modal-footer">
						<button type="button" class="button" data-action="cancel">Cancel</button>
						<button type="button" class="button button-primary" data-action="repair">
							<span class="dashicons dashicons-admin-tools" style="vertical-align: middle; margin-right: 4px;"></span>
							Repair Mappings
						</button>
					</div>
				</div>
			</div>
		`;

		$('body').append(modalHtml);
		const $modal = $('#repair-confirm-modal');
		$modal.css('display', 'flex').hide().fadeIn(200);

		// Handle cancel
		$modal.find('[data-action="cancel"], .aicrmform-modal-close').on('click', function () {
			$modal.fadeOut(200, function () {
				$(this).remove();
			});
		});

		// Click outside to close
		$modal.on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) {
				$modal.fadeOut(200, function () {
					$(this).remove();
				});
			}
		});

		// Handle repair
		$modal.find('[data-action="repair"]').on('click', function () {
			const useAi = $('#repair-use-ai').is(':checked');
			const $repairBtn = $(this);

			$repairBtn
				.prop('disabled', true)
				.html(
					'<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Repairing...'
				);

			$.ajax({
				url: aicrmformAdmin.restUrl + 'forms/' + formId + '/repair-mappings',
				method: 'POST',
				headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
				contentType: 'application/json',
				data: JSON.stringify({ use_ai: useAi }),
			})
				.done(function (response) {
					$modal.fadeOut(200, function () {
						$(this).remove();
					});

					if (response.success) {
						let message = 'Field mappings repaired successfully!';

						if (response.mapping_changes && response.mapping_changes.length > 0) {
							message +=
								'\n\nMappings updated:\n• ' + response.mapping_changes.join('\n• ');
						}

						if (response.unmapped_fields && response.unmapped_fields.length > 0) {
							message +=
								'\n\nUnmapped fields (need manual mapping):\n• ' +
								response.unmapped_fields.join('\n• ');
						}

						showRepairResult('Repair Complete', message);
						showToast('Field mappings repaired!', 'success');
					} else {
						showToast(response.error || 'Failed to repair mappings.', 'error');
					}
				})
				.fail(function (xhr) {
					$modal.fadeOut(200, function () {
						$(this).remove();
					});
					const error = xhr.responseJSON?.error || 'Failed to repair mappings.';
					showToast(error, 'error');
				});
		});

		// Close on backdrop click
		$modal.on('click', function (e) {
			if ($(e.target).is('.aicrmform-modal')) {
				$modal.fadeOut(200, function () {
					$(this).remove();
				});
			}
		});
	}

	/**
	 * Show repair result dialog.
	 */
	function showRepairResult(title, message) {
		const modalHtml = `
			<div id="repair-result-modal" class="aicrmform-modal-overlay">
				<div class="aicrmform-modal aicrmform-modal-md">
					<div class="aicrmform-modal-header aicrmform-modal-header-success">
						<span class="dashicons dashicons-yes-alt"></span>
						<h3>${title}</h3>
					</div>
					<div class="aicrmform-modal-body">
						<pre style="white-space: pre-wrap; font-family: inherit; margin: 0; background: #f5f5f5; padding: 15px; border-radius: 6px; max-height: 300px; overflow-y: auto; font-size: 13px; line-height: 1.6;">${message}</pre>
					</div>
					<div class="aicrmform-modal-footer">
						<button type="button" class="button button-primary" data-action="ok">OK</button>
					</div>
				</div>
			</div>
		`;

		$('body').append(modalHtml);
		const $modal = $('#repair-result-modal');
		$modal.css('display', 'flex').hide().fadeIn(200);

		$modal.find('[data-action="ok"], .aicrmform-modal-close').on('click', function () {
			$modal.fadeOut(200, function () {
				$(this).remove();
			});
		});

		$modal.on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) {
				$modal.fadeOut(200, function () {
					$(this).remove();
				});
			}
		});
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
		const styles = form.form_config?.styles || {};
		const customCss = form.form_config?.custom_css || '';
		const useThemeStyling = styles.use_theme_styling || false;

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

		// Generate custom styles for this form preview
		if (!useThemeStyling) {
			html += '<style id="preview-form-styles">';
			html += generatePreviewFormStyles(form.id, styles, customCss);
			html += '</style>';
		}

		html += '<div class="aicrmform-preview-area" id="modal-form-preview"></div>';
		html += '</div>';
		html +=
			'<div class="aicrmform-modal-footer"><button type="button" class="button" onclick="jQuery(\'#form-preview-modal\').remove();">Close</button></div>';
		html += '</div></div>';

		const $modal = $(html);
		$('body').append($modal);

		if (form.form_config && form.form_config.fields) {
			// Build wrapper classes
			let wrapperClasses = 'aicrmform-wrapper aicrmform-wrapper-' + form.id;
			if (styles.label_position)
				wrapperClasses += ' aicrmform-labels-' + styles.label_position;
			if (styles.field_spacing)
				wrapperClasses += ' aicrmform-spacing-' + styles.field_spacing;
			if (styles.button_style) wrapperClasses += ' aicrmform-button-' + styles.button_style;
			if (styles.button_width === 'full') wrapperClasses += ' aicrmform-button-full';
			if (useThemeStyling) wrapperClasses += ' aicrmform-theme-styled';

			let previewHtml = '<div class="' + wrapperClasses + '">';

			// Add Google Font if specified
			if (styles.font_family && !useThemeStyling) {
				const fontSlug = styles.font_family.replace(/ /g, '+');
				previewHtml =
					'<link href="https://fonts.googleapis.com/css2?family=' +
					fontSlug +
					':wght@400;500;600;700&display=swap" rel="stylesheet">' +
					previewHtml;
			}

			previewHtml += '<form class="aicrmform-form">';
			form.form_config.fields.forEach(function (field) {
				previewHtml += renderPreviewFieldStatic(field);
			});
			previewHtml +=
				'<div class="aicrmform-field aicrmform-submit"><button type="button" class="aicrmform-button">' +
				escapeHtml(form.form_config.submit_button_text || 'Submit') +
				'</button></div>';
			previewHtml += '</form>';
			previewHtml += '</div>';
			$modal.find('#modal-form-preview').html(previewHtml);
		}

		$modal.on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) $modal.remove();
		});
	}

	/**
	 * Generate custom CSS styles for form preview (matches PHP generate_custom_styles)
	 */
	function generatePreviewFormStyles(formId, styles, customCss) {
		let css = '';
		const selector = '.aicrmform-wrapper-' + formId;

		// Font family
		if (styles.font_family) {
			css +=
				selector + ' { font-family: "' + styles.font_family + '", sans-serif !important; }';
			css +=
				selector +
				' *, ' +
				selector +
				' input, ' +
				selector +
				' select, ' +
				selector +
				' textarea, ' +
				selector +
				' button { font-family: inherit !important; }';
		}

		// Font size
		if (styles.font_size) {
			css += selector + ' { font-size: ' + styles.font_size + ' !important; }';
			css +=
				selector +
				' .aicrmform-field label { font-size: ' +
				styles.font_size +
				' !important; }';
			css +=
				selector +
				' .aicrmform-field input, ' +
				selector +
				' .aicrmform-field select, ' +
				selector +
				' .aicrmform-field textarea { font-size: ' +
				styles.font_size +
				' !important; }';
		}

		// Form width
		if (styles.form_width) {
			css += selector + ' .aicrmform-form { max-width: ' + styles.form_width + '; }';
		}

		// Background color
		if (styles.background_color && styles.background_color !== '#ffffff') {
			css +=
				selector +
				' .aicrmform-form { background-color: ' +
				styles.background_color +
				' !important; padding: 24px !important; border-radius: 8px !important; }';
		}

		// Text color
		if (styles.text_color && styles.text_color !== '#333333') {
			css += selector + ' { color: ' + styles.text_color + ' !important; }';
			css +=
				selector +
				' .aicrmform-field label { color: ' +
				styles.text_color +
				' !important; }';
		}

		// Border color
		if (styles.border_color && styles.border_color !== '#dddddd') {
			css +=
				selector +
				' .aicrmform-field input, ' +
				selector +
				' .aicrmform-field select, ' +
				selector +
				' .aicrmform-field textarea { border-color: ' +
				styles.border_color +
				' !important; }';
		}

		// Border radius
		if (styles.border_radius && styles.border_radius !== '4px') {
			css +=
				selector +
				' .aicrmform-field input, ' +
				selector +
				' .aicrmform-field select, ' +
				selector +
				' .aicrmform-field textarea, ' +
				selector +
				' .aicrmform-button { border-radius: ' +
				styles.border_radius +
				' !important; }';
		}

		// Primary/Button color
		if (styles.primary_color && styles.primary_color !== '#0073aa') {
			css +=
				selector +
				' .aicrmform-button { background-color: ' +
				styles.primary_color +
				' !important; background-image: none !important; border-color: ' +
				styles.primary_color +
				' !important; }';
			css +=
				selector +
				' .aicrmform-button:hover { background-color: ' +
				adjustBrightness(styles.primary_color, -20) +
				' !important; }';
		}

		// Append custom CSS
		if (customCss) {
			css += customCss.replace(/\.aicrmform-form/g, selector + ' .aicrmform-form');
		}

		return css;
	}

	/**
	 * Adjust hex color brightness (helper for button hover)
	 */
	function adjustBrightness(hex, steps) {
		hex = hex.replace('#', '');
		const r = Math.max(0, Math.min(255, parseInt(hex.substring(0, 2), 16) + steps));
		const g = Math.max(0, Math.min(255, parseInt(hex.substring(2, 4), 16) + steps));
		const b = Math.max(0, Math.min(255, parseInt(hex.substring(4, 6), 16) + steps));
		return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
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

	// Store edit form fields for the edit modal
	let editFormFields = [];
	let editingFormId = null;

	function showFormEditModal(form) {
		const styles = form.form_config?.styles || {};
		const customCss = form.form_config?.custom_css || '';

		// Initialize edit form fields from form config
		editFormFields = (form.form_config?.fields || []).map((f) => ({
			name: f.name || '',
			label: f.label || '',
			type: f.type || 'text',
			placeholder: f.placeholder || '',
			required: f.required || false,
			options: f.options || [],
			crm_mapping: f.field_id || f.crm_mapping || '',
		}));
		editingFormId = form.id;

		let html = '<div class="aicrmform-modal-overlay" id="form-edit-modal">';
		html += '<div class="aicrmform-modal aicrmform-modal-lg" style="max-width: 900px;">';
		html +=
			'<div class="aicrmform-modal-header"><h3>Edit: ' +
			escapeHtml(form.name) +
			'</h3><button type="button" class="aicrmform-modal-close" id="close-edit-modal"><span class="dashicons dashicons-no-alt"></span></button></div>';
		html += '<div class="aicrmform-modal-body" style="max-height: 70vh; overflow-y: auto;">';

		// Tabs for Basic Info, Fields, and Styling
		html += '<div class="aicrmform-edit-tabs">';
		html +=
			'<button type="button" class="aicrmform-edit-tab active" data-tab="basic">Basic Info</button>';
		html +=
			'<button type="button" class="aicrmform-edit-tab" data-tab="fields">Fields</button>';
		html +=
			'<button type="button" class="aicrmform-edit-tab" data-tab="styling">Styling</button>';
		html += '</div>';

		// Tab Content: Basic Info
		html += '<div class="aicrmform-edit-tab-content" id="edit-tab-basic">';
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
			'<div class="aicrmform-form-row"><label>CRM Form ID *</label><input type="text" id="edit-crm-form-id" class="aicrmform-input" value="' +
			escapeHtml(form.crm_form_id || '') +
			'"></div>';
		html +=
			'<div class="aicrmform-form-row" style="margin-top: 16px;"><label>Submit Button Text</label><input type="text" id="edit-submit-text" class="aicrmform-input" value="' +
			escapeHtml(form.form_config?.submit_button_text || 'Submit') +
			'"></div>';
		html +=
			'<div class="aicrmform-form-row" style="margin-top: 16px;"><label>Success Message</label><textarea id="edit-success-message" class="aicrmform-textarea" rows="2">' +
			escapeHtml(form.form_config?.success_message || 'Thank you for your submission!') +
			'</textarea></div>';
		html +=
			'<div class="aicrmform-form-row" style="margin-top: 16px;"><label>Error Message</label><textarea id="edit-error-message" class="aicrmform-textarea" rows="2">' +
			escapeHtml(
				form.form_config?.error_message || 'Something went wrong. Please try again.'
			) +
			'</textarea></div>';
		html += '</div>';

		// Tab Content: Fields
		html +=
			'<div class="aicrmform-edit-tab-content" id="edit-tab-fields" style="display: none;">';
		html += '<div class="aicrmform-edit-fields-header">';
		html +=
			'<p style="margin: 0 0 12px; color: #6b7280;">Drag fields to reorder. Click to edit or add new fields.</p>';
		html +=
			'<button type="button" class="button button-small" id="edit-add-field-btn"><span class="dashicons dashicons-plus-alt2"></span> Add Field</button>';
		html += '</div>';
		html += '<div id="edit-form-fields-container" class="aicrmform-fields-container"></div>';
		html += '</div>';

		// Tab Content: Styling
		html +=
			'<div class="aicrmform-edit-tab-content" id="edit-tab-styling" style="display: none;">';

		// Use Theme Styling Toggle
		html += '<div class="aicrmform-theme-styling-toggle">';
		html += '<label class="aicrmform-toggle-switch">';
		html +=
			'<input type="checkbox" id="edit-use-theme-styling"' +
			(styles.use_theme_styling ? ' checked' : '') +
			'>';
		html += '<span class="aicrmform-toggle-slider"></span>';
		html += '</label>';
		html += '<div class="aicrmform-toggle-content">';
		html += '<span class="aicrmform-toggle-label">Use Theme Styling</span>';
		html +=
			'<span class="aicrmform-toggle-description">Disable plugin styles and let your theme control the form appearance.</span>';
		html += '</div></div>';

		// Theme Styling Note
		html +=
			'<div class="aicrmform-theme-styling-note" id="edit-theme-styling-note"' +
			(styles.use_theme_styling ? '' : ' style="display: none;"') +
			'>';
		html += '<span class="dashicons dashicons-info"></span>';
		html += '<div class="aicrmform-note-content">';
		html += '<strong>Theme Styling Enabled</strong>';
		html +=
			'<p>All plugin styling options are hidden. Your theme will control how the form looks. You can add custom CSS in the "Custom CSS" section below if needed.</p>';
		html += '</div></div>';

		html +=
			'<div class="aicrmform-style-options" id="edit-style-options"' +
			(styles.use_theme_styling ? ' style="display: none;"' : '') +
			'>';
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
		html += '</div>'; // Close style-grid
		html += '</div>'; // Close style-options

		html += '</div>'; // Close tab content
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

		// Apply initial theme styling toggle state
		if (styles.use_theme_styling) {
			$modal.find('#edit-tab-styling').addClass('aicrmform-theme-styling-active');
		}

		// Render the fields in the edit modal
		renderEditFormFields();

		// Tab switching
		$modal.find('.aicrmform-edit-tab').on('click', function () {
			const tabId = $(this).data('tab');
			$modal.find('.aicrmform-edit-tab').removeClass('active');
			$(this).addClass('active');
			$modal.find('.aicrmform-edit-tab-content').hide();
			$modal.find('#edit-tab-' + tabId).show();
		});

		// Add field button
		$modal.find('#edit-add-field-btn').on('click', function () {
			openEditFieldModal(-1);
		});

		// Edit field click
		$(document).on('click', '#edit-form-fields-container .edit-field-btn', function (e) {
			e.stopPropagation();
			const index = $(this).closest('.aicrmform-field-item').data('index');
			openEditFieldModal(index);
		});

		// Delete field click
		$(document).on('click', '#edit-form-fields-container .delete-field-btn', function (e) {
			e.stopPropagation();
			const index = $(this).closest('.aicrmform-field-item').data('index');
			editFormFields.splice(index, 1);
			renderEditFormFields();
		});

		// Drag and drop for edit modal
		initEditDragDrop();

		$modal.on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) {
				cleanupEditModal();
				$modal.remove();
			}
		});
		$modal.find('#cancel-edit, #close-edit-modal').on('click', function () {
			cleanupEditModal();
			$modal.remove();
		});
		$modal.find('#save-edit').on('click', function () {
			const formId = $(this).data('form-id');
			let formConfig = $modal.data('form-config') || {};

			// Update fields in form config
			formConfig.fields = editFormFields.map((field) => ({
				name: field.name,
				label: field.label,
				type: field.type,
				placeholder: field.placeholder,
				required: field.required,
				options: field.options,
				field_id: field.crm_mapping || '',
			}));

			// Update messages
			formConfig.submit_button_text = $modal.find('#edit-submit-text').val() || 'Submit';
			formConfig.success_message =
				$modal.find('#edit-success-message').val() || 'Thank you for your submission!';
			formConfig.error_message =
				$modal.find('#edit-error-message').val() || 'Something went wrong.';

			// Update styles in form config
			formConfig.styles = {
				use_theme_styling: $modal.find('#edit-use-theme-styling').is(':checked'),
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
						cleanupEditModal();
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

	/**
	 * Cleanup edit modal event handlers.
	 */
	function cleanupEditModal() {
		$(document).off('click', '#edit-form-fields-container .edit-field-btn');
		$(document).off('click', '#edit-form-fields-container .delete-field-btn');
		editFormFields = [];
		editingFormId = null;
	}

	/**
	 * Render fields in the edit modal.
	 */
	function renderEditFormFields() {
		const $container = $('#edit-form-fields-container');

		if (editFormFields.length === 0) {
			$container.html(`
				<div class="aicrmform-empty-fields" style="padding: 30px; text-align: center;">
					<span class="dashicons dashicons-forms" style="font-size: 32px; color: #9ca3af;"></span>
					<p style="margin: 10px 0 0; color: #6b7280;">No fields yet. Click "Add Field" to add your first field.</p>
				</div>
			`);
			return;
		}

		// Detect duplicate CRM mappings
		const mappingCounts = {};
		editFormFields.forEach((field) => {
			if (field.crm_mapping) {
				const key = field.crm_mapping.toLowerCase();
				mappingCounts[key] = (mappingCounts[key] || 0) + 1;
			}
		});

		let html = '';
		editFormFields.forEach((field, index) => {
			const reqBadge = field.required
				? '<span class="aicrmform-field-badge required">Required</span>'
				: '';
			const crmFriendlyName = getCrmFriendlyName(field.crm_mapping);

			// Check for duplicate mapping
			const isDuplicate =
				field.crm_mapping && mappingCounts[field.crm_mapping.toLowerCase()] > 1;
			const duplicateClass = isDuplicate ? ' duplicate' : '';
			const duplicateTitle = isDuplicate
				? ' title="Warning: This CRM field is mapped to multiple form fields"'
				: '';

			const crmBadge = crmFriendlyName
				? `<span class="aicrmform-field-badge crm${duplicateClass}"${duplicateTitle}>${escapeHtml(crmFriendlyName)}${isDuplicate ? ' <span class="dashicons dashicons-warning"></span>' : ''}</span>`
				: '';

			html += `
				<div class="aicrmform-field-item${isDuplicate ? ' has-warning' : ''}" data-index="${index}" draggable="true">
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
	 * Initialize drag and drop for edit modal.
	 */
	function initEditDragDrop() {
		let editDraggedField = null;

		$(document).on(
			'dragstart',
			'#edit-form-fields-container .aicrmform-field-item',
			function (e) {
				editDraggedField = this;
				$(this).addClass('dragging');
				e.originalEvent.dataTransfer.effectAllowed = 'move';
				e.originalEvent.dataTransfer.setData('text/plain', $(this).data('index'));
			}
		);

		$(document).on('dragend', '#edit-form-fields-container .aicrmform-field-item', function () {
			$(this).removeClass('dragging');
			$('#edit-form-fields-container .aicrmform-field-item').removeClass('drag-over');
			editDraggedField = null;
		});

		$(document).on(
			'dragover',
			'#edit-form-fields-container .aicrmform-field-item',
			function (e) {
				e.preventDefault();
				if (editDraggedField && editDraggedField !== this) {
					$(this).addClass('drag-over');
				}
			}
		);

		$(document).on(
			'dragleave',
			'#edit-form-fields-container .aicrmform-field-item',
			function () {
				$(this).removeClass('drag-over');
			}
		);

		$(document).on('drop', '#edit-form-fields-container .aicrmform-field-item', function (e) {
			e.preventDefault();
			$(this).removeClass('drag-over');

			if (!editDraggedField || editDraggedField === this) return;

			const fromIndex = $(editDraggedField).data('index');
			const toIndex = $(this).data('index');

			const field = editFormFields.splice(fromIndex, 1)[0];
			editFormFields.splice(toIndex, 0, field);
			renderEditFormFields();
		});
	}

	/**
	 * Open field editor modal for editing form fields.
	 */
	let editingEditFieldIndex = null;

	function openEditFieldModal(index) {
		editingEditFieldIndex = index;

		// Build CRM mapping options HTML
		const crmMappingOptions = getCrmMappingOptionsHtml();

		let html =
			'<div class="aicrmform-modal-overlay" id="edit-field-modal" style="z-index: 100002;">';
		html += '<div class="aicrmform-modal aicrmform-modal-md">';
		html +=
			'<div class="aicrmform-modal-header"><h3>' +
			(index === -1 ? 'Add Field' : 'Edit Field') +
			'</h3></div>';
		html += '<div class="aicrmform-modal-body">';
		html +=
			'<div class="aicrmform-form-row"><label>Label *</label><input type="text" id="edit-field-label" class="aicrmform-input" placeholder="e.g., Email Address"></div>';
		html +=
			'<div class="aicrmform-form-row-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">';
		html +=
			'<div class="aicrmform-form-row"><label>Field ID *</label><input type="text" id="edit-field-name" class="aicrmform-input" placeholder="e.g., email_address"></div>';
		html +=
			'<div class="aicrmform-form-row"><label>Type</label><select id="edit-field-type" class="aicrmform-input">';
		html +=
			'<option value="text">Text</option><option value="email">Email</option><option value="tel">Phone</option>';
		html +=
			'<option value="number">Number</option><option value="textarea">Textarea</option><option value="select">Dropdown</option>';
		html +=
			'<option value="checkbox">Checkbox</option><option value="radio">Radio</option><option value="date">Date</option>';
		html += '<option value="url">URL</option><option value="hidden">Hidden</option>';
		html += '</select></div></div>';
		html +=
			'<div class="aicrmform-form-row"><label>Placeholder</label><input type="text" id="edit-field-placeholder" class="aicrmform-input"></div>';
		html +=
			'<div class="aicrmform-form-row" id="edit-field-options-row" style="display: none;"><label>Options</label><textarea id="edit-field-options" class="aicrmform-textarea" rows="3" placeholder="Enter each option on a new line"></textarea></div>';
		html +=
			'<div class="aicrmform-form-row"><label>CRM Field Mapping</label><select id="edit-field-crm-mapping" class="aicrmform-input">' +
			crmMappingOptions +
			'</select></div>';
		html +=
			'<div class="aicrmform-form-row"><label class="aicrmform-checkbox-inline"><input type="checkbox" id="edit-field-required"><span>Required field</span></label></div>';
		html += '</div>';
		html += '<div class="aicrmform-modal-footer">';
		html += '<button type="button" class="button" id="cancel-edit-field">Cancel</button>';
		html +=
			'<button type="button" class="button button-primary" id="save-edit-field">' +
			(index === -1 ? 'Add Field' : 'Save Changes') +
			'</button>';
		html += '</div></div></div>';

		const $modal = $(html);
		$('body').append($modal);
		$modal.css('display', 'flex').hide().fadeIn(200);

		// If editing existing field, populate values
		if (index !== -1 && editFormFields[index]) {
			const field = editFormFields[index];
			$modal.find('#edit-field-label').val(field.label);
			$modal.find('#edit-field-name').val(field.name);
			$modal.find('#edit-field-type').val(field.type);
			$modal.find('#edit-field-placeholder').val(field.placeholder || '');
			$modal.find('#edit-field-options').val((field.options || []).join('\n'));

			// Convert FieldID to dropdown key if needed
			const crmMappingKey = getCrmMappingKey(field.crm_mapping);
			$modal.find('#edit-field-crm-mapping').val(crmMappingKey);

			$modal.find('#edit-field-required').prop('checked', field.required);

			// Show options if needed
			if (['select', 'checkbox', 'radio'].includes(field.type)) {
				$modal.find('#edit-field-options-row').show();
			}
		}

		// Auto-generate field name from label
		$modal.find('#edit-field-label').on('input', function () {
			if (index === -1) {
				const label = $(this).val();
				const name = label
					.toLowerCase()
					.replace(/[^a-z0-9\s]/g, '')
					.replace(/\s+/g, '_')
					.substring(0, 30);
				$modal.find('#edit-field-name').val(name);
			}
		});

		// Show/hide options based on type
		$modal.find('#edit-field-type').on('change', function () {
			const type = $(this).val();
			if (['select', 'checkbox', 'radio'].includes(type)) {
				$modal.find('#edit-field-options-row').show();
			} else {
				$modal.find('#edit-field-options-row').hide();
			}
		});

		// Cancel
		$modal.find('#cancel-edit-field').on('click', function () {
			$modal.fadeOut(200, function () {
				$(this).remove();
			});
		});

		$modal.on('click', function (e) {
			if ($(e.target).hasClass('aicrmform-modal-overlay')) {
				$modal.fadeOut(200, function () {
					$(this).remove();
				});
			}
		});

		// Save field
		$modal.find('#save-edit-field').on('click', function () {
			const label = $modal.find('#edit-field-label').val().trim();
			const name = $modal.find('#edit-field-name').val().trim();

			if (!label || !name) {
				showToast('Label and Field ID are required.', 'warning');
				return;
			}

			// Check for duplicate names
			const isDuplicate = editFormFields.some(
				(f, i) => i !== editingEditFieldIndex && f.name === name
			);
			if (isDuplicate) {
				showToast('Field ID must be unique.', 'warning');
				return;
			}

			const type = $modal.find('#edit-field-type').val();
			const optionsText = $modal.find('#edit-field-options').val().trim();
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
				placeholder: $modal.find('#edit-field-placeholder').val().trim(),
				required: $modal.find('#edit-field-required').is(':checked'),
				options: options,
				crm_mapping: $modal.find('#edit-field-crm-mapping').val(),
			};

			if (editingEditFieldIndex === -1) {
				editFormFields.push(field);
			} else {
				editFormFields[editingEditFieldIndex] = field;
			}

			$modal.fadeOut(200, function () {
				$(this).remove();
				renderEditFormFields();
				showToast(
					editingEditFieldIndex === -1 ? 'Field added.' : 'Field updated.',
					'success'
				);
			});
		});
	}

	/**
	 * Get CRM mapping options HTML.
	 */
	function getCrmMappingOptionsHtml() {
		return `
			<option value="">— None —</option>
			<optgroup label="Contact">
				<option value="first_name">First Name</option>
				<option value="last_name">Last Name</option>
				<option value="email">Email</option>
				<option value="phone_number">Phone Number</option>
				<option value="mobile_phone">Mobile Phone</option>
				<option value="additional_emails">Additional Emails</option>
				<option value="tags">Tags</option>
				<option value="message">Message</option>
			</optgroup>
			<optgroup label="Address">
				<option value="primary_address_line1">Address Line 1</option>
				<option value="primary_address_line2">Address Line 2</option>
				<option value="primary_address_city">City</option>
				<option value="primary_address_state">State</option>
				<option value="primary_address_postal">Postal Code</option>
				<option value="primary_address_country">Country</option>
			</optgroup>
			<optgroup label="Company">
				<option value="company_name">Company Name</option>
				<option value="company_website">Company Website</option>
				<option value="company_phone">Company Phone</option>
				<option value="company_address_line1">Company Address</option>
				<option value="company_address_city">Company City</option>
				<option value="company_address_state">Company State</option>
				<option value="company_address_postal">Company Postal Code</option>
				<option value="company_address_country">Company Country</option>
				<option value="company_linkedin_url">LinkedIn URL</option>
				<option value="company_facebook_url">Facebook URL</option>
				<option value="company_instagram_url">Instagram URL</option>
				<option value="company_twitter_url">Twitter URL</option>
			</optgroup>
			<optgroup label="Lead & Source">
				<option value="source_name">Source Name</option>
				<option value="original_source">Original Source</option>
				<option value="lead_score">Lead Score</option>
				<option value="lead_quality">Lead Quality</option>
			</optgroup>
			<optgroup label="UTM Tracking">
				<option value="utm_source">UTM Source</option>
				<option value="utm_medium">UTM Medium</option>
				<option value="utm_campaign">UTM Campaign</option>
				<option value="utm_term">UTM Term</option>
				<option value="utm_content">UTM Content</option>
				<option value="gclid">Google Click ID</option>
				<option value="fbclid">Facebook Click ID</option>
				<option value="msclkid">Microsoft Click ID</option>
			</optgroup>
			<optgroup label="Consent">
				<option value="marketing_email_consent_status">Email Marketing Consent</option>
				<option value="sms_consent_status">SMS Consent</option>
			</optgroup>
		`;
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

				// Determine status class and icon
				const statusClass =
					sub.status === 'success' || sub.status === 'sent'
						? 'success'
						: sub.status === 'pending'
							? 'warning'
							: 'error';
				const statusIcon =
					statusClass === 'success'
						? 'yes-alt'
						: statusClass === 'warning'
							? 'clock'
							: 'warning';

				// Format date
				const submittedDate = new Date(sub.created_at);
				const formattedDate = submittedDate.toLocaleDateString('en-US', {
					weekday: 'long',
					year: 'numeric',
					month: 'long',
					day: 'numeric',
					hour: '2-digit',
					minute: '2-digit',
				});

				// Format field name for display (convert snake_case to Title Case)
				function formatFieldName(name) {
					return name
						.replace(/^input_\d+_\d+$/, 'Field') // Handle Gravity Forms field names
						.replace(/[-_]/g, ' ')
						.replace(/\b\w/g, function (l) {
							return l.toUpperCase();
						});
				}

				let html = `
				<div class="aicrmform-modal-overlay" id="submission-modal">
					<div class="aicrmform-modal aicrmform-modal-lg aicrmform-submission-detail">
						<div class="aicrmform-modal-header">
							<div class="aicrmform-submission-header-content">
								<h3>
									<span class="dashicons dashicons-email-alt"></span>
									Submission #${sub.id}
								</h3>
								<span class="aicrmform-status-pill ${statusClass}">
									<span class="dashicons dashicons-${statusIcon}"></span>
									${sub.status.charAt(0).toUpperCase() + sub.status.slice(1)}
								</span>
							</div>
							<button type="button" class="aicrmform-modal-close" onclick="jQuery('#submission-modal').remove();">&times;</button>
						</div>
						<div class="aicrmform-modal-body">
							<!-- Metadata Section -->
							<div class="aicrmform-submission-meta">
								<div class="aicrmform-meta-item">
									<span class="dashicons dashicons-calendar-alt"></span>
									<div>
										<span class="aicrmform-meta-label">Submitted</span>
										<span class="aicrmform-meta-value">${formattedDate}</span>
									</div>
								</div>
								<div class="aicrmform-meta-item">
									<span class="dashicons dashicons-admin-site-alt3"></span>
									<div>
										<span class="aicrmform-meta-label">IP Address</span>
										<span class="aicrmform-meta-value">${escapeHtml(sub.ip_address)}</span>
									</div>
								</div>
								<div class="aicrmform-meta-item">
									<span class="dashicons dashicons-format-aside"></span>
									<div>
										<span class="aicrmform-meta-label">Form ID</span>
										<span class="aicrmform-meta-value">${escapeHtml(String(sub.form_id))}</span>
									</div>
								</div>
							</div>

							<!-- Form Data Section -->
							<div class="aicrmform-submission-data">
								<h4>
									<span class="dashicons dashicons-list-view"></span>
									Submitted Data
								</h4>
								<div class="aicrmform-data-grid">
									${
										sub.submission_data
											? Object.entries(sub.submission_data)
													.map(function ([key, value]) {
														const displayValue = Array.isArray(value)
															? value.join(', ')
															: String(value);
														const isLongText =
															displayValue.length > 100;
														return `
											<div class="aicrmform-data-item ${isLongText ? 'full-width' : ''}">
												<span class="aicrmform-data-label">${escapeHtml(formatFieldName(key))}</span>
												<span class="aicrmform-data-value">${escapeHtml(displayValue) || '<em>Empty</em>'}</span>
											</div>
										`;
													})
													.join('')
											: '<p class="aicrmform-no-data">No submission data available</p>'
									}
								</div>
							</div>

							${
								sub.crm_response
									? `
							<!-- CRM Response Section -->
							<div class="aicrmform-crm-response">
								<h4>
									<span class="dashicons dashicons-cloud"></span>
									CRM Response
								</h4>
								<pre class="aicrmform-code-block">${escapeHtml(JSON.stringify(sub.crm_response, null, 2))}</pre>
							</div>
							`
									: ''
							}
						</div>
						<div class="aicrmform-modal-footer">
							<button type="button" class="button button-secondary" onclick="jQuery('#submission-modal').remove();">
								Close
							</button>
						</div>
					</div>
				</div>`;

				const $modal = $(html);
				$('body').append($modal);
				$modal.on('click', function (e) {
					if ($(e.target).hasClass('aicrmform-modal-overlay')) $modal.remove();
				});
			}
		});
	}

	/**
	 * Initialize submissions page functionality (filters, export, pagination).
	 */
	function initSubmissionsPage() {
		// Only run on submissions page
		if (!$('.aicrmform-submissions-page').length) return;

		const $table = $('#submissions-table');
		const $tbody = $('#submissions-tbody');
		const $rows = $tbody.find('.submission-row');
		const perPage = 20;
		let currentPage = 1;
		let filteredRows = $rows;

		// Export dropdown toggle
		$('#export-btn').on('click', function (e) {
			e.stopPropagation();
			$('#export-menu').toggle();
		});

		// Close dropdown when clicking outside
		$(document).on('click', function () {
			$('#export-menu').hide();
		});

		// Export all as CSV
		$('#export-csv').on('click', function (e) {
			e.preventDefault();
			exportToCSV({}, 'all-submissions');
			$('#export-menu').hide();
		});

		// Export filtered as CSV (uses IDs from currently filtered rows)
		$('#export-csv-filtered').on('click', function (e) {
			e.preventDefault();
			// Get IDs of currently filtered/visible rows
			const ids = [];
			filteredRows.each(function () {
				const id = $(this).find('.aicrmform-submission-id').text().replace('#', '').trim();
				if (id) ids.push(id);
			});

			if (ids.length === 0) {
				showToast('No submissions to export with current filters.', 'warning');
				$('#export-menu').hide();
				return;
			}

			exportToCSV({ ids: ids.join(',') }, 'filtered-submissions');
			$('#export-menu').hide();
		});

		// Export selected as CSV (uses IDs from checked checkboxes)
		$('#export-csv-selected').on('click', function (e) {
			e.preventDefault();
			const ids = [];
			$('.submission-checkbox:checked').each(function () {
				ids.push($(this).val());
			});

			if (ids.length === 0) {
				showToast('Please select submissions to export.', 'warning');
				$('#export-menu').hide();
				return;
			}

			exportToCSV({ ids: ids.join(',') }, 'selected-submissions');
			$('#export-menu').hide();
		});

		// Apply filters
		$('#apply-filters').on('click', applyFilters);

		// Clear filters
		$('#clear-filters').on('click', function () {
			$('#filter-status').val('');
			$('#filter-form').val('');
			$('#filter-date-from').val('');
			$('#filter-date-to').val('');
			applyFilters();
		});

		// Select all checkbox
		$('#select-all-submissions').on('change', function () {
			const isChecked = $(this).is(':checked');
			filteredRows.find('.submission-checkbox').prop('checked', isChecked);
		});

		// Apply filters function
		function applyFilters() {
			const status = $('#filter-status').val();
			const formId = $('#filter-form').val();
			const dateFrom = $('#filter-date-from').val();
			const dateTo = $('#filter-date-to').val();

			filteredRows = $rows.filter(function () {
				const $row = $(this);
				const rowStatus = $row.data('status');
				const rowFormId = String($row.data('form-id'));
				const rowDate = $row.data('date');

				// Status filter
				if (status && rowStatus !== status) return false;

				// Form filter
				if (formId && rowFormId !== formId) return false;

				// Date from filter
				if (dateFrom && rowDate < dateFrom) return false;

				// Date to filter
				if (dateTo && rowDate > dateTo) return false;

				return true;
			});

			currentPage = 1;
			updateDisplay();
		}

		// Update display with pagination
		function updateDisplay() {
			// Hide all rows
			$rows.hide();

			// Calculate pagination
			const totalFiltered = filteredRows.length;
			const totalPages = Math.ceil(totalFiltered / perPage);
			const start = (currentPage - 1) * perPage;
			const end = start + perPage;

			// Show filtered rows for current page
			filteredRows.slice(start, end).show();

			// Update results count
			$('#results-count').text('Showing ' + totalFiltered + ' submissions');

			// Update pagination controls
			updatePagination(totalFiltered, totalPages);
		}

		// Update pagination controls
		function updatePagination(total, totalPages) {
			const $pagination = $('.aicrmform-pagination');

			if (totalPages <= 1) {
				$pagination.hide();
				return;
			}

			$pagination.show();

			const start = (currentPage - 1) * perPage + 1;
			const end = Math.min(currentPage * perPage, total);

			$pagination
				.find('.aicrmform-pagination-info')
				.text('Showing ' + start + '-' + end + ' of ' + total);
			$pagination
				.find('.aicrmform-page-info')
				.text('Page ' + currentPage + ' of ' + totalPages);

			// Update button states
			$pagination.find('.aicrmform-page-btn').each(function () {
				const $btn = $(this);
				const page = parseInt($btn.data('page'));

				if (
					$btn.find('.dashicons-controls-skipback').length ||
					$btn.find('.dashicons-arrow-left-alt2').length
				) {
					$btn.prop('disabled', currentPage === 1);
				} else {
					$btn.prop('disabled', currentPage === totalPages);
				}
			});
		}

		// Pagination button clicks
		$(document).on('click', '.aicrmform-page-btn:not(:disabled)', function () {
			const $btn = $(this);
			const totalPages = Math.ceil(filteredRows.length / perPage);

			if ($btn.find('.dashicons-controls-skipback').length) {
				currentPage = 1;
			} else if ($btn.find('.dashicons-arrow-left-alt2').length) {
				currentPage = Math.max(1, currentPage - 1);
			} else if ($btn.find('.dashicons-arrow-right-alt2').length) {
				currentPage = Math.min(totalPages, currentPage + 1);
			} else if ($btn.find('.dashicons-controls-skipforward').length) {
				currentPage = totalPages;
			}

			updateDisplay();
		});

		// Export to CSV function (via API to get full submission data)
		function exportToCSV(filters, filename) {
			showToast('Preparing export...', 'info');

			// Build query string
			const params = new URLSearchParams();
			if (filters.status) params.append('status', filters.status);
			if (filters.form_id) params.append('form_id', filters.form_id);
			if (filters.date_from) params.append('date_from', filters.date_from);
			if (filters.date_to) params.append('date_to', filters.date_to);
			if (filters.ids) params.append('ids', filters.ids);

			const url =
				aicrmformAdmin.restUrl +
				'submissions/export' +
				(params.toString() ? '?' + params.toString() : '');

			$.ajax({
				url: url,
				method: 'GET',
				headers: { 'X-WP-Nonce': aicrmformAdmin.nonce },
			})
				.done(function (response) {
					if (response.success && response.rows) {
						// Create CSV content
						let csv =
							response.headers
								.map(function (h) {
									return '"' + String(h).replace(/"/g, '""') + '"';
								})
								.join(',') + '\n';

						response.rows.forEach(function (row) {
							csv +=
								row
									.map(function (cell) {
										// Escape quotes and wrap all in quotes for safety
										const escaped = String(cell || '').replace(/"/g, '""');
										return '"' + escaped + '"';
									})
									.join(',') + '\n';
						});

						// Download
						const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
						const link = document.createElement('a');
						link.href = URL.createObjectURL(blob);
						link.download =
							filename + '-' + new Date().toISOString().slice(0, 10) + '.csv';
						link.click();

						showToast('Exported ' + response.count + ' submissions to CSV', 'success');
					} else {
						showToast('Export failed: ' + (response.error || 'Unknown error'), 'error');
					}
				})
				.fail(function () {
					showToast('Export failed. Please try again.', 'error');
				});
		}

		// Initial display
		updateDisplay();
	}

	// Initialize on document ready
	$(document).ready(init);
})(jQuery);
