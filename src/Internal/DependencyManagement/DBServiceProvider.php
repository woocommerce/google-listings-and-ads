<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migration20231109T1653383133;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\MigrationInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migration20211228T1640692399;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migration20220524T1653383133;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migration20240813T1653383133;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\MigrationVersion141;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migrator;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductMetaQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantPriceBenchmarksQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\AttributeMappingRulesTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\BudgetRecommendationTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantPriceBenchmarksTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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

	use ValidateInterface;

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [
		AttributeMappingRulesTable::class   => true,
		AttributeMappingRulesQuery::class   => true,
		ShippingRateTable::class            => true,
		ShippingRateQuery::class            => true,
		ShippingTimeTable::class            => true,
		ShippingTimeQuery::class            => true,
		BudgetRecommendationTable::class    => true,
		BudgetRecommendationQuery::class    => true,
		MerchantIssueTable::class           => true,
		MerchantIssueQuery::class           => true,
		MerchantPriceBenchmarksTable::class => true,
		MerchantPriceBenchmarksQuery::class => true,
		ProductFeedQueryHelper::class       => true,
		ProductMetaQueryHelper::class       => true,
		MigrationInterface::class           => true,
		Migrator::class                     => true,
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
	 * protected $this->container property or the `getContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->share_table_class( AttributeMappingRulesTable::class );
		$this->add_query_class( AttributeMappingRulesQuery::class, AttributeMappingRulesTable::class );
		$this->share_table_class( BudgetRecommendationTable::class );
		$this->add_query_class( BudgetRecommendationQuery::class, BudgetRecommendationTable::class );
		$this->share_table_class( ShippingRateTable::class );
		$this->add_query_class( ShippingRateQuery::class, ShippingRateTable::class );
		$this->share_table_class( ShippingTimeTable::class );
		$this->add_query_class( ShippingTimeQuery::class, ShippingTimeTable::class );
		$this->share_table_class( MerchantIssueTable::class );
		$this->add_query_class( MerchantIssueQuery::class, MerchantIssueTable::class );
		$this->share_table_class( MerchantPriceBenchmarksTable::class );
		$this->add_query_class( MerchantPriceBenchmarksQuery::class, MerchantPriceBenchmarksTable::class );

		$this->share_with_tags( ProductFeedQueryHelper::class, wpdb::class, ProductRepository::class );
		$this->share_with_tags( ProductMetaQueryHelper::class, wpdb::class );

		// Share DB migrations
		$this->share_migration( MigrationVersion141::class, MerchantIssueTable::class );
		$this->share_migration( Migration20211228T1640692399::class, ShippingRateTable::class, OptionsInterface::class );
		$this->share_with_tags( Migration20220524T1653383133::class, BudgetRecommendationTable::class );
		$this->share_migration( Migration20231109T1653383133::class, BudgetRecommendationTable::class );
		$this->share_migration( Migration20240813T1653383133::class, ShippingTimeTable::class );
		$this->share_with_tags( Migrator::class, MigrationInterface::class );
	}

	/**
	 * Add a query class.
	 *
	 * @param string $class_name
	 * @param mixed  ...$arguments
	 *
	 * @return DefinitionInterface
	 */
	protected function add_query_class( string $class_name, ...$arguments ): DefinitionInterface {
		return $this->add( $class_name, wpdb::class, ...$arguments )->addTag( 'db_query' );
	}

	/**
	 * Share a table class.
	 *
	 * Shared classes will always return the same instance of the class when the class is requested
	 * from the container.
	 *
	 * @param string $class_name   The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share_table_class( string $class_name, ...$arguments ): DefinitionInterface {
		return parent::share( $class_name, WP::class, wpdb::class, ...$arguments )->addTag( 'db_table' );
	}

	/**
	 * Share a migration class.
	 *
	 * @param string $class_name   The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @throws InvalidClass When the given class does not implement the MigrationInterface.
	 *
	 * @since 1.4.1
	 */
	protected function share_migration( string $class_name, ...$arguments ) {
		$this->validate_interface( $class_name, MigrationInterface::class );
		$this->share_with_tags(
			$class_name,
			wpdb::class,
			...$arguments
		);
	}
}
