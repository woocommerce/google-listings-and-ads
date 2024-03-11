<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WP;

use Automattic\Jetpack\Connection\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities as UtilitiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuthService
 * This class implements a service to handle WordPress.com OAuth.
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WP
 */
class OAuthService implements Service {

	use UtilitiesTrait;

	public const AUTH_URL      = 'https://public-api.wordpress.com/oauth2/authorize';
	public const RESPONSE_TYPE = 'code';
	public const SCOPE         = 'wc-partner-access';

	/**
	 * Returns WordPress.com OAuth authorization URL.
	 * https://developer.wordpress.com/docs/oauth2/
	 *
	 * The full auth URL example:
	 *
	 * https://public-api.wordpress.com/oauth2/authorize?
	 * client_id=CLIENT_ID&
	 * redirect_uri=PARTNER_REDIRECT_URL&
	 * response_type=code&
	 * blog=BLOD_ID&
	 * scope=wc-partner-access&
	 * state=BASE64_ENCODED_STRING
	 *
	 * Content of state is an URL query string where the value of its parameter "redirect_url" is being URL encoded.
	 * E.g.
	 * nonce=nonce-123&redirect_url=https%3A%2F%2Fmerchant-site.example.com%2Fwp-admin%2Fadmin.php%3Fpage%3Dwc-admin%26path%3D%2Fgoogle%2Fsetup-mc
	 *
	 * where its URL decoded version is:
	 * nonce=nonce-123&redirect_url=https://merchant-site.example.com/wp-admin/admin.php?page=wc-admin&path=/google/setup-mc
	 *
	 * @param string $path An URL parameter for the path within GL&A page, which will be added in the merchant redirect URL.
	 *
	 * @return string Auth URL.
	 */
	public function get_auth_url( string $path ): string {
		$google_data = $this->get_data_from_google();

		$merchant_redirect_url = urlencode_deep( admin_url( "admin.php?page=wc-admin&path={$path}" ) );

		$state = $this->base64url_encode(
			build_query(
				[
					'nonce'        => $google_data['nonce'],
					'redirect_url' => $merchant_redirect_url,
				]
			)
		);

		$auth_url = esc_url_raw(
			add_query_arg(
				[
					'blog'          => Jetpack_Options::get_option( 'id' ),
					'client_id'     => $google_data['client_id'],
					'redirect_uri'  => $google_data['redirect_uri'],
					'response_type' => self::RESPONSE_TYPE,
					'scope'         => self::SCOPE,
					'state'         => $state,
				],
				self::AUTH_URL
			)
		);

		return $auth_url;
	}

	/**
	 * Calls an API by Google to get required information in order to form an auth URL.
	 *
	 * TODO: Call an actual API by Google.
	 * We'd probably need use WCS to communicate with the new API.
	 *
	 * @return array An associative array contains required information from Google.
	 */
	protected function get_data_from_google(): array {
		return [
			'client_id'    => '91299',
			'redirect_uri' => 'https://woo.com',
			'nonce'        => 'nonce-123',
		];
	}
}
