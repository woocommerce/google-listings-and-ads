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
	<div class="alert alert-warning sync-status">
		<p><strong>Google sync status</strong></p>
		<p><?php echo $is_synced ? 'Synced' : 'Not synced'; ?></p>
		<?php if ( $is_synced && $has_issues ) : ?>
			<div class="gla-product-issues">
				<p><strong>Issues</strong></p>
				<ul>
					<li>Missing description</li>
				</ul>
			</div>
			<div class="gla-product-suggestion-actions">
				<p><strong>Suggested actions</strong></p>
				<ul>
					<li>Add a description for this product</li>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</div>
