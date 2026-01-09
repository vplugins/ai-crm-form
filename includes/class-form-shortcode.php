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
	 * Shortcode configurations for different form plugins.
	 *
	 * @var array
	 */
	private $plugin_shortcodes = [
		'cf7'     => [
			'tags'        => [ 'contact-form-7', 'contact-form' ],
			'check_class' => 'WPCF7_ContactForm',
			'id_attr'     => 'id',
		],
		'gravity' => [
			'tags'        => [ 'gravityform', 'gravityforms' ],
			'check_class' => 'GFForms',
			'id_attr'     => 'id',
		],
		'wpforms' => [
			'tags'        => [ 'wpforms' ],
			'check_class' => 'WPForms',
			'id_attr'     => 'id',
		],
	];

	/**
	 * Register shortcodes.
	 */
	public function register() {
		add_shortcode( 'ai_crm_form', [ $this, 'render_form' ] );

		// Clean up any stale mappings first.
		$this->cleanup_all_stale_mappings();

		// Get shortcode mappings.
		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );

		if ( empty( $shortcode_map ) ) {
			return;
		}

		// Register interceptors for each plugin type.
		foreach ( $this->plugin_shortcodes as $plugin_key => $config ) {
			// Check if we have mappings for this plugin.
			$has_maps = false;
			foreach ( array_keys( $shortcode_map ) as $key ) {
				if ( strpos( $key, $plugin_key . '_' ) === 0 ) {
					$has_maps = true;
					break;
				}
			}

			if ( ! $has_maps ) {
				continue;
			}

			// If the source plugin is active, intercept its shortcode output.
			if ( class_exists( $config['check_class'] ) ) {
				add_filter( 'do_shortcode_tag', [ $this, 'intercept_plugin_shortcode' ], 10, 4 );
			} else {
				// If the source plugin is NOT active, register the shortcode ourselves.
				foreach ( $config['tags'] as $tag ) {
					add_shortcode( $tag, [ $this, 'render_plugin_replacement' ] );
				}
			}
		}
	}

	/**
	 * Clean up all stale mappings where our forms no longer exist.
	 */
	private function cleanup_all_stale_mappings() {
		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );

		if ( empty( $shortcode_map ) ) {
			return;
		}

		$generator = new AICRMFORM_Form_Generator();
		$changed   = false;

		foreach ( $shortcode_map as $key => $form_id ) {
			// Check if our form still exists.
			$form = $generator->get_form( (int) $form_id );
			if ( ! $form ) {
				unset( $shortcode_map[ $key ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( 'aicrmform_shortcode_map', $shortcode_map );
		}
	}

	/**
	 * Render replacement for plugin shortcode when source plugin is deactivated.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag     Shortcode tag.
	 * @return string The form HTML or empty string.
	 */
	public function render_plugin_replacement( $atts, $content = '', $tag = '' ) {
		$atts = shortcode_atts(
			[
				'id'    => '',
				'title' => '',
			],
			$atts,
			$tag
		);

		// Determine plugin type from tag.
		$plugin_key = $this->get_plugin_key_from_tag( $tag );

		if ( ! $plugin_key ) {
			return '';
		}

		// Get the form ID from the shortcode.
		$source_id = $atts['id'];

		if ( empty( $source_id ) ) {
			return $this->get_form_not_found_message( $plugin_key, $source_id );
		}

		// Find our mapped form.
		$our_form_id = $this->get_mapped_form_id( $plugin_key, $source_id );

		if ( ! $our_form_id ) {
			return $this->get_form_not_found_message( $plugin_key, $source_id );
		}

		// Check if our form actually exists before rendering.
		$generator = new AICRMFORM_Form_Generator();
		$form      = $generator->get_form( $our_form_id );

		if ( ! $form ) {
			// Our form was deleted - clean up the stale mapping.
			$this->cleanup_stale_mapping( $plugin_key, $source_id );
			return $this->get_form_not_found_message( $plugin_key, $source_id );
		}

		return $this->render_form( [ 'id' => $our_form_id ] );
	}

	/**
	 * Legacy method for CF7 replacement (backward compatibility).
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag     Shortcode tag.
	 * @return string The form HTML or empty string.
	 */
	public function render_cf7_replacement( $atts, $content = '', $tag = '' ) {
		return $this->render_plugin_replacement( $atts, $content, $tag );
	}

	/**
	 * Get plugin key from shortcode tag.
	 *
	 * @param string $tag Shortcode tag.
	 * @return string|null Plugin key or null.
	 */
	private function get_plugin_key_from_tag( $tag ) {
		foreach ( $this->plugin_shortcodes as $plugin_key => $config ) {
			if ( in_array( $tag, $config['tags'], true ) ) {
				return $plugin_key;
			}
		}
		return null;
	}

	/**
	 * Get plugin name from plugin key.
	 *
	 * @param string $plugin_key Plugin key.
	 * @return string Plugin display name.
	 */
	private function get_plugin_name( $plugin_key ) {
		$names = [
			'cf7'     => 'Contact Form 7',
			'gravity' => 'Gravity Forms',
			'wpforms' => 'WPForms',
		];
		return $names[ $plugin_key ] ?? ucfirst( $plugin_key );
	}

	/**
	 * Get message when form is not found (only when source plugin is deactivated).
	 *
	 * @param string $plugin_key Plugin key.
	 * @param string $source_id  The source form ID.
	 * @return string Message HTML (only for admins).
	 */
	private function get_form_not_found_message( $plugin_key, $source_id ) {
		// Only show message to admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );
		$plugin_name   = $this->get_plugin_name( $plugin_key );

		// If no mappings exist at all, show a helpful message.
		if ( empty( $shortcode_map ) ) {
			return '<div class="aicrmform-admin-notice" style="background: #fef3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 10px 0;">'
				. '<strong>AI CRM Form:</strong> ' . esc_html( $plugin_name ) . ' is deactivated and this form has not been imported.<br>'
				. '<small style="color: #666;">Activate ' . esc_html( $plugin_name ) . ' or import this form using AI CRM Form\'s Import feature.</small>'
				. '</div>';
		}

		$debug_info = 'Looking for: ' . $plugin_key . '_' . $source_id;

		return '<div class="aicrmform-admin-notice" style="background: #fef3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 10px 0;">'
			. '<strong>AI CRM Form:</strong> No imported form found for ' . esc_html( $plugin_name ) . ' ID "' . esc_html( $source_id ) . '".<br>'
			. '<small style="color: #666;">Re-import the form with "Use same shortcode" checked. ' . esc_html( $debug_info ) . '</small>'
			. '</div>';
	}

	/**
	 * Legacy method for CF7 not found message (backward compatibility).
	 *
	 * @param string $cf7_id The CF7 form ID.
	 * @return string Message HTML (only for admins).
	 */
	private function get_cf7_not_found_message( $cf7_id ) {
		return $this->get_form_not_found_message( 'cf7', $cf7_id );
	}

	/**
	 * Get our form ID from source plugin shortcode ID attribute.
	 *
	 * @param string $plugin_key Plugin key (e.g., 'cf7', 'gravity').
	 * @param string $source_id  The ID from the shortcode (can be post ID or hash).
	 * @return int|false Our form ID or false.
	 */
	private function get_mapped_form_id( $plugin_key, $source_id = null ) {
		// Handle legacy single-argument calls.
		if ( null === $source_id ) {
			$source_id  = $plugin_key;
			$plugin_key = 'cf7';
		}

		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );

		// 1. Check direct mapping: {plugin_key}_{id}.
		$map_key = $plugin_key . '_' . $source_id;
		if ( ! empty( $shortcode_map[ $map_key ] ) ) {
			return (int) $shortcode_map[ $map_key ];
		}

		// 2. Check hash mapping: {plugin_key}_hash_{id}.
		$hash_key = $plugin_key . '_hash_' . $source_id;
		if ( ! empty( $shortcode_map[ $hash_key ] ) ) {
			return (int) $shortcode_map[ $hash_key ];
		}

		// 3. Check if shortcode ID is a PREFIX of a stored hash.
		// CF7 shortcodes use short hash (7 chars) but we store full hash.
		$hash_prefix = $plugin_key . '_hash_';
		foreach ( $shortcode_map as $key => $form_id ) {
			if ( strpos( $key, $hash_prefix ) === 0 ) {
				$stored_hash = substr( $key, strlen( $hash_prefix ) );
				if ( strpos( $stored_hash, $source_id ) === 0 ) {
					return (int) $form_id;
				}
			}
		}

		// 4. For CF7: If it's a hash (not numeric), try to find the post ID from database.
		if ( 'cf7' === $plugin_key && ! is_numeric( $source_id ) ) {
			global $wpdb;

			// CF7 stores a hash in _hash meta key - check for prefix match.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_hash' AND meta_value LIKE %s LIMIT 1",
					$source_id . '%'
				)
			);

			if ( $post_id ) {
				$map_key = $plugin_key . '_' . $post_id;
				if ( ! empty( $shortcode_map[ $map_key ] ) ) {
					return (int) $shortcode_map[ $map_key ];
				}
			}
		}

		// No mapping found.
		return false;
	}

	/**
	 * Intercept plugin shortcode and render our form if imported.
	 *
	 * @param string $output Shortcode output.
	 * @param string $tag    Shortcode name.
	 * @param array  $attr   Shortcode attributes.
	 * @param array  $m      Regular expression match array.
	 * @return string Modified output.
	 */
	public function intercept_plugin_shortcode( $output, $tag, $attr, $m ) {
		// Determine which plugin this shortcode belongs to.
		$plugin_key = $this->get_plugin_key_from_tag( $tag );

		if ( ! $plugin_key ) {
			return $output;
		}

		// Get the ID attribute.
		$id_attr  = $this->plugin_shortcodes[ $plugin_key ]['id_attr'] ?? 'id';
		$form_id = $attr[ $id_attr ] ?? '';

		if ( empty( $form_id ) ) {
			return $output;
		}

		// Find our mapped form.
		$our_form_id = $this->get_mapped_form_id( $plugin_key, $form_id );

		if ( ! $our_form_id ) {
			return $output;
		}

		// Check if our form actually exists before replacing.
		$generator = new AICRMFORM_Form_Generator();
		$form      = $generator->get_form( $our_form_id );

		if ( ! $form ) {
			// Our form was deleted - clean up the stale mapping and let original plugin handle it.
			$this->cleanup_stale_mapping( $plugin_key, $form_id );
			return $output;
		}

		// Render our form instead.
		return $this->render_form( [ 'id' => $our_form_id ] );
	}

	/**
	 * Legacy method for CF7 shortcode interception (backward compatibility).
	 *
	 * @param string $output Shortcode output.
	 * @param string $tag    Shortcode name.
	 * @param array  $attr   Shortcode attributes.
	 * @param array  $m      Regular expression match array.
	 * @return string Modified output.
	 */
	public function intercept_cf7_shortcode( $output, $tag, $attr, $m ) {
		return $this->intercept_plugin_shortcode( $output, $tag, $attr, $m );
	}

	/**
	 * Clean up stale mappings when our form no longer exists.
	 *
	 * @param string $plugin_key Plugin key.
	 * @param string $source_id  The source form ID.
	 */
	private function cleanup_stale_mapping( $plugin_key, $source_id = null ) {
		// Handle legacy single-argument calls.
		if ( null === $source_id ) {
			$source_id  = $plugin_key;
			$plugin_key = 'cf7';
		}

		$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );
		$changed       = false;

		// Remove any mappings for this form.
		$keys_to_remove = [
			$plugin_key . '_' . $source_id,
			$plugin_key . '_hash_' . $source_id,
		];

		foreach ( $keys_to_remove as $key ) {
			if ( isset( $shortcode_map[ $key ] ) ) {
				unset( $shortcode_map[ $key ] );
				$changed = true;
			}
		}

		// Also check for hash prefix matches.
		$hash_prefix = $plugin_key . '_hash_';
		foreach ( array_keys( $shortcode_map ) as $key ) {
			if ( strpos( $key, $hash_prefix ) === 0 ) {
				$stored_hash = substr( $key, strlen( $hash_prefix ) );
				if ( strpos( $stored_hash, $source_id ) === 0 ) {
					unset( $shortcode_map[ $key ] );
					$changed = true;
				}
			}
		}

		if ( $changed ) {
			update_option( 'aicrmform_shortcode_map', $shortcode_map );
		}
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
		
		// Check if theme styling is enabled.
		$use_theme_styling = isset( $styles['use_theme_styling'] ) && $styles['use_theme_styling'];
		if ( $use_theme_styling ) {
			$wrapper_class .= ' aicrmform-theme-styled';
		} else {
			// Enqueue the plugin stylesheet only when theme styling is NOT enabled.
			wp_enqueue_style( 'aicrmform-frontend' );
		}
		
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

		// Only load custom fonts and styles if theme styling is NOT enabled.
		if ( ! $use_theme_styling ) {
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

		// If "Use Theme Styling" is enabled, return empty CSS (only HTML will be rendered).
		$use_theme_styling = isset( $styles['use_theme_styling'] ) && $styles['use_theme_styling'];
		if ( $use_theme_styling ) {
			// Only return custom CSS if provided (user might still want to add minor tweaks).
			if ( ! empty( $custom_css ) ) {
				$selector = '.aicrmform-wrapper-' . $form_id;
				$css     .= str_replace( '.aicrmform-form', $selector . ' .aicrmform-form', $custom_css );
			}
			return $css;
		}

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
				$options = $this->normalize_options( $field['options'] ?? [] );
				foreach ( $options as $option ) {
					$html .= '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['label'] ) . '</option>';
				}
				$html .= '</select>';
				break;

			case 'checkbox':
				$options = $this->normalize_options( $field['options'] ?? [ $label ] );
				$html   .= '<div class="aicrmform-checkbox-group">';
				foreach ( $options as $index => $option ) {
					$option_id = $name . '-' . $index;
					$html     .= '<label class="aicrmform-checkbox-label" for="aicrmform-' . esc_attr( $option_id ) . '">';
					$html     .= '<input type="checkbox" id="aicrmform-' . esc_attr( $option_id ) . '" name="' . $name . '[]" value="' . esc_attr( $option['value'] ) . '" data-field-id="' . $field_id . '">';
					$html     .= '<span>' . esc_html( $option['label'] ) . '</span>';
					$html     .= '</label>';
				}
				$html .= '</div>';
				break;

			case 'radio':
				$options = $this->normalize_options( $field['options'] ?? [] );
				$html   .= '<div class="aicrmform-radio-group">';
				foreach ( $options as $index => $option ) {
					$option_id = $name . '-' . $index;
					$html     .= '<label class="aicrmform-radio-label" for="aicrmform-' . esc_attr( $option_id ) . '">';
					$html     .= '<input type="radio" id="aicrmform-' . esc_attr( $option_id ) . '" name="' . $name . '" value="' . esc_attr( $option['value'] ) . '" data-field-id="' . $field_id . '" ' . $req_attr . '>';
					$html     .= '<span>' . esc_html( $option['label'] ) . '</span>';
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

	/**
	 * Normalize options to a consistent format.
	 *
	 * Handles both string options (from CF7) and array options with label/value (from Gravity Forms).
	 *
	 * @param array $options Raw options array.
	 * @return array Normalized options with 'label' and 'value' keys.
	 */
	private function normalize_options( $options ) {
		$normalized = [];

		foreach ( $options as $option ) {
			if ( is_array( $option ) ) {
				// Already in array format (Gravity Forms style).
				$normalized[] = [
					'label' => $option['label'] ?? $option['text'] ?? $option['value'] ?? '',
					'value' => $option['value'] ?? $option['label'] ?? '',
				];
			} else {
				// String format (CF7 style).
				$normalized[] = [
					'label' => (string) $option,
					'value' => (string) $option,
				];
			}
		}

		return $normalized;
	}
}
