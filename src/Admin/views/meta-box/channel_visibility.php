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

$sync_enabled = wc_string_to_bool( $this->sync_enabled );

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
	<label for="sync_enabled">Google Listing & Ads</label>
	<select name="sync_enabled" id="sync_enabled">
		<option value="yes" <?php echo $sync_enabled ? 'selected="selected"' : ''; ?>>Sync and show</option>
		<option value="no" <?php echo ! $sync_enabled ? 'selected="selected"' : ''; ?>>Don't Sync and show</option>
	</select>
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
