<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncNotice
 *
 * Chooses the notice the merchant sees for the outcome of an attempt to share a post to Google
 * Business Profile. Both editors show the notice this returns, so the wording stays the same in
 * each. Google's own error text is never shown.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
final class SyncNotice {

	public const STATUS_ERROR   = 'error';
	public const STATUS_WARNING = 'warning';

	/**
	 * Get the notice for a sync outcome.
	 *
	 * @param array|null $sync_outcome Shaped `{ outcome, message, image_omitted }`, as returned by
	 *                                 {@see LocalPostService::get_sync_outcome()}.
	 *
	 * @return array|null Shaped `{ status, message }`, or null when there's nothing to tell the
	 *                    merchant.
	 */
	public static function for_outcome( ?array $sync_outcome ): ?array {
		$outcome = $sync_outcome['outcome'] ?? null;

		switch ( $outcome ) {
			case SyncOutcome::REQUEST_FAILURE:
				return self::error( __( 'The post couldn\'t be published to Google Business Profile. Google couldn\'t be reached. Update the post to try again.', 'google-listings-and-ads' ) );
			case SyncOutcome::CONTENT_REJECTION:
				return self::error( __( 'This update was rejected by Google\'s content policies. Please check your email or Google Business Profile dashboard for details and appeal options. To try again, edit the Business Profile text and update the post.', 'google-listings-and-ads' ) );
			case SyncOutcome::ACCOUNT_RESTRICTION:
				return self::error( __( 'Your Google Account or Business Profile currently has administrative restrictions that prevent publishing updates. Please log in to your Google Business Profile dashboard to review account alerts and resolve any notices. Once resolved, update the post to try again.', 'google-listings-and-ads' ) );
			case SyncOutcome::NOT_FOUND:
				return self::error( __( 'The post no longer exists on Google Business Profile.', 'google-listings-and-ads' ) );
			case SyncOutcome::SENT_PENDING_VERIFICATION:
				if ( ! empty( $sync_outcome['image_omitted'] ) ) {
					return self::error( __( 'Your Google Business Profile is currently unverified. This post has been submitted without its image, which couldn\'t be synced. It will publish automatically once your profile is verified.', 'google-listings-and-ads' ) );
				}

				return [
					'status'  => self::STATUS_WARNING,
					'message' => __( 'Your Google Business Profile is currently unverified. This post has been submitted and will publish automatically once your profile is verified.', 'google-listings-and-ads' ),
				];
			case SyncOutcome::SUCCESS:
				if ( ! empty( $sync_outcome['image_omitted'] ) ) {
					return self::error( __( 'The post was published to Google Business Profile without its image. The image couldn\'t be synced.', 'google-listings-and-ads' ) );
				}

				return null;
			default:
				return null;
		}
	}

	/**
	 * Build an error notice.
	 *
	 * @param string $message
	 *
	 * @return array
	 */
	private static function error( string $message ): array {
		return [
			'status'  => self::STATUS_ERROR,
			'message' => $message,
		];
	}
}
