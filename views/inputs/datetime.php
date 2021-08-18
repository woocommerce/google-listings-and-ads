<?php
/**
 * Datetime input template
 *
 * @since x.x.x
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var array $input
 */
$input = $this->input;

$input['class']         = $input['class'] ?? '';
$input['placeholder']   = $input['placeholder'] ?? '';
$input['style']         = $input['style'] ?? '';
$input['wrapper_class'] = $input['wrapper_class'] ?? '';
$input['name']          = $input['name'] ?? $input['id'];
$input['desc_tip']      = $input['desc_tip'] ?? false;
$input['date']          = $input['date'] ?? '';
$input['time']          = $input['time'] ?? '';

// Custom attribute handling
$custom_attributes = [];
if ( ! empty( $input['custom_attributes'] ) && is_array( $input['custom_attributes'] ) ) {
	foreach ( $input['custom_attributes'] as $attribute => $value ) {
		$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
	}
}

echo '<p class="form-field ' . esc_attr( $input['id'] ) . '_field gla-input-datetime ' . esc_attr( $input['wrapper_class'] ) . '">
		<label for="' . esc_attr( $input['id'] ) . '_date">' . wp_kses_post( $input['label'] ) . '</label>';

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo '<input type="date" pattern="\d{4}-\d{2}-\d{2}" class="' . esc_attr( $input['class'] ) . '" style="' . esc_attr( $input['style'] ) . '" name="' . esc_attr( $input['name'] ) . '[date]" id="' . esc_attr( $input['id'] ) . '_date" value="' . esc_attr( $input['date'] ) . '" placeholder="' . esc_attr( $input['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';
echo '<input type="time" pattern="[0-9]{2}:[0-9]{2}" class="' . esc_attr( $input['class'] ) . '" style="' . esc_attr( $input['style'] ) . '" name="' . esc_attr( $input['name'] ) . '[time]" id="' . esc_attr( $input['id'] ) . '_time" value="' . esc_attr( $input['time'] ) . '" placeholder="' . esc_attr( $input['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! empty( $input['description'] ) && false !== $input['desc_tip'] ) {
	echo wc_help_tip( $input['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

if ( ! empty( $input['description'] ) && false === $input['desc_tip'] ) {
	echo '<span class="description">' . wp_kses_post( $input['description'] ) . '</span>';
}

echo '</p>';
