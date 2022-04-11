<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework;

use Psr\Container\ContainerInterface;

/**
 * Class ContainerAwareUnitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework
 */
abstract class ContainerAwareUnitTest extends UnitTest {
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setUp(): void {
		parent::setUp();

		$this->container = woogle_get_container();
	}
}
