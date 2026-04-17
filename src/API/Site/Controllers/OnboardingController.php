<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class OnboardingController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class OnboardingController extends BaseOptionsController {

	use EmptySchemaPropertiesTrait;

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'google/onboarding/complete',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_complete_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback for completing onboarding.
	 *
	 * @return callable
	 */
	protected function get_complete_callback(): callable {
		return function ( Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			do_action( 'woocommerce_gla_onboarding_completed' );

			return new Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully onboarded service based merchant.', 'google-listings-and-ads' ),
				],
				200
			);
		};
	}

	/**
	 * Get the callback for deleting onboarding completion.
	 *
	 * @return callable
	 */
	protected function get_delete_callback(): callable {
		return function ( Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			$this->options->delete( OptionsInterface::ONBOARDING_COMPLETED_AT );

			return new Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully deleted onboarding completion status.', 'google-listings-and-ads' ),
				],
				200
			);
		};
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'onboarding';
	}
}
