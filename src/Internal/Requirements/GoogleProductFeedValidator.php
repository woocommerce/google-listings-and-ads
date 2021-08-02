<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExtensionRequirementException;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleProductFeedValidator
 *
 * @since 1.2.0
 *
 * @package AutomatticWooCommerceGoogleListingsAndAdsInternalRequirements
 */
class GoogleProductFeedValidator extends RequirementValidator {

	use PluginHelper;

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
				'woocommerce_gla_custom_merchant_issues',
				function( $issues, $current_time ) {
					return $this->add_conflict_issue( $issues, $current_time );
				},
				10,
				2
			);

			add_action(
				'deactivated_plugin',
				function( $plugin ) {
					if ( 'woocommerce-product-feeds/woocommerce-gpf.php' === $plugin ) {
						/** @var MerchantStatuses $merchant_statuses */
						$merchant_statuses = woogle_get_container()->get( MerchantStatuses::class );
						if ( $merchant_statuses instanceof MerchantStatuses ) {
							$merchant_statuses->clear_cache();
						}
					}
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

	/**
	 * Add an account-level issue regarding the plugin conflict
	 * to the array of issues to be saved in the database.
	 *
	 * @param array    $issues The current array of account-level issues
	 * @param DateTime $cache_created_time The time of the cache/issues generation.
	 *
	 * @return array The issues with the new conflict issue included
	 */
	protected function add_conflict_issue( array $issues, DateTime $cache_created_time ): array {
		$issues[] = [
			'product_id' => 0,
			'product'    => 'All products',
			'code'       => 'incompatible_google_product_feed',
			'issue'      => __( 'The Google Product Feed plugin may cause conflicts or unexpected results.', 'google-listings-and-ads' ),
			'action'     => __( 'Deactivate the Google Product Feed plugin from your store', 'google-listings-and-ads' ),
			'action_url' => 'https://developers.google.com/shopping-content/guides/best-practices#do-not-use-api-and-feeds',
			'created_at' => $cache_created_time->format( 'Y-m-d H:i:s' ),
			'type'       => MerchantStatuses::TYPE_ACCOUNT,
			'severity'   => 'error',
			'source'     => 'filter',
		];

		return $issues;
	}
}
