<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

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

	/** @var string The linked Google post no longer exists. */
	public const NOT_FOUND = 'not-found';

	/** @var string[] Every outcome. */
	public const ALL = [
		self::SUCCESS,
		self::SENT_PENDING_VERIFICATION,
		self::REQUEST_FAILURE,
		self::CONTENT_REJECTION,
		self::ACCOUNT_RESTRICTION,
		self::NOT_FOUND,
	];
}
