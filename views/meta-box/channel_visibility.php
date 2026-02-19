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

<div id="gla-channel-visibility-box"></div>
