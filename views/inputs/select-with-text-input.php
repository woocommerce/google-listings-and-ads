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

/**
 * @var array $custom_value_input
 */
$custom_value_input = $input['children'][0];
?>

<div class="select-with-text-input">
	<?php
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $this->render_partial( 'inputs/select', [ 'input' => $input ] );
	echo $this->render_partial( 'inputs/text', [ 'input' => $custom_value_input ] );
	?>
</div>
