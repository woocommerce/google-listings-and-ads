<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductVisibilityController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ProductVisibilityController extends BaseController {

	use PluginHelper;

	/**
	 * @var ProductHelper $product_helper
	 */
	protected $product_helper;
	/**
	 * @var ProductMetaHandler $meta_handler
	 */
	protected $meta_handler;

	/**
	 * ProductVisibilityController constructor.
	 *
	 * @param RESTServer         $server
	 * @param ProductHelper      $product_helper
	 * @param ProductMetaHandler $meta_handler
	 */
	public function __construct( RESTServer $server, ProductHelper $product_helper, ProductMetaHandler $meta_handler ) {
		parent::__construct( $server );
		$this->product_helper = $product_helper;
		$this->meta_handler   = $meta_handler;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/product-visibility',
			[
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_update_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get a callback for updating products' channel visibility.
	 *
	 * @return callable
	 */
	protected function get_update_callback(): callable {
		return function( Request $request ) {
			$ids     = $request->get_param( 'ids' );
			$visible = $request->get_param( 'visible' ) ? ChannelVisibility::SYNC_AND_SHOW : ChannelVisibility::DONT_SYNC_AND_SHOW;

			$success = [];
			$errors  = [];
			foreach ( $ids as $product_id ) {
				try {
					$product_id = $this->product_helper->maybe_swap_for_parent_id( intval( $product_id ) );
					$product    = wc_get_product( $product_id );
					$product->update_meta_data( $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY ), $visible );
					$product->save();
					$success[] = $product_id;
				} catch ( Exception $e ) {
					$errors[] = $product_id;
				}
			}

			sort( $success );
			sort( $errors );

			return new Response(
				[
					'success' => $success,
					'errors'  => $errors,
				],
				count( $errors ) ? 400 : 200
			);
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'success' => [
				'type'              => 'array',
				'description'       => __( 'Products whose visibility was changed successfully.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'numeric',
				],
			],
			'errors'  => [
				'type'              => 'array',
				'description'       => __( 'Products whose visibility was not changed.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'numeric',
				],
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'product_visibility';
	}
}
