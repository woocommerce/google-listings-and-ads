<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class VerificationService
 *
 * Orchestrates Search Console site-ownership verification: the same-account
 * inheritance check, delegating the META-tag flow to the existing Site
 * Verification service, and reading a resolved property's own verification
 * status directly from data already fetched by SitesService.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class VerificationService {

	/** @var SiteVerification */
	protected $site_verification;

	/**
	 * VerificationService constructor.
	 *
	 * @param SiteVerification $site_verification
	 */
	public function __construct( SiteVerification $site_verification ) {
		$this->site_verification = $site_verification;
	}

	/**
	 * Resolve whether a matched or created property is a verified owner.
	 *
	 * Never assumes verification from selection or creation alone. Checks the
	 * property's own reported permission level first — the Sites API's most
	 * direct, per-property signal — before falling back to same-account
	 * inheritance from an existing Merchant Center verification.
	 *
	 * @param array $site_entry A `siteEntry` resource (`siteUrl`, `permissionLevel`).
	 *
	 * @return string SiteVerification::VERIFICATION_STATUS_VERIFIED or ::VERIFICATION_STATUS_UNVERIFIED.
	 */
	public function resolve_verification( array $site_entry ): string {
		$is_verified = $this->is_owner_verified( $site_entry ) || $this->site_verification->is_verified();

		return $is_verified
			? SiteVerification::VERIFICATION_STATUS_VERIFIED
			: SiteVerification::VERIFICATION_STATUS_UNVERIFIED;
	}

	/**
	 * Whether the Sites API itself already reports the connecting account as a
	 * verified owner of this specific property — distinct from, and checked
	 * before, the same-account Merchant Center inheritance fallback.
	 *
	 * Treats both `siteUnverifiedUser` and `siteRestrictedUser` as not verified.
	 * `SitesService` is expected to exclude `siteRestrictedUser` properties from
	 * matching entirely before they ever reach here, but this check doesn't rely
	 * on that — a restricted-access property is never a verified owner regardless
	 * of what filtering happened upstream.
	 *
	 * @param array $site_entry A `siteEntry` resource (`siteUrl`, `permissionLevel`).
	 *
	 * @return bool
	 */
	public function is_owner_verified( array $site_entry ): bool {
		$permission_level = $site_entry['permissionLevel'] ?? SitesService::PERMISSION_UNVERIFIED;

		return ! in_array( $permission_level, [ SitesService::PERMISSION_UNVERIFIED, SitesService::PERMISSION_RESTRICTED ], true );
	}

	/**
	 * Trigger the normal META-tag verification flow for a site.
	 *
	 * @param string $site_url
	 *
	 * @throws Exception When any step of the site verification process fails.
	 */
	public function verify( string $site_url ): void {
		$this->site_verification->verify_site( $site_url );
	}
}
