<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\MerchantTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification as SiteVerificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification\Resource\WebResource;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification\SiteVerificationWebResourceGettokenResponse;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerificationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class SiteVerificationTest extends UnitTest {

	use MerchantTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|SiteVerficationService $verification_service */
	protected $verification_service;

	/** @var SiteVerification $verification */
	protected $verification;

	/** @var Container $container */
	protected $container;

	/** @var string $site_url */
	protected $site_url;

	protected const TEST_META_TAG = '<meta name="google-site-verification" content="abc" />';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );

		$this->verification_service              = $this->createMock( SiteVerificationService::class );
		$this->verification_service->webResource = $this->createMock( WebResource::class );

		$this->container = new Container();
		$this->container->share( SiteVerificationService::class, $this->verification_service );

		$this->verification = new SiteVerification();
		$this->verification->set_options_object( $this->options );
		$this->verification->set_container( $this->container );

		$this->site_url = get_home_url();
	}

	public function test_verify_site_invalid_url() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid site URL.' );

		$this->verification->verify_site( 'foo://bar' );
	}

	public function test_verify_site_token_exception() {
		$this->verification_service->webResource
			->method( 'getToken' )
			->willThrowException( $this->get_google_service_exception( 400, 'No available tokens' ) );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Unable to retrieve site verification token: No available tokens' );

		$this->verification->verify_site( $this->site_url );

		$this->assertEquals( 1, did_action( 'woocommerce_gla_site_verify_failure' ) );
	}

	public function test_verify_site_insert_exception() {
		$this->mock_service_get_token();

		$this->verification_service->webResource
			->method( 'insert' )
			->willThrowException( $this->get_google_service_exception( 400, 'No necessary verification token.' ) );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Unable to insert site verification: No necessary verification token.' );

		$this->verification->verify_site( $this->site_url );

		$this->assertEquals( 1, did_action( 'woocommerce_gla_site_verify_failure' ) );
	}

	public function test_verify_site() {
		$this->mock_service_get_token();
		$this->mock_service_insert();

		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->withConsecutive(
				[
					OptionsInterface::SITE_VERIFICATION,
					[
						'verified' => SiteVerification::VERIFICATION_STATUS_UNVERIFIED,
						'meta_tag' => self::TEST_META_TAG,
					],
				],
				[
					OptionsInterface::SITE_VERIFICATION,
					[
						'verified' => SiteVerification::VERIFICATION_STATUS_VERIFIED,
						'meta_tag' => self::TEST_META_TAG,
					],
				]
			);

		$this->verification->verify_site( $this->site_url );

		$this->assertEquals( 1, did_action( 'woocommerce_gla_site_verify_success' ) );
	}

	protected function mock_service_get_token() {
		$response = $this->createMock( SiteVerificationWebResourceGettokenResponse::class );
		$response->method( 'getToken' )->willReturn( self::TEST_META_TAG );
		$this->verification_service->webResource->method( 'getToken' )->willReturn( $response );
	}

	protected function mock_service_insert() {
		$this->verification_service->webResource->expects( $this->once() )
			->method( 'insert' );
	}
}
