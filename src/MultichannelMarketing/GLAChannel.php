<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MultichannelMarketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\Price;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class GLAChannel
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MultichannelMarketing
 *
 * @since   2.3.10
 */
class GLAChannel implements MarketingChannelInterface {
	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var AdsCampaign
	 */
	protected $ads_campaign;

	/**
	 * @var Ads
	 */
	protected $ads;

	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * @var ProductSyncStats
	 */
	protected $product_sync_stats;

	/**
	 * @var MarketingCampaignType[]
	 */
	protected $campaign_types;

	/**
	 * GLAChannel constructor.
	 *
	 * @param MerchantCenterService $merchant_center
	 * @param AdsCampaign           $ads_campaign
	 * @param Ads                   $ads
	 * @param MerchantStatuses      $merchant_statuses
	 * @param ProductSyncStats      $product_sync_stats
	 */
	public function __construct( MerchantCenterService $merchant_center, AdsCampaign $ads_campaign, Ads $ads, MerchantStatuses $merchant_statuses, ProductSyncStats $product_sync_stats ) {
		$this->merchant_center    = $merchant_center;
		$this->ads_campaign       = $ads_campaign;
		$this->ads                = $ads;
		$this->merchant_statuses  = $merchant_statuses;
		$this->product_sync_stats = $product_sync_stats;
		$this->campaign_types     = [];

		if ( $this->is_mcm_enabled() ) {
			$this->campaign_types = $this->generate_campaign_types();
		}
	}

	/**
	 * Determines if the multichannel marketing is enabled.
	 *
	 * @return bool
	 */
	protected function is_mcm_enabled(): bool {
		return apply_filters( 'woocommerce_gla_enable_mcm', false ) === true;
	}

	/**
	 * Returns the unique identifier string for the marketing channel extension, also known as the plugin slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'google-listings-and-ads';
	}

	/**
	 * Returns the name of the marketing channel.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Google for WooCommerce', 'google-listings-and-ads' );
	}

	/**
	 * Returns the description of the marketing channel.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Native integration with Google that allows merchants to easily display their products across Google’s network.', 'google-listings-and-ads' );
	}

	/**
	 * Returns the path to the channel icon.
	 *
	 * @return string
	 */
	public function get_icon_url(): string {
		return 'https://woocommerce.com/wp-content/uploads/2021/06/woo-GoogleListingsAds-jworee.png';
	}

	/**
	 * Returns the setup status of the marketing channel.
	 *
	 * @return bool
	 */
	public function is_setup_completed(): bool {
		return $this->merchant_center->is_setup_complete();
	}

	/**
	 * Returns the URL to the settings page, or the link to complete the setup/onboarding if the channel has not been set up yet.
	 *
	 * @return string
	 */
	public function get_setup_url(): string {
		if ( ! $this->is_setup_completed() ) {
			return admin_url( 'admin.php?page=wc-admin&path=/google/start' );
		}

		return admin_url( 'admin.php?page=wc-admin&path=/google/settings' );
	}

	/**
	 * Returns the status of the marketing channel's product listings.
	 *
	 * @return string
	 */
	public function get_product_listings_status(): string {
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			return self::PRODUCT_LISTINGS_NOT_APPLICABLE;
		}

		return $this->product_sync_stats->get_count() > 0 ? self::PRODUCT_LISTINGS_SYNC_IN_PROGRESS : self::PRODUCT_LISTINGS_SYNCED;
	}

	/**
	 * Returns the number of channel issues/errors (e.g. account-related errors, product synchronization issues, etc.).
	 *
	 * @return int The number of issues to resolve, or 0 if there are no issues with the channel.
	 */
	public function get_errors_count(): int {
		try {
			return $this->merchant_statuses->get_issues()['total'];
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * Returns an array of marketing campaign types that the channel supports.
	 *
	 * @return MarketingCampaignType[] Array of marketing campaign type objects.
	 */
	public function get_supported_campaign_types(): array {
		return $this->campaign_types;
	}

	/**
	 * Returns an array of the channel's marketing campaigns.
	 *
	 * @return MarketingCampaign[]
	 */
	public function get_campaigns(): array {
		if ( ! $this->ads->ads_id_exists() || ! $this->is_mcm_enabled() ) {
			return [];
		}

		try {
			$currency = $this->ads->get_ads_currency();

			return array_map(
				function ( array $campaign_data ) use ( $currency ) {
					$cost = null;
					if ( isset( $campaign_data['amount'] ) ) {
						$cost = new Price( (string) $campaign_data['amount'], $currency );
					}

					return new MarketingCampaign(
						(string) $campaign_data['id'],
						$this->campaign_types['google-ads'],
						$campaign_data['name'],
						admin_url( 'admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/edit&programId=' . $campaign_data['id'] ),
						$cost,
					);
				},
				$this->ads_campaign->get_campaigns()
			);
		} catch ( ExceptionWithResponseData $e ) {
			return [];
		}
	}

	/**
	 * Generate an array of supported marketing campaign types.
	 *
	 * @return MarketingCampaignType[]
	 */
	protected function generate_campaign_types(): array {
		return [
			'google-ads' => new MarketingCampaignType(
				'google-ads',
				$this,
				'Google Ads',
				'Boost your product listings with a campaign that is automatically optimized to meet your goals.',
				admin_url( 'admin.php?page=wc-admin&path=/google/dashboard&subpath=/campaigns/create' ),
				$this->get_icon_url()
			),
		];
	}
}
