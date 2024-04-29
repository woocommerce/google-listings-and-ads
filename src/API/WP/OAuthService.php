<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WP;

use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities as UtilitiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuthService
 * This class implements a service to handle WordPress.com OAuth.
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WP
 */
class OAuthService implements Service, OptionsAwareInterface {

	use OptionsAwareTrait;
	use UtilitiesTrait;

	public const AUTH_URL      = 'https://public-api.wordpress.com/oauth2/authorize';
	public const RESPONSE_TYPE = 'code';
	public const SCOPE         = 'wc-partner-access';

	public const STATUS_APPROVED    = 'approved';
	public const STATUS_DISAPPROVED = 'disapproved';
	public const STATUS_ERROR       = 'error';

	public const ALLOWED_STATUSES = [
		self::STATUS_APPROVED,
		self::STATUS_DISAPPROVED,
		self::STATUS_ERROR,
	];

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
	 * state=URL_SAFE_BASE64_ENCODED_STRING
	 *
	 * State is an URL safe base64 encoded string.
	 * E.g.
	 * state=bm9uY2UtMTIzJnJlZGlyZWN0X3VybD1odHRwcyUzQSUyRiUyRm1lcmNoYW50LXNpdGUuZXhhbXBsZS5jb20lMkZ3cC1hZG1pbiUyRmFkbWluLnBocCUzRnBhZ2UlM0R3Yy1hZG1pbiUyNnBhdGglM0QlMkZnb29nbGUlMkZzZXR1cC1tYw
	 *
	 * The decoded content of state is an URL query string where the value of its parameter "redirect_url" is being URL encoded.
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
	 * @return array{client_id: string, redirect_uri: string, nonce: string} An associative array contains required information that is retrived from Google.
	 * client_id:    Google's WPCOM app client ID, will be used to form the authorization URL.
	 * redirect_uri: A Google's URL that will be redirected to when the merchant approve the app access. Note that it needs to be matched with the Google WPCOM app client settings.
	 * nonce:        A string returned by Google that we will put it in the auth URL and the redirect_uri. Google will use it to verify the call.
	 */
	protected function get_data_from_google(): array {
		$nonce = 'nonce-123';
		$this->options->update( OptionsInterface::GOOGLE_WPCOM_AUTH_NONCE, $nonce );
		return [
			'client_id'    => '91299',
			'redirect_uri' => 'https://woo.com',
			'nonce'        => $nonce,
		];
	}
}
