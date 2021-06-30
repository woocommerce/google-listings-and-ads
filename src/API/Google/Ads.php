<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAccountQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsBillingStatusQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Exception;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\V8\Enums\AccessRoleEnum\AccessRole;
use Google\Ads\GoogleAds\V8\Enums\MerchantCenterLinkStatusEnum\MerchantCenterLinkStatus;
use Google\Ads\GoogleAds\V8\Resources\MerchantCenterLink;
use Google\Ads\GoogleAds\V8\Services\MerchantCenterLinkOperation;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ads
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Ads implements OptionsAwareInterface {

	use ApiExceptionTrait;
	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * Ads constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Get billing status.
	 *
	 * @return string
	 */
	public function get_billing_status(): string {
		$ads_id = $this->options->get_ads_id();

		if ( ! $ads_id ) {
			return BillingSetupStatus::UNKNOWN;
		}

		try {
			$results = ( new AdsBillingStatusQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->get_results();

			foreach ( $results->iterateAllElements() as $row ) {
				$billing_setup = $row->getBillingSetup();
				$status        = BillingSetupStatus::label( $billing_setup->getStatus() );
				return apply_filters( 'woocommerce_gla_ads_billing_setup_status', $status, $ads_id );
			}
		} catch ( ApiException $e ) {
			// Do not act upon error as we might not have permission to access this account yet.
			if ( 'PERMISSION_DENIED' !== $e->getStatus() ) {
				do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
			}
		} catch ( ValidationException $e ) {
			// If no billing setups are found, just return UNKNOWN
			return BillingSetupStatus::UNKNOWN;
		}

		return apply_filters( 'woocommerce_gla_ads_billing_setup_status', BillingSetupStatus::UNKNOWN, $ads_id );
	}

	/**
	 * Accept a link from a merchant account.
	 *
	 * @param int $merchant_id Merchant Center account id.
	 * @throws Exception When a link is unavailable.
	 */
	public function accept_merchant_link( int $merchant_id ) {
		$link = $this->get_merchant_link( $merchant_id );

		if ( $link->getStatus() === MerchantCenterLinkStatus::ENABLED ) {
			return;
		}

		$link->setStatus( MerchantCenterLinkStatus::ENABLED );

		$operation = new MerchantCenterLinkOperation();
		$operation->setUpdate( $link );
		$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $link ) );

		$this->client->getMerchantCenterLinkServiceClient()->mutateMerchantCenterLink(
			$this->options->get_ads_id(),
			$operation
		);
	}

	/**
	 * Check if we have access to the merchant account.
	 *
	 * @param string $email Email address of the connected account.
	 *
	 * @return bool
	 */
	public function has_access( string $email ): bool {
		$ads_id = $this->options->get_ads_id();

		try {
			$results = ( new AdsAccountQuery() )
			->set_client( $this->client, $ads_id )
			->where( 'customer_user_access.email_address', $email )
			->get_results();

			foreach ( $results->iterateAllElements() as $row ) {
				$access = $row->getCustomerUserAccess();
				if ( AccessRole::ADMIN === $access->getAccessRole() ) {
					return true;
				}
			}
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return false;
	}

	/**
	 * Get the link from a merchant account.
	 *
	 * @param int $merchant_id Merchant Center account id.
	 *
	 * @return MerchantCenterLink
	 * @throws Exception When the merchant link hasn't been created.
	 */
	private function get_merchant_link( int $merchant_id ): MerchantCenterLink {
		$response = $this->client->getMerchantCenterLinkServiceClient()->listMerchantCenterLinks(
			$this->options->get_ads_id()
		);

		foreach ( $response->getMerchantCenterLinks() as $link ) {
			/** @var MerchantCenterLink $link */
			if ( $merchant_id === absint( $link->getId() ) ) {
				return $link;
			}
		}

		throw new Exception( __( 'Merchant link is not available to accept', 'google-listings-and-ads' ) );
	}

}
