<?php
declare(strict_types = 1);

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit();

/**
 *
 * @var PHPView $this
 */

/**
 *
 * @var int $coupon_id
 */
$coupon_id = $this->coupon_id;

/**
 *
 * @var WC_Coupon $coupon
 */
$coupon = $this->coupon;

$channel_visibility = $this->channel_visibility;

/**
 *
 * @var string
 */
$field_id = $this->field_id;

/**
 *
 * @var bool
 */
$is_setup_complete = $this->is_setup_complete;

/**
 *
 * @var bool
 */
$is_channel_supported = $this->is_channel_supported;

/**
 *
 * @var string
 */
$get_started_url = $this->get_started_url;

/**
 *
 * @var string $sync_status
 */
$is_synced = false;
if ( SyncStatus::HAS_ERRORS === $this->sync_status ) {
    $sync_status = __( 'Issues detected', 'google-listings-and-ads' );
} elseif ( SyncStatus::PENDING === $this->sync_status ){
    $sync_status = __( 'Pending for sync', 'google-listings-and-ads' );
} elseif ( SyncStatus::SYNCED === $this->sync_status ){
    $is_synced = true;
    $sync_status = __( 'Sent to Google', 'google-listings-and-ads' );
} elseif ( ! is_null( $this->sync_status ) ) {
    $sync_status = ucfirst( str_replace( '-', ' ', $this->sync_status ) );
}

$show_status = $channel_visibility ===
ChannelVisibility::SYNC_AND_SHOW && ( ! is_null( $this->sync_status ) );

$check_email_notice = __( 'Check your email for updates.', 'google-listings-and-ads' );

/**
 *
 * @var array $issues
 */
$issues = $this->issues;
$has_issues = ! empty( $issues );

$input_description = '';
$input_disabled = false;
if ( ! CouponSyncer::is_coupon_supported( $coupon ) ) {
    $channel_visibility = ChannelVisibility::DONT_SYNC_AND_SHOW;
    $show_status = false;
    $input_disabled = true;
    $input_description = __(
        $coupon->get_virtual() ? 'This coupon cannot be shown on public channel because it is hidden from your store.' : 'This coupon cannot be shown because the coupon restrictions are not supported to share in Google channel.',
        'google-listings-and-ads' );
} else if (! $is_channel_supported ) {
    $channel_visibility = ChannelVisibility::DONT_SYNC_AND_SHOW;
    $show_status = false;
    $input_disabled = true;
    $input_description = __(
        'This coupon visibility channel has not been supported in your store base country yet.',
        'google-listings-and-ads' );
}

$custom_attributes = [];
if ( $input_disabled ) {
    $custom_attributes['disabled'] = 'disabled';
}
?>

<div class="gla-channel-visibility-box">
	<?php if ( $is_setup_complete ) : ?>
        <?php
    woocommerce_wp_select( 
        [
            'id' => $field_id,
            'value' => $channel_visibility,
            'label' => __( 'Google Listing & Ads', 'google-listings-and-ads' ),
            'description' => $input_description,
            'desc_tip' => false,
            'options' => [
                ChannelVisibility::SYNC_AND_SHOW => __( 
                    'Show coupon on Google',
                    'google-listings-and-ads' ),
                ChannelVisibility::DONT_SYNC_AND_SHOW => __( 
                    'Don\'t show coupon on Google',
                    'google-listings-and-ads' )],
            'custom_attributes' => $custom_attributes,
            'wrapper_class' => 'form-row form-row-full'] );
    ?>
    	<?php if ( $show_status ) : ?>
    		<?php if ( $has_issues ) : ?>
    		<div class="sync-status notice-alt notice-large notice-warning"
				style="border-left-style: solid">
    			<div class="gla-product-issues">
        			<p>
        				<strong><?php esc_html_e( 'Coupon issues', 'google-listings-and-ads' ); ?></strong>
        			</p>
        			<ul>
            					<?php foreach ( $issues as $issue ) : ?>
            					<li><?php echo esc_html( $issue ); ?></li>
            					<?php endforeach; ?>
    				</ul>
				</div>
			</div>
			<?php else : ?>
			<div class="sync-status notice-alt notice-large" style="background-color:#efefef">
				<p><strong><?php echo esc_html( $sync_status ); ?></strong></p>
			</div>
    			<?php if ( $is_synced ) : ?>
        			<div style="padding: 10px 0px" >
        				<?php echo esc_html( $check_email_notice); ?>
        			</div>
    			<?php endif;?>
    		<?php endif; ?>
    	
    	<?php endif; ?>
	<?php else : ?>
		<p>
		<strong><?php esc_html_e( 'Google Listings & Ads', 'google-listings-and-ads' ); ?></strong>
	</p>
	<p><?php esc_html_e( 'Complete setup to get your coupon listed on Google for free.', 'google-listings-and-ads' ); ?></p>
	<a href="<?php echo esc_attr( $get_started_url ); ?>" class="button"><?php esc_html_e( 'Complete setup', 'google-listings-and-ads' ); ?></a>
	<?php endif; ?>
</div>
