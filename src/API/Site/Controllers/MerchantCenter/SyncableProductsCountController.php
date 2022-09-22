<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncableProductsCountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class SyncableProductsCountController extends BaseController {

	/**
	 * @var TransientsInterface
	 */
	protected $transients;

	/**
	 * SyncableProductsCountController constructor.
	 *
	 * @param RESTServer          $server
	 * @param TransientsInterface $transients
	 */
	public function __construct( RESTServer $server, TransientsInterface $transients ) {
		parent::__construct( $server );
		$this->transients = $transients;
	}


	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$this->register_route(
			'mc/syncable-products-count',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_syncable_products_count_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for marking setup complete.
	 *
	 * @return callable
	 */
	protected function get_syncable_products_count_callback(): callable {
		return function( Request $request ) {
			$response = [
				'count' => null,
			];

			$count = (int) $this->transients->get( TransientsInterface::SYNCABLE_PRODUCTS_COUNT );

			if ( $count ) {
				$response['count'] = $count;
			}

			return $this->prepare_item_for_response( $response, $request );
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'count' => [
				'type'        => 'number',
				'description' => __( 'Amount of scheduled jobs which will sync products to Google.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
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
		return 'syncable_products_count';
	}
}
