<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\ViewFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\ServiceBasedMerchant\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

/**
 * Class AdminTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin
 */
class AdminTest extends ContainerAwareUnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var MockObject|ViewFactory $view_factory */
	protected $view_factory;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|AdsService $ads */
	protected $ads;

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var Admin $admin */
	protected $admin;

	public function setUp(): void {
		parent::setUp();

		$this->assets_handler               = $this->createMock( AssetsHandlerInterface::class );
		$this->view_factory                 = $this->createMock( ViewFactory::class );
		$this->merchant_center              = $this->createMock( MerchantCenterService::class );
		$this->ads                          = $this->createMock( AdsService::class );
		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->options                      = $this->createMock( OptionsInterface::class );

		$this->admin = new Admin(
			$this->assets_handler,
			$this->view_factory,
			$this->merchant_center,
			$this->ads,
			$this->service_based_merchant_state
		);

		// Set options object since Admin uses OptionsAwareTrait
		$this->admin->set_options_object( $this->options );
	}

	public function test_get_assets_includes_service_based_merchant_in_gla_data() {
		$this->service_based_merchant_state->expects( $this->once() )
			->method( 'is_service_based_merchant' )
			->willReturn( true );

		// Use reflection to access protected method
		$method = new ReflectionMethod( Admin::class, 'get_assets' );
		$method->setAccessible( true );
		$assets = $method->invoke( $this->admin );

		// Get the first asset (the main script asset)
		$main_asset = $assets[0];

		// Use reflection to get the inline script data
		$reflection = new \ReflectionClass( $main_asset );
		$property   = $reflection->getProperty( 'inline_scripts' );
		$property->setAccessible( true );
		$inline_scripts = $property->getValue( $main_asset );

		// Verify serviceBasedMerchant is in glaData
		$this->assertArrayHasKey( 'glaData', $inline_scripts );
		$this->assertArrayHasKey( 'serviceBasedMerchant', $inline_scripts['glaData'] );
		$this->assertTrue( $inline_scripts['glaData']['serviceBasedMerchant'] );
	}

	public function test_get_assets_service_based_merchant_calls_service_method() {
		$this->service_based_merchant_state->expects( $this->once() )
			->method( 'is_service_based_merchant' )
			->willReturn( false );

		// Use reflection to access protected method
		$method = new ReflectionMethod( Admin::class, 'get_assets' );
		$method->setAccessible( true );
		$assets = $method->invoke( $this->admin );

		// Get the first asset (the main script asset)
		$main_asset = $assets[0];

		// Use reflection to get the inline script data
		$reflection = new \ReflectionClass( $main_asset );
		$property   = $reflection->getProperty( 'inline_scripts' );
		$property->setAccessible( true );
		$inline_scripts = $property->getValue( $main_asset );

		// Verify serviceBasedMerchant is false
		$this->assertArrayHasKey( 'serviceBasedMerchant', $inline_scripts['glaData'] );
		$this->assertFalse( $inline_scripts['glaData']['serviceBasedMerchant'] );
	}
}
