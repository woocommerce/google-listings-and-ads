<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use WC_Data;
use WC_Product;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ChannelVisibilityBlock
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product
 */
class ChannelVisibilityBlock implements Service, Registerable {

	public const PROPERTY = 'google_listings_and_ads__channel_visibility';

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * ChannelVisibilityBlock constructor.
	 *
	 * @param ProductHelper         $product_helper
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( ProductHelper $product_helper, MerchantCenterService $merchant_center ) {
		$this->product_helper  = $product_helper;
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Register hooks for querying and updating product via REST APIs.
	 */
	public function register(): void {
		if ( ! $this->merchant_center->is_setup_complete() ) {
			return;
		}

		// https://github.com/woocommerce/woocommerce/blob/8.6.0/plugins/woocommerce/includes/rest-api/Controllers/Version2/class-wc-rest-products-v2-controller.php#L182-L192
		add_filter( 'woocommerce_rest_prepare_product_object', [ $this, 'prepare_data' ], 10, 2 );

		// https://github.com/woocommerce/woocommerce/blob/8.6.0/plugins/woocommerce/includes/rest-api/Controllers/Version3/class-wc-rest-crud-controller.php#L200-L207
		// https://github.com/woocommerce/woocommerce/blob/8.6.0/plugins/woocommerce/includes/rest-api/Controllers/Version3/class-wc-rest-crud-controller.php#L247-L254
		add_action( 'woocommerce_rest_insert_product_object', [ $this, 'update_data' ], 10, 2 );
	}

	/**
	 * Get channel visibility data from the given product and add it to the given response.
	 *
	 * @param Response           $response Response to be added channel visibility data.
	 * @param WC_Product|WC_Data $product  WooCommerce product to get data.
	 *
	 * @return Response
	 */
	public function prepare_data( Response $response, WC_Data $product ): Response {
		if ( ! $product instanceof WC_Product ) {
			return $response;
		}

		$response->data[ self::PROPERTY ] = [
			'is_visible'         => $product->is_visible(),
			'channel_visibility' => $this->product_helper->get_channel_visibility( $product ),
			'sync_status'        => $this->product_helper->get_sync_status( $product ),
			'issues'             => $this->product_helper->get_validation_errors( $product ),
		];

		return $response;
	}

	/**
	 * Get channel visibility data from the given request and update it to the given product.
	 *
	 * @param WC_Product|WC_Data $product WooCommerce product to be updated.
	 * @param Request            $request Response to get the channel visibility data.
	 */
	public function update_data( WC_Data $product, Request $request ): void {
		if ( ! $product instanceof WC_Product || ! in_array( $product->get_type(), $this->get_visible_product_types(), true ) ) {
			return;
		}

		$params = $request->get_params();

		if ( ! isset( $params[ self::PROPERTY ] ) ) {
			return;
		}

		$channel_visibility = $params[ self::PROPERTY ]['channel_visibility'];

		if ( $channel_visibility !== $this->product_helper->get_channel_visibility( $product ) ) {
			$this->product_helper->update_channel_visibility( $product, $channel_visibility );
		}
	}

	/**
	 * Return the visible product types to control the hidden condition of the channel visibility block
	 * in the Product Block Editor.
	 *
	 * @return array
	 */
	public function get_visible_product_types(): array {
		return array_diff( ProductSyncer::get_supported_product_types(), [ 'variation' ] );
	}

	/**
	 * Return the config used for the input's block within the Product Block Editor.
	 *
	 * @return array
	 */
	public function get_block_config(): array {
		$options = [];

		foreach ( ChannelVisibility::get_value_options() as $key => $value ) {
			$options[] = [
				'label' => $value,
				'value' => $key,
			];
		}

		$attributes = [
			'property'          => self::PROPERTY,
			'options'           => $options,
			'valueOfSync'       => ChannelVisibility::SYNC_AND_SHOW,
			'valueOfDontSync'   => ChannelVisibility::DONT_SYNC_AND_SHOW,
			'statusOfSynced'    => SyncStatus::SYNCED,
			'statusOfHasErrors' => SyncStatus::HAS_ERRORS,
		];

		return [
			'id'         => 'google-listings-and-ads-product-channel-visibility',
			'blockName'  => 'google-listings-and-ads/product-channel-visibility',
			'attributes' => $attributes,
		];
	}
}
