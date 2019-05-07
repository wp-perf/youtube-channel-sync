<?php
/**
 * Filename class-template.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync;

/**
 * Class Template
 *
 * Summary
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class Template {

	/**
	 * Loads a template
	 *
	 * @param string $slug The template relative path/name without extension.
	 */
	public static function load( $slug, $data = false ) {
		$file = self::locate( $slug );
		if ( $file ) {
			require $file;
		}
	}

	/**
	 * Locates a template
	 *
	 * @param string $slug The template relative path/name without extension.
	 *
	 * @return bool|string
	 */
	public static function locate( $slug ) {
		$located = false;
		if ( file_exists( wpp_youtube()->plugin_path . 'templates/' . $slug . '.php' ) ) {
			$located = wpp_youtube()->plugin_path . 'templates/' . $slug . '.php';
			$located = wpp_youtube()->plugin_path . 'templates/' . $slug . '.php';
		}

		return $located;
	}

	/**
	 * Render a HTML Table Row.
	 *
	 * @param string $heading The table row header.
	 * @param string $content The table row content.
	 * @param array  $atts    The table row attributes, see $default_args for format.
	 *
	 * @return string The table row.
	 */
	public static function render_admin_table_row( $heading, $content, $atts = [] ) {
		$default_atts = [
			'tr' => [
				'id'    => '',
				'class' => '',
			],
			'th' => [
				'id'    => '',
				'class' => '',
			],
			'td' => [
				'id'    => '',
				'class' => '',
			],
		];

		$parsed_atts = wp_parse_args( $atts, $default_atts );

		$th = sprintf( '<th id="%s" class="%s">%s</th>',
			$parsed_atts['th']['id'],
			$parsed_atts['th']['class'],
			$heading
		);
		$td = sprintf( '<td id="%s" class="%s">%s</td>',
			$parsed_atts['td']['id'],
			$parsed_atts['td']['class'],
			$content
		);

		$tr = sprintf( '<tr id="%s" class="%s">%s%s</tr>',
			$parsed_atts['tr']['id'],
			$parsed_atts['tr']['class'],
			$th,
			$td
		);

		return $tr;

	}

	/**
	 * Render a HTML field label.
	 *
	 * @param string $text Label text.
	 * @param string $for  Field ID attribute.
	 * @param array  $atts Attributes.
	 *
	 * @return string
	 */
	public static function render_label( $text, $for, $atts = [] ) {
		$formatted_atts = [];
		foreach ( $atts as $k => $v ) {
			$formatted_atts[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}

		$label = sprintf( '<label for="%s" %s>%s</label>',
			$for,
			join( ' ', $formatted_atts ),
			$text
		);

		return $label;

	}

	/**
	 * Render a HTML field
	 *
	 * @param string $type  Field type: text, number, email, hidden, textarea, or select.
	 * @param string $name  Field name.
	 * @param string $id    Field ID.
	 * @param string $value Field value attribute.
	 * @param array  $atts  Attributes.
	 *
	 * @return string The rendered field.
	 */
	public static function render_field( $type, $name, $id, $value = '', $atts = [] ) {

		switch ( $type ) {
			case 'text':
			case 'number':
			case 'email':
			case 'checkbox':
			case 'radio':
			case 'hidden':
				$field = self::render_input( $type, $name, $id, $value, $atts );
				break;
			case 'textarea':
				$field = self::render_textarea( $name, $id, $value, $atts );
				break;
			case 'select':
				// TODO: Implement Select Field?
				$field = '<!-- SELECT -->';
				break;
			default:
				_doing_it_wrong(
					__FUNCTION__,
					esc_html__( 'That field type is not supported.', 'youtube' ),
					'2.0.0'
				);
				break;
		}

		return $field;
	}

	/**
	 * Render a HTML Input field
	 *
	 * @param string $type  Input type attribute.
	 *                      Options: hidden, text, number, email.
	 * @param string $name  Input name attribute.
	 * @param string $id    Input id attribute.
	 * @param string $value Input value attribute.
	 * @param array  $atts  Other attributes passed as an associative array ($key => $value).
	 *                      Classes: large-text, regular-text, small-text, tiny-text.
	 *
	 * @return string The input field.
	 */
	public static function render_input( $type, $name, $id, $value = '', $atts = [] ) {
		$formatted_atts = [];
		foreach ( $atts as $k => $v ) {
			$formatted_atts[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}

		$input = sprintf( '<input type="%s" name="%s" id="%s" value="%s" %s>',
			$type,
			$name,
			$id,
			$value,
			join( ' ', $formatted_atts )
		);

		return $input;
	}

	/**
	 * Render a HTML Textarea field
	 *
	 * @param string $name  Name attribute.
	 * @param string $id    Id attribute.
	 * @param string $value Value.
	 * @param array  $atts  Other attributes passed as an associative array ($key => $value).
	 *                      Classes: large-text, regular-text, small-text, tiny-text.
	 *
	 * @return string The input field.
	 */
	public static function render_textarea( $name, $id, $value = '', $atts = [] ) {
		$formatted_atts = [];
		foreach ( $atts as $k => $v ) {
			$formatted_atts[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}

		$textarea = sprintf( '<textarea name="%s" id="%s" %s>%s</textarea>',
			$name,
			$id,
			join( ' ', $formatted_atts ),
			$value
		);

		return $textarea;
	}

	/**
	 * Render a HTML Select field
	 *
	 * @param string $name    Name attribute.
	 * @param string $id      Id attribute.
	 * @param array  $options Array of options in the format ['value' => 'foo', 'label' => 'Bar'].
	 * @param string $value   Value.
	 * @param array  $atts    Other attributes passed as an associative array ($key => $value).
	 *                        Classes: large-text, regular-text, small-text, tiny-text.
	 *
	 * @return string The input field.
	 */
	public static function render_select( $name, $id, $options, $value = '', $atts = [] ) {
		$formatted_atts = [];
		foreach ( $atts as $k => $v ) {
			$formatted_atts[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}

		$formatted_options = [];

		foreach ( $options as $option ) {
			$formatted_options[] = sprintf( '<option value="%s" %s>%s</option>',
				$option['value'],
				( $value === $option['value'] ) ? 'selected' : '',
				$option['label']
			);
		}

		$select = sprintf( '<select name="%s" id="%s" %s>%s</select>',
			$name,
			$id,
			join( ' ', $formatted_atts ),
			join( '', $formatted_options )
		);

		return $select;
	}
}