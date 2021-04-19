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
 * @var string $form_name
 */
$form_name = $this->form_name;
?>

<select id="gla_<?php printf( '%s_%s', esc_attr( $form_name ), esc_attr( $input->get_id() ) ); ?>"
		class="input-text"
		name="<?php echo esc_attr( $form_name ); ?>[<?php echo esc_attr( $input->get_name() ); ?>]">
	<?php foreach ( $input->get_options() as $option_id => $option ) : ?>
		<option value="<?php echo esc_attr( sanitize_key( $option_id ) ); ?>"
			<?php selected( $input->get_value(), $option_id ); ?> >
			<?php echo esc_html( $option ); ?>
		</option>
	<?php endforeach; ?>
	<option value="<?php echo esc_attr( $input::CUSTOM_VALUE_KEY ); ?>" <?php selected( $input->is_custom_value() ); ?>>
		<?php esc_html_e( 'Enter your value', 'google-listings-and-ads' ); ?>
	</option>
</select>
<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo $this->render_partial(
	path_join( 'inputs/', $custom_value_input->get_type() ),
	[
		'input'     => $custom_value_input,
		'form_name' => $form_name,
	]
);
?>
