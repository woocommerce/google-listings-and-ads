<?php
/**
 * Bulk Edit Coupons
 */

use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<fieldset class="inline-edit-col-right">
	<div id="google-listings-and-ads-fields-bulk" class="inline-edit-col">

		<div class="inline-edit-group">
			<label class="alignleft">
				<span class="title"><?php _e( 'Google visibility', 'google-listings-and-ads' ); ?></span>
				<span class="input-text-wrap">
					<select class="change_channel_visibility change_to" name="change_channel_visibility">
						<?php
						$options = array(
							''                                    => __( '— No change —', 'google-listings-and-ads' ),
						    ChannelVisibility::SYNC_AND_SHOW      => __( 'Show coupon', 'google-listings-and-ads' ),
						    ChannelVisibility::DONT_SYNC_AND_SHOW => __( 'Don\'t show coupon', 'google-listings-and-ads' ),
						);
						foreach ( $options as $key => $value ) {
							echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
						}
						?>
					</select>
				</span>
			</label>
		</div>
			
		<input type="hidden" name="woocommerce_gla_bulk_edit" value="1" />
		<input type="hidden" name="woocommerce_gla_bulk_edit_nonce" value="<?php echo wp_create_nonce( 'woocommerce_gla_bulk_edit_nonce' ); ?>" />
	</div>
</fieldset>
