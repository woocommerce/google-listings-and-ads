<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Exception;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterStatusAbility
 *
 * Tells an agent whether the store is connected to Merchant Center, how its products are doing as a whole,
 * and which issues Google reports against the account. None of this belongs to one product.
 * WooCommerce registers it through the `woocommerce_ability_definition_classes` filter.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 *
 * @since x.x.x
 */
class MerchantCenterStatusAbility implements Service, Registerable, AbilityDefinition {

	public const ABILITY_NAME = 'google-for-woocommerce/get-merchant-center-status';

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * MerchantCenterStatusAbility constructor.
	 *
	 * @param MerchantCenterService $merchant_center
	 * @param MerchantStatuses      $merchant_statuses
	 */
	public function __construct( MerchantCenterService $merchant_center, MerchantStatuses $merchant_statuses ) {
		$this->merchant_center   = $merchant_center;
		$this->merchant_statuses = $merchant_statuses;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_ability_definition_classes',
			static function ( $classes ) {
				$classes   = is_array( $classes ) ? $classes : [];
				$classes[] = self::class;
				return $classes;
			}
		);
	}

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::ABILITY_NAME;
	}

	/**
	 * Get the arguments used to register the ability.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return [
			'label'               => __( 'Get Merchant Center status', 'google-listings-and-ads' ),
			'description'         => __( 'Get whether the store is connected to Google Merchant Center, product totals by status, and the issues Google reports against the account. Connecting an account is done by the merchant in wp-admin. Product data is on the product abilities under extensions.google.', 'google-listings-and-ads' ),
			'category'            => 'woocommerce',
			'output_schema'       => [
				'type'       => 'object',
				'properties' => [
					'connected'      => [ 'type' => 'boolean' ],
					'setup_status'   => [ 'type' => 'string' ],
					'setup_step'     => [ 'anyOf' => [ [ 'type' => 'string' ], [ 'type' => 'null' ] ] ],
					'loading'        => [
						'type'        => 'boolean',
						'description' => __( 'Whether a refresh is running. Ask again later for the statistics.', 'google-listings-and-ads' ),
					],
					'statistics'     => [ 'anyOf' => [ [ 'type' => 'object' ], [ 'type' => 'null' ] ] ],
					'account_issues' => [
						'type'  => 'array',
						'items' => [ 'type' => 'object' ],
					],
				],
			],
			'execute_callback'    => static function () {
				return woogle_get_container()->get( self::class )->get_status();
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
			},
			'meta'                => [
				'show_in_rest' => true,
				'mcp'          => [
					'public' => true,
					'type'   => 'tool',
				],
				'annotations'  => [
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				],
			],
		];
	}

	/**
	 * The status of the Merchant Center connection.
	 *
	 * @return array|WP_Error
	 */
	public function get_status() {
		$setup  = $this->merchant_center->get_setup_status();
		$status = [
			'connected'      => $this->merchant_center->is_connected(),
			'setup_status'   => $setup['status'],
			'setup_step'     => $setup['step'] ?? null,
			'loading'        => false,
			'statistics'     => null,
			'account_issues' => [],
		];

		if ( ! $status['connected'] ) {
			return $status;
		}

		try {
			$statistics = $this->merchant_statuses->get_product_statistics();
			$issues     = $this->merchant_statuses->get_issues( MerchantStatuses::TYPE_ACCOUNT );
		} catch ( Exception $exception ) {
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

			return new WP_Error(
				'merchant_center_status_failed',
				__( 'The Merchant Center status could not be read.', 'google-listings-and-ads' ),
				[ 'status' => 500 ]
			);
		}

		$status['loading']        = ! empty( $statistics['loading'] ) || ! empty( $issues['loading'] );
		$status['statistics']     = $statistics['statistics'] ?? null;
		$status['account_issues'] = $issues['issues'];

		return $status;
	}
}
