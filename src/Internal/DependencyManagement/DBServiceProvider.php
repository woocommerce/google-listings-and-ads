<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\BudgetRecommendationTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\DefinitionInterface;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class DBServiceProvider extends AbstractServiceProvider {

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [
		ShippingRateTable::class         => true,
		ShippingRateQuery::class         => true,
		ShippingTimeTable::class         => true,
		ShippingTimeQuery::class         => true,
		BudgetRecommendationTable::class => true,
		BudgetRecommendationQuery::class => true,
		MerchantIssueTable::class        => true,
		MerchantIssueQuery::class        => true,
		ProductFeedQueryHelper::class    => true,
	];

	/**
	 * Returns a boolean if checking whether this provider provides a specific
	 * service or returns an array of provided services if no argument passed.
	 *
	 * @param string $service
	 *
	 * @return boolean
	 */
	public function provides( string $service ): bool {
		return 'db_table' === $service || 'db_query' === $service || parent::provides( $service );
	}

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->share_table_class( BudgetRecommendationTable::class );
		$this->add_query_class( BudgetRecommendationQuery::class, BudgetRecommendationTable::class );
		$this->share_table_class( ShippingRateTable::class );
		$this->add_query_class( ShippingRateQuery::class, ShippingRateTable::class );
		$this->share_table_class( ShippingTimeTable::class );
		$this->add_query_class( ShippingTimeQuery::class, ShippingTimeTable::class );
		$this->share_table_class( MerchantIssueTable::class );
		$this->add_query_class( MerchantIssueQuery::class, MerchantIssueTable::class );

		$this->share_with_tags( ProductFeedQueryHelper::class, wpdb::class, ProductRepository::class );
	}

	/**
	 * Add a query class.
	 *
	 * @param string $class
	 * @param mixed  ...$arguments
	 *
	 * @return DefinitionInterface
	 */
	protected function add_query_class( string $class, ...$arguments ): DefinitionInterface {
		return $this->add( $class, wpdb::class, ...$arguments )->addTag( 'db_query' );
	}

	/**
	 * Share a table class.
	 *
	 * Shared classes will always return the same instance of the class when the class is requested
	 * from the container.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share_table_class( string $class, ...$arguments ): DefinitionInterface {
		return parent::share( $class, WP::class, wpdb::class, ...$arguments )->addTag( 'db_table' );
	}
}
