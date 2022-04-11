<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Google\Exception as GoogleException;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Account;
use Google\Service\ShoppingContent\AccountAdsLink;
use Google\Service\ShoppingContent\AccountStatus;
use Google\Service\ShoppingContent\ProductstatusesCustomBatchResponse;
use Google\Service\ShoppingContent\ProductstatusesCustomBatchRequest;
use Google\Service\ShoppingContent\Product;
use Exception;
use Google\Service\ShoppingContent\RequestPhoneVerificationRequest;
use Google\Service\ShoppingContent\VerifyPhoneNumberRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Class Merchant
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Merchant implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingContent
	 */
	protected $service;

	/**
	 * Merchant constructor.
	 *
	 * @param ShoppingContent $service
	 */
	public function __construct( ShoppingContent $service ) {
		$this->service = $service;
	}

	/**
	 * @return Product[]
	 */
	public function get_products(): array {
		$products = $this->service->products->listProducts( $this->options->get_merchant_id() );
		$return   = [];

		while ( ! empty( $products->getResources() ) ) {

			foreach ( $products->getResources() as $product ) {
				$return[] = $product;
			}

			if ( empty( $products->getNextPageToken() ) ) {
				break;
			}

			$products = $this->service->products->listProducts(
				$this->options->get_merchant_id(),
				[ 'pageToken' => $products->getNextPageToken() ]
			);
		}

		return $return;
	}


	/**
	 * Claim a website for the user's Merchant Center account.
	 *
	 * @param bool $overwrite Whether to include the overwrite directive.
	 * @return bool
	 * @throws Exception If the website claim fails.
	 */
	public function claimwebsite( bool $overwrite = false ): bool {
		try {
			$id     = $this->options->get_merchant_id();
			$params = $overwrite ? [ 'overwrite' => true ] : [];
			$this->service->accounts->claimwebsite( $id, $id, $params );
			do_action( 'woocommerce_gla_site_claim_success', [ 'details' => 'google_proxy' ] );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			do_action( 'woocommerce_gla_site_claim_failure', [ 'details' => 'google_proxy' ] );

			$error_message = __( 'Unable to claim website.', 'google-listings-and-ads' );
			if ( 403 === $e->getCode() ) {
				$error_message = __( 'Website already claimed, use overwrite to complete the process.', 'google-listings-and-ads' );
			}
			throw new Exception( $error_message, $e->getCode() );
		}
		return true;
	}

	/**
	 * Request verification code to start phone verification.
	 *
	 * @param string $region_code         Two-letter country code (ISO 3166-1 alpha-2) for the phone number, for
	 *                                    example CA for Canadian numbers.
	 * @param string $phone_number        Phone number to be verified.
	 * @param string $verification_method Verification method to receive verification code.
	 * @param string $language_code       Language code IETF BCP 47 syntax (for example, en-US). Language code is used
	 *                                    to provide localized SMS and PHONE_CALL. Default language used is en-US if
	 *                                    not provided.
	 *
	 * @return string The verification ID to use in subsequent calls to
	 *                `Merchant::verify_phone_number`.
	 *
	 * @throws GoogleServiceException If there are any Google API errors.
	 *
	 * @see https://tools.ietf.org/html/bcp47 IETF BCP 47 language codes.
	 * @see https://wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements ISO 3166-1 alpha-2
	 *      officially assigned codes.
	 *
	 * @since 1.5.0
	 */
	public function request_phone_verification( string $region_code, string $phone_number, string $verification_method, string $language_code = 'en-US' ): string {
		$merchant_id = $this->options->get_merchant_id();
		$request     = new RequestPhoneVerificationRequest(
			[
				'phoneRegionCode'         => $region_code,
				'phoneNumber'             => $phone_number,
				'phoneVerificationMethod' => $verification_method,
				'languageCode'            => $language_code,
			]
		);

		try {
			return $this->service->accounts->requestphoneverification( $merchant_id, $merchant_id, $request )->getVerificationId();
		} catch ( GoogleServiceException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw $e;
		}
	}

	/**
	 * Validates verification code to verify phone number for the account.
	 *
	 * @param string $verification_id     The verification ID returned by
	 *                                    `Merchant::request_phone_verification`.
	 * @param string $verification_code   The verification code that was sent to the phone number for validation.
	 * @param string $verification_method Verification method used to receive verification code.
	 *
	 * @return string Verified phone number if verification is successful.
	 *
	 * @throws GoogleServiceException If there are any Google API errors.
	 *
	 * @since 1.5.0
	 */
	public function verify_phone_number( string $verification_id, string $verification_code, string $verification_method ): string {
		$merchant_id = $this->options->get_merchant_id();
		$request     = new VerifyPhoneNumberRequest(
			[
				'verificationId'          => $verification_id,
				'verificationCode'        => $verification_code,
				'phoneVerificationMethod' => $verification_method,
			]
		);

		try {
			return $this->service->accounts->verifyphonenumber( $merchant_id, $merchant_id, $request )->getVerifiedPhoneNumber();
		} catch ( GoogleServiceException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw $e;
		}
	}

	/**
	 * Retrieve the user's Merchant Center account information.
	 *
	 * @param int $id Optional - the Merchant Center account to retrieve
	 *
	 * @return Account The user's Merchant Center account.
	 * @throws MerchantApiException If the account can't be retrieved.
	 */
	public function get_account( int $id = 0 ): Account {
		$id = $id ?: $this->options->get_merchant_id();

		try {
			$mc_account = $this->service->accounts->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw MerchantApiException::account_retrieve_failed( $e->getCode() );
		}
		return $mc_account;
	}

	/**
	 * Retrieve the user's Merchant Center account information.
	 *
	 * @param int $id Optional - the Merchant Center account to retrieve
	 * @return AccountStatus The user's Merchant Center account status.
	 * @throws Exception If the account can't be retrieved.
	 */
	public function get_accountstatus( int $id = 0 ): AccountStatus {
		$id = $id ?: $this->options->get_merchant_id();

		try {
			$mc_account_status = $this->service->accountstatuses->get(
				$id,
				$id,
				[
					'destinations' => 'Shopping',
				]
			);
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve Merchant Center account status.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account_status;
	}

	/**
	 * Retrieve a batch of Merchant Center Product Statuses using the provided Merchant Center product IDs.
	 *
	 * @since 1.1.0
	 *
	 * @param string[] $mc_product_ids
	 *
	 * @return ProductstatusesCustomBatchResponse;
	 */
	public function get_productstatuses_batch( array $mc_product_ids ): ProductstatusesCustomBatchResponse {
		$merchant_id = $this->options->get_merchant_id();
		$entries     = [];
		foreach ( $mc_product_ids as $index => $id ) {
			$entries[] = [
				'batchId'    => $index + 1,
				'productId'  => $id,
				'method'     => 'GET',
				'merchantId' => $merchant_id,
			];
		}

		// Retrieve batch.
		$request = new ProductstatusesCustomBatchRequest();
		$request->setEntries( $entries );
		return $this->service->productstatuses->custombatch( $request );
	}

	/**
	 * Update the provided Merchant Center account information.
	 *
	 * @param Account $account The Account data to update.
	 *
	 * @return Account The user's Merchant Center account.
	 * @throws MerchantApiException If the account can't be retrieved.
	 */
	public function update_account( Account $account ): Account {
		try {
			$account = $this->service->accounts->update( $account->getId(), $account->getId(), $account );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw MerchantApiException::account_update_failed( $e->getCode() );
		}
		return $account;
	}

	/**
	 * Link a Google Ads ID to this Merchant account.
	 *
	 * @param int $ads_id Google Ads ID to link.
	 *
	 * @return bool
	 * @throws MerchantApiException When unable to retrieve or update account data.
	 */
	public function link_ads_id( int $ads_id ): bool {
		$account   = $this->get_account();
		$ads_links = $account->getAdsLinks();

		// Stop early if we already have a link setup.
		foreach ( $ads_links as $link ) {
			if ( $ads_id === absint( $link->getAdsId() ) ) {
				return false;
			}
		}

		$link = new AccountAdsLink();
		$link->setAdsId( $ads_id );
		$link->setStatus( 'active' );
		$account->setAdsLinks( array_merge( $ads_links, [ $link ] ) );
		$this->update_account( $account );

		return true;
	}

	/**
	 * Check if we have access to the merchant account.
	 *
	 * @param string $email Email address of the connected account.
	 *
	 * @return bool
	 */
	public function has_access( string $email ): bool {
		$id = $this->options->get_merchant_id();

		try {
			$account = $this->service->accounts->get( $id, $id );

			foreach ( $account->getUsers() as $user ) {
				if ( $email === $user->getEmailAddress() && $user->getAdmin() ) {
					return true;
				}
			}
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
		}

		return false;
	}

	/**
	 * Update the Merchant Center ID to use for requests.
	 *
	 * @param int $id  Merchant ID number.
	 *
	 * @return bool
	 */
	public function update_merchant_id( int $id ): bool {
		return $this->options->update( OptionsInterface::MERCHANT_ID, $id );
	}
}
