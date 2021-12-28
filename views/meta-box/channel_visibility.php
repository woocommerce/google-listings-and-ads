<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/** @var PHPView $this */

/** @var int $product_id */
$product_id = $this->product_id;
/** @var WC_Product $product */
$product = $this->product;

$channel_visibility = $this->channel_visibility;

/** @var string */
$field_id = $this->field_id;
/** @var bool */
$is_setup_complete = $this->is_setup_complete;
/** @var string */
$get_started_url = $this->get_started_url;

/** @var string $sync_status */
if ( SyncStatus::HAS_ERRORS === $this->sync_status ) {
	$sync_status = __( 'Issues detected', 'google-listings-and-ads' );
} elseif ( ! is_null( $this->sync_status ) ) {
	$sync_status = ucfirst( str_replace( '-', ' ', $this->sync_status ) );
}
$show_status = ! empty( $sync_status ) && $channel_visibility === ChannelVisibility::SYNC_AND_SHOW && $this->sync_status !== SyncStatus::SYNCED;

/** @var array $issues */
$issues     = $this->issues;
$has_issues = ! empty( $issues );

$visibility_box_class = $has_issues ? 'notice-warning' : '';
$visibility_box_style = $has_issues ? 'border-left-style: solid' : 'background-color:#efefef';

$input_description = '';
$input_disabled    = false;
if ( ! $product->is_visible() ) {
	$channel_visibility = ChannelVisibility::DONT_SYNC_AND_SHOW;
	$show_status        = false;
	$input_disabled     = true;
	$input_description  = __( 'This product cannot be shown on any channel because it is hidden from your store catalog.', 'google-listings-and-ads' );
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
				'id'                => $field_id,
				'value'             => $channel_visibility,
				'label'             => __( 'Google Listing & Ads', 'google-listings-and-ads' ),
				'description'       => $input_description,
				'desc_tip'          => false,
				'options'           => [
					ChannelVisibility::SYNC_AND_SHOW      => __( 'Sync and show', 'google-listings-and-ads' ),
					ChannelVisibility::DONT_SYNC_AND_SHOW => __( 'Don\'t Sync and show', 'google-listings-and-ads' ),
				],
				'custom_attributes' => $custom_attributes,
				'wrapper_class'     => 'form-row form-row-full',
			]
		);
		?>
		<?php if ( $show_status ) : ?>
			<div
				class="sync-status notice-alt notice-large <?php echo esc_attr( $visibility_box_class ); ?>"
				style="<?php echo esc_attr( $visibility_box_style ); ?>"
			>
				<p><strong><?php esc_html_e( 'Google sync status', 'google-listings-and-ads' ); ?></strong></p>
				<p><?php echo esc_html( $sync_status ); ?></p>
				<?php if ( $has_issues ) : ?>
					<div class="gla-product-issues">
						<p><strong><?php esc_html_e( 'Issues', 'google-listings-and-ads' ); ?></strong></p>
						<ul>
							<?php foreach ( $issues as $issue ) : ?>
								<li><?php echo esc_html( $issue ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p><strong><?php esc_html_e( 'Google Listings & Ads', 'google-listings-and-ads' ); ?></strong></p>
		<p><?php esc_html_e( 'Complete setup to get your products listed on Google for free.', 'google-listings-and-ads' ); ?></p>
		<a href="<?php echo esc_attr( $get_started_url ); ?>"
			class="button"><?php esc_html_e( 'Complete setup', 'google-listings-and-ads' ); ?></a>
	<?php endif; ?>
</div>
