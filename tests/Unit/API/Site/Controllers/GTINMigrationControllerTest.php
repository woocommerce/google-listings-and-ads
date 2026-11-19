<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\GTINMigrationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\MigrateGTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test suite for GTINMigrationController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 */
class GTINMigrationControllerTest extends RESTControllerUnitTest {

	protected const ROUTE = '/wc/gla/gtin-migration';

	/**
	 * @var MockObject|OptionsInterface
	 */
	private $options;

	public function setUp(): void {
		parent::setUp();
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::INSTALL_VERSION, false, '2.8.0' ],
				[ OptionsInterface::GTIN_MIGRATION_STATUS, MigrateGTIN::GTIN_MIGRATION_READY, MigrateGTIN::GTIN_MIGRATION_STARTED ],
			]
		);

		$this->controller = new GTINMigrationController( $this->server, $this->createMock( JobRepository::class ) );
		$this->controller->register();
		$this->controller->set_options_object( $this->options );
	}

	public function test_get_status_returns_the_migration_status_to_shop_managers() {
		$this->login_as_role( 'shop_manager' );

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'status' => MigrateGTIN::GTIN_MIGRATION_STARTED ], $response->get_data() );
	}

	public function test_get_status_rejects_anonymous_requests() {
		wp_set_current_user( 0 );

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_get_status_rejects_users_who_cannot_manage_woocommerce() {
		$this->login_as_role( 'customer' );

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 403, $response->get_status() );
	}
}
