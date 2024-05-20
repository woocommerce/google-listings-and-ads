<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/**
 * @var PHPView $this
 */

/**
 * @var array $form
 */
$form = $this->form

?>

<div id="gla_attributes" class="panel woocommerce_options_panel">
	<h2><?php esc_html_e( 'Product attributes', 'google-listings-and-ads' ); ?></h2>
	<p class="show_if_variable"><?php esc_html_e( 'As this is a variable product, you can add additional product attributes by going to Variations > Select one variation > Google for WooCommerce.', 'google-listings-and-ads' ); ?></p>
	<?php
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $this->render_partial( 'inputs/form', [ 'form' => $form ] );
	?>
</div>

