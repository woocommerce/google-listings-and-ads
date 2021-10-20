<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\View;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPViewFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\View\ViewException;

/**
 * Class PHPViewFactoryTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility
 *
 * @property PHPViewFactory $view_factory
 */
class PHPViewFactoryTest extends UnitTest {

	use DataTrait;

	public function test_create_view() {
		$view = $this->view_factory->create( $this->get_data_file_path( 'test-php-view.php' ) );

		$this->assertInstanceOf( PHPView::class, $view );
	}

	public function test_create_view_fails_if_non_existing_view_file() {
		$this->expectException( ViewException::class );
		$this->view_factory->create( '/tmp/imaginary-folder/non-existing-view' . md5( (string) rand() ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->view_factory = new PHPViewFactory();
	}

}
