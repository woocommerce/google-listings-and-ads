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
	<div class="options_group">
		<h2><?php esc_html_e( 'Product attributes', 'google-listings-and-ads' ); ?></h2>
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_partial( 'inputs/form', [ 'form' => $form ] );
		?>
	</div>
</div>

