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

$input['type']  = 'text';
$input['class'] = sprintf( '%s %s', $input['class'] ?? '', 'gla-input-number' );

woocommerce_wp_text_input( $input );
