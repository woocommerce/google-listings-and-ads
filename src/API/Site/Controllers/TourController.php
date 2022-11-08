<?php

	declare(strict_types=1);

	namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping;

	use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
	use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
	use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
	use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
	use WP_REST_Request as Request;
	use WP_REST_Response as Response;
	use Exception;

	defined('ABSPATH') || exit;

	/**
	 * Class for handling API requests for getting and update the tour visualizations.
	 *
	 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping
	 */
	class TourController extends BaseOptionsController
	{

		/**
		 * Constructor.
		 * @param RESTServer $server
		 */
		public function __construct(RESTServer $server)
		{
			parent::__construct($server);
		}

		/**
		 * Register rest routes with WordPress.
		 */
		public function register_routes(): void
		{
			/**
			 * GET The tour visualizations
			 */
			$this->register_route(
				'tours',
				[
					[
						'methods' => TransportMethods::READABLE,
						'callback' => $this->get_tours_read_callback(),
						'permission_callback' => $this->get_permission_callback(),
					],
					'schema' => $this->get_api_response_schema_callback(),
				],
			);

			/**
			 * POST Update the tour visualizations
			 */
			$this->register_route(
				'tours',
				[
					[
						'methods' => TransportMethods::CREATABLE,
						'callback' => $this->get_tours_create_callback(),
						'permission_callback' => $this->get_permission_callback(),
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
		protected function get_tours_read_callback(): callable
		{
			return function (Request $request) {
				try {
					$tours = $this->options->get(OptionsInterface::TOURS);
					return $this->prepare_item_for_response($tours, $request);
				} catch (Exception $e) {
					return new Response(['message' => $e->getMessage()], $e->getCode() ?: 400);
				}
			};
		}

		/**
		 * Callback function for saving the Tours
		 *
		 * @return callable
		 */
		protected function get_tours_create_callback(): callable {
			return function( Request $request ) {
				try {
					$tours = $request->get_param('tours');
					$this->options->update(OptionsInterface::TOURS, $tours);
					return new Response(
						[
							'status'  => 'success',
							'message' => __( 'Successfully updated the tours.', 'google-listings-and-ads' ),
						],
						200
					);
				} catch ( Exception $e ) {
					return $this->response_from_exception( $e );
				}
			};
		}


		/**
		 * Get the item schema properties for the controller.
		 *
		 * @return array
		 */
		protected function get_schema_properties(): array
		{
			return [
				'data' => [
					'type' => 'array',
					'description' => __('The tours data.', 'google-listings-and-ads'),
					'context' => ['view'],
					'readonly' => true,
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'id'                 => [
								'type'        => 'string',
								'description' => __( 'Tour ID.', 'google-listings-and-ads' ),
								'context'     => [ 'view' ],
							],
							'viewed'              => [
								'type'        => 'boolean',
								'description' => __( 'Whether the tour was already viewed.', 'google-listings-and-ads' ),
								'context'     => [ 'view' ],
							],
						],
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
		protected function get_schema_title(): string
		{
			return 'tours';
		}
	}
