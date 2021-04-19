<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Number $input
 */
$input = $this->input;

/**
 * @var string $form_name
 */
$form_name = $this->form_name;
?>

<input id="gla_<?php printf( '%s_%s', esc_attr( $form_name ), esc_attr( $input->get_id() ) ); ?>"
	   class="input-number" type="number"
	   name="<?php echo esc_attr( $form_name ); ?>[<?php echo esc_attr( $input->get_name() ); ?>]"
	   value="<?php echo esc_attr( sanitize_text_field( $input->get_value() ) ); ?>"/>
