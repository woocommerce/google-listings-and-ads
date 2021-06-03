<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;

defined( 'ABSPATH' ) || exit;

/**
 * @var PHPView $this
 */

/**
 * @var int $product_id
 */
$product_id = $this->product_id;
/**
 * @var WC_Product $product
 */
$product = $this->product;

$visibility = $this->visibility;

/**
 * @var string $sync_status
 */
if ( SyncStatus::HAS_ERRORS === $this->sync_status ) {
	$sync_status = __( 'Issues detected', 'google-listings-and-ads' );
} else {
	$sync_status = ucfirst( str_replace( '-', ' ', $this->sync_status ) );
}
$show_status = $visibility === ChannelVisibility::SYNC_AND_SHOW && $this->sync_status !== SyncStatus::SYNCED;

/**
 * @var array $issues
 */
$issues     = $this->issues;
$has_issues = ! empty( $issues );
?>

<div class="gla-channel-visibility-box">
	<?php
	woocommerce_wp_select(
		[
			'id'      => 'visibility',
			'value'   => $visibility,
			'label'   => __( 'Google Listing & Ads', 'google-listings-and-ads' ),
			'options' => [
				ChannelVisibility::SYNC_AND_SHOW      => __( 'Sync and show', 'google-listings-and-ads' ),
				ChannelVisibility::DONT_SYNC_AND_SHOW => __( 'Don\'t Sync and show', 'google-listings-and-ads' ),
			],
		]
	);
	?>
	<?php if ( $show_status ) : ?>
	<div class="sync-status notice-alt notice-large notice-warning" style="border-left-style: solid">
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
</div>
