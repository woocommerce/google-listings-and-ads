<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
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
	 * @var TransientsInterface $transients
	 */
	protected $transients;

	/**
	 * @var Merchant $merchant
	 */
	protected $merchant;

	/**
	 * @var MerchantIssueQuery $issue_query
	 */
	protected $issue_query;

	/**
	 * MerchantIssues constructor.
	 *
	 * @param TransientsInterface $transients
	 * @param Merchant            $merchant
	 * @param MerchantIssueQuery  $issue_query
	 */
	public function __construct( TransientsInterface $transients, Merchant $merchant, MerchantIssueQuery $issue_query ) {
		$this->transients  = $transients;
		$this->merchant    = $merchant;
		$this->issue_query = $issue_query;
	}

	/**
	 * Get a count of the number of issues for the Merchant Center account.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 *
	 * @return int The total number of issues.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function count( string $type = null ): int {
		return count( $this->get( $type ) );
	}

	/**
	 * Retrieve or initialize the mc_issues transient. Refresh if the issues have gone stale.
	 * Issue details are reduced, and for products, grouped by type.
	 * Issues can be filtered by type, searched by name or ID (if product type) and paginated.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param int         $per_page The number of issues to return (0 for no limit).
	 * @param int         $page The page to start on (1-indexed).
	 *
	 * @return array The account- and product-level issues for the Merchant Center account.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function get( string $type = null, int $per_page = 0, int $page = 1 ): array {
		$issues = $this->fetch_cached_issues( $type, $per_page, $page );

		if ( is_null( $issues ) ) {
			$issues = $this->refresh_cache();

			// Filter by issue type.
			if ( $type ) {
				if ( ! $this->is_valid_type( $type ) ) {
					throw new Exception( 'Unknown filter type ' . $type );
				}
				$issues = array_filter(
					$issues,
					function( $i ) use ( $type ) {
						return $type === $i['type'];
					}
				);
			}

			// Paginate the results.
			if ( $per_page > 0 ) {
				$issues = array_slice(
					$issues,
					$per_page * ( max( 1, $page ) - 1 ),
					$per_page
				);
			}
		}

		return array_values( $issues );
	}

	/**
	 * Fetch the cached issues from the database, after using the associated transient to determine whether they're
	 * still valid or not.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param int         $per_page The number of issues to return (0 for no limit).
	 * @param int         $page The page to start on (1-indexed).
	 *
	 * @return array|null
	 */
	protected function fetch_cached_issues( string $type = null, int $per_page = 0, int $page = 1 ): ?array {
		if ( null === $this->transients->get( TransientsInterface::MC_ISSUES_CREATED_AT ) ) {
			return null;
		}

		// Filter in query.
		if ( $this->is_valid_type( $type ) ) {
			$this->issue_query->where(
				'product_id',
				0,
				self::TYPE_ACCOUNT === $type ? '=' : '>'
			);
		}

		// Pagination in query.
		if ( $per_page > 0 ) {
			$this->issue_query->set_limit( $per_page );
			$this->issue_query->set_offset( $per_page * ( $page - 1 ) );
		}

		$issues = [];
		foreach ( $this->issue_query->get_results() as $row ) {
			$details = json_decode( $row['details'], true );
			$issue   = [
				'type'        => $row['product_id'] ? self::TYPE_PRODUCT : self::TYPE_ACCOUNT,
				'product_id'  => intval( $row['product_id'] ),
				'product'     => $details['product'],
				'issue'       => $row['issue'],
				'code'        => $row['code'],
				'action'      => $details['action'],
				'action_link' => $details['action_link'],
			];
			if ( $issue['product_id'] ) {
				$issue['applicable_countries'] = json_decode( $row['applicable_countries'], true );
			} else {
				unset( $issue['product_id'] );
			}
			array_push( $issues, $issue );
		}

		return $issues;
	}

	/**
	 * Recalculate the account issues and update the DB transient.
	 *
	 * @returns array The recalculated account issues.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	protected function refresh_cache(): array {
		// Save a request if no MC account connected.
		if ( ! $this->container->get( OptionsInterface::class )->get_merchant_id() ) {
			throw new Exception( __( 'No Merchant Center account connected.', 'google-listings-and-ads' ) );
		}

		$issues = array_merge( $this->retrieve_account_issues(), $this->retrieve_product_issues() );

		// Refresh the cache and the validation transient.
		$this->update_database( $issues );
		$this->transients->set( TransientsInterface::MC_ISSUES_CREATED_AT, time(), $this->get_issues_lifetime() );
		return $issues;
	}

	/**
	 * Retrieve and prepare all account-level issues for the Merchant Center account.
	 *
	 * @return array Account-level issues.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	private function retrieve_account_issues(): array {
		$account_issues = [];
		foreach ( $this->merchant->get_accountstatus()->getAccountLevelIssues() as $issue ) {
			$account_issues[] = [
				'type'        => self::TYPE_ACCOUNT,
				'product'     => __( 'All products', 'google-listings-and-ads' ),
				'code'        => $issue->getId(),
				'issue'       => $issue->getTitle(),
				'action'      => __( 'Read more about this account issue', 'google-listings-and-ads' ),
				'action_link' => $issue->getDocumentation(),
			];
		}
		return $account_issues;
	}

	/**
	 * Retrieve and prepare all product-level issues for the Merchant Center account.
	 *
	 * @return array Unique product issues sorted by product id.
	 */
	private function retrieve_product_issues(): array {
		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		$product_issues = [];
		foreach ( $this->merchant->get_productstatuses() as $product ) {
			$wc_product_id = $product_helper->get_wc_product_id( $product->getProductId() );

			// Skip products not synced by this extension.
			if ( ! $wc_product_id ) {
				continue;
			}

			$product_issue_template = [
				'type'       => self::TYPE_PRODUCT,
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
						'action_link'          => $item_level_issue->getDocumentation(),
						'applicable_countries' => $item_level_issue->getApplicableCountries(),
					];
				}
			}
		}

		// Product issue cleanup (sorting and unique), plus alphabetize applicable_countries..
		ksort( $product_issues );
		$product_issues = array_unique( array_values( $product_issues ), SORT_REGULAR );

		return array_map(
			function( $issue ) {
				sort( $issue['applicable_countries'] );
				return $issue;
			},
			$product_issues
		);
	}

	/**
	 * Store issues in the database.
	 *
	 * @param array $issues Prepared Merchant Center issues.
	 */
	protected function update_database( array $issues ): void {
		$this->container->get( MerchantIssueTable::class )->truncate();

		foreach ( $issues as $i ) {
			$this->issue_query->insert(
				[
					'product_id'           => $i['product_id'] ?? 0,
					'code'                 => $i['code'],
					'issue'                => $i['issue'],
					'applicable_countries' => isset( $i['applicable_countries'] ) ? json_encode( $i['applicable_countries'] ) : '',
					'details'              => json_encode(
						[
							'product'     => $i['product'],
							'action'      => $i['action'],
							'action_link' => $i['action_link'],
						]
					),
				]
			);
		}
	}

	/**
	 * Delete the cached statistics.
	 */
	public function delete(): void {
		$this->transients->delete( TransientsInterface::MC_ISSUES_CREATED_AT );
		$this->container->get( MerchantIssueTable::class )->truncate();
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
	 * @param string|null $type
	 *
	 * @return bool
	 */
	public function is_valid_type( ?string $type ): bool {
		return in_array( $type, $this->get_issue_types(), true );
	}
}
