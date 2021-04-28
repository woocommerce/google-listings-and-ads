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

<div class="gla-metabox closed">
	<div class="options_group">
		<h2><?php esc_html_e( 'Google Listings & Ads', 'google-listings-and-ads' ); ?></h2>
		<?php foreach ( $inputs_data as $input ) : ?>
			<?php
			$input['wrapper_class'] = 'form-row form-row-full';
			if ( 'select-with-text-input' === $input['type'] ) {
				$input['wrapper_class']                = 'form-row form-row-first';
				$input['children'][0]['wrapper_class'] = 'form-row form-row-last';
			}
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->render_partial( 'inputs/input', [ 'input' => $input ] );
			?>
		<?php endforeach; ?>
	</div>
</div>

