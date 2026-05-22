<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Compatibility;

/**
 * Provide lightweight aliases for removed Google Ads API classes.
 *
 * This keeps older deployed plugin code working during a rolling update without
 * shipping the entire previous API version in the bundle.
 */
final class GoogleAdsApiCompatibility {

	/**
	 * Alias removed V22 service client classes to the V23 implementations.
	 */
	public static function register(): void {
		self::alias_service_client( 'AccountLinkServiceClient' );
		self::alias_service_client( 'AdGroupAdLabelServiceClient' );
		self::alias_service_client( 'AdGroupAdServiceClient' );
		self::alias_service_client( 'AdGroupCriterionServiceClient' );
		self::alias_service_client( 'AdGroupServiceClient' );
		self::alias_service_client( 'AdServiceClient' );
		self::alias_service_client( 'AssetGenerationServiceClient' );
		self::alias_service_client( 'AssetGroupListingGroupFilterServiceClient' );
		self::alias_service_client( 'AssetGroupServiceClient' );
		self::alias_service_client( 'BillingSetupServiceClient' );
		self::alias_service_client( 'CampaignBudgetServiceClient' );
		self::alias_service_client( 'CampaignCriterionServiceClient' );
		self::alias_service_client( 'CampaignServiceClient' );
		self::alias_service_client( 'ConversionActionServiceClient' );
		self::alias_service_client( 'CustomerServiceClient' );
		self::alias_service_client( 'CustomerUserAccessServiceClient' );
		self::alias_service_client( 'GeoTargetConstantServiceClient' );
		self::alias_service_client( 'GoogleAdsServiceClient' );
		self::alias_service_client( 'IncentiveServiceClient' );
		self::alias_service_client( 'ProductLinkInvitationServiceClient' );
		self::alias_service_client( 'RecommendationServiceClient' );
	}

	/**
	 * Alias a V22 service client to its V23 counterpart if needed.
	 *
	 * @param string $class_name Class short name without version namespace.
	 */
	private static function alias_service_client( string $class_name ): void {
		$legacy_class = "Google\\Ads\\GoogleAds\\V22\\Services\\Client\\{$class_name}";
		$new_class    = "Google\\Ads\\GoogleAds\\V23\\Services\\Client\\{$class_name}";

		if ( class_exists( $legacy_class, false ) || ! class_exists( $new_class ) ) {
			return;
		}

		class_alias( $new_class, $legacy_class );
	}
}

GoogleAdsApiCompatibility::register();