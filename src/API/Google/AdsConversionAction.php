<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\V6\Resources\ConversionAction as ConversionAction;
use Google\Ads\GoogleAds\V6\Common\TagSnippet;
use Google\Ads\GoogleAds\V6\Enums\ConversionActionCategoryEnum\ConversionActionCategory;
use Google\Ads\GoogleAds\V6\Enums\ConversionActionStatusEnum\ConversionActionStatus;
use Google\Ads\GoogleAds\V6\Enums\ConversionActionTypeEnum\ConversionActionType;
use Google\Ads\GoogleAds\V6\Enums\TrackingCodePageFormatEnum\TrackingCodePageFormat;
use Google\Ads\GoogleAds\V6\Enums\TrackingCodeTypeEnum\TrackingCodeType;
use Google\Ads\GoogleAds\V6\Resources\ConversionAction\ValueSettings;
use Google\Ads\GoogleAds\V6\Services\ConversionActionOperation;
use Google\Ads\GoogleAds\V6\Services\ConversionActionServiceClient;
use Exception;
use Google\Ads\GoogleAds\V6\Services\MutateConversionActionResult;
use Google\ApiCore\ApiException;

/**
 * Class AdsConversionAction
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsConversionAction implements OptionsAwareInterface {

	use ApiExceptionTrait;
	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * AdsConversionAction constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Create the 'Google Listings and Ads purchase action' conversion action.
	 *
	 * @return array An array with some conversion action details.
	 * @throws Exception If the conversion action can't be created or retrieved.
	 */
	public function create_conversion_action(): array {
		try {
			$unique = sprintf( '%04x', mt_rand( 0, 0xffff ) );

			$conversion_action_operation = new ConversionActionOperation();
			$conversion_action_operation->setCreate(
				new ConversionAction(
					[
						'name'           => apply_filters(
							'woocommerce_gla_conversion_action_name',
							sprintf(
							/* translators: %1 is a random 4-digit string */
								__( '[%1$s] Google Listings and Ads purchase action', 'google-listings-and-ads' ),
								$unique
							)
						),
						'category'       => ConversionActionCategory::PURCHASE,
						'type'           => ConversionActionType::WEBPAGE,
						'status'         => ConversionActionStatus::ENABLED,
						'value_settings' => new ValueSettings(
							[
								'default_value'            => 0,
								'always_use_default_value' => false,
							]
						),
					]
				)
			);

			// Create the conversion.
			$response = $this->client->getConversionActionServiceClient()->mutateConversionActions(
				$this->options->get_ads_id(),
				[ $conversion_action_operation ]
			);

			/** @var MutateConversionActionResult $added_conversion_action */
			$added_conversion_action = $response->getResults()->offsetGet( 0 );
			return $this->get_conversion_action( $added_conversion_action->getResourceName() );

		} catch ( Exception $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );
			$message = $e->getMessage();
			if ( $e instanceof ApiException ) {

				if ( $this->has_api_exception_error( $e, 'DUPLICATE_NAME' ) ) {
					$message = __( 'A conversion action with this name already exists', 'google-listings-and-ads' );
				} else {
					$message = $e->getBasicMessage();
				}
			}

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error creating conversion action: %s', 'google-listings-and-ads' ), $message ) );
		}
	}

	/**
	 * Retrieve a Conversion Action.
	 *
	 * @param string|int $resource_name The Conversion Action to retrieve (also accepts the Conversion Action ID).
	 *
	 * @return array An array with some conversion action details.
	 * @throws Exception If the Conversion Action can't be retrieved.
	 */
	public function get_conversion_action( $resource_name ): array {
		try {
			// Accept IDs too
			if ( is_numeric( $resource_name ) ) {
				$resource_name = ConversionActionServiceClient::conversionActionName( $this->options->get_ads_id(), $resource_name );
			}

			$ca_client         = $this->client->getConversionActionServiceClient();
			$conversion_action = $ca_client->getConversionAction( $resource_name );

			return $this->convert_conversion_action( $conversion_action );
		} catch ( Exception $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );
			$message = $e->getMessage();
			if ( $e instanceof ApiException ) {
				$message = $e->getBasicMessage();
			}

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error retrieving conversion action: %s', 'google-listings-and-ads' ), $message ) );
		}
	}

	/**
	 * Convert conversion action data to an array.
	 *
	 * @param ConversionAction $conversion_action
	 *
	 * @return array An array with some conversion action details.
	 */
	private function convert_conversion_action( ConversionAction $conversion_action ): array {
		$return = [
			'id'     => $conversion_action->getId(),
			'name'   => $conversion_action->getName(),
			'status' => ConversionActionStatus::name( $conversion_action->getStatus() ),
		];
		foreach ( $conversion_action->getTagSnippets() as $t ) {
			/** @var TagSnippet $t */
			if ( $t->getType() !== TrackingCodeType::WEBPAGE ) {
				continue;
			}
			if ( $t->getPageFormat() !== TrackingCodePageFormat::HTML ) {
				continue;
			}
			preg_match( "#send_to': '([^/]+)/([^']+)'#", $t->getEventSnippet(), $matches );
			$return['conversion_id']    = $matches[1];
			$return['conversion_label'] = $matches[2];
			break;
		}
		return $return;
	}

}
