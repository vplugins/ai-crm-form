<?php
/**
 * Form Shortcode
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Shortcode Class
 */
class AICRMFORM_Form_Shortcode {

	/**
	 * Register shortcodes.
	 */
	public function register() {
		add_shortcode( 'ai_crm_form', [ $this, 'render_form' ] );

		// Check if we have CF7 shortcode mappings.
		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );
		$has_cf7_maps  = false;
		foreach ( array_keys( $shortcode_map ) as $key ) {
			if ( strpos( $key, 'cf7_' ) === 0 ) {
				$has_cf7_maps = true;
				break;
			}
		}

		if ( $has_cf7_maps ) {
			// If CF7 is active, intercept its shortcode output.
			if ( class_exists( 'WPCF7_ContactForm' ) ) {
				add_filter( 'do_shortcode_tag', [ $this, 'intercept_cf7_shortcode' ], 10, 4 );
			} else {
				// If CF7 is NOT active, register the shortcode ourselves.
				add_shortcode( 'contact-form-7', [ $this, 'render_cf7_replacement' ] );
				add_shortcode( 'contact-form', [ $this, 'render_cf7_replacement' ] );
			}
		}
	}

	/**
	 * Render replacement for CF7 shortcode when CF7 is deactivated.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag     Shortcode tag.
	 * @return string The form HTML or empty string.
	 */
	public function render_cf7_replacement( $atts, $content = '', $tag = '' ) {
		$atts = shortcode_atts(
			[
				'id'    => '',
				'title' => '',
			],
			$atts,
			$tag
		);

		// Get the CF7 form ID from the shortcode.
		$cf7_id = $atts['id'];

		if ( empty( $cf7_id ) ) {
			return $this->get_cf7_not_found_message( $cf7_id );
		}

		// Find our mapped form.
		$our_form_id = $this->get_mapped_form_id( $cf7_id );

		if ( ! $our_form_id ) {
			return $this->get_cf7_not_found_message( $cf7_id );
		}

		return $this->render_form( [ 'id' => $our_form_id ] );
	}

	/**
	 * Get message when CF7 form is not found.
	 *
	 * @param string $cf7_id The CF7 form ID.
	 * @return string Message HTML (only for admins).
	 */
	private function get_cf7_not_found_message( $cf7_id ) {
		// Only show message to admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );
		$debug_info    = 'Looking for: cf7_' . $cf7_id . ' or cf7_hash_' . $cf7_id;
		$debug_info   .= ' | Available mappings: ' . implode( ', ', array_keys( $shortcode_map ) );

		return '<div class="aicrmform-admin-notice" style="background: #fef3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 10px 0;">'
			. '<strong>AI CRM Form:</strong> No imported form found for Contact Form 7 ID "' . esc_html( $cf7_id ) . '".<br>'
			. '<small style="color: #666;">Re-import the form with "Use same shortcode" checked. ' . esc_html( $debug_info ) . '</small>'
			. '</div>';
	}

	/**
	 * Get our form ID from CF7 shortcode ID attribute.
	 *
	 * @param string $cf7_id The ID from the shortcode (can be post ID or hash).
	 * @return int|false Our form ID or false.
	 */
	private function get_mapped_form_id( $cf7_id ) {
		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );

		// 1. Check direct mapping: cf7_{id}
		$map_key = 'cf7_' . $cf7_id;
		if ( ! empty( $shortcode_map[ $map_key ] ) ) {
			return (int) $shortcode_map[ $map_key ];
		}

		// 2. Check hash mapping: cf7_hash_{id}
		$hash_key = 'cf7_hash_' . $cf7_id;
		if ( ! empty( $shortcode_map[ $hash_key ] ) ) {
			return (int) $shortcode_map[ $hash_key ];
		}

		// 3. If it's a hash (not numeric), try to find the post ID from database.
		if ( ! is_numeric( $cf7_id ) ) {
			global $wpdb;

			// CF7 stores a hash in _hash meta key.
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_hash' AND meta_value = %s LIMIT 1",
					$cf7_id
				)
			);

			if ( $post_id ) {
				$map_key = 'cf7_' . $post_id;
				if ( ! empty( $shortcode_map[ $map_key ] ) ) {
					return (int) $shortcode_map[ $map_key ];
				}
			}
		}

		// 4. Last resort: Check if any mapping has this form (by iterating).
		// This handles cases where the form was imported before hash support.
		foreach ( $shortcode_map as $key => $form_id ) {
			if ( strpos( $key, 'cf7_' ) === 0 ) {
				// We found a CF7 mapping - return the first one as fallback.
				// This is not ideal but helps with single-form imports.
				if ( count( array_filter( array_keys( $shortcode_map ), fn( $k ) => strpos( $k, 'cf7_' ) === 0 ) ) === 1 ) {
					return (int) $form_id;
				}
			}
		}

		return false;
	}

	/**
	 * Intercept Contact Form 7 shortcode and render our form if imported.
	 *
	 * @param string $output Shortcode output.
	 * @param string $tag    Shortcode name.
	 * @param array  $attr   Shortcode attributes.
	 * @param array  $m      Regular expression match array.
	 * @return string Modified output.
	 */
	public function intercept_cf7_shortcode( $output, $tag, $attr, $m ) {
		// Only intercept contact-form-7 shortcode.
		if ( 'contact-form-7' !== $tag && 'contact-form' !== $tag ) {
			return $output;
		}

		if ( empty( $attr['id'] ) ) {
			return $output;
		}

		// Find our mapped form.
		$our_form_id = $this->get_mapped_form_id( $attr['id'] );

		if ( ! $our_form_id ) {
			return $output;
		}

		// Render our form instead.
		return $this->render_form( [ 'id' => $our_form_id ] );
	}

	/**
	 * Render form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The form HTML.
	 */
	public function render_form( $atts ) {
		$atts = shortcode_atts(
			[
				'id'    => 0,
				'class' => '',
				'style' => '',
			],
			$atts,
			'ai_crm_form'
		);

		$form_id = (int) $atts['id'];

		if ( ! $form_id ) {
			return '<p class="aicrmform-error">' . esc_html__( 'Form ID is required.', 'ai-crm-form' ) . '</p>';
		}

		// Get the form.
		$generator = new AICRMFORM_Form_Generator();
		$form      = $generator->get_form( $form_id );

		if ( ! $form ) {
			return '<p class="aicrmform-error">' . esc_html__( 'Form not found.', 'ai-crm-form' ) . '</p>';
		}

		if ( 'active' !== $form->status ) {
			return '<p class="aicrmform-error">' . esc_html__( 'This form is not active.', 'ai-crm-form' ) . '</p>';
		}

		// Generate the form HTML.
		$form_config = $form->form_config;

		// Build custom styles.
		$custom_styles = $this->generate_custom_styles( $form_id, $form_config );

		// Build wrapper classes.
		$wrapper_class = 'aicrmform-wrapper aicrmform-wrapper-' . $form_id;
		if ( ! empty( $atts['class'] ) ) {
			$wrapper_class .= ' ' . sanitize_html_class( $atts['class'] );
		}

		// Add style-based classes.
		$styles = $form_config['styles'] ?? [];
		if ( ! empty( $styles['label_position'] ) ) {
			$wrapper_class .= ' aicrmform-labels-' . sanitize_html_class( $styles['label_position'] );
		}
		if ( ! empty( $styles['field_spacing'] ) ) {
			$wrapper_class .= ' aicrmform-spacing-' . sanitize_html_class( $styles['field_spacing'] );
		}
		if ( ! empty( $styles['button_style'] ) ) {
			$wrapper_class .= ' aicrmform-button-' . sanitize_html_class( $styles['button_style'] );
		}
		if ( ! empty( $styles['button_width'] ) && 'full' === $styles['button_width'] ) {
			$wrapper_class .= ' aicrmform-button-full';
		}

		// Build wrapper styles.
		$wrapper_style = '';
		if ( ! empty( $atts['style'] ) ) {
			$wrapper_style = ' style="' . esc_attr( $atts['style'] ) . '"';
		}

		$html = '';

		// Load Google Font if specified for this form.
		$font_family = $styles['font_family'] ?? '';
		if ( ! empty( $font_family ) ) {
			$font_slug = str_replace( ' ', '+', $font_family );
			$html     .= '<link href="https://fonts.googleapis.com/css2?family=' . esc_attr( $font_slug ) . ':wght@400;500;600;700&display=swap" rel="stylesheet">';
		}

		// Add custom styles.
		if ( ! empty( $custom_styles ) ) {
			$html .= '<style>' . $custom_styles . '</style>';
		}

		$html .= '<div class="' . esc_attr( $wrapper_class ) . '"' . $wrapper_style . ' data-form-id="' . esc_attr( $form_id ) . '">';

		// Add form title if set.
		if ( ! empty( $form_config['form_name'] ) ) {
			$html .= '<h3 class="aicrmform-title">' . esc_html( $form_config['form_name'] ) . '</h3>';
		}

		// Add form description if set.
		if ( ! empty( $form_config['form_description'] ) ) {
			$html .= '<p class="aicrmform-description">' . esc_html( $form_config['form_description'] ) . '</p>';
		}

		// Build the form.
		$html .= '<form class="aicrmform-form" data-form-id="' . esc_attr( $form_id ) . '">';

		// Add hidden field for form ID.
		$html .= '<input type="hidden" name="form_id" value="' . esc_attr( $form_id ) . '">';

		// Render fields.
		$fields = $form_config['fields'] ?? [];
		foreach ( $fields as $field ) {
			$html .= $this->render_field( $field );
		}

		// Add submit button.
		$submit_text = $form_config['submit_button_text'] ?? __( 'Submit', 'ai-crm-form' );
		$html       .= '<div class="aicrmform-field aicrmform-submit">';
		$html       .= '<button type="submit" class="aicrmform-button">' . esc_html( $submit_text ) . '</button>';
		$html       .= '<span class="aicrmform-spinner" style="display: none;"></span>';
		$html       .= '</div>';

		$html .= '</form>';

		// Get messages from form config or defaults.
		$settings        = get_option( 'aicrmform_settings', [] );
		$success_message = $form_config['success_message'] ?? ( $settings['default_success_message'] ?? __( 'Thank you for your submission!', 'ai-crm-form' ) );
		$error_message   = $form_config['error_message'] ?? ( $settings['default_error_message'] ?? __( 'Something went wrong. Please try again.', 'ai-crm-form' ) );

		// Add success message container.
		$html .= '<div class="aicrmform-success" style="display: none;" data-message="' . esc_attr( $success_message ) . '">';
		$html .= '<div class="aicrmform-success-icon">✓</div>';
		$html .= '<p>' . esc_html( $success_message ) . '</p>';
		$html .= '</div>';

		// Add error message container.
		$html .= '<div class="aicrmform-error-message" style="display: none;" data-default-message="' . esc_attr( $error_message ) . '"></div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate custom CSS styles for a form.
	 *
	 * @param int   $form_id     The form ID.
	 * @param array $form_config The form configuration.
	 * @return string The CSS styles.
	 */
	private function generate_custom_styles( $form_id, $form_config ) {
		$styles     = $form_config['styles'] ?? [];
		$custom_css = $form_config['custom_css'] ?? '';
		$settings   = get_option( 'aicrmform_settings', [] );
		$css        = '';

		// Get default styling from settings.
		$font_family       = $styles['font_family'] ?? $settings['default_font_family'] ?? '';
		$font_size         = $styles['font_size'] ?? $settings['default_font_size'] ?? '16px';
		$background_color  = $styles['background_color'] ?? $settings['default_background_color'] ?? '#ffffff';

		$selector = '.aicrmform-wrapper-' . $form_id;

		// Font family from Google Fonts.
		if ( ! empty( $font_family ) ) {
			$css .= $selector . ' { font-family: "' . esc_attr( $font_family ) . '", sans-serif !important; }';
			$css .= $selector . ' *, ' . $selector . ' input, ' . $selector . ' select, ' . $selector . ' textarea, ' . $selector . ' button { font-family: inherit !important; }';
		}

		// Font size.
		if ( ! empty( $font_size ) ) {
			$css .= $selector . ' { font-size: ' . esc_attr( $font_size ) . ' !important; }';
			$css .= $selector . ' .aicrmform-field label { font-size: ' . esc_attr( $font_size ) . ' !important; }';
			$css .= $selector . ' .aicrmform-field input, ' . $selector . ' .aicrmform-field select, ' . $selector . ' .aicrmform-field textarea { font-size: ' . esc_attr( $font_size ) . ' !important; }';
		}

		// Form width.
		if ( ! empty( $styles['form_width'] ) ) {
			$css .= $selector . ' .aicrmform-form { max-width: ' . esc_attr( $styles['form_width'] ) . '; }';
		}

		// Background color.
		if ( ! empty( $background_color ) && '#ffffff' !== $background_color ) {
			$css .= $selector . ' .aicrmform-form { background-color: ' . esc_attr( $background_color ) . ' !important; padding: 24px !important; border-radius: 8px !important; }';
		}

		// Text color.
		if ( ! empty( $styles['text_color'] ) && '#333333' !== $styles['text_color'] ) {
			$css .= $selector . ' { color: ' . esc_attr( $styles['text_color'] ) . ' !important; }';
			$css .= $selector . ' .aicrmform-field label { color: ' . esc_attr( $styles['text_color'] ) . ' !important; }';
		}

		// Border color.
		if ( ! empty( $styles['border_color'] ) && '#dddddd' !== $styles['border_color'] ) {
			$css .= $selector . ' .aicrmform-field input, ' . $selector . ' .aicrmform-field select, ' . $selector . ' .aicrmform-field textarea { border-color: ' . esc_attr( $styles['border_color'] ) . ' !important; }';
		}

		// Border radius.
		if ( ! empty( $styles['border_radius'] ) && '4px' !== $styles['border_radius'] ) {
			$css .= $selector . ' .aicrmform-field input, ' . $selector . ' .aicrmform-field select, ' . $selector . ' .aicrmform-field textarea, ' . $selector . ' .aicrmform-button { border-radius: ' . esc_attr( $styles['border_radius'] ) . ' !important; }';
		}

		// Primary/Button color.
		if ( ! empty( $styles['primary_color'] ) && '#0073aa' !== $styles['primary_color'] ) {
			$css .= $selector . ' .aicrmform-button { background-color: ' . esc_attr( $styles['primary_color'] ) . ' !important; border-color: ' . esc_attr( $styles['primary_color'] ) . ' !important; }';
			$css .= $selector . ' .aicrmform-button:hover { background-color: ' . $this->adjust_brightness( $styles['primary_color'], -20 ) . ' !important; }';
			$css .= $selector . ' .aicrmform-field input:focus, ' . $selector . ' .aicrmform-field select:focus, ' . $selector . ' .aicrmform-field textarea:focus { border-color: ' . esc_attr( $styles['primary_color'] ) . ' !important; box-shadow: 0 0 0 3px ' . $this->hex_to_rgba( $styles['primary_color'], 0.15 ) . ' !important; }';
		}

		// Append custom CSS (sanitized).
		if ( ! empty( $custom_css ) ) {
			// Replace .aicrmform-form with scoped selector.
			$scoped_css = str_replace( '.aicrmform-form', $selector . ' .aicrmform-form', $custom_css );
			$css       .= wp_strip_all_tags( $scoped_css );
		}

		return $css;
	}

	/**
	 * Adjust hex color brightness.
	 *
	 * @param string $hex    Hex color.
	 * @param int    $steps  Steps to adjust (-255 to 255).
	 * @return string Adjusted hex color.
	 */
	private function adjust_brightness( $hex, $steps ) {
		$hex = ltrim( $hex, '#' );

		$r = max( 0, min( 255, hexdec( substr( $hex, 0, 2 ) ) + $steps ) );
		$g = max( 0, min( 255, hexdec( substr( $hex, 2, 2 ) ) + $steps ) );
		$b = max( 0, min( 255, hexdec( substr( $hex, 4, 2 ) ) + $steps ) );

		return '#' . sprintf( '%02x%02x%02x', $r, $g, $b );
	}

	/**
	 * Convert hex to rgba.
	 *
	 * @param string $hex   Hex color.
	 * @param float  $alpha Alpha value (0-1).
	 * @return string RGBA color.
	 */
	private function hex_to_rgba( $hex, $alpha = 1 ) {
		$hex = ltrim( $hex, '#' );

		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		return "rgba({$r}, {$g}, {$b}, {$alpha})";
	}

	/**
	 * Render a single field.
	 *
	 * @param array $field The field configuration.
	 * @return string The field HTML.
	 */
	private function render_field( $field ) {
		$name        = esc_attr( $field['name'] ?? '' );
		$field_id    = esc_attr( $field['field_id'] ?? $name );
		$label       = esc_html( $field['label'] ?? ucfirst( str_replace( '_', ' ', $name ) ) );
		$type        = $field['type'] ?? 'text';
		$required    = ! empty( $field['required'] );
		$placeholder = esc_attr( $field['placeholder'] ?? '' );
		$req_attr    = $required ? 'required' : '';
		$req_mark    = $required ? '<span class="aicrmform-required">*</span>' : '';

		$html  = '<div class="aicrmform-field aicrmform-field-' . esc_attr( $type ) . '">';
		$html .= '<label for="aicrmform-' . $name . '">' . $label . $req_mark . '</label>';

		switch ( $type ) {
			case 'textarea':
				$html .= '<textarea id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '" ' . $req_attr . ' rows="4"></textarea>';
				break;

			case 'select':
				$html   .= '<select id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" ' . $req_attr . '>';
				$html   .= '<option value="">' . esc_html__( 'Select...', 'ai-crm-form' ) . '</option>';
				$options = $field['options'] ?? [];
				foreach ( $options as $option ) {
					$html .= '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
				}
				$html .= '</select>';
				break;

			case 'checkbox':
				$options = $field['options'] ?? [ $label ];
				$html   .= '<div class="aicrmform-checkbox-group">';
				foreach ( $options as $index => $option ) {
					$option_id = $name . '-' . $index;
					$html     .= '<label class="aicrmform-checkbox-label" for="aicrmform-' . esc_attr( $option_id ) . '">';
					$html     .= '<input type="checkbox" id="aicrmform-' . esc_attr( $option_id ) . '" name="' . $name . '[]" value="' . esc_attr( $option ) . '" data-field-id="' . $field_id . '">';
					$html     .= '<span>' . esc_html( $option ) . '</span>';
					$html     .= '</label>';
				}
				$html .= '</div>';
				break;

			case 'radio':
				$options = $field['options'] ?? [];
				$html   .= '<div class="aicrmform-radio-group">';
				foreach ( $options as $index => $option ) {
					$option_id = $name . '-' . $index;
					$html     .= '<label class="aicrmform-radio-label" for="aicrmform-' . esc_attr( $option_id ) . '">';
					$html     .= '<input type="radio" id="aicrmform-' . esc_attr( $option_id ) . '" name="' . $name . '" value="' . esc_attr( $option ) . '" data-field-id="' . $field_id . '" ' . $req_attr . '>';
					$html     .= '<span>' . esc_html( $option ) . '</span>';
					$html     .= '</label>';
				}
				$html .= '</div>';
				break;

			case 'email':
				$html .= '<input type="email" id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '" ' . $req_attr . '>';
				break;

			case 'tel':
				$html .= '<input type="tel" id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '" ' . $req_attr . '>';
				break;

			case 'number':
				$min   = isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
				$max   = isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
				$html .= '<input type="number" id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '"' . $min . $max . ' ' . $req_attr . '>';
				break;

			case 'date':
				$html .= '<input type="date" id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" ' . $req_attr . '>';
				break;

			case 'url':
				$html .= '<input type="url" id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '" ' . $req_attr . '>';
				break;

			case 'hidden':
				$value = esc_attr( $field['value'] ?? '' );
				$html  = '<input type="hidden" name="' . $name . '" value="' . $value . '" data-field-id="' . $field_id . '">';
				return $html; // Return early, no wrapper needed.

			default: // text.
				$html .= '<input type="text" id="aicrmform-' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '" ' . $req_attr . '>';
				break;
		}

		$html .= '</div>';

		return $html;
	}
}

