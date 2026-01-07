<?php
/**
 * Admin Settings Page
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings Class
 */
class AICRMFORM_Admin_Settings {

	/**
	 * Render the main settings page.
	 */
	public function render() {
		// Handle form submission.
		if ( isset( $_POST['aicrmform_save_settings'] ) && check_admin_referer( 'aicrmform_settings_nonce' ) ) {
			$this->save_settings();
		}

		$settings    = get_option( 'aicrmform_settings', [] );
		$has_api_key = ! empty( $settings['api_key'] );

		// Check if forms exist for Quick Start widget.
		$generator  = new AICRMFORM_Form_Generator();
		$forms      = $generator->get_all_forms();
		$has_forms  = ! empty( $forms );

		// Determine step states.
		$step1_class = $has_api_key ? 'completed' : 'current';
		$step2_class = $has_forms ? 'completed' : ( $has_api_key ? 'current' : '' );
		$step3_class = $has_forms ? 'current' : '';
		?>
		<div class="wrap aicrmform-admin aicrmform-settings-page-pro">
			<!-- Page Header -->
			<div class="aicrmform-page-header-pro">
				<div class="aicrmform-page-header-content">
					<h1><?php esc_html_e( 'Settings', 'ai-crm-form' ); ?></h1>
					<p class="aicrmform-page-subtitle"><?php esc_html_e( 'Configure your AI and CRM integrations', 'ai-crm-form' ); ?></p>
				</div>
			</div>

			<form method="post" action="" class="aicrmform-settings-form">
				<?php wp_nonce_field( 'aicrmform_settings_nonce' ); ?>

				<div class="aicrmform-settings-layout">
					<div class="aicrmform-settings-main">
						<!-- AI Configuration -->
						<div class="aicrmform-card">
							<div class="aicrmform-card-header">
								<div class="aicrmform-card-header-icon">
									<span class="dashicons dashicons-superhero"></span>
								</div>
								<div>
									<h2><?php esc_html_e( 'AI Configuration', 'ai-crm-form' ); ?></h2>
									<p><?php esc_html_e( 'Set up your AI provider for form generation', 'ai-crm-form' ); ?></p>
								</div>
								<?php if ( $has_api_key ) : ?>
									<span class="aicrmform-config-badge success">
										<span class="dashicons dashicons-yes-alt"></span>
										<?php esc_html_e( 'Configured', 'ai-crm-form' ); ?>
									</span>
								<?php else : ?>
									<span class="aicrmform-config-badge warning">
										<span class="dashicons dashicons-warning"></span>
										<?php esc_html_e( 'Not Configured', 'ai-crm-form' ); ?>
									</span>
								<?php endif; ?>
							</div>
							<div class="aicrmform-card-body">
								<div class="aicrmform-form-row">
									<label for="ai_provider"><?php esc_html_e( 'AI Provider', 'ai-crm-form' ); ?></label>
									<select id="ai_provider" name="ai_provider" class="aicrmform-input" onchange="updateAIModelOptions()">
										<option value="groq" <?php selected( $settings['ai_provider'] ?? 'groq', 'groq' ); ?>>Groq (Recommended)</option>
										<option value="gemini" <?php selected( $settings['ai_provider'] ?? '', 'gemini' ); ?>>Google Gemini</option>
										<option value="openai" <?php selected( $settings['ai_provider'] ?? '', 'openai' ); ?>>OpenAI</option>
									</select>
									<p class="aicrmform-field-hint"><?php esc_html_e( 'Select your preferred AI provider for form generation.', 'ai-crm-form' ); ?></p>
								</div>
								<div class="aicrmform-form-row">
									<label for="api_key"><?php esc_html_e( 'API Key', 'ai-crm-form' ); ?> <span class="required">*</span></label>
									<div class="aicrmform-input-with-icon">
										<input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $settings['api_key'] ?? '' ); ?>" class="aicrmform-input" placeholder="<?php esc_attr_e( 'Enter your API key', 'ai-crm-form' ); ?>">
										<button type="button" class="aicrmform-toggle-password" onclick="togglePasswordVisibility('api_key', this)">
											<span class="dashicons dashicons-visibility"></span>
										</button>
									</div>
									<p class="aicrmform-field-hint" id="api_key_hint">
										<?php esc_html_e( 'Get your API key from:', 'ai-crm-form' ); ?>
										<a href="https://console.groq.com/keys" target="_blank" rel="noopener" id="api_key_link_groq" class="api-key-link">Groq Console →</a>
										<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" id="api_key_link_gemini" class="api-key-link" style="display:none;">Google AI Studio →</a>
										<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener" id="api_key_link_openai" class="api-key-link" style="display:none;">OpenAI Platform →</a>
									</p>
								</div>
								<div class="aicrmform-form-row">
									<label for="ai_model"><?php esc_html_e( 'AI Model', 'ai-crm-form' ); ?></label>
									<select id="ai_model" name="ai_model" class="aicrmform-input">
										<?php $current_model = $settings['ai_model'] ?? 'llama-3.3-70b-versatile'; ?>
										<!-- Groq Models -->
										<optgroup label="Groq Models" id="model_group_groq">
											<option value="llama-3.3-70b-versatile" <?php selected( $current_model, 'llama-3.3-70b-versatile' ); ?>>Llama 3.3 70B (Recommended)</option>
											<option value="llama-3.1-8b-instant" <?php selected( $current_model, 'llama-3.1-8b-instant' ); ?>>Llama 3.1 8B (Fast)</option>
											<option value="mixtral-8x7b-32768" <?php selected( $current_model, 'mixtral-8x7b-32768' ); ?>>Mixtral 8x7B</option>
											<option value="gemma2-9b-it" <?php selected( $current_model, 'gemma2-9b-it' ); ?>>Gemma 2 9B</option>
										</optgroup>
										<!-- Gemini Models -->
										<optgroup label="Gemini Models" id="model_group_gemini" style="display:none;">
											<option value="gemini-1.5-pro" <?php selected( $current_model, 'gemini-1.5-pro' ); ?>>Gemini 1.5 Pro</option>
											<option value="gemini-1.5-flash" <?php selected( $current_model, 'gemini-1.5-flash' ); ?>>Gemini 1.5 Flash (Fast)</option>
											<option value="gemini-2.0-flash-exp" <?php selected( $current_model, 'gemini-2.0-flash-exp' ); ?>>Gemini 2.0 Flash (Experimental)</option>
										</optgroup>
										<!-- OpenAI Models -->
										<optgroup label="OpenAI Models" id="model_group_openai" style="display:none;">
											<option value="gpt-4o" <?php selected( $current_model, 'gpt-4o' ); ?>>GPT-4o (Recommended)</option>
											<option value="gpt-4o-mini" <?php selected( $current_model, 'gpt-4o-mini' ); ?>>GPT-4o Mini (Fast)</option>
											<option value="gpt-4-turbo" <?php selected( $current_model, 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
										</optgroup>
									</select>
									<p class="aicrmform-field-hint"><?php esc_html_e( 'Select the AI model for generating forms.', 'ai-crm-form' ); ?></p>
								</div>
							</div>
						</div>

						<!-- CRM Configuration -->
						<div class="aicrmform-card">
							<div class="aicrmform-card-header">
								<div class="aicrmform-card-header-icon aicrmform-card-header-icon-blue">
									<span class="dashicons dashicons-cloud"></span>
								</div>
								<div>
									<h2><?php esc_html_e( 'CRM Configuration', 'ai-crm-form' ); ?></h2>
									<p><?php esc_html_e( 'Default settings for CRM integration', 'ai-crm-form' ); ?></p>
								</div>
							</div>
							<div class="aicrmform-card-body">
								<div class="aicrmform-form-row">
									<label for="form_id"><?php esc_html_e( 'Default CRM Form ID', 'ai-crm-form' ); ?></label>
									<input type="text" id="form_id" name="form_id" value="<?php echo esc_attr( $settings['form_id'] ?? '' ); ?>" class="aicrmform-input" placeholder="FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
									<p class="aicrmform-field-hint"><?php esc_html_e( 'Default CRM Form ID used when creating new forms.', 'ai-crm-form' ); ?></p>
								</div>
								<div class="aicrmform-form-row">
									<label for="default_success_message"><?php esc_html_e( 'Default Success Message', 'ai-crm-form' ); ?></label>
									<textarea id="default_success_message" name="default_success_message" class="aicrmform-textarea" rows="2"><?php echo esc_textarea( $settings['default_success_message'] ?? 'Thank you for your submission! We will get back to you soon.' ); ?></textarea>
								</div>
								<div class="aicrmform-form-row">
									<label for="default_error_message"><?php esc_html_e( 'Default Error Message', 'ai-crm-form' ); ?></label>
									<textarea id="default_error_message" name="default_error_message" class="aicrmform-textarea" rows="2"><?php echo esc_textarea( $settings['default_error_message'] ?? 'Something went wrong. Please try again later.' ); ?></textarea>
								</div>
							</div>
						</div>

						<!-- Form Styling -->
						<div class="aicrmform-card">
							<div class="aicrmform-card-header">
								<div class="aicrmform-card-header-icon aicrmform-card-header-icon-purple">
									<span class="dashicons dashicons-art"></span>
								</div>
								<div>
									<h2><?php esc_html_e( 'Form Styling', 'ai-crm-form' ); ?></h2>
									<p><?php esc_html_e( 'Default styling options for forms', 'ai-crm-form' ); ?></p>
								</div>
							</div>
							<div class="aicrmform-card-body">
								<div class="aicrmform-form-row">
									<label for="default_font_family"><?php esc_html_e( 'Default Font Family', 'ai-crm-form' ); ?></label>
									<select id="default_font_family" name="default_font_family" class="aicrmform-input">
										<option value="" <?php selected( $settings['default_font_family'] ?? '', '' ); ?>><?php esc_html_e( 'System Default', 'ai-crm-form' ); ?></option>
										<optgroup label="<?php esc_attr_e( 'Sans-serif', 'ai-crm-form' ); ?>">
											<option value="Inter" <?php selected( $settings['default_font_family'] ?? '', 'Inter' ); ?>>Inter</option>
											<option value="Roboto" <?php selected( $settings['default_font_family'] ?? '', 'Roboto' ); ?>>Roboto</option>
											<option value="Open Sans" <?php selected( $settings['default_font_family'] ?? '', 'Open Sans' ); ?>>Open Sans</option>
											<option value="Lato" <?php selected( $settings['default_font_family'] ?? '', 'Lato' ); ?>>Lato</option>
											<option value="Poppins" <?php selected( $settings['default_font_family'] ?? '', 'Poppins' ); ?>>Poppins</option>
											<option value="Montserrat" <?php selected( $settings['default_font_family'] ?? '', 'Montserrat' ); ?>>Montserrat</option>
											<option value="Source Sans Pro" <?php selected( $settings['default_font_family'] ?? '', 'Source Sans Pro' ); ?>>Source Sans Pro</option>
											<option value="Nunito" <?php selected( $settings['default_font_family'] ?? '', 'Nunito' ); ?>>Nunito</option>
										</optgroup>
										<optgroup label="<?php esc_attr_e( 'Serif', 'ai-crm-form' ); ?>">
											<option value="Merriweather" <?php selected( $settings['default_font_family'] ?? '', 'Merriweather' ); ?>>Merriweather</option>
											<option value="Playfair Display" <?php selected( $settings['default_font_family'] ?? '', 'Playfair Display' ); ?>>Playfair Display</option>
											<option value="Lora" <?php selected( $settings['default_font_family'] ?? '', 'Lora' ); ?>>Lora</option>
										</optgroup>
									</select>
									<p class="aicrmform-field-hint"><?php esc_html_e( 'Select a Google Font for your forms. This will load the font automatically.', 'ai-crm-form' ); ?></p>
								</div>
								<div class="aicrmform-form-row">
									<label for="default_font_size"><?php esc_html_e( 'Default Font Size', 'ai-crm-form' ); ?></label>
									<select id="default_font_size" name="default_font_size" class="aicrmform-input">
										<option value="14px" <?php selected( $settings['default_font_size'] ?? '16px', '14px' ); ?>>14px - Small</option>
										<option value="16px" <?php selected( $settings['default_font_size'] ?? '16px', '16px' ); ?>>16px - Default</option>
										<option value="18px" <?php selected( $settings['default_font_size'] ?? '16px', '18px' ); ?>>18px - Large</option>
									</select>
								</div>
								<div class="aicrmform-form-row">
									<label for="default_background_color"><?php esc_html_e( 'Default Form Background Color', 'ai-crm-form' ); ?></label>
									<div class="aicrmform-color-picker-wrapper">
										<input type="color" id="default_background_color" name="default_background_color" value="<?php echo esc_attr( $settings['default_background_color'] ?? '#ffffff' ); ?>" class="aicrmform-color-input">
										<input type="text" id="default_background_color_text" value="<?php echo esc_attr( $settings['default_background_color'] ?? '#ffffff' ); ?>" class="aicrmform-input aicrmform-color-text" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
									</div>
									<p class="aicrmform-field-hint"><?php esc_html_e( 'Background color for the form container.', 'ai-crm-form' ); ?></p>
								</div>
							</div>
						</div>

						<!-- General Settings -->
						<div class="aicrmform-card">
							<div class="aicrmform-card-header">
								<div class="aicrmform-card-header-icon aicrmform-card-header-icon-gray">
									<span class="dashicons dashicons-admin-settings"></span>
								</div>
								<div>
									<h2><?php esc_html_e( 'General Settings', 'ai-crm-form' ); ?></h2>
									<p><?php esc_html_e( 'Plugin-wide configuration options', 'ai-crm-form' ); ?></p>
								</div>
							</div>
							<div class="aicrmform-card-body">
								<div class="aicrmform-toggle-row">
									<div class="aicrmform-toggle-content">
										<label><?php esc_html_e( 'Enable Plugin', 'ai-crm-form' ); ?></label>
										<p><?php esc_html_e( 'Enable AI CRM Form functionality on your site.', 'ai-crm-form' ); ?></p>
									</div>
									<label class="aicrmform-switch">
										<input type="checkbox" id="enabled" name="enabled" value="1" <?php checked( $settings['enabled'] ?? false ); ?>>
										<span class="aicrmform-switch-slider"></span>
									</label>
								</div>
								<div class="aicrmform-form-row" style="margin-top: 20px;">
									<label for="auto_delete_submissions"><?php esc_html_e( 'Auto-delete Submissions After (Days)', 'ai-crm-form' ); ?></label>
									<select id="auto_delete_submissions" name="auto_delete_submissions" class="aicrmform-input">
										<option value="0" <?php selected( $settings['auto_delete_submissions'] ?? '0', '0' ); ?>><?php esc_html_e( 'Never (Keep Forever)', 'ai-crm-form' ); ?></option>
										<option value="7" <?php selected( $settings['auto_delete_submissions'] ?? '0', '7' ); ?>>7 <?php esc_html_e( 'days', 'ai-crm-form' ); ?></option>
										<option value="14" <?php selected( $settings['auto_delete_submissions'] ?? '0', '14' ); ?>>14 <?php esc_html_e( 'days', 'ai-crm-form' ); ?></option>
										<option value="30" <?php selected( $settings['auto_delete_submissions'] ?? '0', '30' ); ?>>30 <?php esc_html_e( 'days', 'ai-crm-form' ); ?></option>
										<option value="60" <?php selected( $settings['auto_delete_submissions'] ?? '0', '60' ); ?>>60 <?php esc_html_e( 'days', 'ai-crm-form' ); ?></option>
										<option value="90" <?php selected( $settings['auto_delete_submissions'] ?? '0', '90' ); ?>>90 <?php esc_html_e( 'days', 'ai-crm-form' ); ?></option>
										<option value="180" <?php selected( $settings['auto_delete_submissions'] ?? '0', '180' ); ?>>180 <?php esc_html_e( 'days', 'ai-crm-form' ); ?></option>
										<option value="365" <?php selected( $settings['auto_delete_submissions'] ?? '0', '365' ); ?>>365 <?php esc_html_e( 'days', 'ai-crm-form' ); ?></option>
									</select>
									<p class="aicrmform-field-hint"><?php esc_html_e( 'Automatically delete form submissions after the specified number of days. Useful for GDPR compliance.', 'ai-crm-form' ); ?></p>
								</div>
							</div>
						</div>

						<!-- Save Button -->
						<div class="aicrmform-settings-footer">
							<button type="submit" name="aicrmform_save_settings" class="button button-primary button-large">
								<span class="dashicons dashicons-saved"></span>
								<?php esc_html_e( 'Save Settings', 'ai-crm-form' ); ?>
							</button>
						</div>
					</div>

					<!-- Sidebar -->
					<div class="aicrmform-settings-sidebar">
						<div class="aicrmform-card aicrmform-help-card">
							<div class="aicrmform-card-body">
								<h3><?php esc_html_e( 'Need Help?', 'ai-crm-form' ); ?></h3>
								<ul class="aicrmform-help-links">
									<li>
										<span class="dashicons dashicons-book"></span>
										<a href="#" target="_blank"><?php esc_html_e( 'Documentation', 'ai-crm-form' ); ?></a>
									</li>
									<li>
										<span class="dashicons dashicons-admin-generic"></span>
										<a href="#" target="_blank"><?php esc_html_e( 'Get API Keys', 'ai-crm-form' ); ?></a>
									</li>
									<li>
										<span class="dashicons dashicons-sos"></span>
										<a href="#" target="_blank"><?php esc_html_e( 'Support', 'ai-crm-form' ); ?></a>
									</li>
								</ul>
							</div>
						</div>

						<div class="aicrmform-card aicrmform-quick-start-card">
							<div class="aicrmform-card-body">
								<h3><?php esc_html_e( 'Quick Start', 'ai-crm-form' ); ?></h3>
								<ol class="aicrmform-steps">
									<li class="<?php echo esc_attr( $step1_class ); ?>">
										<?php esc_html_e( 'Add your API key', 'ai-crm-form' ); ?>
										<?php if ( $has_api_key ) : ?>
											<span class="aicrmform-step-check">✓</span>
										<?php endif; ?>
									</li>
									<li class="<?php echo esc_attr( $step2_class ); ?>">
										<?php esc_html_e( 'Create your first form', 'ai-crm-form' ); ?>
										<?php if ( $has_forms ) : ?>
											<span class="aicrmform-step-check">✓</span>
										<?php endif; ?>
									</li>
									<li class="<?php echo esc_attr( $step3_class ); ?>">
										<?php esc_html_e( 'Embed using shortcode', 'ai-crm-form' ); ?>
									</li>
								</ol>
								<?php if ( $has_forms ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-crm-form-forms' ) ); ?>" class="button button-primary" style="width: 100%; justify-content: center;">
										<?php esc_html_e( 'View Forms & Get Shortcode', 'ai-crm-form' ); ?>
									</a>
								<?php elseif ( $has_api_key ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-crm-form-generator' ) ); ?>" class="button button-primary" style="width: 100%; justify-content: center;">
										<?php esc_html_e( 'Create Your First Form', 'ai-crm-form' ); ?>
									</a>
								<?php else : ?>
									<p class="aicrmform-step-hint" style="margin: 0; font-size: 12px; color: #6b7280; text-align: center;">
										<?php esc_html_e( 'Add your API key above to get started', 'ai-crm-form' ); ?>
									</p>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>

		<script>
		function togglePasswordVisibility(inputId, button) {
			const input = document.getElementById(inputId);
			const icon = button.querySelector('.dashicons');
			if (input.type === 'password') {
				input.type = 'text';
				icon.classList.remove('dashicons-visibility');
				icon.classList.add('dashicons-hidden');
			} else {
				input.type = 'password';
				icon.classList.remove('dashicons-hidden');
				icon.classList.add('dashicons-visibility');
			}
		}

		function updateAIModelOptions() {
			const provider = document.getElementById('ai_provider').value;
			const modelSelect = document.getElementById('ai_model');
			const providers = ['groq', 'gemini', 'openai'];

			// Hide all API key links and model groups
			providers.forEach(function(p) {
				const link = document.getElementById('api_key_link_' + p);
				const group = document.getElementById('model_group_' + p);
				if (link) link.style.display = 'none';
				if (group) group.style.display = 'none';
			});

			// Show selected provider's link and models
			const activeLink = document.getElementById('api_key_link_' + provider);
			const activeGroup = document.getElementById('model_group_' + provider);
			if (activeLink) activeLink.style.display = 'inline';
			if (activeGroup) activeGroup.style.display = 'block';

			// Select first option from the active group if current selection is hidden
			const currentOption = modelSelect.options[modelSelect.selectedIndex];
			if (currentOption && currentOption.parentElement.style.display === 'none') {
				const firstVisibleOption = activeGroup ? activeGroup.querySelector('option') : null;
				if (firstVisibleOption) {
					modelSelect.value = firstVisibleOption.value;
				}
			}
		}

		// Initialize on page load
		document.addEventListener('DOMContentLoaded', function() {
			updateAIModelOptions();
		});
		</script>
		<?php
	}

	/**
	 * Save settings.
	 */
	private function save_settings() {
		$form_id = sanitize_text_field( wp_unslash( $_POST['form_id'] ?? '' ) );

		// Validate Form ID format if provided.
		if ( ! empty( $form_id ) && ! $this->validate_form_id( $form_id ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid Form ID format. Expected: FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'ai-crm-form' ) . '</p></div>';
			return;
		}

		$settings = [
			'api_key'                   => sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) ),
			'ai_provider'               => sanitize_text_field( wp_unslash( $_POST['ai_provider'] ?? 'groq' ) ),
			'ai_model'                  => sanitize_text_field( wp_unslash( $_POST['ai_model'] ?? 'llama-3.3-70b-versatile' ) ),
			'crm_api_url'               => 'https://forms-prod.apigateway.co/forms.v1.FormSubmissionService/CreateFormSubmission',
			'form_id'                   => $form_id,
			'default_success_message'   => sanitize_textarea_field( wp_unslash( $_POST['default_success_message'] ?? '' ) ),
			'default_error_message'     => sanitize_textarea_field( wp_unslash( $_POST['default_error_message'] ?? '' ) ),
			'enabled'                   => ! empty( $_POST['enabled'] ),
			'auto_delete_submissions'   => absint( $_POST['auto_delete_submissions'] ?? 0 ),
			'default_font_family'       => sanitize_text_field( wp_unslash( $_POST['default_font_family'] ?? '' ) ),
			'default_font_size'         => sanitize_text_field( wp_unslash( $_POST['default_font_size'] ?? '16px' ) ),
			'default_background_color'  => sanitize_hex_color( wp_unslash( $_POST['default_background_color'] ?? '#ffffff' ) ),
		];

		update_option( 'aicrmform_settings', $settings );

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'ai-crm-form' ) . '</p></div>';
	}

	/**
	 * Validate Form ID format.
	 *
	 * @param string $form_id The form ID to validate.
	 * @return bool True if valid.
	 */
	private function validate_form_id( $form_id ) {
		// Format: FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
		$pattern = '/^FormConfigID-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i';
		return preg_match( $pattern, $form_id );
	}

	/**
	 * Render forms page.
	 */
	public function render_forms_page() {
		$generator = new AICRMFORM_Form_Generator();
		$forms     = $generator->get_all_forms();
		$crm_api   = new AICRMFORM_CRM_API();
		?>
		<div class="wrap aicrmform-admin aicrmform-forms-page">
			<!-- Page Header -->
			<div class="aicrmform-page-header-pro">
				<div class="aicrmform-page-header-content">
					<h1><?php esc_html_e( 'Forms', 'ai-crm-form' ); ?></h1>
					<p class="aicrmform-page-subtitle"><?php esc_html_e( 'Manage your lead capture forms', 'ai-crm-form' ); ?></p>
				</div>
				<div class="aicrmform-page-header-actions">
					<button type="button" id="import-form-btn" class="button button-primary button-large">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Import Form', 'ai-crm-form' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-crm-form-generator' ) ); ?>" class="button button-primary button-large">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Create Form', 'ai-crm-form' ); ?>
					</a>
				</div>
			</div>

			<?php if ( empty( $forms ) ) : ?>
				<!-- Empty State -->
				<div class="aicrmform-empty-state-pro">
					<div class="aicrmform-empty-state-inner">
						<div class="aicrmform-empty-state-icon">
							<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect x="10" y="10" width="60" height="60" rx="8" fill="#EEF2FF"/>
								<rect x="20" y="24" width="40" height="6" rx="3" fill="#C7D2FE"/>
								<rect x="20" y="36" width="40" height="6" rx="3" fill="#C7D2FE"/>
								<rect x="20" y="48" width="28" height="6" rx="3" fill="#C7D2FE"/>
								<rect x="28" y="58" width="24" height="10" rx="5" fill="#6366F1"/>
							</svg>
						</div>
						<h2><?php esc_html_e( 'Create your first form', 'ai-crm-form' ); ?></h2>
						<p><?php esc_html_e( 'Build beautiful lead capture forms in minutes using AI or our drag-and-drop builder. Connect them to your CRM and start collecting leads.', 'ai-crm-form' ); ?></p>
						<div class="aicrmform-empty-state-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-crm-form-generator' ) ); ?>" class="button button-primary button-hero">
								<span class="dashicons dashicons-plus-alt2"></span>
								<?php esc_html_e( 'Create Your First Form', 'ai-crm-form' ); ?>
							</a>
						</div>
						<div class="aicrmform-empty-state-features">
							<div class="aicrmform-feature-item">
								<span class="dashicons dashicons-superhero"></span>
								<span><?php esc_html_e( 'AI-Powered Generation', 'ai-crm-form' ); ?></span>
							</div>
							<div class="aicrmform-feature-item">
								<span class="dashicons dashicons-move"></span>
								<span><?php esc_html_e( 'Drag & Drop Builder', 'ai-crm-form' ); ?></span>
							</div>
							<div class="aicrmform-feature-item">
								<span class="dashicons dashicons-cloud"></span>
								<span><?php esc_html_e( 'CRM Integration', 'ai-crm-form' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			<?php else : ?>
				<!-- Stats Bar -->
				<div class="aicrmform-stats-bar">
					<div class="aicrmform-stat-item">
						<div class="aicrmform-stat-icon">
							<span class="dashicons dashicons-format-aside"></span>
						</div>
						<div class="aicrmform-stat-content">
							<span class="aicrmform-stat-value" id="forms-total-count"><?php echo esc_html( count( $forms ) ); ?></span>
							<span class="aicrmform-stat-label"><?php esc_html_e( 'Total Forms', 'ai-crm-form' ); ?></span>
						</div>
					</div>
					<div class="aicrmform-stat-item">
						<div class="aicrmform-stat-icon aicrmform-stat-icon-success">
							<span class="dashicons dashicons-yes-alt"></span>
						</div>
						<div class="aicrmform-stat-content">
							<span class="aicrmform-stat-value" id="forms-active-count"><?php echo esc_html( count( array_filter( $forms, fn( $f ) => 'active' === $f->status ) ) ); ?></span>
							<span class="aicrmform-stat-label"><?php esc_html_e( 'Active', 'ai-crm-form' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Forms Grid -->
				<div class="aicrmform-forms-grid-pro">
					<?php
					foreach ( $forms as $form ) :
						$field_count = count( $form->form_config['fields'] ?? [] );
						?>
						<div class="aicrmform-form-card-pro" data-form-id="<?php echo esc_attr( $form->id ); ?>">
							<div class="aicrmform-form-card-top">
								<div class="aicrmform-form-card-icon">
									<span class="dashicons dashicons-feedback"></span>
								</div>
								<div class="aicrmform-form-card-status">
									<span class="aicrmform-status-dot <?php echo 'active' === $form->status ? 'active' : 'inactive'; ?>"></span>
									<?php echo esc_html( ucfirst( $form->status ) ); ?>
								</div>
							</div>
							<div class="aicrmform-form-card-content">
								<h3><?php echo esc_html( $form->name ); ?></h3>
								<?php if ( $form->description ) : ?>
									<p class="aicrmform-form-desc"><?php echo esc_html( wp_trim_words( $form->description, 12 ) ); ?></p>
								<?php endif; ?>
								<div class="aicrmform-form-stats">
									<div class="aicrmform-form-stat">
										<span class="dashicons dashicons-list-view"></span>
										<span>
										<?php
										/* translators: %d: number of fields in the form */
										printf( esc_html( _n( '%d field', '%d fields', $field_count, 'ai-crm-form' ) ), (int) $field_count );
										?>
										</span>
									</div>
									<div class="aicrmform-form-stat">
										<span class="dashicons dashicons-calendar"></span>
										<span><?php echo esc_html( gmdate( 'M j, Y', strtotime( $form->created_at ) ) ); ?></span>
									</div>
								</div>
							</div>
							<div class="aicrmform-form-card-shortcode">
								<code>[ai_crm_form id="<?php echo esc_attr( $form->id ); ?>"]</code>
								<button type="button" class="aicrmform-copy-btn-sm" data-copy='[ai_crm_form id="<?php echo esc_attr( $form->id ); ?>"]' title="<?php esc_attr_e( 'Copy', 'ai-crm-form' ); ?>">
									<span class="dashicons dashicons-admin-page"></span>
								</button>
							</div>
							<div class="aicrmform-form-card-actions">
								<button type="button" class="aicrmform-action-btn aicrmform-preview-form" data-form-id="<?php echo esc_attr( $form->id ); ?>" title="<?php esc_attr_e( 'Preview', 'ai-crm-form' ); ?>">
									<span class="dashicons dashicons-visibility"></span>
								</button>
								<button type="button" class="aicrmform-action-btn aicrmform-edit-form" data-form-id="<?php echo esc_attr( $form->id ); ?>" title="<?php esc_attr_e( 'Edit', 'ai-crm-form' ); ?>">
									<span class="dashicons dashicons-edit"></span>
								</button>
								<button type="button" class="aicrmform-action-btn aicrmform-repair-mappings" data-form-id="<?php echo esc_attr( $form->id ); ?>" title="<?php esc_attr_e( 'Repair Field Mappings', 'ai-crm-form' ); ?>">
									<span class="dashicons dashicons-admin-tools"></span>
								</button>
								<button type="button" class="aicrmform-action-btn aicrmform-action-btn-danger aicrmform-delete-form" data-form-id="<?php echo esc_attr( $form->id ); ?>" title="<?php esc_attr_e( 'Delete', 'ai-crm-form' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Toast Notification -->
		<div id="aicrmform-toast" class="aicrmform-toast"></div>

		<!-- Confirmation Modal -->
		<div id="aicrmform-confirm-modal" class="aicrmform-modal-overlay" style="display: none;">
			<div class="aicrmform-modal aicrmform-modal-sm">
				<div class="aicrmform-modal-header">
					<h3 id="aicrmform-confirm-title"><?php esc_html_e( 'Confirm Action', 'ai-crm-form' ); ?></h3>
				</div>
				<div class="aicrmform-modal-body">
					<p id="aicrmform-confirm-message"></p>
				</div>
				<div class="aicrmform-modal-footer">
					<button type="button" class="button" id="aicrmform-confirm-cancel"><?php esc_html_e( 'Cancel', 'ai-crm-form' ); ?></button>
					<button type="button" class="button button-primary" id="aicrmform-confirm-ok"><?php esc_html_e( 'Confirm', 'ai-crm-form' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Import Form Modal -->
		<div id="import-form-modal" class="aicrmform-modal-overlay" style="display: none;">
			<div class="aicrmform-modal aicrmform-modal-md">
				<div class="aicrmform-modal-header">
					<h3><?php esc_html_e( 'Import Form', 'ai-crm-form' ); ?></h3>
					<button type="button" class="aicrmform-modal-close">&times;</button>
				</div>
				<div class="aicrmform-modal-body">
					<div id="import-loading" style="text-align: center; padding: 40px;">
						<span class="spinner is-active" style="float: none;"></span>
						<p><?php esc_html_e( 'Loading available forms...', 'ai-crm-form' ); ?></p>
					</div>
					<div id="import-content" style="display: none;">
						<div id="import-no-plugins" class="aicrmform-empty-state" style="display: none;">
							<span class="dashicons dashicons-warning" style="font-size: 48px; color: #f59e0b;"></span>
							<h3><?php esc_html_e( 'No Form Plugins Found', 'ai-crm-form' ); ?></h3>
							<p><?php esc_html_e( 'Install and activate Contact Form 7 or Gravity Forms to import forms.', 'ai-crm-form' ); ?></p>
						</div>
						<div id="import-sources-list"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render form generator page.
	 */
	public function render_generator_page() {
		$settings   = get_option( 'aicrmform_settings', [] );
		$generator  = new AICRMFORM_Form_Generator();
		$configured = $generator->is_configured();
		?>
		<div class="wrap aicrmform-admin aicrmform-builder-page">
			<div class="aicrmform-builder-header">
				<h1><?php esc_html_e( 'Form Builder', 'ai-crm-form' ); ?></h1>
				<div class="aicrmform-builder-header-actions">
					<button type="button" id="open-import-modal" class="button button-secondary">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Import Form', 'ai-crm-form' ); ?>
					</button>
					<?php if ( $configured ) : ?>
					<button type="button" id="open-ai-generator" class="button button-secondary">
						<span class="dashicons dashicons-superhero"></span>
						<?php esc_html_e( 'Generate with AI', 'ai-crm-form' ); ?>
					</button>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! $configured ) : ?>
				<div class="aicrmform-alert aicrmform-alert-info">
					<span class="dashicons dashicons-info"></span>
					<div>
						<strong><?php esc_html_e( 'Want to generate forms with AI?', 'ai-crm-form' ); ?></strong>
						<p><?php esc_html_e( 'Add your API key in', 'ai-crm-form' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-crm-form-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'ai-crm-form' ); ?></a> <?php esc_html_e( 'to enable AI form generation.', 'ai-crm-form' ); ?></p>
					</div>
				</div>
			<?php endif; ?>

			<div class="aicrmform-builder-main">
				<!-- Left: Form Settings & Fields -->
				<div class="aicrmform-builder-left">
					<!-- Form Settings Card -->
					<div class="aicrmform-card">
						<div class="aicrmform-card-header">
							<h2><?php esc_html_e( 'Form Settings', 'ai-crm-form' ); ?></h2>
						</div>
						<div class="aicrmform-card-body">
							<div class="aicrmform-settings-grid">
								<div class="aicrmform-form-row">
									<label for="form-name"><?php esc_html_e( 'Form Name', 'ai-crm-form' ); ?> <span class="required">*</span></label>
									<input type="text" id="form-name" class="aicrmform-input" placeholder="<?php esc_attr_e( 'My Contact Form', 'ai-crm-form' ); ?>">
								</div>
								<div class="aicrmform-form-row">
									<label for="crm-form-id"><?php esc_html_e( 'CRM Form ID', 'ai-crm-form' ); ?> <span class="required">*</span></label>
									<input type="text" id="crm-form-id" class="aicrmform-input" value="<?php echo esc_attr( $settings['form_id'] ?? '' ); ?>" placeholder="FormConfigID-xxx...">
									<p class="aicrmform-field-error" id="crm-form-id-error" style="display: none;"></p>
								</div>
							</div>
						</div>
					</div>

					<!-- Form Fields Card -->
					<div class="aicrmform-card">
						<div class="aicrmform-card-header aicrmform-card-header-with-actions">
							<h2><?php esc_html_e( 'Form Fields', 'ai-crm-form' ); ?></h2>
							<button type="button" id="add-field-btn" class="button button-small">
								<span class="dashicons dashicons-plus-alt2"></span>
								<?php esc_html_e( 'Add Field', 'ai-crm-form' ); ?>
							</button>
						</div>
						<div class="aicrmform-card-body">
							<div id="form-fields-container" class="aicrmform-fields-container">
								<div class="aicrmform-empty-fields">
									<div class="aicrmform-empty-fields-icon">
										<span class="dashicons dashicons-forms"></span>
									</div>
									<p><?php esc_html_e( 'No fields yet. Add fields manually or generate with AI.', 'ai-crm-form' ); ?></p>
									<div class="aicrmform-empty-fields-actions">
										<button type="button" id="add-first-field" class="button button-primary">
											<span class="dashicons dashicons-plus-alt2"></span>
											<?php esc_html_e( 'Add Field', 'ai-crm-form' ); ?>
										</button>
										<?php if ( $configured ) : ?>
										<button type="button" class="button open-ai-modal">
											<span class="dashicons dashicons-superhero"></span>
											<?php esc_html_e( 'Generate with AI', 'ai-crm-form' ); ?>
										</button>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Messages Card -->
					<div class="aicrmform-card aicrmform-card-collapsible" id="messages-card">
						<div class="aicrmform-card-header aicrmform-card-header-collapsible" data-collapsed="true">
							<h2><?php esc_html_e( 'Messages & Button', 'ai-crm-form' ); ?></h2>
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</div>
						<div class="aicrmform-card-body" style="display: none;">
							<div class="aicrmform-form-row">
								<label for="submit-button-text"><?php esc_html_e( 'Submit Button Text', 'ai-crm-form' ); ?></label>
								<input type="text" id="submit-button-text" class="aicrmform-input" value="Submit" placeholder="Submit">
							</div>
							<div class="aicrmform-form-row">
								<label for="success-message"><?php esc_html_e( 'Success Message', 'ai-crm-form' ); ?></label>
								<textarea id="success-message" class="aicrmform-textarea" rows="2"><?php echo esc_textarea( $settings['default_success_message'] ?? 'Thank you for your submission!' ); ?></textarea>
							</div>
							<div class="aicrmform-form-row">
								<label for="error-message"><?php esc_html_e( 'Error Message', 'ai-crm-form' ); ?></label>
								<textarea id="error-message" class="aicrmform-textarea" rows="2"><?php echo esc_textarea( $settings['default_error_message'] ?? 'Something went wrong. Please try again.' ); ?></textarea>
							</div>
						</div>
					</div>

					<!-- Style Card -->
					<div class="aicrmform-card aicrmform-card-collapsible" id="style-card">
						<div class="aicrmform-card-header aicrmform-card-header-collapsible" data-collapsed="true">
							<h2><?php esc_html_e( 'Styling', 'ai-crm-form' ); ?></h2>
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</div>
						<div class="aicrmform-card-body" style="display: none;">
							<div class="aicrmform-style-grid">
								<div class="aicrmform-form-row">
									<label for="style-font-family"><?php esc_html_e( 'Font Family', 'ai-crm-form' ); ?></label>
									<select id="style-font-family" class="aicrmform-input">
										<option value=""><?php esc_html_e( 'System Default', 'ai-crm-form' ); ?></option>
										<optgroup label="<?php esc_attr_e( 'Sans-serif', 'ai-crm-form' ); ?>">
											<option value="Inter">Inter</option>
											<option value="Roboto">Roboto</option>
											<option value="Open Sans">Open Sans</option>
											<option value="Lato">Lato</option>
											<option value="Poppins">Poppins</option>
											<option value="Montserrat">Montserrat</option>
											<option value="Source Sans Pro">Source Sans Pro</option>
											<option value="Nunito">Nunito</option>
										</optgroup>
										<optgroup label="<?php esc_attr_e( 'Serif', 'ai-crm-form' ); ?>">
											<option value="Merriweather">Merriweather</option>
											<option value="Playfair Display">Playfair Display</option>
											<option value="Lora">Lora</option>
										</optgroup>
									</select>
								</div>
								<div class="aicrmform-form-row">
									<label for="style-font-size"><?php esc_html_e( 'Font Size', 'ai-crm-form' ); ?></label>
									<select id="style-font-size" class="aicrmform-input">
										<option value="14px"><?php esc_html_e( '14px - Small', 'ai-crm-form' ); ?></option>
										<option value="16px" selected><?php esc_html_e( '16px - Default', 'ai-crm-form' ); ?></option>
										<option value="18px"><?php esc_html_e( '18px - Large', 'ai-crm-form' ); ?></option>
									</select>
								</div>
								<div class="aicrmform-form-row">
									<label for="style-background-color"><?php esc_html_e( 'Background Color', 'ai-crm-form' ); ?></label>
									<input type="color" id="style-background-color" class="aicrmform-color-input" value="#ffffff">
								</div>
								<div class="aicrmform-form-row">
									<label for="style-primary-color"><?php esc_html_e( 'Button Color', 'ai-crm-form' ); ?></label>
									<input type="color" id="style-primary-color" class="aicrmform-color-input" value="#0073aa">
								</div>
								<div class="aicrmform-form-row">
									<label for="style-border-radius"><?php esc_html_e( 'Border Radius', 'ai-crm-form' ); ?></label>
									<select id="style-border-radius" class="aicrmform-input">
										<option value="0"><?php esc_html_e( 'None', 'ai-crm-form' ); ?></option>
										<option value="4px" selected><?php esc_html_e( 'Small', 'ai-crm-form' ); ?></option>
										<option value="8px"><?php esc_html_e( 'Medium', 'ai-crm-form' ); ?></option>
										<option value="12px"><?php esc_html_e( 'Large', 'ai-crm-form' ); ?></option>
									</select>
								</div>
								<div class="aicrmform-form-row">
									<label for="style-label-position"><?php esc_html_e( 'Label Position', 'ai-crm-form' ); ?></label>
									<select id="style-label-position" class="aicrmform-input">
										<option value="top"><?php esc_html_e( 'Top', 'ai-crm-form' ); ?></option>
										<option value="left"><?php esc_html_e( 'Inline', 'ai-crm-form' ); ?></option>
										<option value="hidden"><?php esc_html_e( 'Hidden', 'ai-crm-form' ); ?></option>
									</select>
								</div>
								<div class="aicrmform-form-row">
									<label for="style-button-width"><?php esc_html_e( 'Button Width', 'ai-crm-form' ); ?></label>
									<select id="style-button-width" class="aicrmform-input">
										<option value="auto"><?php esc_html_e( 'Auto', 'ai-crm-form' ); ?></option>
										<option value="full"><?php esc_html_e( 'Full Width', 'ai-crm-form' ); ?></option>
									</select>
								</div>
							</div>
							<div class="aicrmform-form-row">
								<label for="custom-css"><?php esc_html_e( 'Custom CSS', 'ai-crm-form' ); ?></label>
								<textarea id="custom-css" class="aicrmform-textarea aicrmform-code-editor" rows="4" placeholder="/* Your custom styles */"></textarea>
							</div>
						</div>
					</div>
				</div>

				<!-- Right: Live Preview -->
				<div class="aicrmform-builder-right">
					<div class="aicrmform-card aicrmform-preview-card-sticky">
						<div class="aicrmform-card-header">
							<h2><?php esc_html_e( 'Live Preview', 'ai-crm-form' ); ?></h2>
						</div>
						<div class="aicrmform-card-body">
							<div id="live-preview-container" class="aicrmform-live-preview-container">
								<div class="aicrmform-preview-empty">
									<span class="dashicons dashicons-visibility"></span>
									<p><?php esc_html_e( 'Your form preview will appear here', 'ai-crm-form' ); ?></p>
								</div>
							</div>
						</div>
						<div class="aicrmform-card-footer">
							<button type="button" id="save-form" class="button button-primary button-large">
								<span class="dashicons dashicons-saved"></span>
								<?php esc_html_e( 'Save Form', 'ai-crm-form' ); ?>
							</button>
							<button type="button" id="reset-form" class="button">
								<?php esc_html_e( 'Clear', 'ai-crm-form' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- AI Generator Modal -->
		<div id="ai-generator-modal" class="aicrmform-modal-overlay" style="display: none;">
			<div class="aicrmform-modal aicrmform-modal-lg">
				<div class="aicrmform-modal-header">
					<div class="aicrmform-modal-header-content">
						<span class="aicrmform-modal-icon"><span class="dashicons dashicons-superhero"></span></span>
						<h3><?php esc_html_e( 'Generate Form with AI', 'ai-crm-form' ); ?></h3>
					</div>
					<button type="button" class="aicrmform-modal-close" id="close-ai-modal">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="aicrmform-modal-body">
					<p class="aicrmform-ai-help"><?php esc_html_e( 'Describe the form you want to create. Be specific about fields, types, and requirements.', 'ai-crm-form' ); ?></p>
					<textarea id="ai-prompt" class="aicrmform-textarea" rows="4" placeholder="<?php esc_attr_e( 'Example: Create a contact form with first name, last name, email, phone number, and a message field. Make all fields required except phone.', 'ai-crm-form' ); ?>"></textarea>
					
					<div class="aicrmform-ai-suggestions">
						<span class="aicrmform-ai-suggestions-label"><?php esc_html_e( 'Quick templates:', 'ai-crm-form' ); ?></span>
						<button type="button" class="aicrmform-ai-suggestion" data-prompt="Create a simple contact form with name, email, and message fields. All fields required.">
							<?php esc_html_e( 'Contact Form', 'ai-crm-form' ); ?>
						</button>
						<button type="button" class="aicrmform-ai-suggestion" data-prompt="Create a lead capture form with first name, last name, email, phone, company name, and a dropdown for 'How did you hear about us?' with options: Google, Social Media, Friend, Other.">
							<?php esc_html_e( 'Lead Capture', 'ai-crm-form' ); ?>
						</button>
						<button type="button" class="aicrmform-ai-suggestion" data-prompt="Create a newsletter signup form with email field and a checkbox for marketing consent.">
							<?php esc_html_e( 'Newsletter', 'ai-crm-form' ); ?>
						</button>
					</div>
				</div>
				<div class="aicrmform-modal-footer">
					<button type="button" class="button" id="cancel-ai-generate"><?php esc_html_e( 'Cancel', 'ai-crm-form' ); ?></button>
					<button type="button" class="button button-primary" id="generate-with-ai">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Generate Form', 'ai-crm-form' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Add/Edit Field Modal -->
		<div id="field-editor-modal" class="aicrmform-modal-overlay" style="display: none;">
			<div class="aicrmform-modal aicrmform-modal-lg">
				<div class="aicrmform-modal-header">
					<h3 id="field-editor-title"><?php esc_html_e( 'Add Field', 'ai-crm-form' ); ?></h3>
					<button type="button" class="aicrmform-modal-close" id="close-field-modal">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="aicrmform-modal-body">
					<!-- Field Picker View (shown when adding new field) -->
					<div id="field-picker-view">
						<p class="aicrmform-picker-help"><?php esc_html_e( 'Select a field type to add to your form:', 'ai-crm-form' ); ?></p>
						
						<!-- Contact Fields -->
						<div class="aicrmform-field-picker-section">
							<h4><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Contact Fields', 'ai-crm-form' ); ?></h4>
							<p class="aicrmform-picker-section-desc"><?php esc_html_e( 'Primary contact information fields.', 'ai-crm-form' ); ?></p>
							<div class="aicrmform-field-picker-grid">
								<button type="button" class="aicrmform-picker-field" data-preset="first_name">
									<span class="dashicons dashicons-admin-users"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'First Name', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="last_name">
									<span class="dashicons dashicons-admin-users"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Last Name', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="email">
									<span class="dashicons dashicons-email"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Email', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="phone_number">
									<span class="dashicons dashicons-phone"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Phone Number', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="mobile_phone">
									<span class="dashicons dashicons-smartphone"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Mobile Phone', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="additional_emails">
									<span class="dashicons dashicons-email-alt"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Additional Emails', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="tags">
									<span class="dashicons dashicons-tag"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Tags', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="message">
									<span class="dashicons dashicons-testimonial"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Message', 'ai-crm-form' ); ?></span>
								</button>
							</div>
						</div>

						<!-- Address Fields -->
						<div class="aicrmform-field-picker-section">
							<h4><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Address Fields', 'ai-crm-form' ); ?></h4>
							<p class="aicrmform-picker-section-desc"><?php esc_html_e( 'Contact address information.', 'ai-crm-form' ); ?></p>
							<div class="aicrmform-field-picker-grid">
								<button type="button" class="aicrmform-picker-field" data-preset="primary_address_line1">
									<span class="dashicons dashicons-location"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Address Line 1', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="primary_address_line2">
									<span class="dashicons dashicons-location-alt"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Address Line 2', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="primary_address_city">
									<span class="dashicons dashicons-building"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'City', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="primary_address_state">
									<span class="dashicons dashicons-flag"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'State', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="primary_address_postal">
									<span class="dashicons dashicons-location"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Postal Code', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="primary_address_country">
									<span class="dashicons dashicons-admin-site"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Country', 'ai-crm-form' ); ?></span>
								</button>
							</div>
						</div>

						<!-- Company Fields -->
						<div class="aicrmform-field-picker-section">
							<h4><span class="dashicons dashicons-building"></span> <?php esc_html_e( 'Company Fields', 'ai-crm-form' ); ?></h4>
							<p class="aicrmform-picker-section-desc"><?php esc_html_e( 'Company and organization information.', 'ai-crm-form' ); ?></p>
							<div class="aicrmform-field-picker-grid">
								<button type="button" class="aicrmform-picker-field" data-preset="company_name">
									<span class="dashicons dashicons-building"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company Name', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_website">
									<span class="dashicons dashicons-admin-site-alt3"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company Website', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_phone">
									<span class="dashicons dashicons-phone"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company Phone', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_address_line1">
									<span class="dashicons dashicons-location"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company Address', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_address_city">
									<span class="dashicons dashicons-building"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company City', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_address_state">
									<span class="dashicons dashicons-flag"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company State', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_address_postal">
									<span class="dashicons dashicons-location"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company Postal', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_address_country">
									<span class="dashicons dashicons-admin-site"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Company Country', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_linkedin_url">
									<span class="dashicons dashicons-linkedin"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'LinkedIn URL', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_facebook_url">
									<span class="dashicons dashicons-facebook"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Facebook URL', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_instagram_url">
									<span class="dashicons dashicons-instagram"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Instagram URL', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="company_twitter_url">
									<span class="dashicons dashicons-twitter"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Twitter URL', 'ai-crm-form' ); ?></span>
								</button>
							</div>
						</div>

						<!-- Lead & Source Fields -->
						<div class="aicrmform-field-picker-section">
							<h4><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e( 'Lead & Source Fields', 'ai-crm-form' ); ?></h4>
							<p class="aicrmform-picker-section-desc"><?php esc_html_e( 'Lead scoring and source tracking fields.', 'ai-crm-form' ); ?></p>
							<div class="aicrmform-field-picker-grid">
								<button type="button" class="aicrmform-picker-field" data-preset="source_name">
									<span class="dashicons dashicons-megaphone"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Source Name', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="original_source">
									<span class="dashicons dashicons-admin-links"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Original Source', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="lead_score">
									<span class="dashicons dashicons-star-filled"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Lead Score', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="lead_quality">
									<span class="dashicons dashicons-star-half"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Lead Quality', 'ai-crm-form' ); ?></span>
								</button>
							</div>
						</div>

						<!-- UTM Fields -->
						<div class="aicrmform-field-picker-section">
							<h4><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'UTM Tracking Fields', 'ai-crm-form' ); ?></h4>
							<p class="aicrmform-picker-section-desc"><?php esc_html_e( 'Campaign and tracking parameters.', 'ai-crm-form' ); ?></p>
							<div class="aicrmform-field-picker-grid">
								<button type="button" class="aicrmform-picker-field" data-preset="utm_source">
									<span class="dashicons dashicons-admin-links"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'UTM Source', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="utm_medium">
									<span class="dashicons dashicons-admin-links"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'UTM Medium', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="utm_campaign">
									<span class="dashicons dashicons-megaphone"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'UTM Campaign', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="utm_term">
									<span class="dashicons dashicons-search"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'UTM Term', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="utm_content">
									<span class="dashicons dashicons-text-page"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'UTM Content', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="gclid">
									<span class="dashicons dashicons-google"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Google Click ID', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="fbclid">
									<span class="dashicons dashicons-facebook"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Facebook Click ID', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="msclkid">
									<span class="dashicons dashicons-admin-site"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Microsoft Click ID', 'ai-crm-form' ); ?></span>
								</button>
							</div>
						</div>

						<!-- Consent Fields -->
						<div class="aicrmform-field-picker-section">
							<h4><span class="dashicons dashicons-privacy"></span> <?php esc_html_e( 'Consent Fields', 'ai-crm-form' ); ?></h4>
							<p class="aicrmform-picker-section-desc"><?php esc_html_e( 'Marketing consent and compliance fields.', 'ai-crm-form' ); ?></p>
							<div class="aicrmform-field-picker-grid">
								<button type="button" class="aicrmform-picker-field" data-preset="marketing_email_consent_status">
									<span class="dashicons dashicons-email"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Email Consent', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-preset="sms_consent_status">
									<span class="dashicons dashicons-smartphone"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'SMS Consent', 'ai-crm-form' ); ?></span>
								</button>
							</div>
						</div>
						
						<!-- Basic Fields -->
						<div class="aicrmform-field-picker-section">
							<h4><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Basic Fields', 'ai-crm-form' ); ?></h4>
							<p class="aicrmform-picker-section-desc"><?php esc_html_e( 'Standard form fields that you can customize.', 'ai-crm-form' ); ?></p>
							<div class="aicrmform-field-picker-grid">
								<button type="button" class="aicrmform-picker-field" data-type="text">
									<span class="dashicons dashicons-editor-textcolor"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Text Input', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="email">
									<span class="dashicons dashicons-email"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Email', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="tel">
									<span class="dashicons dashicons-phone"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Phone', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="number">
									<span class="dashicons dashicons-editor-ol"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Number', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="textarea">
									<span class="dashicons dashicons-text"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Textarea', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="select">
									<span class="dashicons dashicons-arrow-down-alt2"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Dropdown', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="checkbox">
									<span class="dashicons dashicons-yes"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Checkbox', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="radio">
									<span class="dashicons dashicons-marker"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Radio', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="date">
									<span class="dashicons dashicons-calendar-alt"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Date', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="url">
									<span class="dashicons dashicons-admin-links"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'URL', 'ai-crm-form' ); ?></span>
								</button>
								<button type="button" class="aicrmform-picker-field" data-type="hidden">
									<span class="dashicons dashicons-hidden"></span>
									<span class="aicrmform-picker-field-name"><?php esc_html_e( 'Hidden', 'ai-crm-form' ); ?></span>
								</button>
							</div>
						</div>
					</div>

					<!-- Field Editor View (shown when editing or after selecting field type) -->
					<div id="field-editor-view" style="display: none;">
						<div class="aicrmform-form-row">
							<label for="field-label"><?php esc_html_e( 'Label', 'ai-crm-form' ); ?> <span class="required">*</span></label>
							<input type="text" id="field-label" class="aicrmform-input" placeholder="<?php esc_attr_e( 'e.g., Email Address', 'ai-crm-form' ); ?>">
						</div>
						<div class="aicrmform-form-row-grid">
							<div class="aicrmform-form-row">
								<label for="field-name"><?php esc_html_e( 'Field ID', 'ai-crm-form' ); ?> <span class="required">*</span></label>
								<input type="text" id="field-name" class="aicrmform-input" placeholder="<?php esc_attr_e( 'e.g., email_address', 'ai-crm-form' ); ?>">
							</div>
							<div class="aicrmform-form-row">
								<label for="field-type"><?php esc_html_e( 'Type', 'ai-crm-form' ); ?></label>
								<select id="field-type" class="aicrmform-input">
									<option value="text"><?php esc_html_e( 'Text', 'ai-crm-form' ); ?></option>
									<option value="email"><?php esc_html_e( 'Email', 'ai-crm-form' ); ?></option>
									<option value="tel"><?php esc_html_e( 'Phone', 'ai-crm-form' ); ?></option>
									<option value="number"><?php esc_html_e( 'Number', 'ai-crm-form' ); ?></option>
									<option value="textarea"><?php esc_html_e( 'Textarea', 'ai-crm-form' ); ?></option>
									<option value="select"><?php esc_html_e( 'Dropdown', 'ai-crm-form' ); ?></option>
									<option value="checkbox"><?php esc_html_e( 'Checkbox', 'ai-crm-form' ); ?></option>
									<option value="radio"><?php esc_html_e( 'Radio', 'ai-crm-form' ); ?></option>
									<option value="date"><?php esc_html_e( 'Date', 'ai-crm-form' ); ?></option>
									<option value="url"><?php esc_html_e( 'URL', 'ai-crm-form' ); ?></option>
									<option value="hidden"><?php esc_html_e( 'Hidden', 'ai-crm-form' ); ?></option>
								</select>
							</div>
						</div>
						<div class="aicrmform-form-row">
							<label for="field-placeholder"><?php esc_html_e( 'Placeholder', 'ai-crm-form' ); ?></label>
							<input type="text" id="field-placeholder" class="aicrmform-input">
						</div>
						<div class="aicrmform-form-row" id="field-options-row" style="display: none;">
							<label for="field-options"><?php esc_html_e( 'Options', 'ai-crm-form' ); ?></label>
							<textarea id="field-options" class="aicrmform-textarea" rows="3" placeholder="<?php esc_attr_e( 'Enter each option on a new line', 'ai-crm-form' ); ?>"></textarea>
						</div>
						<div class="aicrmform-form-row">
							<label for="field-crm-mapping"><?php esc_html_e( 'CRM Field Mapping', 'ai-crm-form' ); ?></label>
							<select id="field-crm-mapping" class="aicrmform-input">
								<option value=""><?php esc_html_e( '— None —', 'ai-crm-form' ); ?></option>
								<optgroup label="<?php esc_attr_e( 'Contact', 'ai-crm-form' ); ?>">
									<option value="first_name"><?php esc_html_e( 'First Name', 'ai-crm-form' ); ?></option>
									<option value="last_name"><?php esc_html_e( 'Last Name', 'ai-crm-form' ); ?></option>
									<option value="email"><?php esc_html_e( 'Email', 'ai-crm-form' ); ?></option>
									<option value="phone_number"><?php esc_html_e( 'Phone Number', 'ai-crm-form' ); ?></option>
									<option value="mobile_phone"><?php esc_html_e( 'Mobile Phone', 'ai-crm-form' ); ?></option>
									<option value="additional_emails"><?php esc_html_e( 'Additional Emails', 'ai-crm-form' ); ?></option>
									<option value="tags"><?php esc_html_e( 'Tags', 'ai-crm-form' ); ?></option>
									<option value="message"><?php esc_html_e( 'Message', 'ai-crm-form' ); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'Address', 'ai-crm-form' ); ?>">
									<option value="primary_address_line1"><?php esc_html_e( 'Address Line 1', 'ai-crm-form' ); ?></option>
									<option value="primary_address_line2"><?php esc_html_e( 'Address Line 2', 'ai-crm-form' ); ?></option>
									<option value="primary_address_city"><?php esc_html_e( 'City', 'ai-crm-form' ); ?></option>
									<option value="primary_address_state"><?php esc_html_e( 'State', 'ai-crm-form' ); ?></option>
									<option value="primary_address_postal"><?php esc_html_e( 'Postal Code', 'ai-crm-form' ); ?></option>
									<option value="primary_address_country"><?php esc_html_e( 'Country', 'ai-crm-form' ); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'Company', 'ai-crm-form' ); ?>">
									<option value="company_name"><?php esc_html_e( 'Company Name', 'ai-crm-form' ); ?></option>
									<option value="company_website"><?php esc_html_e( 'Company Website', 'ai-crm-form' ); ?></option>
									<option value="company_phone"><?php esc_html_e( 'Company Phone', 'ai-crm-form' ); ?></option>
									<option value="company_address_line1"><?php esc_html_e( 'Company Address', 'ai-crm-form' ); ?></option>
									<option value="company_address_city"><?php esc_html_e( 'Company City', 'ai-crm-form' ); ?></option>
									<option value="company_address_state"><?php esc_html_e( 'Company State', 'ai-crm-form' ); ?></option>
									<option value="company_address_postal"><?php esc_html_e( 'Company Postal Code', 'ai-crm-form' ); ?></option>
									<option value="company_address_country"><?php esc_html_e( 'Company Country', 'ai-crm-form' ); ?></option>
									<option value="company_linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'ai-crm-form' ); ?></option>
									<option value="company_facebook_url"><?php esc_html_e( 'Facebook URL', 'ai-crm-form' ); ?></option>
									<option value="company_instagram_url"><?php esc_html_e( 'Instagram URL', 'ai-crm-form' ); ?></option>
									<option value="company_twitter_url"><?php esc_html_e( 'Twitter URL', 'ai-crm-form' ); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'Lead & Source', 'ai-crm-form' ); ?>">
									<option value="source_name"><?php esc_html_e( 'Source Name', 'ai-crm-form' ); ?></option>
									<option value="original_source"><?php esc_html_e( 'Original Source', 'ai-crm-form' ); ?></option>
									<option value="lead_score"><?php esc_html_e( 'Lead Score', 'ai-crm-form' ); ?></option>
									<option value="lead_quality"><?php esc_html_e( 'Lead Quality', 'ai-crm-form' ); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'UTM Tracking', 'ai-crm-form' ); ?>">
									<option value="utm_source"><?php esc_html_e( 'UTM Source', 'ai-crm-form' ); ?></option>
									<option value="utm_medium"><?php esc_html_e( 'UTM Medium', 'ai-crm-form' ); ?></option>
									<option value="utm_campaign"><?php esc_html_e( 'UTM Campaign', 'ai-crm-form' ); ?></option>
									<option value="utm_term"><?php esc_html_e( 'UTM Term', 'ai-crm-form' ); ?></option>
									<option value="utm_content"><?php esc_html_e( 'UTM Content', 'ai-crm-form' ); ?></option>
									<option value="gclid"><?php esc_html_e( 'Google Click ID', 'ai-crm-form' ); ?></option>
									<option value="fbclid"><?php esc_html_e( 'Facebook Click ID', 'ai-crm-form' ); ?></option>
									<option value="msclkid"><?php esc_html_e( 'Microsoft Click ID', 'ai-crm-form' ); ?></option>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'Consent', 'ai-crm-form' ); ?>">
									<option value="marketing_email_consent_status"><?php esc_html_e( 'Email Marketing Consent', 'ai-crm-form' ); ?></option>
									<option value="sms_consent_status"><?php esc_html_e( 'SMS Consent', 'ai-crm-form' ); ?></option>
								</optgroup>
							</select>
						</div>
						<div class="aicrmform-form-row">
							<label class="aicrmform-checkbox-inline">
								<input type="checkbox" id="field-required">
								<span><?php esc_html_e( 'Required field', 'ai-crm-form' ); ?></span>
							</label>
						</div>
					</div>
				</div>
				<div class="aicrmform-modal-footer">
					<button type="button" class="button" id="back-to-picker" style="display: none;"><?php esc_html_e( '← Back', 'ai-crm-form' ); ?></button>
					<button type="button" class="button" id="cancel-field-edit"><?php esc_html_e( 'Cancel', 'ai-crm-form' ); ?></button>
					<button type="button" class="button button-primary" id="save-field-edit" style="display: none;"><?php esc_html_e( 'Add Field', 'ai-crm-form' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Confirmation Modal -->
		<div id="aicrmform-confirm-modal" class="aicrmform-modal-overlay" style="display: none;">
			<div class="aicrmform-modal aicrmform-modal-md">
				<div class="aicrmform-modal-header">
					<h3 id="aicrmform-confirm-title"><?php esc_html_e( 'Confirm Action', 'ai-crm-form' ); ?></h3>
				</div>
				<div class="aicrmform-modal-body">
					<p id="aicrmform-confirm-message"></p>
				</div>
				<div class="aicrmform-modal-footer">
					<button type="button" class="button" id="aicrmform-confirm-cancel"><?php esc_html_e( 'Cancel', 'ai-crm-form' ); ?></button>
					<button type="button" class="button button-primary" id="aicrmform-confirm-ok"><?php esc_html_e( 'Confirm', 'ai-crm-form' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Toast Notification -->
		<div id="aicrmform-toast" class="aicrmform-toast"></div>

		<!-- Success Modal -->
		<div id="aicrmform-success-modal" class="aicrmform-modal-overlay" style="display: none;">
			<div class="aicrmform-modal">
				<div class="aicrmform-modal-header aicrmform-modal-header-success">
					<span class="dashicons dashicons-yes-alt"></span>
					<h3><?php esc_html_e( 'Form Saved Successfully!', 'ai-crm-form' ); ?></h3>
				</div>
				<div class="aicrmform-modal-body">
					<p><?php esc_html_e( 'Your form has been created and is ready to use.', 'ai-crm-form' ); ?></p>
					<div class="aicrmform-shortcode-display">
						<label><?php esc_html_e( 'Copy this shortcode to embed your form:', 'ai-crm-form' ); ?></label>
						<div class="aicrmform-shortcode-box">
							<code id="saved-form-shortcode"></code>
							<button type="button" class="aicrmform-copy-btn" id="copy-shortcode-btn">
								<span class="dashicons dashicons-admin-page"></span>
							</button>
						</div>
					</div>
				</div>
				<div class="aicrmform-modal-footer">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-crm-form-forms' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View All Forms', 'ai-crm-form' ); ?></a>
					<button type="button" class="button" id="create-another-form"><?php esc_html_e( 'Create Another Form', 'ai-crm-form' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Import Form Modal -->
		<div id="import-form-modal" class="aicrmform-modal-overlay" style="display: none;">
			<div class="aicrmform-modal aicrmform-modal-md">
				<div class="aicrmform-modal-header">
					<h3><?php esc_html_e( 'Import Form', 'ai-crm-form' ); ?></h3>
					<button type="button" class="aicrmform-modal-close">&times;</button>
				</div>
				<div class="aicrmform-modal-body">
					<div id="import-loading" style="text-align: center; padding: 40px;">
						<span class="spinner is-active" style="float: none;"></span>
						<p><?php esc_html_e( 'Loading available forms...', 'ai-crm-form' ); ?></p>
					</div>
					<div id="import-content" style="display: none;">
						<div id="import-no-plugins" class="aicrmform-empty-state" style="display: none;">
							<span class="dashicons dashicons-warning" style="font-size: 48px; color: #f59e0b;"></span>
							<h3><?php esc_html_e( 'No Form Plugins Found', 'ai-crm-form' ); ?></h3>
							<p><?php esc_html_e( 'Install and activate Contact Form 7 or another supported plugin to import forms.', 'ai-crm-form' ); ?></p>
						</div>
						<div id="import-sources-list"></div>
					</div>
				</div>
			</div>
		</div>

		<!-- Alert Modal (Popup - replaces JS alerts) -->
		<div id="aicrmform-alert-modal" class="aicrmform-alert-modal">
			<div class="aicrmform-alert-content">
				<div id="aicrmform-alert-icon" class="aicrmform-alert-icon warning">
					<span class="dashicons dashicons-warning"></span>
				</div>
				<h3 id="aicrmform-alert-title" class="aicrmform-alert-title"><?php esc_html_e( 'Warning', 'ai-crm-form' ); ?></h3>
				<p id="aicrmform-alert-message" class="aicrmform-alert-message"></p>
				<div class="aicrmform-alert-actions">
					<button type="button" class="button" id="aicrmform-alert-ignore" style="display: none;"><?php esc_html_e( 'Ignore & Save', 'ai-crm-form' ); ?></button>
					<button type="button" class="button button-primary" id="aicrmform-alert-ok"><?php esc_html_e( 'OK', 'ai-crm-form' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render submissions page.
	 */
	public function render_submissions_page() {
		$crm_api     = new AICRMFORM_CRM_API();
		$generator   = new AICRMFORM_Form_Generator();
		$forms       = $generator->get_all_forms();
		$submissions = $crm_api->get_all_submissions();
		$total_submissions = count( $submissions );
		$success_count = count( array_filter( $submissions, fn( $s ) => 'success' === $s->status || 'sent' === $s->status ) );
		$pending_count = count( array_filter( $submissions, fn( $s ) => 'pending' === $s->status ) );
		$failed_count  = count( array_filter( $submissions, fn( $s ) => 'failed' === $s->status || 'error' === $s->status ) );

		// Pagination settings.
		$per_page     = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$total_pages  = ceil( $total_submissions / $per_page );
		?>
		<div class="wrap aicrmform-admin aicrmform-submissions-page">
			<!-- Page Header -->
			<div class="aicrmform-page-header-pro">
				<div class="aicrmform-page-header-content">
					<h1><?php esc_html_e( 'Submissions', 'ai-crm-form' ); ?></h1>
					<p class="aicrmform-page-subtitle"><?php esc_html_e( 'View and manage form submissions', 'ai-crm-form' ); ?></p>
				</div>
				<?php if ( ! empty( $submissions ) ) : ?>
				<div class="aicrmform-page-header-actions">
					<div class="aicrmform-export-dropdown">
						<button type="button" id="export-btn" class="button button-secondary button-large">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export', 'ai-crm-form' ); ?>
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
					<div class="aicrmform-dropdown-menu" id="export-menu" style="display: none;">
						<a href="#" id="export-csv" class="aicrmform-dropdown-item">
							<span class="dashicons dashicons-media-spreadsheet"></span>
							<?php esc_html_e( 'Export All (CSV)', 'ai-crm-form' ); ?>
						</a>
						<a href="#" id="export-csv-filtered" class="aicrmform-dropdown-item">
							<span class="dashicons dashicons-filter"></span>
							<?php esc_html_e( 'Export Filtered (CSV)', 'ai-crm-form' ); ?>
						</a>
						<a href="#" id="export-csv-selected" class="aicrmform-dropdown-item">
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Export Selected (CSV)', 'ai-crm-form' ); ?>
						</a>
					</div>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( empty( $submissions ) ) : ?>
				<!-- Empty State -->
				<div class="aicrmform-empty-state-pro">
					<div class="aicrmform-empty-state-inner">
						<div class="aicrmform-empty-state-icon">
							<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect x="10" y="16" width="60" height="48" rx="6" fill="#EEF2FF"/>
								<rect x="18" y="10" width="44" height="8" rx="4" fill="#C7D2FE"/>
								<circle cx="28" cy="40" r="8" fill="#A5B4FC"/>
								<rect x="42" y="34" width="20" height="4" rx="2" fill="#C7D2FE"/>
								<rect x="42" y="42" width="14" height="4" rx="2" fill="#C7D2FE"/>
								<path d="M24 52h32" stroke="#6366F1" stroke-width="2" stroke-linecap="round"/>
							</svg>
						</div>
						<h2><?php esc_html_e( 'No submissions yet', 'ai-crm-form' ); ?></h2>
						<p><?php esc_html_e( 'Once visitors start filling out your forms, their submissions will appear here. You can view details and track CRM sync status.', 'ai-crm-form' ); ?></p>
						<div class="aicrmform-empty-state-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-crm-form-forms' ) ); ?>" class="button button-secondary">
								<span class="dashicons dashicons-format-aside"></span>
								<?php esc_html_e( 'View Forms', 'ai-crm-form' ); ?>
							</a>
						</div>
					</div>
				</div>
			<?php else : ?>
				<!-- Stats Bar -->
				<div class="aicrmform-stats-bar">
					<div class="aicrmform-stat-item">
						<div class="aicrmform-stat-icon">
							<span class="dashicons dashicons-email-alt"></span>
						</div>
						<div class="aicrmform-stat-content">
							<span class="aicrmform-stat-value"><?php echo esc_html( $total_submissions ); ?></span>
							<span class="aicrmform-stat-label"><?php esc_html_e( 'Total Submissions', 'ai-crm-form' ); ?></span>
						</div>
					</div>
					<div class="aicrmform-stat-item">
						<div class="aicrmform-stat-icon aicrmform-stat-icon-success">
							<span class="dashicons dashicons-yes-alt"></span>
						</div>
						<div class="aicrmform-stat-content">
							<span class="aicrmform-stat-value"><?php echo esc_html( $success_count ); ?></span>
							<span class="aicrmform-stat-label"><?php esc_html_e( 'Synced to CRM', 'ai-crm-form' ); ?></span>
						</div>
					</div>
				<?php if ( $pending_count > 0 ) : ?>
				<div class="aicrmform-stat-item">
					<div class="aicrmform-stat-icon aicrmform-stat-icon-warning">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<div class="aicrmform-stat-content">
						<span class="aicrmform-stat-value"><?php echo esc_html( $pending_count ); ?></span>
						<span class="aicrmform-stat-label"><?php esc_html_e( 'Pending', 'ai-crm-form' ); ?></span>
					</div>
				</div>
				<?php endif; ?>
				<?php if ( $failed_count > 0 ) : ?>
				<div class="aicrmform-stat-item">
					<div class="aicrmform-stat-icon aicrmform-stat-icon-error">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="aicrmform-stat-content">
						<span class="aicrmform-stat-value"><?php echo esc_html( $failed_count ); ?></span>
						<span class="aicrmform-stat-label"><?php esc_html_e( 'Failed', 'ai-crm-form' ); ?></span>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<!-- Filters -->
			<div class="aicrmform-filters-bar">
				<div class="aicrmform-filter-group">
					<label for="filter-status"><?php esc_html_e( 'Status', 'ai-crm-form' ); ?></label>
					<select id="filter-status" class="aicrmform-filter-select">
						<option value=""><?php esc_html_e( 'All Statuses', 'ai-crm-form' ); ?></option>
						<option value="success"><?php esc_html_e( 'Success', 'ai-crm-form' ); ?></option>
						<option value="sent"><?php esc_html_e( 'Sent', 'ai-crm-form' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending', 'ai-crm-form' ); ?></option>
						<option value="failed"><?php esc_html_e( 'Failed', 'ai-crm-form' ); ?></option>
					</select>
				</div>
				<div class="aicrmform-filter-group">
					<label for="filter-form"><?php esc_html_e( 'Form', 'ai-crm-form' ); ?></label>
					<select id="filter-form" class="aicrmform-filter-select">
						<option value=""><?php esc_html_e( 'All Forms', 'ai-crm-form' ); ?></option>
						<?php foreach ( $forms as $form ) : ?>
						<option value="<?php echo esc_attr( $form->id ); ?>"><?php echo esc_html( $form->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="aicrmform-filter-group">
					<label for="filter-date-from"><?php esc_html_e( 'From', 'ai-crm-form' ); ?></label>
					<input type="date" id="filter-date-from" class="aicrmform-filter-input">
				</div>
				<div class="aicrmform-filter-group">
					<label for="filter-date-to"><?php esc_html_e( 'To', 'ai-crm-form' ); ?></label>
					<input type="date" id="filter-date-to" class="aicrmform-filter-input">
				</div>
				<div class="aicrmform-filter-actions">
					<button type="button" id="apply-filters" class="button button-primary">
						<span class="dashicons dashicons-filter"></span>
						<?php esc_html_e( 'Apply', 'ai-crm-form' ); ?>
					</button>
					<button type="button" id="clear-filters" class="button button-secondary">
						<?php esc_html_e( 'Clear', 'ai-crm-form' ); ?>
					</button>
				</div>
			</div>

			<!-- Submissions Table -->
			<div class="aicrmform-card">
				<div class="aicrmform-card-header">
					<h2><?php esc_html_e( 'Submissions', 'ai-crm-form' ); ?></h2>
					<span class="aicrmform-results-count" id="results-count">
						<?php
						/* translators: %d: number of submissions */
						printf( esc_html__( 'Showing %d submissions', 'ai-crm-form' ), $total_submissions );
						?>
					</span>
				</div>
				<div class="aicrmform-table-container">
					<table class="aicrmform-table" id="submissions-table">
						<thead>
							<tr>
								<th class="aicrmform-th-checkbox">
									<input type="checkbox" id="select-all-submissions" title="<?php esc_attr_e( 'Select All', 'ai-crm-form' ); ?>">
								</th>
								<th class="aicrmform-th-id"><?php esc_html_e( 'ID', 'ai-crm-form' ); ?></th>
								<th><?php esc_html_e( 'Form', 'ai-crm-form' ); ?></th>
								<th><?php esc_html_e( 'Status', 'ai-crm-form' ); ?></th>
								<th><?php esc_html_e( 'IP Address', 'ai-crm-form' ); ?></th>
								<th><?php esc_html_e( 'Submitted', 'ai-crm-form' ); ?></th>
								<th class="aicrmform-th-actions"><?php esc_html_e( 'Actions', 'ai-crm-form' ); ?></th>
							</tr>
						</thead>
						<tbody id="submissions-tbody">
							<?php
							foreach ( $submissions as $submission ) :
								$status_class = 'success' === $submission->status || 'sent' === $submission->status ? 'success' : ( 'pending' === $submission->status ? 'warning' : 'error' );
								$form_name    = $submission->form_id;
								// Try to get actual form name.
								foreach ( $forms as $form ) {
									if ( (string) $form->id === (string) $submission->form_id ) {
										$form_name = $form->name;
										break;
									}
								}
								?>
								<tr class="submission-row" 
									data-status="<?php echo esc_attr( $submission->status ); ?>" 
									data-form-id="<?php echo esc_attr( $submission->form_id ); ?>"
									data-date="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( $submission->created_at ) ) ); ?>">
									<td class="aicrmform-td-checkbox">
										<input type="checkbox" class="submission-checkbox" value="<?php echo esc_attr( $submission->id ); ?>">
									</td>
									<td class="aicrmform-td-id">
										<span class="aicrmform-submission-id">#<?php echo esc_html( $submission->id ); ?></span>
									</td>
									<td>
										<span class="aicrmform-form-name"><?php echo esc_html( $form_name ); ?></span>
									</td>
									<td>
										<span class="aicrmform-status-pill <?php echo esc_attr( $status_class ); ?>">
											<?php if ( 'success' === $status_class ) : ?>
												<span class="dashicons dashicons-yes"></span>
											<?php elseif ( 'warning' === $status_class ) : ?>
												<span class="dashicons dashicons-clock"></span>
											<?php else : ?>
												<span class="dashicons dashicons-warning"></span>
											<?php endif; ?>
											<?php echo esc_html( ucfirst( $submission->status ) ); ?>
										</span>
									</td>
									<td>
										<span class="aicrmform-ip"><?php echo esc_html( $submission->ip_address ); ?></span>
									</td>
									<td>
										<span class="aicrmform-date"><?php echo esc_html( gmdate( 'M j, Y', strtotime( $submission->created_at ) ) ); ?></span>
										<span class="aicrmform-time"><?php echo esc_html( gmdate( 'g:i A', strtotime( $submission->created_at ) ) ); ?></span>
									</td>
									<td class="aicrmform-td-actions">
										<button type="button" class="aicrmform-action-btn aicrmform-view-submission" data-submission-id="<?php echo esc_attr( $submission->id ); ?>" title="<?php esc_attr_e( 'View Details', 'ai-crm-form' ); ?>">
											<span class="dashicons dashicons-visibility"></span>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) : ?>
				<div class="aicrmform-pagination">
					<div class="aicrmform-pagination-info">
						<?php
						$start = ( ( $current_page - 1 ) * $per_page ) + 1;
						$end   = min( $current_page * $per_page, $total_submissions );
						/* translators: 1: start number, 2: end number, 3: total submissions */
						printf( esc_html__( 'Showing %1$d-%2$d of %3$d', 'ai-crm-form' ), $start, $end, $total_submissions );
						?>
					</div>
					<div class="aicrmform-pagination-controls" id="pagination-controls">
						<button type="button" class="aicrmform-page-btn" data-page="1" <?php disabled( $current_page, 1 ); ?>>
							<span class="dashicons dashicons-controls-skipback"></span>
						</button>
						<button type="button" class="aicrmform-page-btn" data-page="<?php echo esc_attr( max( 1, $current_page - 1 ) ); ?>" <?php disabled( $current_page, 1 ); ?>>
							<span class="dashicons dashicons-arrow-left-alt2"></span>
						</button>
						<span class="aicrmform-page-info">
							<?php
							/* translators: 1: current page, 2: total pages */
							printf( esc_html__( 'Page %1$d of %2$d', 'ai-crm-form' ), $current_page, $total_pages );
							?>
						</span>
						<button type="button" class="aicrmform-page-btn" data-page="<?php echo esc_attr( min( $total_pages, $current_page + 1 ) ); ?>" <?php disabled( $current_page, $total_pages ); ?>>
							<span class="dashicons dashicons-arrow-right-alt2"></span>
						</button>
						<button type="button" class="aicrmform-page-btn" data-page="<?php echo esc_attr( $total_pages ); ?>" <?php disabled( $current_page, $total_pages ); ?>>
							<span class="dashicons dashicons-controls-skipforward"></span>
						</button>
					</div>
				</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		</div>

		<!-- Toast Notification -->
		<div id="aicrmform-toast" class="aicrmform-toast"></div>
		<?php
	}
}
