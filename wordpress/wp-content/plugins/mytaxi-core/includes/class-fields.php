<?php
/**
 * Shared field rendering and sanitizing.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Admin field helpers.
 */
class Fields {

	/**
	 * Open a labelled section.
	 *
	 * @param string $title   Section title.
	 * @param string $help    Optional help.
	 */
	public static function section( $title, $help = '' ) {
		echo '<section class="mytaxi-section">';
		echo '<h2>' . esc_html( $title ) . '</h2>';
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
	}

	/**
	 * Close a section.
	 */
	public static function end_section() {
		echo '</section>';
	}

	/**
	 * Text input.
	 *
	 * @param string $name  Field name.
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $help  Help.
	 * @param string $type  Input type.
	 */
	public static function input( $name, $label, $value, $help = '', $type = 'text' ) {
		$id = 'mytaxi-' . sanitize_html_class( $name );
		echo '<p class="mytaxi-field">';
		echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		printf(
			'<input class="widefat" type="%1$s" id="%2$s" name="%3$s" value="%4$s">',
			esc_attr( $type ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value )
		);
		if ( $help ) {
			echo '<span class="description">' . esc_html( $help ) . '</span>';
		}
		echo '</p>';
	}

	/**
	 * Textarea.
	 *
	 * @param string $name  Field name.
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $help  Help.
	 * @param int    $rows  Rows.
	 */
	public static function textarea( $name, $label, $value, $help = '', $rows = 4 ) {
		$id = 'mytaxi-' . sanitize_html_class( $name );
		echo '<p class="mytaxi-field">';
		echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		printf(
			'<textarea class="widefat" rows="%1$d" id="%2$s" name="%3$s">%4$s</textarea>',
			(int) $rows,
			esc_attr( $id ),
			esc_attr( $name ),
			esc_textarea( $value )
		);
		if ( $help ) {
			echo '<span class="description">' . esc_html( $help ) . '</span>';
		}
		echo '</p>';
	}

	/**
	 * Select.
	 *
	 * @param string               $name    Field name.
	 * @param string               $label   Label.
	 * @param string               $value   Selected value.
	 * @param array<string,string> $options Options.
	 * @param string               $help    Help.
	 */
	public static function select( $name, $label, $value, array $options, $help = '' ) {
		$id = 'mytaxi-' . sanitize_html_class( $name );
		echo '<p class="mytaxi-field">';
		echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
		foreach ( $options as $key => $option_label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $option_label )
			);
		}
		echo '</select>';
		if ( $help ) {
			echo '<span class="description">' . esc_html( $help ) . '</span>';
		}
		echo '</p>';
	}

	/**
	 * Checkbox.
	 *
	 * @param string $name  Field name.
	 * @param string $label Label.
	 * @param bool   $checked Checked.
	 * @param string $help Help.
	 */
	public static function checkbox( $name, $label, $checked, $help = '' ) {
		echo '<p class="mytaxi-field">';
		echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( $checked, true, false ) . '> ';
		echo '<strong>' . esc_html( $label ) . '</strong></label>';
		if ( $help ) {
			echo '<br><span class="description">' . esc_html( $help ) . '</span>';
		}
		echo '</p>';
	}

	/**
	 * One value per line.
	 *
	 * @param string        $name  Field name.
	 * @param string        $label Label.
	 * @param array<int,string> $lines Lines.
	 * @param string        $help Help.
	 */
	public static function lines( $name, $label, array $lines, $help = '' ) {
		self::textarea( $name, $label, implode( "\n", $lines ), $help, 5 );
	}

	/**
	 * Repeater with add, remove, and reorder.
	 *
	 * @param string                    $name    Base name.
	 * @param array<int,array<string,string>> $rows Rows.
	 * @param array<string,string>      $columns Key => label.
	 * @param string                    $add     Add button label.
	 */
	public static function repeater( $name, array $rows, array $columns, $add ) {
		if ( ! $rows ) {
			$rows = array( array_fill_keys( array_keys( $columns ), '' ) );
		}

		echo '<div class="mytaxi-repeater" data-repeater>';
		echo '<div data-rows>';
		foreach ( $rows as $index => $row ) {
			self::repeater_row( $name, (int) $index, $row, $columns );
		}
		echo '</div>';
		echo '<template>';
		self::repeater_row( $name, 0, array_fill_keys( array_keys( $columns ), '' ), $columns );
		echo '</template>';
		echo '<p><button type="button" class="button" data-mytaxi-add>' . esc_html( $add ) . '</button></p>';
		echo '</div>';
	}

	/**
	 * One repeater row.
	 *
	 * @param string               $name    Base name.
	 * @param int                  $index   Index.
	 * @param array<string,string> $row     Values.
	 * @param array<string,string> $columns Columns.
	 */
	private static function repeater_row( $name, $index, array $row, array $columns ) {
		echo '<div class="mytaxi-repeater-row" data-row>';
		foreach ( $columns as $key => $label ) {
			$field = $name . '[' . $index . '][' . $key . ']';
			$long  = in_array( $key, array( 'answer', 'text', 'quote' ), true );
			echo '<p><label><strong>' . esc_html( $label ) . '</strong><br>';
			if ( $long ) {
				printf(
					'<textarea class="widefat" rows="3" name="%1$s">%2$s</textarea>',
					esc_attr( $field ),
					esc_textarea( (string) ( $row[ $key ] ?? '' ) )
				);
			} else {
				printf(
					'<input class="widefat" type="text" name="%1$s" value="%2$s">',
					esc_attr( $field ),
					esc_attr( (string) ( $row[ $key ] ?? '' ) )
				);
			}
			echo '</label></p>';
		}
		echo '<p class="mytaxi-repeater-actions">';
		echo '<button type="button" class="button" data-mytaxi-up>Нагоре</button> ';
		echo '<button type="button" class="button" data-mytaxi-down>Надолу</button> ';
		echo '<button type="button" class="button" data-mytaxi-remove>Премахни</button>';
		echo '</p></div>';
	}

	/**
	 * Sanitize a phone to a leading plus and digits.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function phone( $value ) {
		$value = preg_replace( '/[^\d+]/', '', (string) $value );
		$value = is_string( $value ) ? $value : '';
		if ( '' === $value ) {
			return '';
		}
		$digits = ltrim( $value, '+' );
		if ( ! preg_match( '/^\d{8,15}$/', $digits ) ) {
			return '';
		}
		return '+' . $digits;
	}

	/**
	 * Sanitize an email and reject header breaks.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function email( $value ) {
		$value = self::header_safe( (string) $value );
		$value = sanitize_email( $value );
		return is_email( $value ) ? $value : '';
	}

	/**
	 * Sanitize an http(s) URL.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function url( $value ) {
		$value = esc_url_raw( trim( (string) $value ) );
		if ( ! $value || ! preg_match( '#^https?://#i', $value ) ) {
			return '';
		}
		return $value;
	}

	/**
	 * Remove characters that can inject mail headers.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function header_safe( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		$value = str_ireplace( array( '%0a', '%0d' ), ' ', $value );
		$value = str_replace( array( "\r", "\n", "\0" ), ' ', $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		return trim( (string) $value );
	}

	/**
	 * Empty string when the amount is missing or not a positive number.
	 *
	 * @param string $value Raw price.
	 * @return string
	 */
	public static function price( $value ) {
		$value = trim( str_replace( ',', '.', (string) $value ) );
		if ( '' === $value || ! is_numeric( $value ) ) {
			return '';
		}
		$amount = round( (float) $value, 2 );
		if ( $amount <= 0 ) {
			return '';
		}
		return number_format( $amount, 2, '.', '' );
	}

	/**
	 * Split a textarea into clean lines.
	 *
	 * @param string $value Raw text.
	 * @return array<int,string>
	 */
	public static function line_list( $value ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
		$clean = array();
		foreach ( (array) $lines as $line ) {
			$line = sanitize_text_field( $line );
			if ( '' !== $line ) {
				$clean[] = $line;
			}
		}
		return array_values( array_slice( $clean, 0, 40 ) );
	}

	/**
	 * Sanitize repeater rows.
	 *
	 * @param mixed              $rows Submitted rows.
	 * @param array<int,string>  $keys Allowed keys.
	 * @param bool               $long Use textarea sanitizing for every key.
	 * @return array<int,array<string,string>>
	 */
	public static function clean_repeater( $rows, array $keys, $long = false ) {
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$clean = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$item    = array();
			$has_any = false;
			foreach ( $keys as $key ) {
				$raw = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
				$item[ $key ] = $long ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
				if ( '' !== $item[ $key ] ) {
					$has_any = true;
				}
			}
			if ( $has_any ) {
				$clean[] = $item;
			}
			if ( count( $clean ) >= 30 ) {
				break;
			}
		}
		return $clean;
	}

	/**
	 * Decode a JSON list.
	 *
	 * @param mixed $value Stored value.
	 * @return array<int,mixed>
	 */
	public static function decode_list( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Encode a list for post meta.
	 *
	 * @param array<int,mixed> $value List.
	 * @return string
	 */
	public static function encode_list( array $value ) {
		return wp_json_encode( array_values( $value ), JSON_UNESCAPED_UNICODE );
	}
}
