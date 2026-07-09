<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiIssueResolutionService
 *
 * Wraps the Merchant API Issue Resolution Service. renderaccountissues returns the
 * account-level issues with their resolution content and actions; triggeraction
 * completes a built-in user-input action in-app (for example, requesting an account
 * review).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiIssueResolutionService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** Render user-input actions as in-app built-in actions (triggerable via triggeraction). */
	public const USER_INPUT_BUILT_IN = 'BUILT_IN_USER_INPUT_ACTIONS';

	/** Render user-input actions as redirects to Merchant Center. */
	public const USER_INPUT_REDIRECT = 'REDIRECT_TO_MERCHANT_CENTER';

	/** Return issue content as pre-rendered HTML. */
	public const CONTENT_PRE_RENDERED_HTML = 'PRE_RENDERED_HTML';

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiIssueResolutionService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Render the account-level issues, their content and actions.
	 *
	 * @param string $user_input_action_option How user-input actions are rendered (in-app vs redirect).
	 * @param string $language_code            Optional language for the rendered content.
	 *
	 * @return array RenderAccountIssuesResponse decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function render_account_issues(
		string $user_input_action_option = self::USER_INPUT_BUILT_IN,
		string $language_code = ''
	): array {
		return $this->client->post(
			$this->build_path( ':renderaccountissues', $language_code ),
			[
				'userInputActionOption' => $user_input_action_option,
				'contentOption'         => self::CONTENT_PRE_RENDERED_HTML,
			]
		);
	}

	/**
	 * Trigger a built-in user-input action in-app (allowlist-restricted).
	 *
	 * @param string $action_context Opaque token identifying the action, taken from a rendered action.
	 * @param string $action_flow_id The action flow id to trigger.
	 * @param array  $input_values   Optional input values for the action flow.
	 * @param string $language_code  Optional language for the response.
	 *
	 * @return array TriggerActionResponse decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function trigger_action( string $action_context, string $action_flow_id, array $input_values = [], string $language_code = '' ): array {
		return $this->client->post(
			$this->build_path( ':triggeraction', $language_code ),
			[
				'actionContext' => $action_context,
				'actionInput'   => [
					'actionFlowId' => $action_flow_id,
					'inputValues'  => $input_values,
				],
			]
		);
	}

	/**
	 * Build the issue-resolution resource path for the connected account.
	 *
	 * @param string $method        The custom method suffix (for example ':renderaccountissues').
	 * @param string $language_code Optional language appended as a query parameter.
	 *
	 * @return string
	 */
	protected function build_path( string $method, string $language_code = '' ): string {
		$path = sprintf(
			'%s/accounts/%s%s',
			MapiPaths::ISSUE_RESOLUTION,
			$this->options->get_merchant_id(),
			$method
		);

		if ( '' !== $language_code ) {
			$path .= '?languageCode=' . rawurlencode( $language_code );
		}

		return $path;
	}
}
