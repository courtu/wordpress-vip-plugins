<?php

use Apple_Exporter\Settings as Settings;

/**
 * Describes a WordPress setting section
 *
 * @since 0.6.0
 */
class Admin_Apple_Settings_Section extends Apple_News {

	/**
	 * Name of the settings section.
	 *
	 * @var string
	 * @access protected
	 */
	protected $name;

	/**
	 * Slug of the settings section.
	 *
	 * @var string
	 * @access protected
	 */
	protected $slug;

	/**
	 * Settings page.
	 *
	 * @var string
	 * @access protected
	 */
	protected $page;

	/**
	 * Allow for a settings section to be hidden.
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $hidden = false;

	/**
	 * Option name used for the section.
	 *
	 * @var string
	 * @access protected
	 */
	protected static $section_option_name;

	/**
	 * Action used for saving the section.
	 *
	 * @var string
	 * @access protected
	 */
	protected $save_action;

	/**
	 * Base settings.
	 *
	 * @var Settings
	 * @access protected
	 */
	protected static $base_settings;

	/**
	 * Loaded settings.
	 *
	 * @var Settings
	 * @access protected
	 */
	protected static $loaded_settings;

	/**
	 * Settings for the section.
	 *
	 * @var array
	 * @access protected
	 */
	protected $settings = array();

	/**
	 * Groups for the section.
	 *
	 * @var array
	 * @access protected
	 */
	protected $groups = array();

	/**
	 * Allowed HTML for settings pages.
	 *
	 * @var array
	 * @access public
	 */
	public static $allowed_html = array(
		'select' => array(
			'class' => array(),
			'name' => array(),
			'multiple' => array(),
			'id' => array(),
		),
		'option' => array(
			'value' => array(),
			'selected' => array(),
		),
		'input' => array(
			'class' => array(),
			'name' => array(),
			'value' => array(),
			'placeholder' => array(),
			'step' => array(),
			'type' => array(),
			'required' => array(),
			'size' => array(),
			'id' => array(),
		),
		'br' => array(),
		'b' => array(),
		'strong' => array(),
		'i' => array(),
		'em' => array(),
		'a' => array(
			'href' => array(),
			'target' => array(),
		),
		'div' => array(
			'class' => array(),
		),
		'h1' => array(
			'class' => array(),
		),
		'h2' => array(
			'class' => array(),
		),
		'h3' => array(
			'class' => array(),
		),
		'h4' => array(
			'class' => array(),
		),
		'h5' => array(
			'class' => array(),
		),
		'h6' => array(
			'class' => array(),
		),
	);

	/**
	 * Constructor.
	 *
	 * @param string $page
	 * @param boolean $hidden
	 * @param string $save_action
	 * @param string $section_option_name
	 */
	function __construct( $page, $hidden = false, $save_action = 'apple_news_options', $section_option_name = null ) {
		$this->page = $page;
		self::$section_option_name = ( ! empty( $section_option_name ) ) ? $section_option_name : self::$option_name;
		$this->save_action = $save_action;
		$base_settings = new \Apple_Exporter\Settings;
		self::$base_settings = $base_settings->all();
		self::$loaded_settings = get_option( self::$section_option_name );
		$this->settings = apply_filters( 'apple_news_section_settings', $this->settings, $page );
		$this->groups = apply_filters( 'apple_news_section_groups', $this->groups, $page );
		$this->hidden = $hidden;

		// Save settings if necessary
		$this->save_settings();
	}

	/**
	 * Get the settings section name.
	 *
	 * @return string
	 * @access public
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * Return an array which contains all groups and their related settings,
	 * embedded.
	 *
	 * @return array
	 * @access public
	 */
	public function groups() {
		$result = array();
		foreach ( $this->groups as $name => $info ) {
			$settings = array();
			foreach ( $info['settings'] as $name ) {
				$settings[ $name ] = $this->settings[ $name ];
				$settings[ $name ]['default'] = self::get_default_for( $name );
				$settings[ $name ]['callback'] = ( ! empty( $this->settings[ $name ]['callback'] ) ) ? $this->settings[ $name ]['callback'] : '';
			}

			$result[ $name ] = array(
				'label'       => $info['label'],
				'description' => empty( $info['description'] ) ? null : $info['description'],
				'settings'    => $settings,
			);
		}

		return $result;
	}

	/**
	 * Get the ID of the settings section.
	 *
	 * @return string
	 * @access public
	 */
	public function id() {
		return $this->plugin_slug . '_options_section_' . $this->slug;
	}

	/**
	 * Render a settings field.
	 *
	 * @param array $args
	 * @access public
	 */
	public function render_field( $args ) {
		list( $name, $default_value, $callback ) = $args;

		$type  = $this->get_type_for( $name );

		// If the field has it's own render callback, use that here.
		// This is because the options page doesn't actually use do_settings_section.
		if ( ! empty( $callback ) ) {
			return call_user_func( $callback, $type );
		}

		$value = self::get_value( $name, self::$loaded_settings );
		$field = null;

		// Get the field size
		$size = $this->get_size_for( $name );

		// FIXME: A cleaner object-oriented solution would create Input objects
		// and instantiate them according to their type.
		if ( is_array( $type ) ) {
			// Check if this is a multiple select
			$multiple_name = $multiple_attr = '';
			if ( $this->is_multiple( $name ) ) {
				$multiple_name = '[]';
				$multiple_attr = 'multiple="multiple"';
			}

			// Check if we're using names as values
			$keys = array_keys( $type );
			$use_name_as_value = ( array_keys( $keys ) === $keys );

			// Use select2 only when there is a considerable ammount of options available
			if ( count( $type ) > 10 ) {
				$field = '<select class="select2 standard" id="%s" name="%s' . $multiple_name . '" ' . $multiple_attr . '>';
			} else {
				$field = '<select id="%s" name="%s' . $multiple_name . '" ' . $multiple_attr . '>';
			}

			foreach ( $type as $key => $option ) {
				$store_value = $use_name_as_value ? $option : $key;
				$field .= "<option value='" . esc_attr( $store_value ) . "' ";
				if ( $this->is_multiple( $name ) ) {
					if ( in_array( $store_value, $value, true ) ) {
						$field .= 'selected="selected"';
					}
				} else {
					$field .= selected( $value, $store_value, false );
				}
				$field .= ">" . esc_html( $option ) . "</option>";
			}
			$field .= '</select>';
		} elseif ( 'password' === $type ) {
			$field = '<input type="password" id="%s" name="%s" value="%s" size="%s" %s>';
		} elseif ( 'hidden' === $type ) {
			$field = '<input type="hidden" id="%s" name="%s" value="%s">';
		}  else {
			// If nothing else matches, it's a string.
			$field = '<input type="text" id="%s" name="%s" value="%s" size="%s" %s>';
		}

		// Add a description, if set.
		$description = $this->get_description_for( $name );
		if ( ! empty( $description ) && 'hidden' !== $type ) {
			$field .= apply_filters( 'apple_news_field_description_output_html', '<br/><i>' . $description . '</i>', $name );
		}

		// Use the proper template to build the field
		if ( is_array( $type ) ) {
			return sprintf(
				$field,
				esc_attr( $name ),
				esc_attr( $name )
			);
		} elseif ( 'hidden' === $type ) {
			return sprintf(
				$field,
				esc_attr( $name ),
				esc_attr( $name ),
				esc_attr( $value )
			);
		} else {
			return sprintf(
				$field,
				esc_attr( $name ),
				esc_attr( $name ),
				esc_attr( $value ),
				intval( $size ),
				esc_attr( $this->is_required( $name ) )
			);

		}
	}

	/**
	 * Get the type for a field.
	 *
	 * @param string $name
	 * @return string
	 * @access protected
	 */
	protected function get_type_for( $name ) {
		if ( $this->hidden ) {
			return 'hidden';
		} else {
			return empty( $this->settings[ $name ]['type'] ) ? 'string' : $this->settings[ $name ]['type'];
		}
	}

	/**
	 * Get the description for a field.
	 *
	 * @param string $name
	 * @return string
	 * @access protected
	 */
	protected function get_description_for( $name ) {
		return empty( $this->settings[ $name ]['description'] ) ? '' : $this->settings[ $name ]['description'];
	}

	/**
	 * Get the size for a field.
	 *
	 * @param string $name
	 * @return int
	 * @access protected
	 */
	protected function get_size_for( $name ) {
		return empty( $this->settings[ $name ]['size'] ) ? 20 : $this->settings[ $name ]['size'];
	}

	/**
	 * Check if a field is required.
	 *
	 * @param string $name
	 * @return int
	 * @access protected
	 */
	protected function is_required( $name ) {
		$required = ! isset( $this->settings[ $name ]['required'] ) ? true : $this->settings[ $name ]['required'];
		return ( $required ) ? 'required' : '';
	}

	/**
	 * Check if the field can hold multiple values.
	 *
	 * @param string $name
	 * @return boolean
	 * @access protected
	 */
	protected function is_multiple( $name ) {
		return ! empty( $this->settings[ $name ]['multiple'] );
	}

	/**
	 * Get the default for a field.
	 *
	 * @param string $name
	 * @return string
	 * @access protected
	 */
	protected static function get_default_for( $name ) {
		return isset( self::$base_settings[ $name ] ) ? self::$base_settings[ $name ] : '';
	}

	/**
	 * Gets section info.
	 *
	 * @return string
	 * @access public
	 */
	public function get_section_info() {
		return '';
	}

	/**
	 * HTML to display before the section.
	 *
	 * @return string
	 * @access public
	 */
	public function before_section() {
		echo '';
	}

	/**
	 * HTML to display after the section.
	 *
	 * @return string
	 * @access public
	 */
	public function after_section() {
		echo '';
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 * @access public
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get loaded settings.
	 *
	 * @return array
	 * @access public
	 */
	public function get_loaded_settings() {
		return $this->loaded_settings;
	}

	/**
	 * Check if the section is hidden on the settings page.
	 *
	 * @return boolean
	 * @access public
	 */
	public function is_hidden() {
		return $this->hidden;
	}

	/**
	 * Sanitizes a single dimension array with text values.
	 *
	 * @param array $value
	 * @return array
	 */
	public function sanitize_array( $value ) {
		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Get the current value for an option.
	 *
	 * @param string $key
	 * @param array $saved_settings
	 * @return mixed
	 */
	public static function get_value( $key, $saved_settings = null ) {
		if ( empty( $saved_settings ) ) {
			$saved_settings = get_option( self::$section_option_name );
		}
		return ( isset( $saved_settings[ $key ] ) ) ? $saved_settings[ $key ] : self::get_default_for( $key );
	}

	/**
	 * Each section is responsible for saving its own settings
	 * since only it knows the nature of the fields and sanitization methods.
	 */
	public function save_settings() {
		// Check if we're saving options and that there are settings to save
		if ( empty( $_POST['action'] )
			|| $this->save_action !== $_POST['action']
			|| empty( $this->settings ) ) {
			return;
		}

		// Form nonce check
		check_admin_referer( $this->save_action );

		// Get the current Apple News settings
		$settings = get_option( self::$section_option_name, array() );

		// Iterate over the settings and save each value.
		// Settings can't be empty unless allowed, so if no value is found
		// use the default value to be safe.
		$default_settings = new Settings();
		foreach ( $this->settings as $key => $attributes ) {
			if ( ! empty( $_POST[ $key ] )
				|| ( isset( $_POST[ $key ] )
					&& in_array( $_POST[ $key ], array( 0, '0' ), true )
				)
			) {
				// Sanitize the value
				$sanitize = ( empty( $attributes['sanitize'] ) || ! is_callable( $attributes['sanitize'] ) ) ? 'sanitize_text_field' : $attributes['sanitize'];
				$value = call_user_func( $sanitize, $_POST[ $key ] );
			} else {
				// Use the default value
				$value = $default_settings->$key;
			}

			// Add to the array
			$settings[ $key ] = $value;
		}

		// Clear certain caches
		delete_transient( 'apple_news_sections' );

		// Save to options
		update_option( self::$section_option_name, $settings, 'no' );
	}
}
