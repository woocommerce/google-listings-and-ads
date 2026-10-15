<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

defined( 'ABSPATH' ) || exit;

/**
 * Class VerificationsApiClient
 *
 * {@see BusinessProfileApiClient} talking to the Business Profile Verifications API
 * (`locations.getVoiceOfMerchantState`). A distinct class from its siblings only so the container
 * can tell apart multiple registered base URLs for the same generic client shape -- Woo's real
 * Connect Server routes each Business Profile API family to its own host. No current caller: the
 * eligibility-check feature that calls `getVoiceOfMerchantState` isn't built yet, so this client
 * exists to carry the confirmed real base URL ready for that work.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class VerificationsApiClient extends BusinessProfileApiClient {

}
