<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CheckUnclaimedIncentive;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class IncentivesController
 *
 * Handles fetching and applying Choose-Your-Own (CYO) incentive offers via the Google Ads API.
 *
 * @since 3.3.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class IncentivesController extends BaseController implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * @var AdsIncentives
	 */
	protected $ads_incentives;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * @var CheckUnclaimedIncentive
	 */
	protected $check_unclaimed_incentive;

	/**
	 * IncentivesController constructor.
	 *
	 * @param RESTServer               $rest_server
	 * @param AdsIncentives            $ads_incentives
	 * @param WC                       $wc
	 * @param ActionSchedulerInterface $action_scheduler
	 * @param CheckUnclaimedIncentive  $check_unclaimed_incentive
	 */
	public function __construct( RESTServer $rest_server, AdsIncentives $ads_incentives, WC $wc, ActionSchedulerInterface $action_scheduler, CheckUnclaimedIncentive $check_unclaimed_incentive ) {
		parent::__construct( $rest_server );
		$this->ads_incentives            = $ads_incentives;
		$this->wc                        = $wc;
		$this->action_scheduler          = $action_scheduler;
		$this->check_unclaimed_incentive = $check_unclaimed_incentive;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/incentives',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_incentives_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->apply_incentive_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_apply_incentive_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * @return callable
	 */
	protected function get_incentives_callback(): callable {
		return function ( Request $request ) {
			$incentives = $this->ads_incentives->fetch_incentives();

			return $this->prepare_item_for_response( $incentives, $request );
		};
	}

	/**
	 * @return callable
	 */
	protected function apply_incentive_callback(): callable {
		return function ( Request $request ) {
			try {
				$incentive_id = $request->get_param( 'id' );
				$country_code = $this->wc->get_base_country();

				$result = $this->ads_incentives->apply_incentive( $incentive_id, $country_code );

				// Clear any stale flags from a previous failed attempt.
				$this->options->delete( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR );
				$this->options->delete( OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE );

				return new Response( $result );
			} catch ( ExceptionWithResponseData $e ) {
				$this->handle_apply_failure( $e );
				return $this->response_from_exception( $e );
			} catch ( Exception $e ) {
				$this->handle_apply_failure( $e );
				return new Response(
					[ 'message' => $e->getMessage() ],
					500
				);
			}
		};
	}

	/**
	 * Handle a failure when applying an incentive
	 *
	 * @param Exception $e
	 */
	protected function handle_apply_failure( Exception $e ): void {
		do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

		$this->options->update( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR, 'error' );

		$this->action_scheduler->schedule_immediate(
			$this->check_unclaimed_incentive->get_start_hook()->get_hook()
		);
	}

	/**
	 * Get the request params for applying an incentive.
	 *
	 * @return array
	 */
	protected function get_apply_incentive_params(): array {
		return [
			'id' => [
				'type'              => 'string',
				'description'       => __( 'The incentive ID to apply.', 'google-listings-and-ads' ),
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'type'                  => [
				'type'        => 'string',
				'description' => __( 'The offer type.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'termsAndConditionsUrl' => [
				'type'        => 'string',
				'description' => __( 'The consolidated terms and conditions URL.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'incentives'            => [
				'type'        => 'array',
				'description' => __( 'The available incentive offers.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'                    => [
							'type'        => 'string',
							'description' => __( 'The incentive ID.', 'google-listings-and-ads' ),
						],
						'type'                  => [
							'type'        => 'string',
							'description' => __( 'The incentive type.', 'google-listings-and-ads' ),
						],
						'offer'                 => [
							'type'        => 'string',
							'enum'        => [ 'low', 'medium', 'high' ],
							'description' => __( 'The offer level.', 'google-listings-and-ads' ),
						],
						'termsAndConditionsUrl' => [
							'type'        => 'string',
							'description' => __( 'The terms and conditions URL for this incentive.', 'google-listings-and-ads' ),
						],
						'requirement'           => [
							'type'       => 'object',
							'properties' => [
								'spend' => [
									'type'       => 'object',
									'properties' => [
										'awardAmount'    => [
											'type'       => 'object',
											'properties' => [
												'currencyCode' => [
													'type' => 'string',
												],
												'units'        => [
													'type' => 'string',
												],
											],
										],
										'requiredAmount' => [
											'type'       => 'object',
											'properties' => [
												'currencyCode' => [
													'type' => 'string',
												],
												'units'        => [
													'type' => 'string',
												],
											],
										],
									],
								],
							],
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
	protected function get_schema_title(): string {
		return 'incentives';
	}
}
