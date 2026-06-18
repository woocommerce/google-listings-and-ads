<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\WcInstallTimestamp;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
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

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var WcInstallTimestamp $service */
	protected $service;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->wp      = $this->createMock( WP::class );
		$this->service = new WcInstallTimestamp();
		$this->service->set_options_object( $this->options );
		$this->service->set_wp_proxy_object( $this->wp );
	}

	public function test_records_install_timestamp_once_on_woocommerce_installed() {
		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->wp->method( 'get_option' )
			->with( 'woocommerce_admin_install_timestamp' )
			->willReturn( false );

		$this->options->expects( $this->once() )
			->method( 'add' )
			->with(
				OptionsInterface::WC_INSTALL_TIMESTAMP,
				$this->isType( 'int' )
			);

		$this->service->register();
		do_action( 'woocommerce_installed' );
	}

	public function test_backfills_wc_install_timestamp_from_woocommerce_core_on_register() {
		$wc_admin_timestamp = time() - ( 120 * DAY_IN_SECONDS );

		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->wp->method( 'get_option' )
			->with( 'woocommerce_admin_install_timestamp' )
			->willReturn( $wc_admin_timestamp );

		$this->options->expects( $this->once() )
			->method( 'add' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP, $wc_admin_timestamp );

		$this->service->register();
	}

	public function test_does_not_backfill_when_wc_install_timestamp_already_exists() {
		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( time() - DAY_IN_SECONDS );

		$this->wp->expects( $this->never() )->method( 'get_option' );
		$this->options->expects( $this->never() )->method( 'add' );

		$this->service->register();
	}

	public function test_does_not_backfill_when_woocommerce_core_timestamp_is_missing() {
		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->wp->method( 'get_option' )
			->with( 'woocommerce_admin_install_timestamp' )
			->willReturn( false );

		$this->options->expects( $this->never() )->method( 'add' );

		$this->service->register();
	}
}
