<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface $input
 */
$input = $this->input;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\InputForm $form
 */
$form = $this->form;
?>

<p class="form-field">
	<label for="gla_<?php echo sanitize_key( $input->get_id() ); ?>"><?php echo esc_html( sanitize_text_field( $input->get_label() ) ); ?></label>
	<?php
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $this->render_partial(
		path_join( 'src/Admin/views/inputs/', $input->get_type() ),
		[
			'input' => $input,
			'form'  => $form,
		]
	);
	?>
	<?php if ( ! empty( $input->get_description() ) ) : ?>
		<?php echo wc_help_tip( $input->get_description() ); ?>
	<?php endif; ?>
</p>
