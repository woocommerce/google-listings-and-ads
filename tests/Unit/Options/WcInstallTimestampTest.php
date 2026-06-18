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
		$this->service = new WcInstallTimestamp( $this->wp );
		$this->service->set_options_object( $this->options );
	}

	public function test_records_wc_install_timestamp_from_woocommerce_option_on_admin_init() {
		$wc_install_timestamp = time() - ( 120 * DAY_IN_SECONDS );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->wp->expects( $this->once() )
			->method( 'get_option' )
			->with( 'woocommerce_admin_install_timestamp' )
			->willReturn( (string) $wc_install_timestamp );

		$this->options->expects( $this->once() )
			->method( 'add' )
			->with(
				OptionsInterface::WC_INSTALL_TIMESTAMP,
				$wc_install_timestamp
			);

		$this->service->register();
		do_action( 'admin_init' );
	}

	public function test_does_not_record_when_gla_option_already_exists() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( time() - ( 120 * DAY_IN_SECONDS ) );

		$this->wp->expects( $this->never() )
			->method( 'get_option' );

		$this->options->expects( $this->never() )
			->method( 'add' );

		$this->service->register();
		do_action( 'admin_init' );
	}

	public function test_does_not_record_when_woocommerce_install_timestamp_missing() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->wp->expects( $this->once() )
			->method( 'get_option' )
			->with( 'woocommerce_admin_install_timestamp' )
			->willReturn( false );

		$this->options->expects( $this->never() )
			->method( 'add' );

		$this->service->register();
		do_action( 'admin_init' );
	}

	public function test_does_not_record_when_woocommerce_install_timestamp_invalid() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->wp->expects( $this->once() )
			->method( 'get_option' )
			->with( 'woocommerce_admin_install_timestamp' )
			->willReturn( 'not-a-timestamp' );

		$this->options->expects( $this->never() )
			->method( 'add' );

		$this->service->register();
		do_action( 'admin_init' );
	}
}
