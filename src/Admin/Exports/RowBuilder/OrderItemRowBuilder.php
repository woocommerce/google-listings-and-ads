<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\RowBuilder;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Contracts\ExportableRowBuilderInterface;
use WC_Order_Item;
use WC_Order_Refund;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderItemRowBuilder
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\RowBuilder
 */
class OrderItemRowBuilder implements ExportableRowBuilderInterface {

	/**
	 * Create a row of an order item for the Merchant Reported Conversions CSV.
	 *
	 * @param mixed $item
	 * @return array|null
	 */
	public function build_row( $item ): ?array {
		if ( ! $item instanceof WC_Order_Item ) {
			return null;
		}

		$order = $item->get_order();

		// Determine if the order is a refund.
		$is_refund = $order instanceof WC_Order_Refund;
		$refund    = null;

		if ( $is_refund ) {
			$refund = $order;
			$order  = wc_get_order( $order->get_parent_id() );
		}

		// Refunds use negative quantity.
		$quantity = absint( $item->get_quantity() );

		return [
			/**
			 * The type of this transaction.
			 *
			 * Possible values: purchase or refund
			 *
			 * @param string
			 */
			'transaction_type'           => $is_refund ? 'refund' : 'purchase',

			/**
			 * The Google Merchant Center ID.
			 * Must be a positive integer number.
			 *
			 * @param int
			 */
			'gmc_merchant_id'            => get_option( 'gla_merchant_id', '' ),

			/**
			 * Merchant specific unique identifier of this transaction.
			 *
			 * @param string
			 */
			'transaction_id'             => $is_refund ? (string) $refund->get_id() : (string) $order->get_id(),

			/**
			 * Merchant specific identifier of the product.
			 *
			 * @param string
			 */
			'item_id'                    => $item->get_product_id(),

			/**
			 * The name of the product
			 *
			 * @param string
			 */
			'item_name'                  => $item->get_name(),

			/**
			 * The date of the transaction.
			 *
			 * If this is a refund, it represents the date of the purchase.
			 * Must be in ISO 8601 format with timezone offset.
			 *
			 * Example: 2024-02-09T00:00:00−02:00
			 *
			 * @param string
			 */
			'transaction_date'           => $order->get_date_created()->format( 'c' ),

			/**
			 * The date of the refund.
			 *
			 * Should only be present on transactions of type "refund" and represents the date when the refund
			 * was successfully accepted (i.e the date when merchant receives & processes the return)
			 *
			 * Must be in ISO 8601 format with timezone offset
			 *
			 * Example: 2024-02-09T00:00:00−02:00
			 *
			 * @param string
			 */
			'refund_date'                => $is_refund ? $refund->get_date_created()->date( 'c' ) : '',

			/**
			 * The number of items of this product in this transaction.
			 *
			 * This value is always positive, even in the case of refunds.
			 *
			 * @param int
			 */
			'quantity'                   => $quantity,

			/**
			 * Item unit price.
			 *
			 * List price of 1 unit of this item, before any discount.
			 *
			 * Must be in currency provided by the “currency_code” field. Does not include tax or shipping.
			 * This value is always positive, even in the case of refunds.
			 *
			 * Example: 1000.00
			 *
			 * @param float
			 */
			'item_unit_price'            => absint( $item->get_subtotal() ) / $quantity,

			/**
			 * Item unit discounted price.
			 *
			 * Discounted price of 1 unit of this item (i.e. sale price of 1 unit of this item,
			 * but before transaction level discounts. If the item is not on sale, this
			 * should match item_unit_price)
			 *
			 * Must be in currency provided by the “currency_code” field.
			 *
			 * Does not include tax or shipping.
			 *
			 * This value is always positive, even in the case of refunds
			 *
			 * Example: 500.00
			 *
			 * @param float
			 */
			'item_unit_discounted_price' => absint( $item->get_total() ) / $quantity,

			/**
			 * Item Price.
			 *
			 * Total list price of this quantity of this item in this transaction (before any discounts)
			 *
			 * Essentially, item_unit_price * quantity
			 *
			 * Does not include tax or shipping.
			 *
			 * Must be in currency provided by the “currency_code” field.
			 *
			 * This value is always positive, even in the case of refunds.
			 *
			 * Example: 99.99
			 *
			 * @param float
			 */
			'item_price'                 => absint( $item->get_subtotal() ),

			/**
			 * Item discounted price.
			 *
			 * Discounted price of this quantity of this item in this transaction (but before
			 * transaction level discount. If the item is not on sale, this should match item_price)
			 *
			 * This value is expected to match item_discounted_unit_price * quantity.
			 *
			 * Must be in currency provided by the “currency_code” field.
			 *
			 * This value is always positive, even in the case of refunds.
			 *
			 * Example: 100.52
			 *
			 * @param float
			 */
			'item_discounted_price'      => absint( $item->get_total() ),

			/**
			 * Coupons.
			 *
			 * Comma separated list of all coupons applied to this transaction.
			 *
			 * Example: BLACKFRIDAY20,BLACKFRIDAY10
			 *
			 * @param string
			 */
			'coupons'                    => implode( ',', $order->get_coupon_codes() ),

			/**
			 * Transaction tax.
			 *
			 * Total tax of the entire transaction.
			 *
			 * Must be in currency provided by the “currency_code” field.
			 *
			 * Example: 24.00
			 *
			 * @param float
			 */
			'transaction_tax'            => $order->get_total_tax(),

			/**
			 * Transaction shipping.
			 *
			 * Total shipping cost of the entire transaction.
			 *
			 * Must be in currency provided by the “currency_code” field.
			 *
			 * Example: 15.00
			 *
			 * @param float
			 */
			'transaction_shipping'       => $order->get_shipping_total(),

			/**
			 * Transaction total.
			 *
			 * Total cost of the entire transaction, including taxes, shipping and any discounts.
			 *
			 * In the case of a refund, this is a total value that is being refunded as part of this refund event.
			 *
			 * Must be in currency provided by the “currency_code” field.
			 *
			 * Example: 150.00
			 *
			 * @param float
			 */
			'transaction_total'          => abs( (float) $order->get_total() ),

			/**
			 * Currency code.
			 *
			 * Currency code of all prices in this transaction.
			 *
			 * Must be in ISO 4217 format.
			 *
			 * Example: USD
			 *
			 * @param string
			 */
			'currency_code'              => $order->get_currency(),

			/**
			 * Landing page URL.
			 *
			 * The merchant URL that YouTube directed the viewer to when they clicked an offer on YouTube.
			 *
			 * If YouTube directed the viewer to the merchant several times, this is expected to be the URL of the last click.
			 *
			 * This URL is expected to include various UTM parameters.
			 *
			 * Example: https://example.store/product?utm_source=youtube&utm_content=YT3-NMQzOvlXnr65R_e8QybmMIr1vAL4wG2_A8X0puoaTLO4wkrGc4MhnWOBCbX809aCd-wBYk2YnLOAfNw3M6U
			 *
			 * @param string
			 */
			'landing_page_url'           => $order->get_meta( '_wc_order_attribution_session_entry' ),

			/**
			 * Attribution ID.
			 *
			 * The value of utm_content parameter in the landing page url.
			 *
			 * YouTube requires this value to attribute the conversion to the correct YouTube creator.
			 *
			 * Example: YT3-NMQzOvlXnr65R_e8QybmMIr1vAL4wG2_A8X0puoaTLO4wkrGc4MhnWOBCbX809aCd-wBYk2YnLOAfNw3M6U
			 *
			 * @param string
			 */
			'attribution_id'             => $order->get_meta( '_wc_order_attribution_utm_content' ),

			/**
			 * Country code.
			 *
			 * Country code of the transacting user
			 *
			 * Example: US
			 *
			 * @param string
			 */
			'country_code'               => $order->get_billing_country(),

			/**
			 * The ID of the GMC Sub-account associated with the item(s) purchased.
			 *
			 * Required if the transacting Merchant of Record (MoR) is a multi-client account (MCA).
			 *
			 * Must be a positive integer number.
			 *
			 * Example: 1234
			 *
			 * @param int
			 */
			'subaccount_id'              => '',

			/**
			 * Reversal reason.
			 *
			 * Optional. When a transaction is reversed, this field should be populated with the reason for the reversal.
			 *
			 * Example: Cancelled by user.
			 *
			 * @param string
			 */
			'reversal_reason'            => $is_refund ? $refund->get_reason() : '',
		];
	}
}
