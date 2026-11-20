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

	public function test_register_records_wc_install_timestamp_immediately() {
		$wc_install_timestamp = time() - ( 91 * DAY_IN_SECONDS );

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
	}

	public function test_register_adds_admin_init_action() {
		$this->options->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( time() - DAY_IN_SECONDS );

		$this->service->register();

		$this->assertNotFalse(
			has_action( 'admin_init', [ $this->service, 'record_wc_install_timestamp' ] ),
			'admin_init action should be registered'
		);
	}

	public function test_records_wc_install_timestamp_from_woocommerce_option() {
		$wc_install_timestamp = time() - ( 91 * DAY_IN_SECONDS );

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

		$this->service->record_wc_install_timestamp();
	}

	public function test_does_not_record_when_gla_option_already_exists() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( time() - ( 91 * DAY_IN_SECONDS ) );

		$this->wp->expects( $this->never() )
			->method( 'get_option' );

		$this->options->expects( $this->never() )
			->method( 'add' );

		$this->service->record_wc_install_timestamp();
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

		$this->service->record_wc_install_timestamp();
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

		$this->service->record_wc_install_timestamp();
	}

	public function test_does_not_record_when_woocommerce_install_timestamp_is_zero() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::WC_INSTALL_TIMESTAMP )
			->willReturn( null );

		$this->wp->expects( $this->once() )
			->method( 'get_option' )
			->with( 'woocommerce_admin_install_timestamp' )
			->willReturn( '0' );

		$this->options->expects( $this->never() )
			->method( 'add' );

		$this->service->record_wc_install_timestamp();
	}
}
