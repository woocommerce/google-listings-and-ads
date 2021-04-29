<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use DateTime;
use Exception;

/**
 * Class MerchantProducts
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class MerchantProducts implements Service, ContainerAwareInterface {

	use ContainerAwareTrait;
	/**
	 * @var Merchant $merchant
	 */
	protected $merchant;

	/**
	 * @var MerchantIssueQuery $issue_query
	 */
	protected $issue_query;

	/**
	 * @var DateTime $current_time For cache age operations.
	 */
	protected $current_time;

	/**
	 * MerchantProducts constructor.
	 *
	 * @param Merchant            $merchant
	 * @param MerchantIssueQuery  $issue_query
	 */
	public function __construct( Merchant $merchant, MerchantIssueQuery $issue_query ) {
		$this->merchant     = $merchant;
		$this->issue_query  = $issue_query;
		$this->current_time = new DateTime();
	}

	/**
	 * Retrieve all product-level issues and store them in the database.
	 */
	public function refresh_product_issues(): void {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		$product_issues   = [];
		$product_statuses = [];
		$created_at       = $this->current_time->format( 'Y-m-d H:i:s' );
		foreach ( $this->merchant->get_productstatuses() as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );

			// Skip products not synced by this extension.
			if ( ! $wc_product_id ) {
				continue;
			}

			$product_issue_template = [
				'product'    => $product->getTitle(),
				'product_id' => $wc_product_id,
			];
			foreach ( $product->getItemLevelIssues() as $item_level_issue ) {
				if ( 'merchant_action' !== $item_level_issue->getResolution() ) {
					continue;
				}
				$code = $wc_product_id . '__' . md5( $item_level_issue->getDescription() );

				if ( isset( $product_issues[ $code ] ) ) {
					$product_issues[ $code ]['applicable_countries'] = array_merge(
						$product_issues[ $code ]['applicable_countries'],
						$item_level_issue->getApplicableCountries()
					);
				} else {
					$product_issues[ $code ] = $product_issue_template + [
						'code'                 => $item_level_issue->getCode(),
						'issue'                => $item_level_issue->getDescription(),
						'action'               => $item_level_issue->getDetail(),
						'action_url'           => $item_level_issue->getDocumentation(),
						'applicable_countries' => $item_level_issue->getApplicableCountries(),
						'created_at'           => $created_at,
					];
				}
			}
		}

		// Product issue cleanup: sorting (by product ID) and sort applicable countries.
		ksort( $product_issues );
		$product_issues = array_map(
			function( $issue ) {
				sort( $issue['applicable_countries'] );
				$issue['applicable_countries'] = json_encode( $issue['applicable_countries'] );
				return $issue;
			},
			$product_issues
		);

		$this->issue_query->update_or_insert( array_values( $product_issues ) );
	}
}
