/**
 * AI CRM Form - Frontend JavaScript
 */

(function () {
	'use strict';

	/**
	 * Initialize all forms on the page.
	 */
	function init() {
		const forms = document.querySelectorAll('.aicrmform-form');
		forms.forEach(function (form) {
			initForm(form);
		});
	}

	/**
	 * Initialize a single form.
	 */
	function initForm(form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			submitForm(form);
		});

		// Add real-time validation.
		const inputs = form.querySelectorAll('input, select, textarea');
		inputs.forEach(function (input) {
			input.addEventListener('blur', function () {
				validateField(input);
			});

			input.addEventListener('input', function () {
				clearFieldError(input);
			});
		});
	}

	/**
	 * Submit form.
	 */
	function submitForm(form) {
		const formId = form.getAttribute('data-form-id');
		const wrapper = form.closest('.aicrmform-wrapper');
		const submitButton = form.querySelector('.aicrmform-button');
		const spinner = form.querySelector('.aicrmform-spinner');
		const successEl = wrapper ? wrapper.querySelector('.aicrmform-success') : null;
		const errorEl = wrapper ? wrapper.querySelector('.aicrmform-error-message') : null;

		// Validate all fields.
		if (!validateForm(form)) {
			return;
		}

		// Collect form data.
		const formData = collectFormData(form);

		// Show loading state.
		submitButton.disabled = true;
		if (spinner) spinner.style.display = 'inline-block';
		if (errorEl) errorEl.style.display = 'none';

		// Submit to API.
		fetch(aicrmformConfig.restUrl + 'submit/' + formId, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': aicrmformConfig.nonce,
			},
			body: JSON.stringify({ data: formData }),
		})
			.then(function (response) {
				return response.json().then(function (data) {
					return { status: response.status, data: data };
				});
			})
			.then(function (result) {
				if (result.status >= 200 && result.status < 300 && result.data.success) {
					// Success.
					form.style.display = 'none';
					if (successEl) {
						successEl.style.display = 'block';
						if (result.data.message) {
							successEl.querySelector('p').textContent = result.data.message;
						}
					}
				} else {
					// Error - use custom message from data attribute if available.
					const defaultError = errorEl
						? errorEl.getAttribute('data-default-message')
						: null;
					const errorMessage =
						result.data.error || defaultError || 'An error occurred. Please try again.';
					showError(errorEl, errorMessage);
					submitButton.disabled = false;
				}
			})
			.catch(function (error) {
				console.error('Form submission error:', error);
				const defaultError = errorEl ? errorEl.getAttribute('data-default-message') : null;
				showError(errorEl, defaultError || 'An error occurred. Please try again.');
				submitButton.disabled = false;
			})
			.finally(function () {
				if (spinner) spinner.style.display = 'none';
			});
	}

	/**
	 * Collect form data.
	 */
	function collectFormData(form) {
		const data = {};
		const inputs = form.querySelectorAll('input, select, textarea');

		inputs.forEach(function (input) {
			const name = input.getAttribute('name');
			if (!name || input.type === 'hidden') return;

			if (input.type === 'checkbox') {
				// Handle checkbox groups.
				const baseName = name.replace('[]', '');
				if (input.checked) {
					if (!data[baseName]) {
						data[baseName] = [];
					}
					data[baseName].push(input.value);
				}
			} else if (input.type === 'radio') {
				if (input.checked) {
					data[name] = input.value;
				}
			} else {
				data[name] = input.value;
			}
		});

		return data;
	}

	/**
	 * Validate form.
	 */
	function validateForm(form) {
		let isValid = true;
		const inputs = form.querySelectorAll('input, select, textarea');

		inputs.forEach(function (input) {
			if (!validateField(input)) {
				isValid = false;
			}
		});

		// Focus first error.
		if (!isValid) {
			const firstError = form.querySelector(
				'.aicrmform-field.error input, .aicrmform-field.error select, .aicrmform-field.error textarea'
			);
			if (firstError) {
				firstError.focus();
			}
		}

		return isValid;
	}

	/**
	 * Validate single field.
	 */
	function validateField(input) {
		const field = input.closest('.aicrmform-field');
		if (!field) return true;

		const isRequired = input.hasAttribute('required');
		const value = input.value.trim();
		const type = input.getAttribute('type') || 'text';

		// Clear previous errors.
		clearFieldError(input);

		// Required validation.
		if (isRequired && !value) {
			setFieldError(input, 'This field is required.');
			return false;
		}

		// Skip further validation if empty and not required.
		if (!value) return true;

		// Type-specific validation.
		switch (type) {
			case 'email':
				const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if (!emailPattern.test(value)) {
					setFieldError(input, 'Please enter a valid email address.');
					return false;
				}
				break;

			case 'tel':
				const telPattern = /^[\d\s\-+()]+$/;
				if (!telPattern.test(value)) {
					setFieldError(input, 'Please enter a valid phone number.');
					return false;
				}
				break;

			case 'url':
				try {
					new URL(value);
				} catch (e) {
					setFieldError(input, 'Please enter a valid URL.');
					return false;
				}
				break;

			case 'number':
				if (isNaN(parseFloat(value))) {
					setFieldError(input, 'Please enter a valid number.');
					return false;
				}
				break;
		}

		return true;
	}

	/**
	 * Set field error.
	 */
	function setFieldError(input, message) {
		const field = input.closest('.aicrmform-field');
		if (!field) return;

		field.classList.add('error');

		// Add error message.
		let errorEl = field.querySelector('.field-error');
		if (!errorEl) {
			errorEl = document.createElement('div');
			errorEl.className = 'field-error';
			field.appendChild(errorEl);
		}
		errorEl.textContent = message;
	}

	/**
	 * Clear field error.
	 */
	function clearFieldError(input) {
		const field = input.closest('.aicrmform-field');
		if (!field) return;

		field.classList.remove('error');

		const errorEl = field.querySelector('.field-error');
		if (errorEl) {
			errorEl.remove();
		}
	}

	/**
	 * Show error message.
	 */
	function showError(errorEl, message) {
		if (errorEl) {
			errorEl.textContent = message;
			errorEl.style.display = 'block';
		} else {
			alert(message);
		}
	}

	// Initialize on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
