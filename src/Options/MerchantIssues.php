<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
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
	 * @var WP $wp The WP proxy object.
	 */
	protected $wp;

	/**
	 * Retrieve or initialize the mc_issues transient. Refresh if the issues have gone stale.
	 * Issue details are reduced, and for products, grouped by type.
	 * Issues can be filtered by type, searched by name or ID (if product type) and paginated.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param string|null $query To search by product title or ID
	 * @param int         $per_page The number of issues to return (0 for no limit)
	 * @param int         $page The page to start on (1-indexed).
	 *
	 * @return array The account- and product-level issues for the Merchant Center account.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function get( string $type = null, string $query = null, int $per_page = 0, int $page = 1 ): array {
		$issues = $this->container->get( TransientsInterface::class )->get( Transients::MC_ISSUES, null );

		if ( is_null( $issues ) ) {
			$issues = $this->refresh();
		}

		// Filter by issue type?
		if ( $type ) {
			if ( ! in_array( $type, $this->get_issue_types(), true ) ) {
				throw new Exception( 'Unknown filter type ' . $type );
			}
			$issues = array_filter(
				$issues,
				function( $i ) use ( $type ) {
					return $type === $i['type'];
				}
			);
		}

		// Search on product name?
		if ( $query ) {
			if ( self::TYPE_PRODUCT !== $type ) {
				throw new Exception( 'Search only enabled for product-level issues' );
			}
			$issues = array_filter(
				$issues,
				function( $i ) use ( $query ) {
					$match = stripos( $i['product'], $query ) !== false;
					if ( ! $match && is_numeric( $query ) ) {
						$match = preg_match( '/post=' . intval( $query ) . '(&|$)/', $i['edit_link'] );
					}
					return $match;
				}
			);
		}

		// Paginate the results?
		if ( $per_page > 0 ) {
			$issues = array_slice(
				$issues,
				$per_page * ( max( 1, $page ) - 1 ),
				$per_page
			);
		}

		return array_values( $issues );
	}

	/**
	 * Get a count of the number of issues for the Merchant Center account.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param string|null $query To search by product title or ID
	 *
	 * @return int The total number of issues.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function count( string $type = null, string $query = null ): int {
		return count( $this->get( $type, $query ) );
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
			$issues[] = $this->convert_issue( [ 'type' => self::TYPE_ACCOUNT ] + (array) $i->toSimpleObject() );
		}

		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );
		foreach ( $merchant->get_productstatuses() as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );

			// Skip products not synced by this extension.
			if ( ! $wc_product_id ) {
				continue;
			}

			$product_issue_template = [
				'type'                 => self::TYPE_PRODUCT,
				'productId'            => $product->getProductId(),
				'title'                => $product->getTitle(),
				'wc_product_id'        => $wc_product_id,
				'applicable_countries' => [],
			];
			foreach ( $product->getItemLevelIssues() as $item_level_issue ) {
				if ( 'merchant_action' === $item_level_issue->getResolution() ) {
					$code = md5( $wc_product_id . $item_level_issue->getCode() );

					if ( isset( $issues[ $code ] ) ) {
						$issues[ $code ]['applicable_countries'] = array_merge(
							$issues[ $code ]['applicable_countries'],
							$item_level_issue->getApplicableCountries()
						);
					} else {
						$issues[ $code ] = $this->convert_issue(
							$product_issue_template + (array) $item_level_issue->toSimpleObject()
						);
					}
				}
			}
		}

		// Avoid duplicate errors for the same product (if present for multiple geos).
		$issues = array_unique( array_values( $issues ), SORT_REGULAR );

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
	protected function get_issues_lifetime(): int {
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
				'code'        => $item['id'],
				'issue'       => $item['title'],
				'action'      => __( 'Read more about this account issue', 'google-listings-and-ads' ),
				'action_link' => $item['documentation'],
			];
		} else {
			if ( empty( $this->wp ) ) {
				$this->wp = $this->container->get( WP::class );
			}

			return [
				'type'                 => $item['type'],
				'product'              => $item['title'],
				'code'                 => $item['code'],
				'issue'                => $item['description'],
				'action'               => $item['detail'],
				'action_link'          => $item['documentation'],
				'edit_link'            => $this->wp->get_edit_post_link( $item['wc_product_id'], 'json' ),
				'applicable_countries' => $item['applicableCountries'],
			];
		}
	}
}
