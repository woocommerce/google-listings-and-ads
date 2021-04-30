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
use DateTime;
use Exception;

/**
 * Class MerchantIssues
 *
 * ContainerAware used to access:
 * - MerchantIssueTable
 * - OptionsInterface
 * - ProductHelper
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
	 * @var DateTime $current_time For cache age operations.
	 */
	protected $current_time;

	/**
	 * MerchantIssues constructor.
	 *
	 * @param TransientsInterface $transients
	 * @param Merchant            $merchant
	 * @param MerchantIssueQuery  $issue_query
	 */
	public function __construct( TransientsInterface $transients, Merchant $merchant, MerchantIssueQuery $issue_query ) {
		$this->transients   = $transients;
		$this->merchant     = $merchant;
		$this->issue_query  = $issue_query;
		$this->current_time = new DateTime();
	}

	/**
	 * Retrieve the Merchant Center issues and total count. Refresh if the cache issues have gone stale.
	 * Issue details are reduced, and for products, grouped by type.
	 * Issues can be filtered by type, searched by name or ID (if product type) and paginated.
	 * Count takes into account the type filter, but not the pagination.
	 *
	 * @param string|null $type To filter by issue type if desired.
	 * @param int         $per_page The number of issues to return (0 for no limit).
	 * @param int         $page The page to start on (1-indexed).
	 *
	 * @return array With two indices, results (may be paged) and count (considers type).
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function get( string $type = null, int $per_page = 0, int $page = 1 ): array {
		if ( null === $this->transients->get( TransientsInterface::MC_ISSUES_CREATED_AT ) ) {
			$this->refresh_cache();
		}
		return $this->fetch_issue_data( $type, $per_page, $page );
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
	protected function fetch_issue_data( string $type = null, int $per_page = 0, int $page = 1 ): array {
		// Ensure account issues are shown first.
		$this->issue_query->set_order( 'product_id' );
		$this->issue_query->set_order( 'issue' );

		// Filter by type.
		if ( $this->is_valid_type( $type ) ) {
			$this->issue_query->where(
				'product_id',
				0,
				self::TYPE_ACCOUNT === $type ? '=' : '>'
			);
		}

		// Result pagination.
		if ( $per_page > 0 ) {
			$this->issue_query->set_limit( $per_page );
			$this->issue_query->set_offset( $per_page * ( $page - 1 ) );
		}

		$issues = [];
		foreach ( $this->issue_query->get_results() as $row ) {
			$issue = [
				'type'       => $row['product_id'] ? self::TYPE_PRODUCT : self::TYPE_ACCOUNT,
				'product_id' => intval( $row['product_id'] ),
				'product'    => $row['product'],
				'issue'      => $row['issue'],
				'code'       => $row['code'],
				'action'     => $row['action'],
				'action_url' => $row['action_url'],
			];
			if ( $issue['product_id'] ) {
				$issue['applicable_countries'] = json_decode( $row['applicable_countries'], true );
			} else {
				unset( $issue['product_id'] );
			}
			$issues[] = $issue;
		}

		return [
			'results' => array_values( $issues ),
			'count'   => $this->issue_query->get_count(),
		];
	}

	/**
	 * Recalculate the account issues and update the DB transient.
	 *
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	protected function refresh_cache(): void {
		// Save a request if no MC account connected.
		if ( ! $this->container->get( OptionsInterface::class )->get_merchant_id() ) {
			throw new Exception( __( 'No Merchant Center account connected.', 'google-listings-and-ads' ) );
		}

		$this->refresh_account_issues();
		$this->container->get( MerchantProducts::class )->refresh_stats_and_issues();

		$delete_before = clone $this->current_time;
		$delete_before->modify( '-' . $this->get_issues_lifetime() . ' seconds' );
		$this->container->get( MerchantIssueTable::class )->delete_stale( $delete_before );

		$this->transients->set(
			TransientsInterface::MC_ISSUES_CREATED_AT,
			$this->current_time->getTimestamp(),
			$this->get_issues_lifetime()
		);
	}

	/**
	 * Retrieve all account-level issues and store them in the database.
	 *
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	private function refresh_account_issues(): void {
		$account_issues = [];
		foreach ( $this->merchant->get_accountstatus()->getAccountLevelIssues() as $issue ) {
			$account_issues[] = [
				'product_id' => 0,
				'product'    => __( 'All products', 'google-listings-and-ads' ),
				'code'       => $issue->getId(),
				'issue'      => $issue->getTitle(),
				'action'     => __( 'Read more about this account issue', 'google-listings-and-ads' ),
				'action_url' => $issue->getDocumentation(),
				'created_at' => $this->current_time->format( 'Y-m-d H:i:s' ),
			];
		}

		$this->issue_query->update_or_insert( $account_issues );
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
