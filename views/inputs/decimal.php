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

$input['type']      = 'text';
$input['data_type'] = 'decimal';

woocommerce_wp_text_input( $input );
