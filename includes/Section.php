<?php

namespace WPObjectified\SettingsAPI;

class Section {
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $page;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var callable
	 */
	protected $render_callback;

	/**
	 * @var callable
	 */
	protected $sanitize_callback;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var Field[]
	 */
	protected $fields;

	/**
	 * @param array $options
	 * @throws \InvalidArgumentException When section name is not provided
	 */
	public function __construct( array $options ) {
		if ( empty( $options['name'] ) ) {
			throw new \InvalidArgumentException( 'Section name was not provided' );
		}

		$this->name = $options['name'];
		$this->page = isset( $options['page'] ) ? $options['page'] : $this->name;

		if ( isset( $options['title'] ) ) {
			$this->title = $options['title'];
		}

		if ( isset( $options['fields'] ) ) {
			$this->set_fields( $options['fields'] );
		}

		if ( isset( $options['render_callback'] ) ) {
			$this->render_callback = Utils::check_callback( $options['render_callback'] );
		}

		if ( isset( $options['sanitize_callback'] ) ) {
			$this->sanitize_callback = Utils::check_callback( $options['sanitize_callback'] );
		}

		unset(
			$options['name'],
			$options['title'],
			$options['fields'],
			$options['render_callback'],
			$options['sanitize_callback']
		);

		$this->options = $options;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	public function get_title() {
		return $this->title;
	}

	/**
	 * @return Field[]
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * @param array $fields
	 * @return $this
	 */
	protected function set_fields( array $fields ) {
		foreach ( (array) $fields as $field ) {
			$this->add_field( $field );
		}

		return $this;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	protected function add_field( array $options ) {
		$options['section'] = $this->name;
		$options['page']    = $this->page;

		$field = new Field( $options );
		$this->fields[ $field->get_name() ] = $field;

		return $this;
	}

	/**
	 * Undefined or values corresponding to fields with 'ignore' flag
	 * are removed from options
	 *
	 * @param mixed $values
	 * @return array
	 */
	public function sanitize_values( $values ) {
		$values = (array) $values;
		$sanitized_values = $this->sanitize_field_values( $values );

		if ( $this->sanitize_callback ) {
			$sanitized_values = $this->sanitize_section_values( $sanitized_values );
		}

		return $sanitized_values;
	}

	/**
	 * @param array $values
	 * @return array
	 */
	protected function sanitize_field_values( array $values ) {
		$sanitized_values = array();

		foreach ( (array) $values as $field_name => $field_value ) {
			$field = $this->get_field( $field_name );

			if ( ! $field || $field->get_ignore() ) {
				continue;
			}

			$sanitized_values[ $field_name ] = $field->sanitize_value( $field_value );
		}

		return $sanitized_values;
	}

	/**
	 * @param array $values
	 * @return array
	 */
	protected function sanitize_section_values( array $values ) {
		$sanitized_values = call_user_func( $this->sanitize_callback, $values );

		if ( $sanitized_values instanceof \WP_Error ) {
			$sanitization_error = $sanitized_values;
			$sanitized_values = $values;

			foreach ( $this->fields as $field_name => $field ) {
				$error_messages = $sanitization_error->get_error_messages( $field_name );

				if ( ! $error_messages ) {
					continue;
				}

				foreach ( $error_messages as $message ) {
					$field->add_error( $message );
				}

				// Reset erroneous field to a last saved value
				$sanitized_values[ $field_name ] = $field->get_value();
			}
		}

		return $sanitized_values;
	}

	/**
	 * @param $name
	 * @return Field
	 */
	public function get_field( $name ) {
		if ( isset( $this->fields[ $name ] ) ) {
			return $this->fields[ $name ];
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function get_errors() {
		return get_settings_errors( $this->name );
	}

	/**
	 * @return void
	 */
	public function register() {
		add_settings_section( $this->name, $this->title, $this->render_callback, $this->page );

		foreach ( $this->fields as $field ) {
			$field->register();
		}

		register_setting( $this->name, $this->name, array( $this, 'sanitize_values' ) );
	}

	/**
	 * @param array $options
	 * @return Section
	 */
	public static function create( array $options ) {
		return new static( $options );
	}
}
