<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncNotice;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncNoticeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class SyncNoticeTest extends UnitTest {

	/**
	 * @dataProvider data_provider_outcomes
	 *
	 * @param string $outcome
	 * @param bool   $image_omitted
	 * @param string $status
	 * @param string $message
	 */
	public function test_for_outcome_returns_the_notice_for_each_outcome( string $outcome, bool $image_omitted, string $status, string $message ) {
		$this->assertSame(
			[
				'status'  => $status,
				'message' => $message,
			],
			SyncNotice::for_outcome( $this->outcome( $outcome, $image_omitted ) )
		);
	}

	/**
	 * @return array
	 */
	public function data_provider_outcomes(): array {
		return [
			'request failure'            => [
				SyncOutcome::REQUEST_FAILURE,
				false,
				SyncNotice::STATUS_ERROR,
				'The post couldn\'t be published to Google Business Profile. Google couldn\'t be reached. Update the post to try again.',
			],
			'account disconnected'       => [
				SyncOutcome::ACCOUNT_DISCONNECTED,
				false,
				SyncNotice::STATUS_ERROR,
				'The post couldn\'t be published to Google Business Profile. Reconnect your Google account, then update the post to try again.',
			],
			'content rejection'          => [
				SyncOutcome::CONTENT_REJECTION,
				false,
				SyncNotice::STATUS_ERROR,
				'This update was rejected by Google\'s content policies. Please check your email or Google Business Profile dashboard for details and appeal options. To try again, edit the Business Profile text and update the post.',
			],
			'account restriction'        => [
				SyncOutcome::ACCOUNT_RESTRICTION,
				false,
				SyncNotice::STATUS_ERROR,
				'Your Google Account or Business Profile currently has administrative restrictions that prevent publishing updates. Please log in to your Google Business Profile dashboard to review account alerts and resolve any notices. Once resolved, update the post to try again.',
			],
			'not found'                  => [
				SyncOutcome::NOT_FOUND,
				false,
				SyncNotice::STATUS_ERROR,
				'The post no longer exists on Google Business Profile.',
			],
			'sent, pending verification' => [
				SyncOutcome::SENT_PENDING_VERIFICATION,
				false,
				SyncNotice::STATUS_WARNING,
				'Your Google Business Profile is currently unverified. This post has been submitted and will publish automatically once your profile is verified.',
			],
			'sent, pending verification, without the image' => [
				SyncOutcome::SENT_PENDING_VERIFICATION,
				true,
				SyncNotice::STATUS_ERROR,
				'Your Google Business Profile is currently unverified. This post has been submitted without its image, which couldn\'t be synced. It will publish automatically once your profile is verified.',
			],
			'success without the image'  => [
				SyncOutcome::SUCCESS,
				true,
				SyncNotice::STATUS_ERROR,
				'The post was published to Google Business Profile without its image. The image couldn\'t be synced.',
			],
		];
	}

	public function test_for_outcome_returns_no_notice_for_a_plain_success() {
		$this->assertNull( SyncNotice::for_outcome( $this->outcome( SyncOutcome::SUCCESS, false ) ) );
	}

	public function test_for_outcome_returns_no_notice_when_the_post_has_never_been_sent() {
		$this->assertNull( SyncNotice::for_outcome( null ) );
	}

	public function test_for_outcome_never_shows_googles_own_message() {
		$outcome            = $this->outcome( SyncOutcome::ACCOUNT_RESTRICTION, false );
		$outcome['message'] = 'Request contains an invalid argument.';

		$this->assertStringNotContainsString( 'invalid argument', SyncNotice::for_outcome( $outcome )['message'] );
	}

	public function test_for_outcome_shows_the_failure_rather_than_the_image_notice_when_the_send_failed() {
		$this->assertSame(
			SyncNotice::for_outcome( $this->outcome( SyncOutcome::REQUEST_FAILURE, false ) ),
			SyncNotice::for_outcome( $this->outcome( SyncOutcome::REQUEST_FAILURE, true ) )
		);
	}

	/**
	 * Build a stored sync outcome.
	 *
	 * @param string $outcome
	 * @param bool   $image_omitted
	 *
	 * @return array
	 */
	private function outcome( string $outcome, bool $image_omitted ): array {
		return [
			'outcome'       => $outcome,
			'message'       => '',
			'image_omitted' => $image_omitted,
		];
	}
}
