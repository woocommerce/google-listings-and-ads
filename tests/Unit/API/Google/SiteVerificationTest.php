<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Exception;
use Google\Service\SiteVerification as SiteVerificationService;
use Google\Service\SiteVerification\Resource\WebResource;
use Google\Service\SiteVerification\SiteVerificationWebResourceGettokenResponse;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerificationTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface       $options
 * @property MockObject|SiteVerficationService $verification_service
 * @property SiteVerification                  $verficiation
 * @property Container                         $container
 * @property string                            $site_url
 */
class SiteVerificationTest extends UnitTest {

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
			->willThrowException( new GoogleServiceException( 'error', 400 ) );

		try {
			$this->verification->verify_site( $this->site_url );
		} catch ( Exception $e ) {
			$this->assertEquals( 1, did_action( 'woocommerce_gla_site_verify_failure' ) );
			$this->assertEquals( 400, $e->getCode() );
			$this->assertEquals(
				'Unable to retrieve site verification token.',
				$e->getMessage()
			);
		}
	}

	public function test_verify_site_insert_exception() {
		$this->mock_service_get_token();

		$this->verification_service->webResource
			->method( 'insert' )
			->willThrowException( new GoogleServiceException( 'error', 400 ) );

		try {
			$this->verification->verify_site( $this->site_url );
		} catch ( Exception $e ) {
			$this->assertEquals( 1, did_action( 'woocommerce_gla_site_verify_failure' ) );
			$this->assertEquals( 400, $e->getCode() );
			$this->assertEquals(
				'Unable to insert site verification.',
				$e->getMessage()
			);
		}
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
