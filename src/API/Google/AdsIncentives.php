<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Google\Ads\GoogleAds\V23\Services\ApplyIncentiveRequest;
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
class AdsIncentives implements OptionsAwareInterface {

	use ExceptionTrait;
	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * AdsIncentives constructor.
	 *
	 * @param GoogleAdsClient $client
	 * @param WC              $wc
	 */
	public function __construct( GoogleAdsClient $client, WC $wc ) {
		$this->client = $client;
		$this->wc     = $wc;
	}

	/**
	 * Fetch available incentive offers from the Google Ads API.
	 *
	 * Country and language are derived from the store's base country and the WP locale.
	 *
	 * @since 3.3.0
	 *
	 * @return array Structured incentive offer data. Always returns a valid structure,
	 *               falling back to an empty CYO_INCENTIVE response on API errors.
	 */
	public function fetch_incentives(): array {
		$empty_response = [
			'type'                  => OfferType::name( OfferType::CYO_INCENTIVE ),
			'termsAndConditionsUrl' => '',
			'incentives'            => [],
		];

		try {
			$request = new FetchIncentiveRequest();
			$request->setCountryCode( $this->wc->get_base_country() );
			$request->setLanguageCode( $this->get_language_code() );

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

	/**
	 * Apply a selected incentive to the connected Google Ads account.
	 *
	 * @since 3.3.0
	 *
	 * @param string $incentive_id The selected incentive ID.
	 * @param string $country_code ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The applied incentive data with coupon_code and creation_time.
	 * @throws ExceptionWithResponseData When the API call fails.
	 */
	public function apply_incentive( string $incentive_id, string $country_code ): array {
		try {
			$request = new ApplyIncentiveRequest(
				[
					'selected_incentive_id' => (int) $incentive_id,
					'customer_id'           => (string) $this->options->get_ads_id(),
					'country_code'          => $country_code,
				]
			);

			$response = $this->client->getIncentiveServiceClient()->applyIncentive( $request );

			return [
				'coupon_code'   => $response->getCouponCode(),
				'creation_time' => $response->getCreationTime(),
			];
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				sprintf(
				/* translators: %s Error message */
					__( 'Error applying incentive: %s', 'google-listings-and-ads' ),
					reset( $errors )
				),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Get the ISO 639-1 language code from the WordPress locale.
	 *
	 * @return string
	 */
	protected function get_language_code(): string {
		$locale = get_locale();

		if ( empty( $locale ) ) {
			return 'en';
		}

		return strtolower( substr( $locale, 0, 2 ) );
	}
}
