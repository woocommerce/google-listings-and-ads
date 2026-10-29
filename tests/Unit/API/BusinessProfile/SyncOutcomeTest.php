<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Error;
use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncOutcomeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class SyncOutcomeTest extends UnitTest {

	public function test_an_accepted_post_for_an_unverified_location_is_sent_pending_verification() {
		$state = [
			'hasVoiceOfMerchant'   => false,
			'hasBusinessAuthority' => true,
			'verify'               => [ 'hasPendingVerification' => false ],
		];

		$this->assertSame( SyncOutcome::SENT_PENDING_VERIFICATION, SyncOutcome::for_success( $state ) );
	}

	public function test_an_accepted_post_for_an_eligible_location_is_a_success() {
		$state = [
			'hasVoiceOfMerchant'   => true,
			'hasBusinessAuthority' => true,
		];

		$this->assertSame( SyncOutcome::SUCCESS, SyncOutcome::for_success( $state ) );
	}

	public function test_an_accepted_post_is_a_success_when_the_voice_of_merchant_state_could_not_be_read() {
		$this->assertSame( SyncOutcome::SUCCESS, SyncOutcome::for_success( null ) );
	}

	/**
	 * @dataProvider provide_restricted_voice_of_merchant_states
	 *
	 * @param array $state Decoded `getVoiceOfMerchantState` response.
	 */
	public function test_an_accepted_post_for_a_restricted_location_is_sent_location_restricted( array $state ) {
		$this->assertSame( SyncOutcome::SENT_LOCATION_RESTRICTED, SyncOutcome::for_success( $state ) );
	}

	/**
	 * A restricted outcome must never land in `FAILURES` -- the post genuinely reached Google, so
	 * treating it as retryable would send a duplicate. See `MissingLocalPostIdException`, which
	 * carries this outcome when Google's response has no post ID.
	 */
	public function test_sent_location_restricted_is_not_a_retryable_failure() {
		$this->assertNotContains( SyncOutcome::SENT_LOCATION_RESTRICTED, SyncOutcome::FAILURES );
		$this->assertContains( SyncOutcome::SENT_LOCATION_RESTRICTED, SyncOutcome::ALL );
	}

	public function test_a_restricted_state_is_a_success_when_has_voice_of_merchant_is_not_explicitly_false() {
		$state = [
			'hasVoiceOfMerchant'     => true,
			'waitForVoiceOfMerchant' => [],
		];

		$this->assertSame( SyncOutcome::SUCCESS, SyncOutcome::for_success( $state ) );
	}

	public function provide_restricted_voice_of_merchant_states(): array {
		return [
			'under review'       => [
				[
					'hasVoiceOfMerchant'     => false,
					'hasBusinessAuthority'   => true,
					'waitForVoiceOfMerchant' => [],
				],
			],
			'ownership conflict' => [
				[
					'hasVoiceOfMerchant'       => false,
					'hasBusinessAuthority'     => true,
					'resolveOwnershipConflict' => [],
				],
			],
			'suspended'          => [
				[
					'hasVoiceOfMerchant'   => false,
					'hasBusinessAuthority' => true,
					'complyWithGuidelines' => [ 'recommendationReason' => 'BUSINESS_LOCATION_SUSPENDED' ],
				],
			],
			'disabled'           => [
				[
					'hasVoiceOfMerchant'   => false,
					'hasBusinessAuthority' => true,
					'complyWithGuidelines' => [ 'recommendationReason' => 'BUSINESS_LOCATION_DISABLED' ],
				],
			],
		];
	}

	/**
	 * @dataProvider provide_voice_of_merchant_states_with_no_clear_answer
	 *
	 * @param array $state Decoded `getVoiceOfMerchantState` response.
	 */
	public function test_an_accepted_post_is_a_success_for_a_voice_of_merchant_state_with_no_clear_answer( array $state ) {
		$this->assertSame( SyncOutcome::SUCCESS, SyncOutcome::for_success( $state ) );
	}

	public function provide_voice_of_merchant_states_with_no_clear_answer(): array {
		return [
			'no voice of merchant, no reason given' => [ [ 'hasVoiceOfMerchant' => false ] ],
			'verify block that is not an object'    => [
				[
					'hasVoiceOfMerchant' => false,
					'verify'             => 'unexpected',
				],
			],
			'empty body'                            => [ [] ],
			'unexpected body'                       => [ [ 'unexpected' => 'shape' ] ],
		];
	}

	public function test_an_account_condition_refusal_is_an_account_restriction_with_googles_message() {
		$failure = new BusinessProfileApiException( 400, $this->get_account_restricted_body(), __METHOD__ );

		$this->assertSame(
			[
				'outcome' => SyncOutcome::ACCOUNT_RESTRICTION,
				'message' => 'The operation is not supported under the current account condition.',
			],
			SyncOutcome::for_failure( $failure )
		);
	}

	public function test_an_account_condition_refusal_named_only_by_its_numeric_code_is_an_account_restriction() {
		$failure = new BusinessProfileApiException(
			400,
			[
				'error' => [
					'code'    => 400,
					'message' => 'Your profile is suspended.',
					'status'  => 'FAILED_PRECONDITION',
					'details' => [
						[
							'@type'    => 'type.googleapis.com/google.rpc.ErrorInfo',
							'metadata' => [ 'code' => '1301' ],
						],
					],
				],
			],
			__METHOD__
		);

		$this->assertSame( SyncOutcome::ACCOUNT_RESTRICTION, SyncOutcome::for_failure( $failure )['outcome'] );
	}

	public function test_a_google_rejection_is_a_content_rejection_with_googles_message_unchanged() {
		$failure = new BusinessProfileApiException(
			400,
			[
				'error' => [
					'code'    => 400,
					'message' => 'Request contains an invalid argument.',
					'status'  => 'INVALID_ARGUMENT',
				],
			],
			__METHOD__
		);

		$this->assertSame(
			[
				'outcome' => SyncOutcome::CONTENT_REJECTION,
				'message' => 'Request contains an invalid argument.',
			],
			SyncOutcome::for_failure( $failure )
		);
	}

	public function test_a_lost_google_grant_is_an_account_disconnection_with_no_message() {
		$this->assertSame(
			[
				'outcome' => SyncOutcome::ACCOUNT_DISCONNECTED,
				'message' => '',
			],
			SyncOutcome::for_failure( AccountReconnect::google_disconnected() )
		);
	}

	public function test_a_failure_with_no_response_is_a_request_failure_with_no_message() {
		$failure = new BusinessProfileApiException( 503, [], __METHOD__ );

		$this->assertSame(
			[
				'outcome' => SyncOutcome::REQUEST_FAILURE,
				'message' => '',
			],
			SyncOutcome::for_failure( $failure )
		);
	}

	public function test_a_proxy_error_with_no_google_error_is_a_request_failure() {
		$failure = new BusinessProfileApiException( 400, [ 'message' => 'Bad path.' ], __METHOD__ );

		$this->assertSame(
			[
				'outcome' => SyncOutcome::REQUEST_FAILURE,
				'message' => '',
			],
			SyncOutcome::for_failure( $failure )
		);
	}

	/**
	 * @dataProvider data_provider_google_errors_without_a_verdict
	 *
	 * @param int    $status
	 * @param string $google_status
	 */
	public function test_a_google_error_that_is_not_a_verdict_on_the_post_is_a_request_failure( int $status, string $google_status ) {
		$failure = new BusinessProfileApiException(
			$status,
			[
				'error' => [
					'code'    => $status,
					'message' => 'Google error.',
					'status'  => $google_status,
				],
			],
			__METHOD__
		);

		$this->assertSame(
			[
				'outcome' => SyncOutcome::REQUEST_FAILURE,
				'message' => 'Google error.',
			],
			SyncOutcome::for_failure( $failure )
		);
	}

	/**
	 * @return array
	 */
	public function data_provider_google_errors_without_a_verdict(): array {
		return [
			'lost grant'     => [ 401, 'UNAUTHENTICATED' ],
			'no permission'  => [ 403, 'PERMISSION_DENIED' ],
			'used-up quota'  => [ 429, 'RESOURCE_EXHAUSTED' ],
			'server error'   => [ 500, 'INTERNAL' ],
			'no Google post' => [ 404, 'NOT_FOUND' ],
		];
	}

	/**
	 * @dataProvider data_provider_failures_from_outside_google
	 *
	 * @param Throwable $failure
	 */
	public function test_a_failure_from_outside_google_is_a_request_failure_with_no_message( Throwable $failure ) {
		$this->assertSame(
			[
				'outcome' => SyncOutcome::REQUEST_FAILURE,
				'message' => '',
			],
			SyncOutcome::for_failure( $failure )
		);
	}

	/**
	 * @return array
	 */
	public function data_provider_failures_from_outside_google(): array {
		return [
			'reconnect needed'   => [ AccountReconnect::jetpack_disconnected() ],
			'plain exception'    => [ new Exception( 'No Google Business Profile location is connected.', 404 ) ],
			'error in this code' => [ new Error( 'Call to a member function on null.' ) ],
		];
	}

	/**
	 * Google's response refusing a post because of the account or profile's condition.
	 *
	 * @return array
	 */
	private function get_account_restricted_body(): array {
		return [
			'error' => [
				'code'    => 400,
				'message' => 'The operation is not supported under the current account condition.',
				'errors'  => [
					[
						'message' => 'The operation is not supported under the current account condition.',
						'domain'  => 'global',
						'reason'  => 'OPERATION_UNSUPPORTED_UNDER_ACCOUNT_CONDITION',
					],
				],
				'status'  => 'FAILED_PRECONDITION',
				'details' => [
					[
						'@type'    => 'type.googleapis.com/google.rpc.ErrorInfo',
						'reason'   => 'OPERATION_UNSUPPORTED_UNDER_ACCOUNT_CONDITION',
						'domain'   => 'mybusiness.googleapis.com',
						'metadata' => [ 'code' => '1301' ],
					],
				],
			],
		];
	}
}
