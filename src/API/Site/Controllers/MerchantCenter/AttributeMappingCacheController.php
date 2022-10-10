<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests handling cache for Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class AttributeMappingCacheController extends BaseOptionsController {

	/**
	 * @var TransientsInterface
	 */
	private TransientsInterface $transients;


	/**
	 * AttributeMappingCacheController constructor.
	 *
	 * @param RESTServer          $server
	 * @param TransientsInterface $transients
	 */
	public function __construct( RESTServer $server, TransientsInterface $transients ) {
		parent::__construct( $server );
		$this->transients = $transients;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		/**
		 * DELETE Flush the cache
		 */
		$this->register_route(
			'mc/mapping/cache',
			[
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->delete_mapping_attributes_cache_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Callback function for flushing the cache
	 *
	 * @return callable
	 */
	protected function delete_mapping_attributes_cache_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->prepare_item_for_response( [ 'message' => __( 'Attribute Mapping cache was flushed', 'google-listings-and-ads' ) ], $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'message' => [
				'type'        => 'string',
				'description' => __( 'Request result message.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
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
		return 'attribute_mapping_cache';
	}
}
