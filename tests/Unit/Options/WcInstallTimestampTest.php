<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\WcInstallTimestamp;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class WcInstallTimestampTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class WcInstallTimestampTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var WcInstallTimestamp $service */
	protected $service;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->service = new WcInstallTimestamp();
		$this->service->set_options_object( $this->options );
	}

	public function test_records_install_timestamp_once_on_woocommerce_installed() {
		$this->options->expects( $this->once() )
			->method( 'add' )
			->with(
				OptionsInterface::WC_INSTALL_TIMESTAMP,
				$this->isType( 'int' )
			);

		$this->service->register();
		do_action( 'woocommerce_installed' );
	}
}
