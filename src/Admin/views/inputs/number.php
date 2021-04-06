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
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\InputForm $form
 */
$form = $this->form;
?>

<input id="gla_<?php echo sanitize_key( $input->get_id() ); ?>"
	   class="input-number" type="number"
	   name="<?php echo esc_attr( $form->get_name() ); ?>[<?php echo esc_attr( $input->get_name() ); ?>]"
	   value="<?php echo esc_attr( sanitize_text_field( $input->get_value() ) ); ?>"/>
