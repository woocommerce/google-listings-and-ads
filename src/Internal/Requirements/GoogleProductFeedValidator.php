<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExtensionRequirementException;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleProductFeedValidator
 *
 * @package AutomatticWooCommerceGoogleListingsAndAdsInternalRequirements
 */
class GoogleProductFeedValidator extends RequirementValidator {

	/**
	 * Validate all requirements for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_google_product_feed_inactive();
		} catch ( ExtensionRequirementException $e ) {
			add_filter(
				'woocommerce_gla_account_issues',
				function( $arr ) {
					foreach ( $arr as &$issue ) {
						// Make sure all issues have the source attribute to avoid erros.
						if ( ! empty( $issue['source'] ) ) {
							continue;
						}
						$issue['source'] = 'mc';
					}

					array_push(
						$arr,
						[
							'product_id' => '0',
							'product'    => 'All products',
							'code'       => 'incompatible_google_product_feed',
							'issue'      => 'The Google Product Feed plugin may cause conflicts or unexpected results.',
							'action'     => 'Delete or deactivate the Google Product Feed plugin from your store',
							'action_url' => 'https://developers.google.com/shopping-content/guides/best-practices#do-not-use-api-and-feeds',
							'created_at' => date_format( date_create(), 'Y-m-d H:i:s' ),
							'type'       => MerchantStatuses::TYPE_ACCOUNT,
							'severity'   => 'error',
							'source'     => 'filter',
						]
					);
					return $arr;
				}
			);
		}
		return true;
	}

	/**
	 * Validate that Google Product Feed isn't enabled.
	 *
	 * @throws ExtensionRequirementException When Google Product Feed is active.
	 */
	protected function validate_google_product_feed_inactive() {
		if ( defined( 'WOOCOMMERCE_GPF_VERSION' ) ) {
			throw ExtensionRequirementException::incompatible_plugin( 'Google Product Feed' );
		}
	}
}
