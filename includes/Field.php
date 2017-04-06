<?php

namespace WPObjectified\SettingsAPI;

class Field {
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $section;

	/**
	 * @var
	 */
	protected $page;

	/**
	 * @var callable
	 */
	protected $display_callback;

	/**
	 * @var callable
	 */
	protected $sanitize_callback;

	/**
	 * @var bool
	 */
	protected $ignore;

	/**
	 * @var array
	 */
	protected $args;

	/**
	 * @param array $options
	 * @throws \InvalidArgumentException When section name is not provided
	 */
	public function __construct( array $options ) {
		if ( empty( $options['page'] ) ) {
			throw new \InvalidArgumentException( 'Page name was not provided' );
		}

		if ( empty( $options['name'] ) ) {
			throw new \InvalidArgumentException( 'Field name was not provided' );
		}

		$section = isset( $options['section'] ) ? $options['section'] : 'default';
		$name = $options['name'];
		$type = isset( $options['type'] ) ? $options['type'] : 'text';

		if ( isset( $options['display_callback'] ) ) {
			$display_callback = Utils::check_callback( $options['display_callback'] );
		}
		if ( empty( $display_callback ) ) {
			$display_callback = array( 'WPObjectified\SettingsAPI\FieldRenderer', 'show_field' );
		}
		unset( $options['display_callback'] );

		$sanitize_callback = Utils::check_callback(
			isset( $options['sanitize_callback'] ) ? $options['sanitize_callback'] : null
		);
		unset( $options['sanitize_callback'] );

		$ignore = isset( $options['ignore'] ) && $options['ignore'];
		unset( $options['ignore'] );

		$this->page              = $options['page'];
		$this->section           = $section;
		$this->name              = $name;
		$this->label             = isset( $options['label'] ) ? $options['label'] : '';
		$this->display_callback  = $display_callback;
		$this->sanitize_callback = $sanitize_callback;
		$this->ignore            = $ignore;
		$this->default           = isset( $options['default'] ) ? $options['default'] : '';

		$id = isset( $options['id'] ) ? $options['id'] : "{$section}-{$name}";

		$this->args = array_merge($options, array(
			'section'   => $section,
			'type'      => $type,
			'name'      => "{$section}[{$name}]",
			'id'        => $id,
			'label_for' => $id,
		));
	}

	/**
	 * @param array $args
	 * @return mixed
	 */
	public function display( array $args ) {
		$args['value']  = $this->get_value();
		$args['errors'] = $this->get_errors();

		return call_user_func( $this->display_callback, $args );
	}

	/**
	 * @return mixed
	 */
	public function get_value() {
		$options = get_option( $this->section );

		if ( is_array( $options ) && isset( $options[ $this->name ] ) ) {
			return $options[ $this->name ];
		}

		return $this->default;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize_value( $value ) {
		if ( $this->sanitize_callback ) {
			$sanitized_value = call_user_func( $this->sanitize_callback, $value );

			if ( $sanitized_value instanceof \WP_Error ) {
				foreach ( $sanitized_value->get_error_messages() as $message ) {
					$this->add_error( $message );
				}

				// WP SettingsAPI offers no way to prevent options form saving,
				// so in order to prevent invalid values to be saved, we need to
				// fallback to last saved value (or to default if necessary).
				return $this->get_value();
			}
		}

		return $value;
	}

	/**
	 * @return array
	 */
	public function get_errors() {
		$errors = array();

		foreach ( get_settings_errors( $this->section ) as $error ) {
			if ( $error['code'] === $this->name ) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @param string $message
	 * @param string $type
	 * @return $this
	 */
	public function add_error( $message, $type = 'error' ) {
		add_settings_error( $this->section, $this->name, $message, $type );
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function get_page() {
		return $this->page;
	}

	/**
	 * @return string
	 */
	public function get_section() {
		return $this->section;
	}

	/**
	 * @return array
	 */
	public function get_args() {
		return $this->args;
	}

	/**
	 * @param string $arg
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get_arg( $arg, $default = null ) {
		return isset( $this->args[ $arg ] ) ? $this->args[ $arg ] : $default;
	}

	/**
	 * @return bool
	 */
	public function get_ignore() {
		return $this->ignore;
	}

	/**
	 * @return void
	 */
	public function register() {
		add_settings_field( $this->name, $this->label, array( $this, 'display' ), $this->page, $this->section, $this->args );
	}

	/**
	 * @param array $options
	 * @return Field
	 */
	public static function create( array $options ) {
		return new static( $options );
	}
}
