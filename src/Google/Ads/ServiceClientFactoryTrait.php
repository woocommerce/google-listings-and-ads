<?php
declare( strict_types=1 );

/**
 * Overrides vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/Lib/V12/ServiceClientFactoryTrait.php
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.NamingConventions.ValidVariableName
 * phpcs:disable Squiz.Commenting.VariableComment
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads;

use Google\Ads\GoogleAds\Constants;
use Google\Ads\GoogleAds\Lib\ConfigurationTrait;
use Google\Ads\GoogleAds\V12\Services\AccountBudgetServiceClient;
use Google\Ads\GoogleAds\V12\Services\AccountLinkServiceClient;
use Google\Ads\GoogleAds\V12\Services\AdGroupAdLabelServiceClient;
use Google\Ads\GoogleAds\V12\Services\AdGroupAdServiceClient;
use Google\Ads\GoogleAds\V12\Services\AdGroupCriterionServiceClient;
use Google\Ads\GoogleAds\V12\Services\AdGroupServiceClient;
use Google\Ads\GoogleAds\V12\Services\AdServiceClient;
use Google\Ads\GoogleAds\V12\Services\AssetGroupListingGroupFilterServiceClient;
use Google\Ads\GoogleAds\V12\Services\AssetGroupServiceClient;
use Google\Ads\GoogleAds\V12\Services\BillingSetupServiceClient;
use Google\Ads\GoogleAds\V12\Services\CampaignBudgetServiceClient;
use Google\Ads\GoogleAds\V12\Services\CampaignCriterionServiceClient;
use Google\Ads\GoogleAds\V12\Services\CampaignServiceClient;
use Google\Ads\GoogleAds\V12\Services\ConversionActionServiceClient;
use Google\Ads\GoogleAds\V12\Services\CustomerServiceClient;
use Google\Ads\GoogleAds\V12\Services\CustomerUserAccessServiceClient;
use Google\Ads\GoogleAds\V12\Services\GeoTargetConstantServiceClient;
use Google\Ads\GoogleAds\V12\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V12\Services\MerchantCenterLinkServiceClient;

/**
 * Contains service client factory methods.
 */
trait ServiceClientFactoryTrait {
	use ConfigurationTrait;

	private static $CREDENTIALS_LOADER_KEY  = 'credentials';
	private static $DEVELOPER_TOKEN_KEY     = 'developer-token';
	private static $LOGIN_CUSTOMER_ID_KEY   = 'login-customer-id';
	private static $LINKED_CUSTOMER_ID_KEY  = 'linked-customer-id';
	private static $SERVICE_ADDRESS_KEY     = 'serviceAddress';
	private static $DEFAULT_SERVICE_ADDRESS = 'googleads.googleapis.com';
	private static $TRANSPORT_KEY           = 'transport';

	/**
	 * Gets the Google Ads client options for making API calls.
	 *
	 * @return array the client options
	 */
	public function getGoogleAdsClientOptions(): array {
		$clientOptions = [
			self::$CREDENTIALS_LOADER_KEY => $this->getOAuth2Credential(),
			self::$DEVELOPER_TOKEN_KEY    => '',
			self::$TRANSPORT_KEY          => 'rest',
			'libName'                     => Constants::LIBRARY_NAME,
			'libVersion'                  => Constants::LIBRARY_VERSION,
		];

		if ( ! empty( $this->getEndpoint() ) ) {
			$clientOptions += [ self::$SERVICE_ADDRESS_KEY => $this->getEndpoint() ];
		}

		if ( isset( $this->httpClient ) ) {
			$clientOptions['transportConfig'] = [
				'rest' => [
					'httpHandler' => $this->buildHttpHandler(),
				],
			];
		}

		return $clientOptions;
	}

	/**
	 * @return AccountBudgetServiceClient
	 */
	public function getAccountBudgetServiceClient(): AccountBudgetServiceClient {
		return new AccountBudgetServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AccountLinkServiceClient
	 */
	public function getAccountLinkServiceClient(): AccountLinkServiceClient {
		return new AccountLinkServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AdGroupAdLabelServiceClient
	 */
	public function getAdGroupAdLabelServiceClient(): AdGroupAdLabelServiceClient {
		return new AdGroupAdLabelServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AdGroupAdServiceClient
	 */
	public function getAdGroupAdServiceClient(): AdGroupAdServiceClient {
		return new AdGroupAdServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AdGroupCriterionServiceClient
	 */
	public function getAdGroupCriterionServiceClient(): AdGroupCriterionServiceClient {
		return new AdGroupCriterionServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AdGroupServiceClient
	 */
	public function getAdGroupServiceClient(): AdGroupServiceClient {
		return new AdGroupServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AdServiceClient
	 */
	public function getAdServiceClient(): AdServiceClient {
		return new AdServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AssetGroupListingGroupFilterServiceClient
	 */
	public function getAssetGroupListingGroupFilterServiceClient(): AssetGroupListingGroupFilterServiceClient {
		return new AssetGroupListingGroupFilterServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return AssetGroupServiceClient
	 */
	public function getAssetGroupServiceClient(): AssetGroupServiceClient {
		return new AssetGroupServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return BillingSetupServiceClient
	 */
	public function getBillingSetupServiceClient(): BillingSetupServiceClient {
		return new BillingSetupServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return CampaignBudgetServiceClient
	 */
	public function getCampaignBudgetServiceClient(): CampaignBudgetServiceClient {
		return new CampaignBudgetServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return CampaignCriterionServiceClient
	 */
	public function getCampaignCriterionServiceClient(): CampaignCriterionServiceClient {
		return new CampaignCriterionServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return CampaignServiceClient
	 */
	public function getCampaignServiceClient(): CampaignServiceClient {
		return new CampaignServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return ConversionActionServiceClient
	 */
	public function getConversionActionServiceClient(): ConversionActionServiceClient {
		return new ConversionActionServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return CustomerServiceClient
	 */
	public function getCustomerServiceClient(): CustomerServiceClient {
		return new CustomerServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return CustomerUserAccessServiceClient
	 */
	public function getCustomerUserAccessServiceClient(): CustomerUserAccessServiceClient {
		return new CustomerUserAccessServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return GeoTargetConstantServiceClient
	 */
	public function getGeoTargetConstantServiceClient(): GeoTargetConstantServiceClient {
		return new GeoTargetConstantServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return GoogleAdsServiceClient
	 */
	public function getGoogleAdsServiceClient(): GoogleAdsServiceClient {
		return new GoogleAdsServiceClient( $this->getGoogleAdsClientOptions() );
	}

	/**
	 * @return MerchantCenterLinkServiceClient
	 */
	public function getMerchantCenterLinkServiceClient(): MerchantCenterLinkServiceClient {
		return new MerchantCenterLinkServiceClient( $this->getGoogleAdsClientOptions() );
	}

}
