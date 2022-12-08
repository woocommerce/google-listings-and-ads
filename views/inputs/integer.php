<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var array $input
 */
$input = $this->input;

$input['type'] = 'number';
// Not so "custom" but standard `<input type="number">` attribute.
$input['custom_attributes'] = [
	'min' => '0',
];

woocommerce_wp_text_input( $input );
