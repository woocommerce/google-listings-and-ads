<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select $input
 */
$input = $this->input;

/**
 * @var string $form_name
 */
$form_name = $this->form_name;
?>

<select id="gla_<?php printf( '%s_%s', sanitize_key( $form_name ), sanitize_key( $input->get_id() ) ); ?>"
		class="input-text"
		name="<?php echo esc_attr( $form_name ); ?>[<?php echo esc_attr( $input->get_name() ); ?>]">
	<?php foreach ( $input->get_options() as $option_id => $option ) : ?>
		<option value="<?php echo esc_attr( sanitize_key( $option_id ) ); ?>"
			<?php echo $input->get_value() === $option_id ? 'selected="selected"' : ''; ?> >
			<?php echo esc_html( $option ); ?>
		</option>
	<?php endforeach; ?>
</select>
