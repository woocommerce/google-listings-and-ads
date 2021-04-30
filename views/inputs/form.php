<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var array $form
 */
$form = $this->form;
?>

<div class="gla-input <?php echo esc_attr( $form['gla_wrapper_class'] ?? '' ); ?>">
	<?php
	if ( ! empty( $form['type'] ) ) {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_partial( path_join( 'inputs/', $form['type'] ), [ 'input' => $form ] );
	}

	if ( ! empty( $form['children'] ) ) {
		foreach ( $form['children'] as $form ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->render_partial( 'inputs/form', [ 'form' => $form ] );
		}
	}
	?>
</div>


