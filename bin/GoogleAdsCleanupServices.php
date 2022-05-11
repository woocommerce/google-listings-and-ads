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
		'AccountLink',
		'Ad',
		'AdGroup',
		'AdGroupAdLabel',
		'AdGroupAd',
		'AdGroupCriterion',
		'AssetGroup',
		'AssetGroupListingGroupFilter',
		'BillingSetup',
		'Campaign',
		'CampaignBudget',
		'CampaignCriterion',
		'ConversionAction',
		'Customer',
		'CustomerUserAccess',
		'GeoTargetConstant',
		'GoogleAds',
		'MerchantCenterLink',
	];

	/**
	 * List of services to remove.
	 *
	 * @var array
	 */
	protected $services = [
		'AccountBudgetProposal',
		'AdGroupAdAssetView',
		'AdGroupAudienceView',
		'AdGroupBidModifier',
		'AdGroupCriterionCustomizer',
		'AdGroupCriterionLabel',
		'AdGroupCriterionSimulation',
		'AdGroupCustomizer',
		'AdGroupExtensionSetting',
		'AdGroupFeed',
		'AdGroupLabel',
		'AdGroupSimulation',
		'AdParameter',
		'AdScheduleView',
		'AgeRangeView',
		'Asset',
		'AssetFieldTypeView',
		'AssetGroupProductGroupView',
		'AssetSet',
		'BatchJob',
		'BiddingDataExclusion',
		'BiddingSeasonalityAdjustment',
		'BiddingStrategy',
		'BiddingStrategySimulation',
		'CallView',
		'CampaignAsset',
		'CampaignAssetSet',
		'CampaignAudienceView',
		'CampaignBidModifier',
		'CampaignConversionGoal',
		'CampaignCriterionSimulation',
		'CampaignCustomizer',
		'CampaignDraft',
		'CampaignExperiment',
		'CampaignExtensionSetting',
		'CampaignFeed',
		'CampaignLabel',
		'CampaignSimulation',
		'CampaignSharedSet',
		'CarrierConstant',
		'ChangeStatus',
		'ClickView',
		'CombinedAudience',
		'ConversionAdjustmentUpload',
		'ConversionCustomVariable',
		'ConversionGoalCampaignConfig',
		'ConversionUpload',
		'ConversionValueRule',
		'ConversionValueRuleSet',
		'CurrencyConstant',
		'CustomAudience',
		'CustomerClientLink',
		'CustomerClient',
		'CustomerConversionGoal',
		'CustomerCustomizer',
		'CustomerExtensionSetting',
		'CustomerFeed',
		'CustomerLabel',
		'CustomerManagerLink',
		'CustomerNegativeCriterion',
		'CustomerUserAccessInvitation',
		'CustomConversionGoal',
		'CustomInterest',
		'CustomizerAttribute',
		'DetailedDemographic',
		'DetailPlacementView',
		'DisplayKeywordView',
		'DistanceView',
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
		'GoogleAdsField',
		'GroupPlacementView',
		'HotelGroupView',
		'HotelPerformanceView',
		'HotelReconciliation',
		'IncomeRangeView',
		'Invoice',
		'KeywordPlanAdGroupKeyword',
		'KeywordPlanAdGroup',
		'KeywordPlanCampaignKeyword',
		'KeywordPlanCampaign',
		'KeywordPlanIdea',
		'KeywordPlan',
		'KeywordThemeConstant',
		'KeywordView',
		'Label',
		'LandingPageView',
		'LanguageConstant',
		'LifeEvent',
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
		'SmartCampaignSearchTermView',
		'SmartCampaignSetting',
		'SmartCampaignSuggest',
		'ThirdPartyAppAnalyticsLink',
		'TopicConstant',
		'TopicView',
		'UserData',
		'UserInterest',
		'UserList',
		'UserLocationView',
		'Video',
		'WebpageView',
	];

	/**
	 * API version.
	 *
	 * @var string
	 */
	protected $version = 'V9';

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
	 * @var string Source code path.
	 */
	protected $code_path = null;

	/**
	 * Constructor.
	 *
	 * @param Event|null  $event Composer event.
	 * @param string|null $path  Path of the Ads library.
	 */
	public function __construct( Event $event = null, string $path = null ) {
		$this->event     = $event;
		$this->path      = $path ?: dirname( __DIR__ ) . '/vendor/googleads/google-ads-php';
		$this->code_path = dirname( __DIR__ ) . '/src';
	}

	/**
	 * Remove unused classes from the library.
	 *
	 * @param Event $event Event context provided by Composer
	 */
	public static function remove( Event $event = null ) {
		$cleanup = new GoogleAdsCleanupServices( $event );
		$cleanup->remove_services();
		$cleanup->remove_enums();
	}

	/**
	 * Remove all services and resources.
	 */
	protected function remove_services() {
		$this->output_text( 'Removing unused services and resources from Google Ads library ' . $this->version );

		$library = array_unique(
			array_merge(
				$this->find_library_file_pattern(
					"{$this->path}/metadata/Google/Ads/GoogleAds/{$this->version}/Services"
				),
				$this->find_library_file_pattern(
					"{$this->path}/metadata/Google/Ads/GoogleAds/{$this->version}/Resources"
				),
			)
		);

		$used = array_unique(
			array_merge(
				$this->find_used_pattern(
					"use Google\\\\Ads\\\\GoogleAds\\\\{$this->version}\\\\Services\\\\([A-Za-z0-9]+)ServiceClient;"
				),
				$this->find_used_pattern(
					"use Google\\\\Ads\\\\GoogleAds\\\\{$this->version}\\\\Resources\\\\([A-Za-z0-9]+);"
				)
			)
		);

		foreach ( array_diff( $library, $used ) as $service ) {
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
		$this->src_path = '/src/Google/Ads/GoogleAds';

		$file = "/Util/{$this->version}/ResourceNames.php";
		$this->remove_use_statement( $file, "{$service}ServiceClient" );
		$this->remove_function( $file, "for{$service}" );

		$file = "/Lib/{$this->version}/ServiceClientFactoryTrait.php";
		$this->remove_use_statement( $file, "{$service}ServiceClient" );
		$this->remove_function( $file, "get{$service}ServiceClient" );

		$this->remove_file( "/{$this->version}/Services/Gapic/{$service}ServiceGapicClient.php" );
		$this->remove_file( "/{$this->version}/Services/{$service}ServiceGrpcClient.php" );
		$this->remove_file( "/{$this->version}/Services/{$service}ServiceClient.php" );

		$this->remove_file( "/{$this->version}/Services/Mutate{$service}Result.php" );
		$this->remove_file( "/{$this->version}/Services/Mutate{$service}sRequest.php" );
		$this->remove_file( "/{$this->version}/Services/Mutate{$service}sResponse.php" );
		$this->remove_file( "/{$this->version}/Services/{$service}Operation.php" );
		$this->remove_file( "/{$this->version}/Services/Get{$service}Request.php" );
		$this->remove_file( "/{$this->version}/Resources/{$service}.php" );

		$snake_case = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $service ) );
		$this->remove_file( "/{$this->version}/Services/resources/{$snake_case}_service_descriptor_config.php" );
		$this->remove_file( "/{$this->version}/Services/resources/{$snake_case}_service_rest_client_config.php" );
		$this->remove_file( "/{$this->version}/Services/resources/{$snake_case}_service_client_config.json" );

		// Remove metadata files.
		$this->src_path = '/metadata/Google/Ads/GoogleAds';
		$this->remove_file( "/{$this->version}/Services/{$service}Service.php" );
		$this->remove_file( "/{$this->version}/Resources/{$service}.php" );
	}

	/**
	 * Find used enums and remove any unused ones.
	 */
	protected function remove_enums() {
		$this->output_text( 'Removing unused enums from Google Ads library ' . $this->version );

		$library_enums = $this->find_library_file_pattern(
			"{$this->path}/metadata/Google/Ads/GoogleAds/{$this->version}/Enums"
		);

		$used_enums = $this->find_used_pattern(
			"use Google\\\\Ads\\\\GoogleAds\\\\{$this->version}\\\\Enums\\\\([A-Za-z0-9]+)Enum\\\\"
		);

		foreach ( array_diff( $library_enums, $used_enums ) as $enum ) {
			$this->remove_enum( $enum );
		};
	}

	/**
	 * Remove a specific enum.
	 *
	 * @param string $enum Enum name.
	 */
	protected function remove_enum( string $enum ) {
		$this->output_text( "Removing enum {$enum}" );
		$this->src_path = '/src/Google/Ads/GoogleAds';

		$this->remove_file( "/{$this->version}/Enums/{$enum}Enum.php" );
		$this->remove_file( "/{$this->version}/Enums/{$enum}Enum_{$enum}.php" );
		$this->remove_file( "/{$this->version}/Enums/{$enum}Enum/{$enum}.php" );
		$this->remove_directory( "/{$this->version}/Enums/{$enum}Enum" );

		// Remove metadata files.
		$this->src_path = '/metadata/Google/Ads/GoogleAds';
		$this->remove_file( "/{$this->version}/Enums/{$enum}.php" );
	}

	/**
	 * Find a specific pattern used within the extension.
	 *
	 * @param string $pattern Regexp pattern to match.
	 *
	 * @return array List of names that match.
	 */
	protected function find_used_pattern( string $pattern ): array {
		$command = "grep -rE --include=*.php '{$pattern}' {$this->code_path}";

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec( $command, $output );

		if ( empty( $output ) ) {
			return [];
		}

		return array_unique(
			array_map(
				function( $line ) use ( $pattern ) {
					preg_match( "/{$pattern}/", $line, $matches );
					return $matches[1];
				},
				$output
			)
		);
	}

	/**
	 * Find a specific filename pattern within the library.
	 *
	 * @param string $pattern Regexp pattern to match.
	 * @param string $suffix  Suffix to remove from filename.
	 *
	 * @return array List of matched names.
	 */
	protected function find_library_file_pattern( string $pattern, string $suffix = null ): array {
		$output = glob( "{$pattern}/*.php" );

		if ( empty( $output ) ) {
			return [];
		}

		return array_map(
			function( $file ) use ( $suffix ) {
				$name = pathinfo( $file, PATHINFO_FILENAME );
				return $suffix ? $this->remove_suffix( $suffix, $name ) : $name;
			},
			$output
		);
	}

	/**
	 * Optionally remove a suffix from a string.
	 *
	 * @param string $suffix Suffix to remove.
	 * @param string $text   Text to remove the suffix from.
	 *
	 * @return string
	 */
	protected function remove_suffix( string $suffix, string $text ): string {
		return preg_replace( '/' . preg_quote( $suffix, '/' ) . '$/' );
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
	 * Remove a directory.
	 *
	 * @param string $directory
	 */
	protected function remove_directory( string $directory ) {
		$directory = $this->file_path( $directory );
		if ( ! is_dir( $directory ) ) {
			$this->warning( sprintf( 'Directory does not exist: %s', $directory ) );
			return;
		}

		rmdir( $directory );
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
			$this->event->getIO()->write( $text );
		} else {
			echo $text . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
