<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WC_Product;
use WP_UnitTestCase;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchProductHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *
 * @property MockObject|ProductMetaHandler $product_meta
 * @property MockObject|ProductHelper      $product_helper
 * @property MockObject|ValidatorInterface $validator
 * @property MockObject|ProductFactory     $product_factory
 * @property MockObject|BatchProductHelper $batch_product_helper
 */
class BatchProductHelperTest extends WP_UnitTestCase {
	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_filter_synced_products_all_synced( $products ) {
		$this->product_helper->expects( $this->any() )
							 ->method( 'is_product_synced' )
							 ->willReturn( true );
		$results = $this->batch_product_helper->filter_synced_products( $products );
		$this->assertCount( 3, $results );
		$this->assertEqualSets( $products, $results );
	}

	/**
	 * @dataProvider return_blank_test_products
	 */
	public function test_filter_synced_products_none_synced( $products ) {
		$this->product_helper->expects( $this->any() )
							 ->method( 'is_product_synced' )
							 ->willReturn( false );
		$results = $this->batch_product_helper->filter_synced_products( $products );
		$this->assertEmpty( $results );
	}

	/**
	 * @return WC_Product[][]
	 */
	public function return_blank_test_products(): array {
		$products = [
			new WC_Product(),
			new WC_Product(),
			new WC_Product(),
		];

		return [
			[ $products ],
		];
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->product_meta         = $this->createMock( ProductMetaHandler::class );
		$this->product_helper       = $this->createMock( ProductHelper::class );
		$this->validator            = $this->createMock( ValidatorInterface::class );
		$this->product_factory      = $this->createMock( ProductFactory::class );
		$this->batch_product_helper = new BatchProductHelper( $this->product_meta, $this->product_helper, $this->validator, $this->product_factory );
	}
}
