<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\View;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPView;
use Automattic\WooCommerce\GoogleListingsAndAds\View\PHPViewFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\View\ViewException;

/**
 * Class PHPViewTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility
 *
 * @property PHPViewFactory $view_factory
 */
class PHPViewTest extends UnitTest {

	use DataTrait;

	public function test_view_construct_fails_if_non_existing_view_file() {
		$this->expectException( ViewException::class );
		new PHPView( '/tmp/imaginary-folder/non-existing-view' . md5( (string) rand() ), $this->view_factory );
	}

	public function test_view_render_with_partial() {
		$view = new PHPView( $this->get_data_file_path( 'test-php-view.php' ), $this->view_factory );

		$expected = <<<VIEW
Variable value is: Test
Raw variable value is: <strong>Test Raw HTML</strong>
Boolean variable value is: 1
Array variable value is: Value 1, Value 2
Partial variable value is: Test partial
Partial with no context:
Partial variable value is: Test
VIEW;

		$this->assertEquals(
			$expected,
			$view->render(
				[
					'some_variable'  => '<strong>Test</strong>%30',
					'raw_variable'   => '<strong>Test Raw HTML</strong>',
					'bool_variable'  => true,
					'array_variable' => [
						'<p>Value 1</p>',
						'<p>Value 2</p>',
					],
					'partial_path'   => $this->get_data_file_path( 'test-php-view-partial.php' ),
				]
			)
		);
	}

	public function test_view_render_fails_if_context_variable_missing() {
		$view = new PHPView( $this->get_data_file_path( 'test-php-view.php' ), $this->view_factory );
		$this->expectException( ViewException::class );
		$view->render();
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->view_factory = new PHPViewFactory();
	}

}
