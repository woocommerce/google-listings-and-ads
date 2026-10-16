<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountUsersService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountUsersServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiAccountUsersServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const PATH        = 'accounts/v1/accounts/12345/users/me';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiAccountUsersService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiAccountUsersService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_get_current_user() {
		$user = [
			'name'         => 'accounts/12345/users/me@example.com',
			'state'        => 'VERIFIED',
			'accessRights' => [ 'ADMIN', 'STANDARD' ],
		];

		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::PATH )
			->willReturn( $user );

		$this->assertSame( $user, $this->service->get_current_user() );
	}

	public function test_get_current_user_builds_path_from_merchant_id() {
		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get_merchant_id' )->willReturn( 67890 );

		$service = new MapiAccountUsersService( $this->client );
		$service->set_options_object( $options );

		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( 'accounts/v1/accounts/67890/users/me' )
			->willReturn( [] );

		$service->get_current_user();
	}
}
