<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Covers Query::reset_results() against a real table, using ShippingRateQuery as the
 * concrete subject since Query is abstract.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB
 */
class QueryTest extends UnitTest {

	/** @var ShippingRateQuery */
	protected $query;

	public function setUp(): void {
		global $wpdb;

		parent::setUp();

		$table = new ShippingRateTable( new WP(), $wpdb );
		$table->install();

		$this->query = new ShippingRateQuery( $wpdb, $table );
		$this->query->delete( 'country', 'US' );
		$this->query->delete( 'country', 'GB' );
		$this->query->reset_results();
	}

	public function test_reset_results_makes_the_next_read_see_rows_written_since(): void {
		$this->query->insert(
			[
				'country'  => 'US',
				'currency' => 'USD',
				'rate'     => '5.00',
				'options'  => [],
			]
		);

		$this->assertCount( 1, $this->query->get_results() );

		$this->query->insert(
			[
				'country'  => 'GB',
				'currency' => 'GBP',
				'rate'     => '9.99',
				'options'  => [],
			]
		);

		$this->query->reset_results();

		$this->assertCount( 2, $this->query->get_results() );
	}

	public function test_reset_results_also_discards_the_memoized_count(): void {
		$this->query->insert(
			[
				'country'  => 'US',
				'currency' => 'USD',
				'rate'     => '5.00',
				'options'  => [],
			]
		);

		$this->assertSame( 1, $this->query->get_count() );

		$this->query->insert(
			[
				'country'  => 'GB',
				'currency' => 'GBP',
				'rate'     => '9.99',
				'options'  => [],
			]
		);

		$this->query->reset_results();

		$this->assertSame( 2, $this->query->get_count() );
	}
}
