<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var \Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView $this
 */

/**
 * @var int $product_id
 */
$product_id = $this->product_id;
/**
 * @var WC_Product $product
 */
$product = $this->product;

$sync_enabled = $this->sync_enabled;

/**
 * @var int $synced_at Timestamp
 */
$synced_at = $this->synced_at;
$is_synced = ! empty( $synced_at );

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
			'id'      => 'sync_enabled',
			'value'   => $sync_enabled,
			'label'   => __( 'Google Listing & Ads', 'google-listings-and-ads' ),
			'options' => [
				'yes' => __( 'Sync and show', 'google-listings-and-ads' ),
				'no'  => __( 'Don\'t Sync and show', 'google-listings-and-ads' ),
			],
		]
	);
	?>
	<?php if ( 0 === 1 ) : // Temporarily hide the sync status. See https://github.com/woocommerce/google-listings-and-ads/issues/152#issuecomment-776408166 ?>
	<div class="sync-status notice-alt notice-large notice-warning">
		<p><strong><?php esc_html_e( 'Google sync status', 'google-listings-and-ads' ); ?></strong></p>
		<p><?php echo $is_synced ? 'Synced' : 'Not synced'; ?></p>
		<?php if ( $is_synced && $has_issues ) : ?>
			<div class="gla-product-issues">
				<p><strong><?php esc_html_e( 'Issues', 'google-listings-and-ads' ); ?></strong></p>
				<ul>
					<li>Missing description</li>
				</ul>
			</div>
			<div class="gla-product-suggested-actions">
				<p><strong><?php esc_html_e( 'Suggested actions', 'google-listings-and-ads' ); ?></strong></p>
				<ul>
					<li>Add a description for this product</li>
				</ul>
			</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>
