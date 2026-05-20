<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( AbilityDefinition::class ) ) {
	return;
}

/**
 * Reads Google for WooCommerce setup state from local plugin state.
 */
class GetSetupStatus implements AbilityDefinition {

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'google-listings-and-ads/get-setup-status';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return [
			'label'               => __( 'Get Google for WooCommerce setup status', 'google-listings-and-ads' ),
			'description'         => __( 'Read local Google for WooCommerce account, onboarding, and sync setup status.', 'google-listings-and-ads' ),
			'category'            => 'woocommerce',
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => [ __CLASS__, 'execute' ],
			'permission_callback' => [ __CLASS__, 'can_read_setup_status' ],
			'meta'                => [
				'show_in_rest' => true,
				'mcp'          => [
					'public' => true,
					'type'   => 'tool',
				],
				'annotations'  => [
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				],
			],
		];
	}

	/**
	 * Execute the ability.
	 *
	 * @return array
	 */
	public static function execute(): array {
		$container       = \woogle_get_container();
		$options         = $container->get( OptionsInterface::class );
		$merchant_center = $container->get( MerchantCenterService::class );
		$ads             = $container->get( AdsService::class );

		return self::build_response( $options, $merchant_center, $ads );
	}

	/**
	 * Build the ability response from local plugin services.
	 *
	 * @param OptionsInterface      $options         Options service.
	 * @param MerchantCenterService $merchant_center Merchant Center service.
	 * @param AdsService            $ads             Ads service.
	 * @return array
	 */
	public static function build_response(
		OptionsInterface $options,
		MerchantCenterService $merchant_center,
		AdsService $ads
	): array {
		return [
			'connections'     => [
				'google_connected'  => (bool) $options->get( OptionsInterface::GOOGLE_CONNECTED, false ),
				'jetpack_connected' => (bool) $options->get( OptionsInterface::JETPACK_CONNECTED, false ),
				'wp_tos_accepted'   => (bool) $options->get( OptionsInterface::WP_TOS_ACCEPTED, false ),
			],
			'merchant_center' => [
				'merchant_id'          => self::get_integer_option( $options, OptionsInterface::MERCHANT_ID ),
				'setup_complete'       => $merchant_center->is_setup_complete(),
				'connected'            => $merchant_center->is_connected(),
				'setup_completed_at'   => self::get_timestamp_option( $options, OptionsInterface::MC_SETUP_COMPLETED_AT ),
				'account_state'        => self::format_account_state( self::get_array_option( $options, OptionsInterface::MERCHANT_ACCOUNT_STATE ) ),
				'target_audience'      => self::format_target_audience( self::get_array_option( $options, OptionsInterface::TARGET_AUDIENCE ) ),
				'contact_info_setup'   => (bool) $options->get( OptionsInterface::CONTACT_INFO_SETUP, false ),
				'site_verification'    => self::format_site_verification( self::get_array_option( $options, OptionsInterface::SITE_VERIFICATION ) ),
				'shipping_rates_setup' => null !== $options->get( OptionsInterface::SHIPPING_RATES, null ),
				'shipping_times_setup' => null !== $options->get( OptionsInterface::SHIPPING_TIMES, null ),
			],
			'ads'             => [
				'ads_id'                         => self::get_integer_option( $options, OptionsInterface::ADS_ID ),
				'setup_started'                  => $ads->is_setup_started(),
				'setup_complete'                 => $ads->is_setup_complete(),
				'connected'                      => $ads->is_connected(),
				'setup_completed_at'             => self::get_timestamp_option( $options, OptionsInterface::ADS_SETUP_COMPLETED_AT ),
				'account_state'                  => self::format_account_state( self::get_array_option( $options, OptionsInterface::ADS_ACCOUNT_STATE ) ),
				'enhanced_conversions_enabled'   => (bool) $options->get( OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED, false ),
				'eu_political_declarations_done' => (bool) $options->get( OptionsInterface::ADS_EU_POLITICAL_DECLARATIONS_COMPLETE, false ),
				'has_unclaimed_incentive'        => (bool) $options->get( OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE, false ),
				'campaign_convert_status'        => self::format_campaign_convert_status( self::get_array_option( $options, OptionsInterface::CAMPAIGN_CONVERT_STATUS ) ),
			],
			'onboarding'      => [
				'completed'    => (bool) $options->get( OptionsInterface::ONBOARDING_COMPLETED_AT, false ),
				'completed_at' => self::get_timestamp_option( $options, OptionsInterface::ONBOARDING_COMPLETED_AT ),
			],
			'sync'            => [
				'syncable_products_count' => (int) $options->get( OptionsInterface::SYNCABLE_PRODUCTS_COUNT, 0 ),
				'last_full_product_sync'  => self::get_timestamp_option( $options, OptionsInterface::UPDATE_ALL_PRODUCTS_LAST_SYNC ),
				'api_pull_sync_mode'      => self::format_api_pull_sync_mode( self::get_array_option( $options, OptionsInterface::API_PULL_SYNC_MODE ) ),
			],
			'plugin_version'  => defined( 'WC_GLA_VERSION' ) ? WC_GLA_VERSION : '',
		];
	}

	/**
	 * Check whether the current user can read setup status.
	 *
	 * @return bool
	 */
	public static function can_read_setup_status(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get an option as an array.
	 *
	 * @param OptionsInterface $options Options service.
	 * @param string           $name    Option name.
	 * @return array
	 */
	private static function get_array_option( OptionsInterface $options, string $name ): array {
		$value = $options->get( $name, [] );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * Get an option as an integer without using request-overridable helper methods.
	 *
	 * @param OptionsInterface $options Options service.
	 * @param string           $name    Option name.
	 * @return int
	 */
	private static function get_integer_option( OptionsInterface $options, string $name ): int {
		return (int) $options->get( $name, 0 );
	}

	/**
	 * Get a timestamp option as an integer.
	 *
	 * @param OptionsInterface $options Options service.
	 * @param string           $name    Option name.
	 * @return int
	 */
	private static function get_timestamp_option( OptionsInterface $options, string $name ): int {
		return (int) $options->get( $name, 0 );
	}

	/**
	 * Format account setup state without leaking step payloads or exception data.
	 *
	 * @param array $state Account state option value.
	 * @return array
	 */
	private static function format_account_state( array $state ): array {
		$steps = [];

		foreach ( $state as $step => $details ) {
			if ( ! is_array( $details ) ) {
				continue;
			}

			$steps[] = [
				'step'   => sanitize_key( (string) $step ),
				'status' => isset( $details['status'] ) ? (int) $details['status'] : 0,
			];
		}

		return $steps;
	}

	/**
	 * Format the target audience option.
	 *
	 * @param array $target_audience Target audience option value.
	 * @return array
	 */
	private static function format_target_audience( array $target_audience ): array {
		return [
			'location'  => isset( $target_audience['location'] ) ? sanitize_key( (string) $target_audience['location'] ) : '',
			'countries' => array_values(
				array_filter(
					array_map(
						static function ( $country ): string {
							return strtoupper( sanitize_text_field( (string) $country ) );
						},
						(array) ( $target_audience['countries'] ?? [] )
					)
				)
			),
		];
	}

	/**
	 * Format site verification state without exposing the stored verification meta tag.
	 *
	 * @param array $site_verification Site verification option value.
	 * @return array
	 */
	private static function format_site_verification( array $site_verification ): array {
		return [
			'verified' => isset( $site_verification['verified'] ) ? sanitize_key( (string) $site_verification['verified'] ) : '',
		];
	}

	/**
	 * Format the campaign conversion status option.
	 *
	 * @param array $convert_status Campaign conversion status option value.
	 * @return array
	 */
	private static function format_campaign_convert_status( array $convert_status ): array {
		return [
			'status'  => isset( $convert_status['status'] ) ? sanitize_key( (string) $convert_status['status'] ) : 'unknown',
			'updated' => isset( $convert_status['updated'] ) ? (int) $convert_status['updated'] : 0,
		];
	}

	/**
	 * Format API pull sync mode by data type.
	 *
	 * @param array $sync_mode API pull sync mode option value.
	 * @return array
	 */
	private static function format_api_pull_sync_mode( array $sync_mode ): array {
		$modes = [];

		foreach ( $sync_mode as $data_type => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$modes[] = [
				'data_type' => sanitize_key( (string) $data_type ),
				'push'      => (bool) ( $entry['push'] ?? false ),
				'pull'      => (bool) ( $entry['pull'] ?? false ),
			];
		}

		return $modes;
	}

	/**
	 * Get the output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'connections'     => [
					'type'                 => 'object',
					'properties'           => [
						'google_connected'  => [ 'type' => 'boolean' ],
						'jetpack_connected' => [ 'type' => 'boolean' ],
						'wp_tos_accepted'   => [ 'type' => 'boolean' ],
					],
					'additionalProperties' => false,
				],
				'merchant_center' => [
					'type'                 => 'object',
					'properties'           => [
						'merchant_id'          => [ 'type' => 'integer' ],
						'setup_complete'       => [ 'type' => 'boolean' ],
						'connected'            => [ 'type' => 'boolean' ],
						'setup_completed_at'   => [ 'type' => 'integer' ],
						'account_state'        => [
							'type'  => 'array',
							'items' => [
								'type'                 => 'object',
								'properties'           => [
									'step'   => [ 'type' => 'string' ],
									'status' => [ 'type' => 'integer' ],
								],
								'required'             => [ 'step', 'status' ],
								'additionalProperties' => false,
							],
						],
						'target_audience'      => [
							'type'                 => 'object',
							'properties'           => [
								'location'  => [ 'type' => 'string' ],
								'countries' => [
									'type'  => 'array',
									'items' => [ 'type' => 'string' ],
								],
							],
							'required'             => [ 'location', 'countries' ],
							'additionalProperties' => false,
						],
						'contact_info_setup'   => [ 'type' => 'boolean' ],
						'site_verification'    => [
							'type'                 => 'object',
							'properties'           => [
								'verified' => [ 'type' => 'string' ],
							],
							'required'             => [ 'verified' ],
							'additionalProperties' => false,
						],
						'shipping_rates_setup' => [ 'type' => 'boolean' ],
						'shipping_times_setup' => [ 'type' => 'boolean' ],
					],
					'additionalProperties' => false,
				],
				'ads'             => [
					'type'                 => 'object',
					'properties'           => [
						'ads_id'                         => [ 'type' => 'integer' ],
						'setup_started'                  => [ 'type' => 'boolean' ],
						'setup_complete'                 => [ 'type' => 'boolean' ],
						'connected'                       => [ 'type' => 'boolean' ],
						'setup_completed_at'              => [ 'type' => 'integer' ],
						'account_state'                   => [
							'type'  => 'array',
							'items' => [
								'type'                 => 'object',
								'properties'           => [
									'step'   => [ 'type' => 'string' ],
									'status' => [ 'type' => 'integer' ],
								],
								'required'             => [ 'step', 'status' ],
								'additionalProperties' => false,
							],
						],
						'enhanced_conversions_enabled'    => [ 'type' => 'boolean' ],
						'eu_political_declarations_done'  => [ 'type' => 'boolean' ],
						'has_unclaimed_incentive'         => [ 'type' => 'boolean' ],
						'campaign_convert_status'         => [
							'type'                 => 'object',
							'properties'           => [
								'status'  => [ 'type' => 'string' ],
								'updated' => [ 'type' => 'integer' ],
							],
							'required'             => [ 'status', 'updated' ],
							'additionalProperties' => false,
						],
					],
					'additionalProperties' => false,
				],
				'onboarding'      => [
					'type'                 => 'object',
					'properties'           => [
						'completed'    => [ 'type' => 'boolean' ],
						'completed_at' => [ 'type' => 'integer' ],
					],
					'additionalProperties' => false,
				],
				'sync'            => [
					'type'                 => 'object',
					'properties'           => [
						'syncable_products_count' => [ 'type' => 'integer' ],
						'last_full_product_sync'  => [ 'type' => 'integer' ],
						'api_pull_sync_mode'      => [
							'type'  => 'array',
							'items' => [
								'type'                 => 'object',
								'properties'           => [
									'data_type' => [ 'type' => 'string' ],
									'push'      => [ 'type' => 'boolean' ],
									'pull'      => [ 'type' => 'boolean' ],
								],
								'required'             => [ 'data_type', 'push', 'pull' ],
								'additionalProperties' => false,
							],
						],
					],
					'additionalProperties' => false,
				],
				'plugin_version'  => [ 'type' => 'string' ],
			],
			'required'             => [ 'connections', 'merchant_center', 'ads', 'onboarding', 'sync', 'plugin_version' ],
			'additionalProperties' => false,
		];
	}
}
