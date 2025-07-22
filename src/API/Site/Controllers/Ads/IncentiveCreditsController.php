<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class IncentiveCreditsController
 *
 * Handles fetching Google Ads incentive credits.
 *
 * ContainerAware used for:
 * - Middleware
 *
 * @since 3.2.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class IncentiveCreditsController extends BaseController implements ContainerAwareInterface {

	use ContainerAwareTrait;

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/incentive-credits',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_incentive_credits_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * @return callable
	 */
	protected function get_incentive_credits_callback(): callable {
		return function ( Request $request ) {
			try {
				// Fetch incentive credits from the Middleware.
				$incentive_credits = $this->container->get( Middleware::class )->get_incentive_credits();

				if ( empty( $incentive_credits ) ) {
					return new Response(
						[
							'message' => __( 'No incentive credits found.', 'google-listings-and-ads' ),
						],
						404
					);
				}

				return $this->prepare_item_for_response( $incentive_credits, $request );
			} catch ( Exception $e ) {
				return new Response(
					[
						'message' => $e->getMessage(),
					],
					500
				);
			}
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'country'      => [
				'type'        => 'string',
				'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'currency'     => [
				'type'        => 'string',
				'description' => __( 'Currency code in ISO 4217 format.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'ads_currency' => [
				'type'        => 'string',
				'description' => __( 'Ads account currency code in ISO 4217 format.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'spending'     => [
				'type'        => 'number',
				'description' => __( 'Spending amount required to earn the incentive credit.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'credit'       => [
				'type'        => 'number',
				'description' => __( 'Incentive credit amount.', 'google-listings-and-ads' ),
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
		return 'incentive-credits';
	}
}
