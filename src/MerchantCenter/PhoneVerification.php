<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PhoneNumber;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneVerification
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 *
 * @since 1.5.0
 */
class PhoneVerification implements Service {

	public const VERIFICATION_METHOD_SMS        = 'SMS';
	public const VERIFICATION_METHOD_PHONE_CALL = 'PHONE_CALL';

	/**
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * @var ISOUtility
	 */
	protected $iso_utility;

	/**
	 * PhoneVerification constructor.
	 *
	 * @param Merchant   $merchant
	 * @param WP         $wp
	 * @param ISOUtility $iso_utility
	 */
	public function __construct( Merchant $merchant, WP $wp, ISOUtility $iso_utility ) {
		$this->merchant    = $merchant;
		$this->wp          = $wp;
		$this->iso_utility = $iso_utility;
	}

	/**
	 * Request verification code to start phone verification.
	 *
	 * @param string      $region_code         Two-letter country code (ISO 3166-1 alpha-2) for the phone number, for
	 *                                         example CA for Canadian numbers.
	 * @param PhoneNumber $phone_number        Phone number to be verified.
	 * @param string      $verification_method Verification method to receive verification code.
	 *
	 * @return string The verification ID to use in subsequent calls to
	 *                `PhoneVerification::verify_phone_number`.
	 *
	 * @throws PhoneVerificationException If there are any errors requesting verification.
	 * @throws InvalidValue If an invalid input provided.
	 */
	public function request_phone_verification( string $region_code, PhoneNumber $phone_number, string $verification_method ): string {
		$this->validate_verification_method( $verification_method );
		$this->validate_phone_region( $region_code );

		try {
			return $this->merchant->request_phone_verification( $region_code, $phone_number->get(), $verification_method, $this->get_language_code() );
		} catch ( GoogleServiceException $e ) {
			throw $this->map_google_exception( $e );
		}
	}

	/**
	 * Validates verification code to verify phone number for the account.
	 *
	 * @param string $verification_id     The verification ID returned by
	 *                                    `PhoneVerification::request_phone_verification`.
	 * @param string $verification_code   The verification code that was sent to the phone number for validation.
	 * @param string $verification_method Verification method used to receive verification code.
	 *
	 * @return void
	 *
	 * @throws PhoneVerificationException If there are any errors verifying the phone number.
	 * @throws InvalidValue If an invalid input provided.
	 */
	public function verify_phone_number( string $verification_id, string $verification_code, string $verification_method ): void {
		$this->validate_verification_method( $verification_method );

		try {
			$this->merchant->verify_phone_number( $verification_id, $verification_code, $verification_method );
		} catch ( GoogleServiceException $e ) {
			throw $this->map_google_exception( $e );
		}
	}

	/**
	 * @param string $method
	 *
	 * @throws InvalidValue If the verification method is invalid.
	 */
	protected function validate_verification_method( string $method ) {
		$allowed = [ self::VERIFICATION_METHOD_SMS, self::VERIFICATION_METHOD_PHONE_CALL ];
		if ( ! in_array( $method, $allowed, true ) ) {
			throw InvalidValue::not_in_allowed_list( $method, $allowed );
		}
	}

	/**
	 * @param string $region_code
	 *
	 * @throws InvalidValue If the phone region code is not a valid ISO 3166-1 alpha-2 country code.
	 */
	protected function validate_phone_region( string $region_code ) {
		if ( ! $this->iso_utility->is_iso3166_alpha2_country_code( $region_code ) ) {
			throw new InvalidValue( 'Invalid phone region! Phone region must be a two letter ISO 3166-1 alpha-2 country code.' );
		}
	}

	/**
	 * @return string
	 */
	protected function get_language_code(): string {
		return $this->iso_utility->wp_locale_to_bcp47( $this->wp->get_user_locale() );
	}

	/**
	 * @param GoogleServiceException $exception
	 *
	 * @return PhoneVerificationException
	 */
	protected function map_google_exception( GoogleServiceException $exception ): PhoneVerificationException {
		$code    = $exception->getCode();
		$message = $exception->getMessage();
		$reason  = '';

		$errors = $exception->getErrors();
		if ( ! empty( $errors ) ) {
			$error   = $errors[ array_key_first( $errors ) ];
			$message = $error['message'] ?? '';
			$reason  = $error['reason'] ?? '';
		}

		return new PhoneVerificationException( $message, $code, $exception, [ 'reason' => $reason ] );
	}
}
