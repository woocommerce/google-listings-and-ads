<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncOutcome
 *
 * The outcomes a post's last attempt to reach Google Business Profile can end in, as stored on the
 * post for the editor to tell the merchant what happened.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
final class SyncOutcome {

	/** @var string Google accepted the post. */
	public const SUCCESS = 'success';

	/** @var string Google accepted the post, but holds it as pending until the location is verified. */
	public const SENT_PENDING_VERIFICATION = 'sent-pending-verification';

	/** @var string The request never got an answer from Google, or Google couldn't handle it. */
	public const REQUEST_FAILURE = 'request-failure';

	/** @var string Google didn't accept the post's content. Google gives no reason for this. */
	public const CONTENT_REJECTION = 'content-rejection';

	/** @var string Google refused the post because of a restriction on the merchant's account or profile. */
	public const ACCOUNT_RESTRICTION = 'account-restriction';

	/** @var string The store lost its Google grant, so the merchant has to reconnect their Google Account. */
	public const ACCOUNT_DISCONNECTED = 'account-disconnected';

	/** @var string The linked Google post no longer exists. */
	public const NOT_FOUND = 'not-found';

	/** @var string[] Outcomes that saving the post again retries. */
	public const FAILURES = [
		self::REQUEST_FAILURE,
		self::CONTENT_REJECTION,
		self::ACCOUNT_RESTRICTION,
		self::ACCOUNT_DISCONNECTED,
	];

	/** @var string[] Every outcome. */
	public const ALL = [
		self::SUCCESS,
		self::SENT_PENDING_VERIFICATION,
		self::REQUEST_FAILURE,
		self::CONTENT_REJECTION,
		self::ACCOUNT_RESTRICTION,
		self::ACCOUNT_DISCONNECTED,
		self::NOT_FOUND,
	];

	/** @var string Response code of the reconnect failure thrown when the store loses its Google grant. */
	private const GOOGLE_DISCONNECTED_CODE = 'GOOGLE_DISCONNECTED';

	/** @var string Google's error reason for a post refused because of the account or profile's condition. */
	private const ACCOUNT_CONDITION_REASON = 'OPERATION_UNSUPPORTED_UNDER_ACCOUNT_CONDITION';

	/** @var int Google's numeric error code for the same refusal. */
	private const ACCOUNT_CONDITION_CODE = 1301;

	/** @var int HTTP status Google returns when it doesn't accept a post. */
	private const REJECTED_STATUS = 400;

	/**
	 * Get the outcome of a post Google accepted.
	 *
	 * @param bool $location_unverified Whether the connected location was unverified when the post was sent.
	 *
	 * @return string
	 */
	public static function for_success( bool $location_unverified ): string {
		return $location_unverified ? self::SENT_PENDING_VERIFICATION : self::SUCCESS;
	}

	/**
	 * Classify a failed attempt to send a post to Google.
	 *
	 * Three failures are worth telling apart: a restriction on the account or profile, which
	 * Google explains; a rejected post, which it doesn't; and a lost Google grant, which the
	 * merchant has to reconnect before a retry can work. Anything else, including a server error,
	 * a used-up quota, or a lost Jetpack connection, means the post never got a verdict.
	 *
	 * @param Throwable $failure
	 *
	 * @return array Shaped `{ outcome, message }`. The message is Google's own, or empty when Google gave none.
	 */
	public static function for_failure( Throwable $failure ): array {
		if ( $failure instanceof AccountReconnect && self::GOOGLE_DISCONNECTED_CODE === ( $failure->get_response_data()['code'] ?? null ) ) {
			return [
				'outcome' => self::ACCOUNT_DISCONNECTED,
				'message' => '',
			];
		}

		$error = $failure instanceof BusinessProfileApiException ? ( $failure->get_response_body()['error'] ?? null ) : null;

		// A proxy error, such as a bad path, has no nested `error`, so it's not Google's verdict.
		if ( ! is_array( $error ) ) {
			return [
				'outcome' => self::REQUEST_FAILURE,
				'message' => '',
			];
		}

		$message = is_string( $error['message'] ?? null ) ? $error['message'] : '';

		if ( self::is_account_restriction( $error ) ) {
			$outcome = self::ACCOUNT_RESTRICTION;
		} elseif ( self::REJECTED_STATUS === $failure->get_http_status() ) {
			$outcome = self::CONTENT_REJECTION;
		} else {
			$outcome = self::REQUEST_FAILURE;
		}

		return [
			'outcome' => $outcome,
			'message' => $message,
		];
	}

	/**
	 * Whether Google refused the post because of the account or profile's condition. Google names
	 * the reason in its `errors` list and in an `ErrorInfo` detail, which also carries the code.
	 *
	 * @param array $error Google's decoded `error` object.
	 *
	 * @return bool
	 */
	private static function is_account_restriction( array $error ): bool {
		$entries = array_merge(
			is_array( $error['errors'] ?? null ) ? $error['errors'] : [],
			is_array( $error['details'] ?? null ) ? $error['details'] : []
		);

		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			if ( self::ACCOUNT_CONDITION_REASON === ( $entry['reason'] ?? null ) ) {
				return true;
			}

			if ( self::ACCOUNT_CONDITION_CODE === (int) ( $entry['metadata']['code'] ?? 0 ) ) {
				return true;
			}
		}

		return false;
	}
}
