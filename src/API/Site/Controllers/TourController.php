<?php

	declare(strict_types=1);

	namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

	use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
	use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
	use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
	use WP_REST_Request as Request;
	use WP_REST_Response as Response;
	use Exception;

	defined( 'ABSPATH' ) || exit;

	/**
	 * Class for handling API requests for getting and update the tour visualizations.
	 *
	 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
	 */
class TourController extends BaseOptionsController {

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
			"/tours/(?P<id>{$this->get_tour_id_regex()})",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_tours_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);

		/**
		 * POST Update the tour visualizations
		 */
		$this->register_route(
			'/tours',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_tours_create_callback(),
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
	protected function get_tours_read_callback(): callable {
		return function ( Request $request ) {
			try {
				$tour_id = $request->get_url_params()['id'];
				return $this->prepare_item_for_response( $this->get_tour( $tour_id ), $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Callback function for saving the Tours
	 *
	 * @return callable
	 */
	protected function get_tours_create_callback(): callable {
		return function ( Request $request ) {
			try {
				$tour_id           = $request->get_param( 'id' );
				$tours             = $this->get_tours();
				$tours[ $tour_id ] = $this->prepare_item_for_database( $request );

				if ( $this->options->update( OptionsInterface::TOURS, $tours ) ) {
					return new Response(
						[
							'status'  => 'success',
							'message' => __( 'Successfully updated the tour.', 'google-listings-and-ads' ),
						],
						200
					);
				} else {
					throw new Exception( __( 'Unable to updated the tour.', 'google-listings-and-ads' ), 400 );
				}
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the tours
	 *
	 * @return array|null The tours saved in databse
	 */
	private function get_tours(): ?array {
		return $this->options->get( OptionsInterface::TOURS );
	}

	/**
	 * Get the tour by Id
	 *
	 * @param string $tour_id The tour ID
	 * @return array The tour
	 * @throws Exception In case the tour is not found.
	 */
	private function get_tour( string $tour_id ): array {
		$tours = $this->get_tours();
		if ( ! isset( $tours[ $tour_id ] ) ) {
			throw new Exception( __( 'Tour not found', 'google-listings-and-ads' ), 404 );
		}

		return $tours[ $tour_id ];
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array The Schema properties
	 */
	protected function get_schema_properties(): array {
		return [
			'id'      => [
				'description'       => __( 'The Id for the tour.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
				'pattern'           => "^{$this->get_tour_id_regex()}$",
			],
			'checked' => [
				'description'       => __( 'Whether the tour was checked.', 'google-listings-and-ads' ),
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
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
		return 'tours';
	}

	/**
	 * Get the regex used for the Tour ID
	 *
	 * @return string The regex
	 */
	private function get_tour_id_regex(): string {
		return '[a-zA-z0-9-_]+';
	}
}
