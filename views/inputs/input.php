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

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo $this->render_partial( path_join( 'inputs/', $input['type'] ), [ 'input' => $input ] );
