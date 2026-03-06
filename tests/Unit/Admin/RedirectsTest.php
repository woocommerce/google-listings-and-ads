<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Redirect;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Dashboard;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GetStarted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\Admin\PageController;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedirectsTest extends TestCase {

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var MockObject|Redirect $redirects */
	protected $redirects;

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/**
	 * Setup tests
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp                   = $this->createMock( WP::class );
		$this->redirects            = $this->createMock( Redirect::class );
		$this->onboarding_completed = $this->createMock( OnboardingCompleted::class );
		$this->options              = $this->createMock( OptionsInterface::class );
	}

	/**
	 * Test `maybe_redirect` to confirm that merchant is only redirected to onboarding if:
	 * 1. Onboarding is not complete
	 * 2. They are attempting to access the dashboard
	 *
	 * @return void
	 */
	public function test_maybe_redirect_to_onboarding(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'is_current_wc_admin_page', 'redirect_to' ] );

		// Set up options mock: no redirect flag set
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $name ) {
					if ( $name === OptionsInterface::REDIRECT_TO_ONBOARDING ) {
						return 'no'; // No redirect flag
					}
					return null;
				}
			);

		// Set up onboarding completed mock: onboarding not complete
		$this->onboarding_completed->method( 'is_onboarding_complete' )
			->willReturn( false );

		// Set up page check: on dashboard page
		$redirect_instance->method( 'is_current_wc_admin_page' )
			->willReturnCallback(
				function ( $path ) {
					return $path === Dashboard::PATH;
				}
			);

		$redirect_instance->expects( $this->once() )
			->method( 'redirect_to' )
			->with( GetStarted::PATH );

		$redirect_instance->maybe_redirect();
	}

	/**
	 * Test `maybe_redirect` to confirm that merchant is only redirected to the dashboard if:
	 * 1. Onboarding is complete
	 * 2. They are attempting to access the onboarding screen
	 *
	 * @return void
	 */
	public function test_maybe_redirect_to_dashboard(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'is_current_wc_admin_page', 'redirect_to' ] );

		// Set up options mock: no redirect flag set
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $name ) {
					if ( $name === OptionsInterface::REDIRECT_TO_ONBOARDING ) {
						return 'no'; // No redirect flag
					}
					return null;
				}
			);

		// Set up onboarding completed mock: onboarding complete
		$this->onboarding_completed->method( 'is_onboarding_complete' )
			->willReturn( true );

		// Set up page check: not on dashboard, but on get_started page
		$redirect_instance->method( 'is_current_wc_admin_page' )
			->willReturnCallback(
				function ( $path ) {
					if ( $path === Dashboard::PATH ) {
						return false; // Not on dashboard
					}
					if ( $path === GetStarted::PATH ) {
						return true; // On get_started page
					}
					return false;
				}
			);

		$redirect_instance->expects( $this->once() )
			->method( 'redirect_to' )
			->with( Dashboard::PATH );

		$this->assertNull( $redirect_instance->maybe_redirect() );
	}

	/**
	 * Test `maybe_redirect_after_activation` when onboarding is already complete.
	 * Should not redirect and should clear the redirect flag.
	 *
	 * @return void
	 */
	public function test_maybe_redirect_after_activation_onboarding_complete(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'is_current_wc_admin_page', 'redirect_to' ] );

		// Set up options mock: redirect flag set
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $name ) {
					if ( $name === OptionsInterface::REDIRECT_TO_ONBOARDING ) {
						return 'yes'; // Redirect flag set
					}
					return null;
				}
			);

		// Set up onboarding completed mock: onboarding complete
		$this->onboarding_completed->method( 'is_onboarding_complete' )
			->willReturn( true );

		// Should update the redirect flag to 'no'
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::REDIRECT_TO_ONBOARDING, 'no' );

		// Should not redirect
		$redirect_instance->expects( $this->never() )
			->method( 'redirect_to' );

		$this->assertFalse( $redirect_instance->maybe_redirect() );
	}

	/**
	 * Test `maybe_redirect_after_activation` when already on get_started page.
	 * Should not redirect and should clear the redirect flag.
	 *
	 * @return void
	 */
	public function test_maybe_redirect_after_activation_on_get_started_page(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'is_current_wc_admin_page', 'redirect_to' ] );

		// Set up options mock: redirect flag set
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $name ) {
					if ( $name === OptionsInterface::REDIRECT_TO_ONBOARDING ) {
						return 'yes'; // Redirect flag set
					}
					return null;
				}
			);

		// Set up onboarding completed mock: onboarding not complete
		$this->onboarding_completed->method( 'is_onboarding_complete' )
			->willReturn( false );

		// Set up page check: on get_started page
		$redirect_instance->method( 'is_current_wc_admin_page' )
			->with( GetStarted::PATH )
			->willReturn( true );

		// Should update the redirect flag to 'no'
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::REDIRECT_TO_ONBOARDING, 'no' );

		// Should not redirect
		$redirect_instance->expects( $this->never() )
			->method( 'redirect_to' );

		$this->assertFalse( $redirect_instance->maybe_redirect() );
	}

	/**
	 * Test `maybe_redirect_after_activation` when onboarding is not complete and not on get_started page.
	 * Should redirect to get_started page.
	 *
	 * @return void
	 */
	public function test_maybe_redirect_after_activation_redirects_to_onboarding(): void {
		$redirect_instance = $this->get_redirect_instance( [ 'is_current_wc_admin_page', 'redirect_to' ] );

		// Set up options mock: redirect flag set
		$this->options->method( 'get' )
			->willReturnCallback(
				function ( $name ) {
					if ( $name === OptionsInterface::REDIRECT_TO_ONBOARDING ) {
						return 'yes'; // Redirect flag set
					}
					return null;
				}
			);

		// Set up onboarding completed mock: onboarding not complete
		$this->onboarding_completed->method( 'is_onboarding_complete' )
			->willReturn( false );

		// Set up page check: not on get_started page
		$redirect_instance->method( 'is_current_wc_admin_page' )
			->with( GetStarted::PATH )
			->willReturn( false );

		// Should redirect to get_started
		$redirect_instance->expects( $this->once() )
			->method( 'redirect_to' )
			->with( GetStarted::PATH );

		$this->assertTrue( $redirect_instance->maybe_redirect() );
	}

	/**
	 * Test is_current_wc_admin_page:
	 */
	public function test_is_current_wc_admin_page(): void {
		$redirect_instance = $this->get_redirect_instance();

		$this->assertFalse( $redirect_instance->is_current_wc_admin_page( Dashboard::PATH ) );

		$_GET['page'] = PageController::PAGE_ROOT;
		$_GET['path'] = Dashboard::PATH;

		$this->assertTrue( $redirect_instance->is_current_wc_admin_page( Dashboard::PATH ) );
	}

	/**
	 * Get mock instance of Redirect class
	 *
	 * @param array $only_methods Array listing the Redirect methods that will be replaced with test doubles
	 *
	 * @return object Mock instance of Redirect class
	 */
	private function get_redirect_instance( $only_methods = [] ): object {
		$instance = $this->getMockBuilder( Redirect::class )
			->setConstructorArgs( [ $this->wp, $this->onboarding_completed ] )
			->onlyMethods( $only_methods )
			->getMock();

		$instance->set_options_object( $this->options );

		return $instance;
	}
}
