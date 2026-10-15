<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountManagementApiClient
 *
 * {@see BusinessProfileApiClient} talking to the Business Profile Account Management API
 * (`accounts.list`). A distinct class from its siblings only so the container can tell apart
 * multiple registered base URLs for the same generic client shape -- Woo's real Connect Server
 * routes each Business Profile API family to its own host.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class AccountManagementApiClient extends BusinessProfileApiClient {

}
