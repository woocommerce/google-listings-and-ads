<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\SelectWithTextInput $input
 */
$input = $this->input;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text $custom_value_input
 */
$custom_value_input = $input->get_text_input();

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\InputForm $form
 */
$form = $this->form;
?>

<select id="gla_<?php echo sanitize_key( $input->get_id() ); ?>"
		class="input-text"
		name="<?php echo esc_attr( $form->get_name() ); ?>[<?php echo esc_attr( $input->get_name() ); ?>]">
	<?php foreach ( $input->get_options() as $option_id => $option ) : ?>
		<option value="<?php echo esc_attr( sanitize_key( $option_id ) ); ?>"
			<?php echo $input->get_value() === $option_id ? 'selected="selected"' : ''; ?> >
			<?php echo esc_html( $option ); ?>
		</option>
	<?php endforeach; ?>
	<option value="<?php echo esc_attr( $input::CUSTOM_VALUE_KEY ); ?>" <?php echo $input->is_custom_value() ? 'selected="selected"' : ''; ?>>
		Enter your value
	</option>
</select>
<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo $this->render_partial(
	path_join( 'src/Admin/views/inputs/', $custom_value_input->get_type() ),
	[
		'input' => $custom_value_input,
		'form'  => $form,
	]
);
?>
