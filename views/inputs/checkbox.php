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

$input['value'] = $input['value'] ?? false;
$input['value'] = wc_bool_to_string( $input['value'] );

$input['wrapper_class'] = sprintf( '%s %s', $input['wrapper_class'] ?? '', 'options' );

woocommerce_wp_checkbox( $input );
