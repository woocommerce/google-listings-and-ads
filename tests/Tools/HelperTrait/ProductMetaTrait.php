<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Trait ProductMeta
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait ProductMetaTrait {
	/**
	 * @return MockObject|ProductMetaHandler
	 */
	public function get_product_meta_handler_mock() {
		// Generate the list of magic methods and include them in the mock object
		$base_methods = [
			'update',
			'delete',
			'get',
		];

		$product_meta_class = new \ReflectionClass( ProductMetaHandler::class );
		$meta_keys          = array_keys( $product_meta_class->getConstant( 'TYPES' ) );

		$methods = [];
		foreach ( $meta_keys as $meta_key ) {
			$methods = array_merge(
				$methods,
				array_map(
					function ( $method ) use ( $meta_key ) {
						return "{$method}_{$meta_key}";
					},
					$base_methods
				)
			);
		}

		return $this->getMockBuilder( ProductMetaHandler::class )
					->setMethods( $methods )
					->disableOriginalConstructor()
					->disableOriginalClone()
					->disableArgumentCloning()
					->disallowMockingUnknownTypes()
					->getMock();
	}
}
