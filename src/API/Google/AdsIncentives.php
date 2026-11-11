<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAppliedIncentiveQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Exception;
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
	 * @var TransientsInterface
	 */
	protected $transients;

	/**
	 * How long to cache a "not applied" result. An offer the merchant applies directly in
	 * Google Ads shows up as applied within this time.
	 */
	protected const APPLIED_INCENTIVE_CACHE_DURATION = 15 * MINUTE_IN_SECONDS;

	/**
	 * AdsIncentives constructor.
	 *
	 * @param GoogleAdsClient     $client
	 * @param WC                  $wc
	 * @param TransientsInterface $transients
	 */
	public function __construct( GoogleAdsClient $client, WC $wc, TransientsInterface $transients ) {
		$this->client     = $client;
		$this->wc         = $wc;
		$this->transients = $transients;
	}

	/**
	 * Whether an offer was already applied to the connected Google Ads account.
	 *
	 * The incentives API keeps returning offers after the merchant applies one, so this asks for the
	 * applied incentives instead. An offer can't be unapplied, so an applied result is stored in an
	 * option and Google isn't asked again. A "not applied" result is cached for a short time, so an
	 * offer applied directly in Google Ads is still picked up.
	 *
	 * A failed query counts as applied, so the offer picker hides, and isn't cached, so a short
	 * Google outage doesn't hide the offer for the whole cache time.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool
	 */
	public function has_applied_incentive(): bool {
		$ads_id = $this->options->get_ads_id();
		if ( ! $ads_id ) {
			return false;
		}

		if ( $this->options->get( OptionsInterface::ADS_INCENTIVE_APPLIED ) ) {
			return true;
		}

		if ( 'no' === $this->transients->get( TransientsInterface::ADS_APPLIED_INCENTIVE ) ) {
			return false;
		}

		try {
			$results     = ( new AdsAppliedIncentiveQuery() )->set_client( $this->client, $ads_id )->get_results();
			$has_applied = false;

			foreach ( $results->iterateAllElements() as $row ) {
				$has_applied = true;
				break;
			}
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			return true;
		}

		if ( $has_applied ) {
			$this->mark_incentive_applied();
		} else {
			$this->transients->set( TransientsInterface::ADS_APPLIED_INCENTIVE, 'no', self::APPLIED_INCENTIVE_CACHE_DURATION );
		}

		return $has_applied;
	}

	/**
	 * Store that an offer was applied to the connected Google Ads account, and clear any cached
	 * "not applied" result.
	 *
	 * @since n.e.x.t
	 */
	protected function mark_incentive_applied(): void {
		$this->options->update( OptionsInterface::ADS_INCENTIVE_APPLIED, true );
		$this->transients->delete( TransientsInterface::ADS_APPLIED_INCENTIVE );
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

			$this->mark_incentive_applied();

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
