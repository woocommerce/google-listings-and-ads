<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountBusinessInfoService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountHomepageService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountServicesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountUsersService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiIssueResolutionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Account;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\RequestPhoneVerificationRequest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\VerifyPhoneNumberRequest;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Merchant
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Merchant implements OptionsAwareInterface {

	use ExceptionTrait;
	use OptionsAwareTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingContent
	 */
	protected $service;

	/**
	 * The Merchant API homepage service.
	 *
	 * @var MapiAccountHomepageService
	 */
	protected $homepage_service;

	/**
	 * The Merchant API business info service.
	 *
	 * @var MapiAccountBusinessInfoService
	 */
	protected $business_info_service;

	/**
	 * The Merchant API users service.
	 *
	 * @var MapiAccountUsersService
	 */
	protected $users_service;

	/**
	 * The Merchant API account services service.
	 *
	 * @var MapiAccountServicesService
	 */
	protected $services_service;

	/**
	 * The Merchant API issue resolution service.
	 *
	 * @var MapiIssueResolutionService
	 */
	protected $issue_resolution_service;

	/**
	 * Merchant constructor.
	 *
	 * @param ShoppingContent                $service
	 * @param MapiAccountHomepageService     $homepage_service
	 * @param MapiAccountBusinessInfoService $business_info_service
	 * @param MapiAccountUsersService        $users_service
	 * @param MapiAccountServicesService     $services_service
	 * @param MapiIssueResolutionService     $issue_resolution_service
	 */
	public function __construct( ShoppingContent $service, MapiAccountHomepageService $homepage_service, MapiAccountBusinessInfoService $business_info_service, MapiAccountUsersService $users_service, MapiAccountServicesService $services_service, MapiIssueResolutionService $issue_resolution_service ) {
		$this->service                  = $service;
		$this->homepage_service         = $homepage_service;
		$this->business_info_service    = $business_info_service;
		$this->users_service            = $users_service;
		$this->services_service         = $services_service;
		$this->issue_resolution_service = $issue_resolution_service;
	}

	/**
	 * Claim a website for the user's Merchant Center account.
	 *
	 * A MerchantApiException from the claim request is caught and translated into
	 * a plain Exception (code 403 for a claim conflict, otherwise the original
	 * status) so callers receive a stable exception type.
	 *
	 * @return bool
	 * @throws Exception If the website claim fails.
	 */
	public function claimwebsite(): bool {
		try {
			$this->homepage_service->claim();
			do_action( 'woocommerce_gla_site_claim_success', [ 'details' => 'google_proxy' ] );
		} catch ( MerchantApiException $e ) {
			// The woocommerce_gla_mc_client_exception action is fired by MerchantApiException::__construct().
			do_action( 'woocommerce_gla_site_claim_failure', [ 'details' => 'google_proxy' ] );

			if ( $this->is_website_claim_conflict_error( $e ) ) {
				// Use 403 to maintain compatibility with AccountService which relies on this code to identify claim conflicts.
				throw new Exception(
					__( 'Website already claimed, use overwrite to complete the process.', 'google-listings-and-ads' ),
					403
				);
			}

			throw new Exception(
				__( 'Unable to claim website.', 'google-listings-and-ads' ),
				$e->getCode()
			);
		}
		return true;
	}

	/**
	 * Check if the exception indicates a website claim conflict error.
	 *
	 * The Merchant API returns a FAILED_PRECONDITION status when the homepage is
	 * already claimed by another account and overwrite was not requested. A 403 is
	 * also treated as a conflict for parity with the Content API, which surfaced
	 * claim conflicts that way; AccountService keys off the resulting 403 code to
	 * offer the overwrite flow.
	 *
	 * @param MerchantApiException $e The exception to check.
	 * @return bool True if the error indicates a claim conflict.
	 */
	private function is_website_claim_conflict_error( MerchantApiException $e ): bool {
		if ( 403 === $e->get_http_status() ) {
			return true;
		}

		return 'FAILED_PRECONDITION' === ( $e->get_response_body()['error']['status'] ?? '' );
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
	 * @throws ExceptionWithResponseData If the account can't be retrieved.
	 */
	public function get_account( int $id = 0 ): Account {
		$id = $id ?: $this->options->get_merchant_id();

		try {
			$mc_account = $this->service->accounts->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Unable to retrieve Merchant Center account: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$e->getCode(),
				null,
				[ 'errors' => $errors ]
			);
		}
		return $mc_account;
	}

	/**
	 * Get hash of the site URL we used during onboarding.
	 * If not available in a local option, it's fetched from the Merchant Center account.
	 *
	 * @since 1.13.0
	 * @return string|null
	 */
	public function get_claimed_url_hash(): ?string {
		$claimed_url_hash = $this->options->get( OptionsInterface::CLAIMED_URL_HASH );

		if ( empty( $claimed_url_hash ) && $this->options->get_merchant_id() ) {
			try {
				$homepage    = $this->homepage_service->get_homepage();
				$account_url = $homepage['uri'] ?? '';

				if ( empty( $account_url ) || empty( $homepage['claimed'] ) ) {
					return null;
				}

				$claimed_url_hash = md5( untrailingslashit( $account_url ) );
				$this->options->update( OptionsInterface::CLAIMED_URL_HASH, $claimed_url_hash );
			} catch ( Exception $e ) {
				return null;
			}
		}

		return $claimed_url_hash;
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
			$mc_account_status = $this->service->accountstatuses->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve Merchant Center account status.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account_status;
	}

	/**
	 * Update the provided Merchant Center account information.
	 *
	 * @param Account $account The Account data to update.
	 *
	 * @return Account The user's Merchant Center account.
	 * @throws ExceptionWithResponseData If the account can't be updated.
	 */
	public function update_account( Account $account ): Account {
		try {
			$account = $this->service->accounts->update( $account->getId(), $account->getId(), $account );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Unable to update Merchant Center account: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$e->getCode(),
				null,
				[ 'errors' => $errors ]
			);
		}
		return $account;
	}

	/**
	 * Link a Google Ads ID to this Merchant account.
	 *
	 * @param int $ads_id Google Ads ID to link.
	 *
	 * @return bool True if the link invitation is waiting for acceptance. False if the link is already active.
	 * @throws ExceptionWithResponseData When unable to retrieve or update account data.
	 */
	public function link_ads_id( int $ads_id ): bool {
		try {
			$link = $this->services_service->get_google_ads_link( $ads_id );
			if ( null === $link ) {
				$link = $this->services_service->propose_google_ads_link( $ads_id );
			}
		} catch ( MerchantApiException $e ) {
			throw new ExceptionWithResponseData( $e->getMessage(), $e->getCode(), $e, $e->get_response_body() );
		}

		// The MAPI handshake is ESTABLISHED once both sides have approved; anything
		// else means the Ads-side acceptance is still pending.
		return 'ESTABLISHED' !== ( $link['handshake']['approvalState'] ?? '' );
	}

	/**
	 * Get the business information for the connected Merchant Center account.
	 *
	 * @return array The businessInfo resource decoded as an array.
	 * @throws MerchantApiException If the business info can't be retrieved.
	 */
	public function get_business_info(): array {
		return $this->business_info_service->get_business_info();
	}

	/**
	 * Update the business information for the connected Merchant Center account.
	 *
	 * @param array  $business_info BusinessInfo fields to write.
	 * @param string $update_mask   Comma-separated list of fields to update.
	 *
	 * @return array The updated businessInfo.
	 * @throws MerchantApiException If the business info can't be updated.
	 */
	public function update_business_info( array $business_info, string $update_mask ): array {
		return $this->business_info_service->update_business_info( $business_info, $update_mask );
	}

	/**
	 * Check if we have access to the merchant account.
	 *
	 * A MerchantApiException from the users lookup is caught and reported as no
	 * access (returns false) rather than propagated; it is logged through the
	 * woocommerce_gla_mc_client_exception action fired by the exception.
	 *
	 * @param string $email Email address of the connected account.
	 *
	 * @return bool
	 */
	public function has_access( string $email ): bool {
		try {
			$user = $this->users_service->get_current_user();
		} catch ( MerchantApiException $e ) {
			// The woocommerce_gla_mc_client_exception action is fired by MerchantApiException::__construct().
			return false;
		}

		$name_parts = explode( '/', $user['name'] ?? '' );
		$user_email = (string) end( $name_parts );

		return $email === $user_email && in_array( 'ADMIN', $user['accessRights'] ?? [], true );
	}

	/**
	 * Update the Merchant Center ID to use for requests.
	 *
	 * @param int $id  Merchant ID number.
	 *
	 * @return bool
	 */
	public function update_merchant_id( int $id ): bool {
		$previous_id = $this->options->get_merchant_id();

		// Cached data source resource names embed the account id (accounts/{id}/dataSources/...),
		// so a relink to a different account must not reuse the previous account's names.
		if ( $previous_id && $previous_id !== $id ) {
			$this->options->delete( OptionsInterface::MAPI_DATA_SOURCES );
		}

		return $this->options->update( OptionsInterface::MERCHANT_ID, $id );
	}

	/**
	 * Render the account-level issues and their resolution actions for the connected account.
	 *
	 * @since 2.7.1
	 *
	 * @param string $user_input_action_option How user-input actions are rendered (in-app built-in vs redirect).
	 *
	 * @return array The RenderAccountIssuesResponse (renderedIssues with their actions).
	 * @throws Exception When an exception happens in the Merchant API.
	 */
	public function get_account_review_status( string $user_input_action_option = MapiIssueResolutionService::USER_INPUT_BUILT_IN ) {
		try {
			return $this->issue_resolution_service->render_account_issues( $user_input_action_option );
		} catch ( MerchantApiException $e ) {
			// The woocommerce_gla_mc_client_exception action is fired by MerchantApiException::__construct().
			throw new Exception( $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * Trigger the in-app account-review action.
	 *
	 * Completes the review request without leaving the store, using the action context and
	 * flow taken from the rendered review action.
	 *
	 * @since 2.7.1
	 *
	 * @param string $action_context The action context taken from the rendered review action.
	 * @param string $action_flow_id The review action flow id.
	 * @param array  $input_values   Optional input values for the action flow.
	 *
	 * @return array The Merchant API response.
	 * @throws Exception When the trigger produces an exception on the Merchant API side.
	 */
	public function trigger_review_action( string $action_context, string $action_flow_id, array $input_values = [] ): array {
		try {
			return $this->issue_resolution_service->trigger_action(
				$action_context,
				$action_flow_id,
				$input_values
			);
		} catch ( MerchantApiException $e ) {
			// The woocommerce_gla_mc_client_exception action is fired by MerchantApiException::__construct().
			throw new Exception( $e->getMessage(), $e->getCode() );
		}
	}
}
