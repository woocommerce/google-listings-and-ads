<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleDatafeedService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Datafeed;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\DatafeedTarget;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\DatafeedsListResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Resource\Datafeeds as DatafeedsResource;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
 */
class GoogleDatafeedServiceTest extends UnitTest {

	/** @var MockObject|ShoppingContent $shopping_service */
	protected $shopping_service;

	/** @var MockObject|DatafeedsResource $datafeeds */
	protected $datafeeds;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var GoogleDatafeedService $service */
	protected $service;

	protected const MERCHANT_ID = 12345;

	public function setUp(): void {
		parent::setUp();

		$this->datafeeds        = $this->createMock( DatafeedsResource::class );
		$this->shopping_service = $this->createMock( ShoppingContent::class );
		// Public property set directly (matches the real ShoppingContent class).
		$this->shopping_service->datafeeds = $this->datafeeds;

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->service = new GoogleDatafeedService( $this->shopping_service );
		$this->service->set_options_object( $this->options );
	}

	public function test_get_all_returns_resources(): void {
		$datafeed = new Datafeed();
		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [ $datafeed ] );

		$this->datafeeds->expects( $this->once() )
						->method( 'listDatafeeds' )
						->with( self::MERCHANT_ID )
						->willReturn( $response );

		$result = $this->service->get_all();

		$this->assertCount( 1, $result );
		$this->assertSame( $datafeed, $result[0] );
	}

	public function test_get_all_returns_empty_array_when_no_resources(): void {
		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( null );

		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );

		$this->assertSame( [], $this->service->get_all() );
	}

	public function test_find_by_feed_label_returns_matching_datafeed(): void {
		$target = new DatafeedTarget();
		$target->setFeedLabel( 'en-USD' );

		$datafeed = new Datafeed();
		$datafeed->setTargets( [ $target ] );

		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [ $datafeed ] );
		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );

		$found = $this->service->find_by_feed_label( 'en-USD' );

		$this->assertSame( $datafeed, $found );
	}

	public function test_find_by_feed_label_matches_case_insensitively(): void {
		// MC may canonicalise to uppercase.
		$target = new DatafeedTarget();
		$target->setFeedLabel( 'EN-USD' );

		$datafeed = new Datafeed();
		$datafeed->setTargets( [ $target ] );

		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [ $datafeed ] );
		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );

		$this->assertSame( $datafeed, $this->service->find_by_feed_label( 'en-USD' ) );
	}

	public function test_find_by_feed_label_returns_null_when_not_found(): void {
		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [] );
		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );

		$this->assertNull( $this->service->find_by_feed_label( 'fr-EUR' ) );
	}

	public function test_ensure_for_feed_label_creates_when_not_found(): void {
		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [] );
		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );

		$this->datafeeds->expects( $this->once() )
						->method( 'insert' )
						->with(
							self::MERCHANT_ID,
							$this->callback(
								function ( Datafeed $df ) {
									$targets = $df->getTargets();
									return 'products' === $df->getContentType()
										&& 'GLA: en-USD' === $df->getName()
										&& 'gla-en-usd.xml' === $df->getFileName()
										&& 1 === count( $targets )
										// feedLabel is normalised to uppercase on insert.
										&& 'EN-USD' === $targets[0]->getFeedLabel()
										&& 'en' === $targets[0]->getLanguage()
										&& [ 'US' ] === $targets[0]->getTargetCountries();
								}
							)
						)
						->willReturn( new Datafeed() );

		$this->service->ensure_for_feed_label( 'en-USD', 'en', [ 'US' ] );
	}

	public function test_ensure_for_feed_label_updates_when_already_exists(): void {
		$target = new DatafeedTarget();
		$target->setFeedLabel( 'EN-USD' );

		$existing = new Datafeed();
		$existing->setId( '999' );
		$existing->setTargets( [ $target ] );

		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [ $existing ] );
		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );
		$this->datafeeds->method( 'get' )->with( self::MERCHANT_ID, '999' )->willReturn( $existing );

		$this->datafeeds->expects( $this->once() )
						->method( 'update' )
						->with(
							self::MERCHANT_ID,
							'999',
							$this->callback(
								function ( Datafeed $df ) {
									$targets = $df->getTargets();
									return [ 'US', 'CA' ] === $targets[0]->getTargetCountries();
								}
							)
						)
						->willReturn( new Datafeed() );

		$this->service->ensure_for_feed_label( 'en-USD', 'en', [ 'US', 'CA' ] );
	}

	public function test_delete_by_feed_label_deletes_matching_datafeed(): void {
		$target = new DatafeedTarget();
		$target->setFeedLabel( 'fr-EUR' );

		$datafeed = new Datafeed();
		$datafeed->setId( '42' );
		$datafeed->setTargets( [ $target ] );

		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [ $datafeed ] );
		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );

		$this->datafeeds->expects( $this->once() )
						->method( 'delete' )
						->with( self::MERCHANT_ID, '42' );

		$this->service->delete_by_feed_label( 'fr-EUR' );
	}

	public function test_delete_by_feed_label_is_noop_when_not_found(): void {
		$response = $this->createMock( DatafeedsListResponse::class );
		$response->method( 'getResources' )->willReturn( [] );
		$this->datafeeds->method( 'listDatafeeds' )->willReturn( $response );

		$this->datafeeds->expects( $this->never() )->method( 'delete' );

		$this->service->delete_by_feed_label( 'de-CHF' );
	}
}
