<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountIssuesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiAccountIssuesServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Services
 */
class MapiAccountIssuesServiceTest extends UnitTest {

	protected const MERCHANT_ID = 12345;
	protected const LIST_PATH   = 'accounts/v1/accounts/12345/issues';

	/** @var MockObject|MerchantApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MapiAccountIssuesService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new MapiAccountIssuesService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function test_returns_account_issues() {
		$issues = [
			[
				'name'  => 'accounts/12345/issues/one',
				'title' => 'Issue one',
			],
			[
				'name'  => 'accounts/12345/issues/two',
				'title' => 'Issue two',
			],
		];

		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn( [ 'accountIssues' => $issues ] );

		$this->assertSame( $issues, $this->service->get_account_issues() );
	}

	public function test_returns_empty_array_when_no_issues() {
		$this->client->expects( $this->once() )
			->method( 'get' )
			->with( self::LIST_PATH )
			->willReturn( [] );

		$this->assertSame( [], $this->service->get_account_issues() );
	}

	public function test_follows_pagination() {
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ self::LIST_PATH ],
				[ self::LIST_PATH . '?pageToken=page-2' ]
			)
			->willReturnOnConsecutiveCalls(
				[
					'accountIssues' => [ [ 'name' => 'accounts/12345/issues/one' ] ],
					'nextPageToken' => 'page-2',
				],
				[
					'accountIssues' => [ [ 'name' => 'accounts/12345/issues/two' ] ],
				]
			);

		$this->assertSame(
			[
				[ 'name' => 'accounts/12345/issues/one' ],
				[ 'name' => 'accounts/12345/issues/two' ],
			],
			$this->service->get_account_issues()
		);
	}
}
