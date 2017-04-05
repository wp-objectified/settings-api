<?php

namespace WPObjectified\SettingsAPI;

/**
 * @todo This class is likely to be moved to a separate package
 */
class FieldRenderer {
	public static function show_text_field( array $args ) {
		$name        = esc_attr( $args['name'] );
		$value       = esc_attr( $args['value'] );
		$id          = isset( $args['id'] ) ? esc_attr( $args['id'] ) : '';
		$size        = isset( $args['size'] ) ? esc_attr( $args['size'] ) : 'regular';
		$type        = isset( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';
		$placeholder = isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';

		$html = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s" name="%4$s" value="%5$s"%6$s />', $type, $size, $id, $name, $value, $placeholder );

		$html .= self::render_field_description( $args );
		$html .= self::render_field_errors( $args );

		echo $html;
	}

	public static function show_number_field( array $args ) {
		$name        = esc_attr( $args['name'] );
		$value       = esc_attr( $args['value'] );
		$id          = isset( $args['id'] ) ? esc_attr( $args['id'] ) : '';
		$size        = isset( $args['size'] ) ? esc_attr( $args['size'] ) : 'regular';
		$type        = isset( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';
		$placeholder = isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';

		$min         = isset( $args['min'] ) ? ' min="' . esc_attr( $args['min'] ) . '"' : '';
		$max         = isset( $args['max'] ) ? ' max="' . esc_attr( $args['max'] ) . '"' : '';
		$step        = isset( $args['max'] ) ? ' step="' . esc_attr( $args['step'] ) . '"' : '';

		$html = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s" name="%4$s" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $id, $name, $value, $placeholder, $min, $max, $step );

		$html .= self::render_field_errors( $args );
		$html .= self::render_field_description( $args );

		echo $html;
	}

	// todo
	public static function show_checkbox_field( array $args ) {
		$name        = esc_attr( $args['name'] );
		$value       = esc_attr( isset($args['value']) ? $args['value'] : 1 );
		$id          = isset( $args['id'] ) ? esc_attr( $args['id'] ) : '';
		$choices     = isset($args['choices']) ? (array) $args['choices'] : array('1' => '');

		$html  = '<fieldset>';
		$html  .= sprintf( '<legend class="screen-reader-text">%s</legend>', $args['label']);

		foreach ($choices as $choice_value => $choice_label ) {
			$html  .= sprintf( '<label for="%1$s">', $id );
			$html  .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s" name="%2$s" value="1" %3$s />', $id, $name, checked( $value, $choice_value, false ) );
			$html  .= sprintf( '%1$s</label>', $choice_label );
			break;
		}

		$html .= self::render_field_errors( $args );
		$html .= self::render_field_description( $args );

		$html  .= '</fieldset>';

		echo $html;
	}

	public static function show_select_field( array $args ) {
		$name  = esc_attr( $args['name'] );
		$value = esc_attr( $args['value'] );
		$id    = isset( $args['id'] ) ? esc_attr( $args['id'] ) : '';
		$size  = isset( $args['size'] ) ? esc_attr( $args['size'] ) : 'regular';

		$html  = sprintf( '<select class="%1$s" name="%2$s" id="%3$s">', $size, $name, $id );

		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
		}

		$html .= sprintf( '</select>' );

		$html .= self::render_field_errors( $args );
		$html .= self::render_field_description( $args );

		echo $html;
	}

	public static function render_field_description( array $args ) {
		if ( ! empty( $args['desc'] ) ) {
			$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
		} else {
			$desc = '';
		}

		return $desc;
	}

	public static function render_field_errors( array $args ) {
		$errors = isset( $args['errors'] ) ? $args['errors'] : null;
		if ( $errors ) {
			$html = '<p style="color:#800">';
			$html .= implode( ' ', wp_list_pluck( (array) $errors, 'message' ) );
			$html .= '</p>';
		} else {
			$html = '';
		}

		return $html;
	}
}
