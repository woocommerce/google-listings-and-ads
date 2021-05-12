<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Util;

use Composer\Script\Event;

/**
 * Utilities to remove Google Ads API services in the library.
 */
class GoogleAdsCleanupServices {

	/**
	 * Display debug output.
	 *
	 * @var boolean
	 */
	protected $debug = false;

	/** List of services to keep.
	 *
	 * @var array
	 */
	protected $services_keep = [
		'AccountBudget',
		'AccountBudgetProposal',
		'AccountLink',
		'Ad',
		'AdGroup',
		'AdGroupAdLabel',
		'AdGroupAd',
		'AdGroupCriterion',
		'BillingSetup',
		'Campaign',
		'CampaignBudget',
		'ConversionAction',
		'GoogleAds',
		'MerchantCenterLink',
	];

	/**
	 * List of services to remove.
	 *
	 * @var array
	 */
	protected $services = [
		'AdGroupAudienceView',
		'AdGroupBidModifier',
		'AdGroupCriterionLabel',
		'AdGroupCriterionSimulation',
		'AdGroupExtensionSetting',
		'AdGroupFeed',
		'AdGroupLabel',
		'AdGroupSimulation',
		'AdParameter',
		'AdScheduleView',
		'AgeRangeView',
		'Asset',
		'BatchJob',
		'BiddingStrategy',
		'CampaignAsset',
		'CampaignAudienceView',
		'CampaignBidModifier',
		'CampaignCriterion',
		'CampaignCriterionSimulation',
		'CampaignDraft',
		'CampaignExperiment',
		'CampaignExtensionSetting',
		'CampaignFeed',
		'CampaignLabel',
		'CampaignSharedSet',
		'CarrierConstant',
		'ChangeStatus',
		'ClickView',
		'CombinedAudience',
		'ConversionAdjustmentUpload',
		'ConversionUpload',
		'CurrencyConstant',
		'CustomerClientLink',
		'CustomerClient',
		'CustomerExtensionSetting',
		'CustomerFeed',
		'CustomerLabel',
		'CustomerManagerLink',
		'CustomerNegativeCriterion',
		'Customer',
		'CustomerUserAccess',
		'CustomInterest',
		'DetailPlacementView',
		'DisplayKeywordView',
		'DomainCategory',
		'DynamicSearchAdsSearchTermView',
		'ExpandedLandingPageView',
		'ExtensionFeedItem',
		'FeedItem',
		'FeedItemSetLink',
		'FeedItemSet',
		'FeedItemTarget',
		'FeedMapping',
		'FeedPlaceholderView',
		'Feed',
		'GenderView',
		'GeographicView',
		'GeoTargetConstant',
		'GoogleAdsField',
		'GroupPlacementView',
		'HotelGroupView',
		'HotelPerformanceView',
		'IncomeRangeView',
		'Invoice',
		'KeywordPlanAdGroupKeyword',
		'KeywordPlanAdGroup',
		'KeywordPlanCampaignKeyword',
		'KeywordPlanCampaign',
		'KeywordPlanIdea',
		'KeywordPlan',
		'KeywordView',
		'Label',
		'LandingPageView',
		'LanguageConstant',
		'LocationView',
		'ManagedPlacementView',
		'MediaFile',
		'MobileAppCategoryConstant',
		'MobileDeviceConstant',
		'OfflineUserDataJob',
		'OperatingSystemVersionConstant',
		'PaidOrganicSearchTermView',
		'ParentalStatusView',
		'PaymentsAccount',
		'ProductBiddingCategoryConstant',
		'ProductGroupView',
		'ReachPlan',
		'Recommendation',
		'RemarketingAction',
		'SearchTermView',
		'SharedCriterion',
		'SharedSet',
		'ShoppingPerformanceView',
		'ThirdPartyAppAnalyticsLink',
		'TopicConstant',
		'TopicView',
		'UserData',
		'UserInterest',
		'UserList',
		'Video',
	];

	/**
	 * API version.
	 *
	 * @var string
	 */
	protected $version = 'V6';

	/**
	 * @var Event Composer event.
	 */
	protected $event = null;

	/**
	 * @var string The path of the Ads library.
	 */
	protected $path = null;

	/**
	 * @var string Source path.
	 */
	protected $src_path = '/src/Google/Ads/GoogleAds';

	/**
	 * Constructor.
	 *
	 * @param Event|null  $event Composer event.
	 * @param string|null $path  Path of the Ads library.
	 */
	public function __construct( Event $event = null, string $path = null ) {
		$this->event = $event;
		$this->path  = $path ?: dirname( __DIR__ ) . '/vendor/googleads/google-ads-php';
	}

	/**
	 * Remove services.
	 *
	 * @param Event $event Event context provided by Composer
	 */
	public static function remove( Event $event = null ) {
		$cleanup = new GoogleAdsCleanupServices( $event );
		$cleanup->remove_services();
	}

	/**
	 * Remove all services.
	 */
	protected function remove_services() {
		$this->output_text( 'Removing unused services from Google Ads library' );

		foreach ( $this->services as $service ) {
			$this->remove_service( $service );
		}
	}

	/**
	 * Remove a specific service.
	 *
	 * @param string $service Service name.
	 */
	protected function remove_service( string $service ) {
		$this->output_text( "Removing service {$service}" );

		$file = "/Util/{$this->version}/ResourceNames.php";
		$this->remove_use_statement( $file, "{$service}ServiceClient" );
		$this->remove_function( $file, "for{$service}" );

		$file = "/Lib/{$this->version}/ServiceClientFactoryTrait.php";
		$this->remove_use_statement( $file, "{$service}ServiceClient" );
		$this->remove_function( $file, "get{$service}ServiceClient" );

		$this->remove_file( "/{$this->version}/Services/Gapic/{$service}ServiceGapicClient.php" );
		$this->remove_file( "/{$this->version}/Services/{$service}ServiceGrpcClient.php" );
		$this->remove_file( "/{$this->version}/Services/{$service}ServiceClient.php" );

		$this->remove_file( "/{$this->version}/Services/Get{$service}Request.php" );
		$this->remove_file( "/{$this->version}/Resources/{$service}.php" );

		$snake_case = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $service ) );
		$this->remove_file( "/{$this->version}/Services/resources/{$snake_case}_service_descriptor_config.php" );
		$this->remove_file( "/{$this->version}/Services/resources/{$snake_case}_service_rest_client_config.php" );
		$this->remove_file( "/{$this->version}/Services/resources/{$snake_case}_service_client_config.json" );

		// Remove metadata files.
		$this->src_path = '/metadata/Google/Ads/GoogleAds';
		$this->remove_file( "/{$this->version}/Services/{$service}Service.php" );
	}

	/**
	 * Remove a use statement from a file.
	 *
	 * @param string $file
	 * @param string $name
	 */
	protected function remove_use_statement( string $file, string $name ) {
		$pattern = '/\s*use\s+[\w\d\\\]+' . preg_quote( $name, '/' ) . ';/';
		$this->remove_pattern( $file, $pattern );
	}

	/**
	 * Remove a function from a file.
	 *
	 * @param string $file
	 * @param string $name Function name.
	 */
	protected function remove_function( string $file, string $name ) {
		$file = $this->file_path( $file );
		if ( ! file_exists( $file ) ) {
			$this->warning( sprintf( 'File does not exist: %s', $file ) );
			return;
		}

		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$pattern = '/function ' . preg_quote( $name, '/' ) . '\(/';
		if ( ! preg_match( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
			$this->warning( sprintf( 'Function %s not found in %s', $name, $file ) );
			return;
		}

		$offset  = $matches[0][1];
		$length  = strlen( $contents );
		$bracket = 0;

		// Parse until we find beginning of doc block.
		$start = strrpos( $contents, '/**', ( $length - $offset ) * -1 );

		// Parse until we encounter closing bracket.
		for ( $end = $offset; $end < $length; $end++ ) {
			if ( '{' === $contents[ $end ] ) {
				$bracket++;
			}
			if ( '}' === $contents[ $end ] ) {
				$bracket--;
				if ( 1 > $bracket ) {
					break;
				}
			}
		}

		if ( false === $start || 0 > $start || $end >= $length ) {
			$this->warning( sprintf( 'Function %s not found in %s', $name, $file ) );
			return;
		}

		// Include whitespaces before start.
		while ( 0 < $start && ctype_space( $contents[ $start - 1 ] ) ) {
			$start--;
		}

		$new  = substr( $contents, 0, $start );
		$new .= substr( $contents, $end + 1 );

		if ( empty( $new ) ) {
			$this->warning( sprintf( 'Replace failed for function %s in %s', $name, $file ) );
			return;
		}

		file_put_contents( $file, $new ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/**
	 * Remove regex pattern from a file.
	 *
	 * @param string $file
	 * @param string $pattern Regex pattern to match.
	 */
	protected function remove_pattern( string $file, string $pattern ) {
		$file = $this->file_path( $file );
		if ( ! file_exists( $file ) ) {
			$this->warning( sprintf( 'File does not exist: %s', $file ) );
			return;
		}

		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! preg_match( $pattern, $contents, $matches ) ) {
			$this->warning( sprintf( 'Pattern %s not found in %s', $pattern, $file ) );
			return;
		}

		$new = preg_replace( $pattern, '', $contents );
		if ( empty( $new ) ) {
			$this->warning( sprintf( 'Replace failed for pattern %s in %s', $pattern, $file ) );
			return;
		}

		file_put_contents( $file, $new ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/**
	 * Remove a file.
	 *
	 * @param string $file
	 */
	protected function remove_file( string $file ) {
		$file = $this->file_path( $file );
		if ( ! file_exists( $file ) ) {
			$this->warning( sprintf( 'File does not exist: %s', $file ) );
			return;
		}

		unlink( $file );
	}

	/**
	 * Return the full path name.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	protected function file_path( string $file ): string {
		return $this->path . $this->src_path . $file;
	}

	/**
	 * Output warning text (only if debug is enabled).
	 *
	 * @param string $text
	 */
	protected function warning( string $text ) {
		if ( $this->debug ) {
			$this->output_text( $text );
		}
	}

	/**
	 * Output text.
	 *
	 * @param string $text
	 */
	protected function output_text( string $text ) {
		if ( $this->event ) {
			$event->getIO()->write( $text );
		} else {
			echo $text . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}

GoogleAdsCleanupServices::remove();
