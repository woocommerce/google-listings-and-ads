<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\Ads\GoogleAds\V23\Services\FetchIncentiveRequest;
use Google\Ads\GoogleAds\V23\Services\FetchIncentiveRequest\IncentiveType;
use Google\Ads\GoogleAds\V23\Services\Incentive;
use Google\Ads\GoogleAds\V23\Services\IncentiveOffer\OfferType;
use Google\ApiCore\ApiException;
use Google\Type\Money;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsIncentives
 *
 * @since 3.3.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsIncentives {

	use ExceptionTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * AdsIncentives constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Fetch available incentive offers from the Google Ads API.
	 *
	 * @since 3.3.0
	 *
	 * @param string $country_code  ISO 3166-1 alpha-2 country code.
	 * @param string $language_code ISO 639-1 language code.
	 *
	 * @return array Structured incentive offer data. Always returns a valid structure,
	 *               falling back to an empty CYO_INCENTIVE response on API errors.
	 */
	public function fetch_incentives( string $country_code, string $language_code ): array {
		$empty_response = [
			'type'                  => OfferType::name( OfferType::CYO_INCENTIVE ),
			'termsAndConditionsUrl' => '',
			'incentives'            => [],
		];

		try {
			$request = new FetchIncentiveRequest();
			$request->setCountryCode( $country_code );
			$request->setLanguageCode( $language_code );

			$response = $this->client->getIncentiveServiceClient()->fetchIncentive( $request );
			$offer    = $response->getIncentiveOffer();

			if ( ! $offer || ! $offer->hasType() ) {
				return $empty_response;
			}

			$result = [
				'type'                  => OfferType::name( $offer->getType() ),
				'termsAndConditionsUrl' => $offer->getConsolidatedTermsAndConditionsUrl(),
				'incentives'            => [],
			];

			if ( OfferType::CYO_INCENTIVE === $offer->getType() && $offer->hasCyoIncentives() ) {
				$cyo = $offer->getCyoIncentives();

				$offer_map = [
					'low'    => $cyo->getLowOffer(),
					'medium' => $cyo->getMediumOffer(),
					'high'   => $cyo->getHighOffer(),
				];

				foreach ( $offer_map as $level => $incentive ) {
					if ( $incentive ) {
						$result['incentives'][] = $this->format_incentive( $incentive, $level );
					}
				}
			}

			return $result;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			return $empty_response;
		}
	}

	/**
	 * Format an Incentive protobuf message into an array for the REST response.
	 *
	 * @since 3.3.0
	 *
	 * @param Incentive $incentive The incentive object.
	 * @param string    $level     The offer level (low, medium, high).
	 *
	 * @return array
	 */
	protected function format_incentive( Incentive $incentive, string $level ): array {
		$data = [
			'id'                    => (string) $incentive->getIncentiveId(),
			'type'                  => IncentiveType::name( $incentive->getType() ),
			'offer'                 => $level,
			'termsAndConditionsUrl' => $incentive->getIncentiveTermsAndConditionsUrl(),
			'requirement'           => [],
		];

		if ( $incentive->hasRequirement() ) {
			$requirement = $incentive->getRequirement();

			if ( $requirement->hasSpend() ) {
				$spend                        = $requirement->getSpend();
				$data['requirement']['spend'] = [
					'awardAmount'    => $this->format_money( $spend->getAwardAmount() ),
					'requiredAmount' => $this->format_money( $spend->getRequiredAmount() ),
				];
			}
		}

		return $data;
	}

	/**
	 * Format a Money protobuf message into an array.
	 *
	 * @since 3.3.0
	 *
	 * @param Money|null $money The Money object.
	 *
	 * @return array
	 */
	protected function format_money( ?Money $money ): array {
		if ( ! $money ) {
			return [
				'currencyCode' => '',
				'units'        => '0',
			];
		}

		return [
			'currencyCode' => $money->getCurrencyCode(),
			'units'        => (string) $money->getUnits(),
		];
	}
}
