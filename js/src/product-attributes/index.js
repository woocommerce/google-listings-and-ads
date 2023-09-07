/**
 * External dependencies
 */
import $ from 'jquery';

/**
 * Internal dependencies
 */
import './index.scss';

const applicableProductTypes = new Set( [
	// Simple product
	'simple',
	// Variable product
	'variable',
	// Product bundle from WooCommerce Product Bundles
	'bundle',
] );

// Originally, this extension relied on a WooCommerce core processing to show or hide
// the product data tab and meta box added to the product editing page.
//
// However, WooCommerce Subscriptions has an additional processing, which overrides
// the associated processed result in WooCommerce core.
//
// Since there is no available way to continue to work around it with `show_if_{productType}`
// or `hide_if_{productType}` CSS classes, a jQuery custom event dispatched by WooCommerce core
// is used to handle show or hide them instead.
//
// See:
// - https://github.com/woocommerce/google-listings-and-ads/issues/2086
//
// Ref:
// - https://github.com/woocommerce/woocommerce/blob/8.0.3/plugins/woocommerce/client/legacy/js/admin/meta-boxes-product.js#L204-L243
// - https://github.com/Automattic/woocommerce-subscriptions-core/blob/6.2.0/assets/js/admin/admin.js#L18-L88
// - https://github.com/woocommerce/woocommerce/blob/8.0.3/plugins/woocommerce/client/legacy/js/admin/meta-boxes-product.js#L130-L158
$( document ).on(
	'woocommerce-product-type-change',
	'body',
	( e, productType ) => {
		const shouldDisplay = applicableProductTypes.has( productType );
		$( '.gla_attributes_tab, .gla_meta_box' ).toggle( shouldDisplay );
	}
);
