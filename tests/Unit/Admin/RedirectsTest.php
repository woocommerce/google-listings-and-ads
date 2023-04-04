<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Redirect;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Dashboard;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GetStarted;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\Admin\PageController;

use PHPUnit\Framework\TestCase;

class RedirectsTest extends TestCase {

	/**
	 * Setup tests
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp              = $this->createMock( WP::class );
		$this->redirects       = $this->createMock( Redirect::class );
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->options         = $this->createMock( OptionsInterface::class );
	}

	/**
	 * Test `maybe_redirect` to confirm that merchant is only redirected to onboarding if:
	 * 1. Merchant center setup is not complete
	 * 2. They are attempting to access the dashboard
	 *
	 * @return void
	 */
	public function test_maybe_redirect_to_onboarding(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'maybe_redirect' ] );

		$this->merchant_center->method( 'is_setup_complete' )
							  ->willReturn( false );

		$this->assertFalse( $redirect_instance->maybe_redirect() );

		$redirect_instance->method( 'is_current_wc_admin_page' )
						  ->with( Dashboard::PATH )
						  ->willReturn( true );

		$redirect_instance->expects( $this->once() )
						  ->method( 'redirect_to' )
						  ->with( GetStarted::PATH );

		$this->assertNull( $redirect_instance->maybe_redirect() );
	}

	/**
	 * Test `maybe_redirect` to confirm that merchant is only redirected to the dashboard if:
	 * 1. Merchant center setup is complete
	 * 2. They are attempting to access the onboarding screen
	 *
	 * @return void
	 */
	public function test_maybe_redirect_to_dashboard(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'maybe_redirect' ] );

		$this->merchant_center->method( 'is_setup_complete' )
							  ->willReturn( true );

		$this->assertFalse( $redirect_instance->maybe_redirect() );

		$redirect_instance->method( 'is_current_wc_admin_page' )
						  ->with( GetStarted::PATH )
						  ->willReturn( true );

		$redirect_instance->expects( $this->once() )
						  ->method( 'redirect_to' )
						  ->with( Dashboard::PATH );

		$this->assertNull( $redirect_instance->maybe_redirect() );
	}

	/**
	 * Test is_current_wc_admin_page:
	 */
	public function test_is_current_wc_admin_page(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'is_current_wc_admin_page' ] );

		$this->assertFalse( $redirect_instance->is_current_wc_admin_page( Dashboard::PATH ) );

		$_GET['page'] = PageController::PAGE_ROOT;
		$_GET['path'] = Dashboard::PATH;

		$this->assertTrue( $redirect_instance->is_current_wc_admin_page( Dashboard::PATH ) );
	}

	/**
	 * Get mock instance of Redirect class
	 *
	 * @param array $methods_under_test Array listing the Redirect methods that will not be replaced in this mock
	 *
	 * @return object Mock instance of Redirect class
	 */
	private function get_redirect_instance( $methods_under_test = [] ): object {
		$instance = $this->getMockBuilder( Redirect::class )
						 ->setConstructorArgs( [ $this->wp ] )
						 ->setMethodsExcept( [ 'set_options_object', 'set_merchant_center_object', ...$methods_under_test ] )
						 ->getMock();

		$instance->set_options_object( $this->options );
		$instance->set_merchant_center_object( $this->merchant_center );

		return $instance;
	}
}
