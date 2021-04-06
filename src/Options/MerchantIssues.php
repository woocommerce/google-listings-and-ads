<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Google_Service_ShoppingContent_ProductStatus as MC_Product_Status;
use Exception;

/**
 * Class MerchantIssues
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class MerchantIssues implements Service, ContainerAwareInterface {

	use ContainerAwareTrait;
	use PluginHelper;

	/**
	 * The time the statistics option should live.
	 */
	public const ISSUES_LIFETIME = HOUR_IN_SECONDS;

	/**
	 * The types of issues that can be filtered.
	 */
	public const TYPE_ACCOUNT = 'account';
	public const TYPE_PRODUCT = 'product';

	/**
	 * Retrieve or initialize the mc_issues transient. Refresh if the issues have gone stale.
	 *
	 * @param string|null $filter To filter by issue type if desired.
	 *
	 * @return array The account- and product-level issues for the Merchant Center account.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function get( string $filter = null ): array {
		$issues = $this->container->get( TransientsInterface::class )->get( Transients::MC_ISSUES, null );

		if ( is_null( $issues ) ) {
			$issues = $this->refresh();
		}

		if ( $filter ) {
			if ( ! in_array( $filter, $this->get_issue_types(), true ) ) {
				throw new Exception( 'Unknown filter type ' . $filter );
			}
			$issues = array_filter(
				$issues,
				function( $i ) use ( $filter ) {
					return $filter === $i['type'];
				}
			);
		}

		return array_map( [ $this, 'convert_issue' ], $issues );
	}

	/**
	 * Recalculate the account issues and update the DB transient.
	 *
	 * @returns array The recalculated account issues.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function refresh(): array {
		// Save a request if no MC account connected.
		if ( ! $this->container->get( OptionsInterface::class )->get_merchant_id() ) {
			throw new Exception( __( 'No Merchant Center account connected.', 'google-listings-and-ads' ) );
		}

		/** @var Merchant $merchant */
		$merchant = $this->container->get( Merchant::class );
		$issues   = [];
		foreach ( $merchant->get_accountstatus()->getAccountLevelIssues() as $i ) {
			$issues[] = [ 'type' => self::TYPE_ACCOUNT ] + (array) $i->toSimpleObject();
		}

		/** @var MC_Product_Status $product */
		foreach ( $merchant->get_productstatuses() as $product ) {
			$issue_template = [
				'type'      => self::TYPE_PRODUCT,
				'productId' => $product->getProductId(),
				'title'     => $product->getTitle(),
			];
			foreach ( $product->getItemLevelIssues() as $item_level_issue ) {
				if ( 'merchant_action' === $item_level_issue->getResolution() ) {
					$issues[] = $issue_template + (array) $item_level_issue->toSimpleObject();
				}
			}
		}

		// Update the cached values
		/** @var TransientsInterface $transients */
		$transients = $this->container->get( TransientsInterface::class );
		$transients->set( Transients::MC_ISSUES, $issues, $this->get_issues_lifetime() );

		return $issues;
	}

	/**
	 * Delete the cached statistics.
	 */
	public function delete(): void {
		$this->container->get( TransientsInterface::class )->delete( Transients::MC_ISSUES );
	}

	/**
	 * Allows a hook to modify the statistics lifetime.
	 *
	 * @return int
	 */
	private function get_issues_lifetime(): int {
		return apply_filters( 'woocommerce_gla_mc_issues_lifetime', self::ISSUES_LIFETIME );
	}

	/**
	 * Get valid issue types.
	 *
	 * @return string[] The valid issue types.
	 */
	public function get_issue_types(): array {
		return [
			self::TYPE_ACCOUNT,
			self::TYPE_PRODUCT,
		];
	}

	/**
	 * Standardize the issue data for display.
	 *
	 * @param array $item Array of data about an issue, as returned by Shopping API and/or saved in transient.
	 *
	 * @return array Standardized issue data.
	 */
	protected function convert_issue( array $item ): array {
		if ( $item['type'] === self::TYPE_ACCOUNT ) {
			return [
				'type'        => $item['type'],
				'product'     => __( 'All products', 'google-listings-and-ads' ),
				'issue'       => $item['title'],
				'action'      => __( 'Read more about this account issue', 'google-listings-and-ads' ),
				'action_link' => $item['documentation'],
			];
		} else {
			/** @var ProductHelper $product_helper */
			$product_helper = $this->container->get( ProductHelper::class );
			$product_id     = $product_helper->get_wc_product_id( $item['productId'] );
			return [
				'type'      => $item['type'],
				'product'   => $item['title'],
				'issue'     => $item['description'],
				'action'    => $item['detail'],
				'edit_link' => $product_id ? get_edit_post_link( $product_id ) : '',
			];
		}
	}
}
