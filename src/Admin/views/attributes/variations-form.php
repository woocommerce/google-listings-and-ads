<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/**
 * @var PHPView $this
 */

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\InputForm $form
 */
$form = $this->form;

/**
 * @var int
 */
$variation_id = $this->variation_id;

/**
 * @var int
 */
$loop_index = $this->loop_index;

$form_name = "{$form->get_name()}[{$loop_index}]";

?>

<div class="gla-metabox closed">
	<div class="options_group">
		<h2><?php esc_html_e( 'Google Listings & Ads', 'google-listings-and-ads' ); ?></h2>
		<?php foreach ( $form->get_inputs() as $input ) : ?>
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->render_partial(
				'src/Admin/views/inputs/input',
				[
					'input'     => $input,
					'form_name' => $form_name,
				]
			);
			?>
		<?php endforeach; ?>
	</div>
</div>

