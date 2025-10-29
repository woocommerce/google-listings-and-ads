<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class FeaturesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class FeaturesController extends BaseOptionsController {

	/**
	 * Constructor.
	 *
	 * @param RESTServer $server
	 */
	public function __construct( RESTServer $server ) {
		parent::__construct( $server );
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		/**
		 * GET The tour visualizations
		 */
		$this->register_route(
			'/features',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_features_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Callback function for returning the tours
	 *
	 * @return callable
	 */
	protected function get_features_read_callback(): callable {
		return function () {
			return $this->options->get( OptionsInterface::WCS_FEATURE_FLAGS, [] );
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array The Schema properties
	 */
	protected function get_schema_properties(): array {
		return [];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'features';
	}
}
