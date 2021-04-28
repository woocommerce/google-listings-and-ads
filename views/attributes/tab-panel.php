<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/**
 * @var PHPView $this
 */

/**
 * @var array $inputs_data
 */
$inputs_data = $this->inputs

?>

<div id="gla_attributes" class="panel woocommerce_options_panel">
	<div class="options_group">
		<h2><?php esc_html_e( 'Product attributes', 'google-listings-and-ads' ); ?></h2>
		<?php foreach ( $inputs_data as $input ) : ?>
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->render_partial( 'inputs/input', [ 'input' => $input ] );
			?>
		<?php endforeach; ?>
	</div>
</div>

