<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\V23\Services\ApplyIncentiveResponse;
use Google\Ads\GoogleAds\V23\Services\Client\IncentiveServiceClient;
use Google\Ads\GoogleAds\V23\Services\CyoIncentives;
use Google\Ads\GoogleAds\V23\Services\FetchIncentiveResponse;
use Google\Ads\GoogleAds\V23\Services\FetchIncentiveRequest\IncentiveType;
use Google\Ads\GoogleAds\V23\Services\Incentive;
use Google\Ads\GoogleAds\V23\Services\Incentive\Requirement;
use Google\Ads\GoogleAds\V23\Services\Incentive\Requirement\Spend;
use Google\Ads\GoogleAds\V23\Services\IncentiveOffer;
use Google\Ads\GoogleAds\V23\Services\IncentiveOffer\OfferType;
use Google\ApiCore\ApiException;
use Google\Type\Money;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsIncentivesTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsIncentivesTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|IncentiveServiceClient $incentive_service */
	protected $incentive_service;

	/** @var MockObject|WC $wc */
	protected $wc;

	/** @var AdsIncentives $ads_incentives */
	protected $ads_incentives;

	protected const TEST_ADS_ID       = 1234567890;
	protected const TEST_COUNTRY      = 'US';
	protected const TEST_LANGUAGE     = 'en';
	protected const TEST_INCENTIVE_ID = '2378556534';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->incentive_service = $this->getMockBuilder( IncentiveServiceClient::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'fetchIncentive', 'applyIncentive' ] )
			->getMock();

		$this->client->method( 'getIncentiveServiceClient' )->willReturn( $this->incentive_service );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->wc      = $this->createMock( WC::class );
		$this->wc->method( 'get_base_country' )->willReturn( self::TEST_COUNTRY );

		$this->ads_incentives = new AdsIncentives( $this->client, $this->wc );
		$this->ads_incentives->set_options_object( $this->options );
	}

	public function test_fetch_incentives_returns_empty_response_when_offer_is_null() {
		$response = $this->createMock( FetchIncentiveResponse::class );
		$response->method( 'getIncentiveOffer' )->willReturn( null );

		$this->incentive_service->method( 'fetchIncentive' )->willReturn( $response );

		$result = $this->ads_incentives->fetch_incentives();

		$this->assertEquals( 'CYO_INCENTIVE', $result['type'] );
		$this->assertSame( '', $result['termsAndConditionsUrl'] );
		$this->assertEmpty( $result['incentives'] );
	}

	public function test_fetch_incentives_returns_empty_response_when_offer_has_no_type() {
		$offer = $this->createMock( IncentiveOffer::class );
		$offer->method( 'hasType' )->willReturn( false );

		$response = $this->createMock( FetchIncentiveResponse::class );
		$response->method( 'getIncentiveOffer' )->willReturn( $offer );

		$this->incentive_service->method( 'fetchIncentive' )->willReturn( $response );

		$result = $this->ads_incentives->fetch_incentives();

		$this->assertEquals( 'CYO_INCENTIVE', $result['type'] );
		$this->assertSame( '', $result['termsAndConditionsUrl'] );
		$this->assertEmpty( $result['incentives'] );
	}

	public function test_fetch_incentives_returns_empty_response_on_api_exception() {
		$this->incentive_service->method( 'fetchIncentive' )
			->willThrowException( new ApiException( 'unavailable', 14, 'UNAVAILABLE' ) );

		$result = $this->ads_incentives->fetch_incentives();

		$this->assertEquals( 'CYO_INCENTIVE', $result['type'] );
		$this->assertSame( '', $result['termsAndConditionsUrl'] );
		$this->assertEmpty( $result['incentives'] );
	}

	public function test_fetch_incentives_returns_non_cyo_offer_without_incentives() {
		$offer = $this->createMock( IncentiveOffer::class );
		$offer->method( 'hasType' )->willReturn( true );
		$offer->method( 'getType' )->willReturn( OfferType::NO_INCENTIVE );
		$offer->method( 'getConsolidatedTermsAndConditionsUrl' )->willReturn( '' );
		$offer->method( 'hasCyoIncentives' )->willReturn( false );

		$response = $this->createMock( FetchIncentiveResponse::class );
		$response->method( 'getIncentiveOffer' )->willReturn( $offer );

		$this->incentive_service->method( 'fetchIncentive' )->willReturn( $response );

		$result = $this->ads_incentives->fetch_incentives();

		$this->assertEquals( 'NO_INCENTIVE', $result['type'] );
		$this->assertEmpty( $result['incentives'] );
	}

	public function test_fetch_incentives_returns_cyo_incentives() {
		$tc_url     = 'https://ads.google.com/intl/en_us/home/terms-and-conditions/incentives/?bc=US';
		$low_tc_url = $tc_url . '&bid=low';

		$low_incentive = $this->generate_incentive_mock( '1111', IncentiveType::ACQUISITION, $low_tc_url, '500', '500' );

		$cyo = $this->createMock( CyoIncentives::class );
		$cyo->method( 'getLowOffer' )->willReturn( $low_incentive );
		$cyo->method( 'getMediumOffer' )->willReturn( null );
		$cyo->method( 'getHighOffer' )->willReturn( null );

		$offer = $this->createMock( IncentiveOffer::class );
		$offer->method( 'hasType' )->willReturn( true );
		$offer->method( 'getType' )->willReturn( OfferType::CYO_INCENTIVE );
		$offer->method( 'getConsolidatedTermsAndConditionsUrl' )->willReturn( $tc_url );
		$offer->method( 'hasCyoIncentives' )->willReturn( true );
		$offer->method( 'getCyoIncentives' )->willReturn( $cyo );

		$response = $this->createMock( FetchIncentiveResponse::class );
		$response->method( 'getIncentiveOffer' )->willReturn( $offer );

		$this->incentive_service->method( 'fetchIncentive' )->willReturn( $response );

		$result = $this->ads_incentives->fetch_incentives();

		$this->assertEquals( 'CYO_INCENTIVE', $result['type'] );
		$this->assertEquals( $tc_url, $result['termsAndConditionsUrl'] );
		$this->assertCount( 1, $result['incentives'] );

		$incentive = $result['incentives'][0];
		$this->assertEquals( '1111', $incentive['id'] );
		$this->assertEquals( 'ACQUISITION', $incentive['type'] );
		$this->assertEquals( 'low', $incentive['offer'] );
		$this->assertEquals( $low_tc_url, $incentive['termsAndConditionsUrl'] );
		$this->assertEquals( 'USD', $incentive['requirement']['spend']['awardAmount']['currencyCode'] );
		$this->assertEquals( '500', $incentive['requirement']['spend']['awardAmount']['units'] );
		$this->assertEquals( '500', $incentive['requirement']['spend']['requiredAmount']['units'] );
	}

	public function test_fetch_incentives_formats_missing_spend_amounts_as_zero() {
		$incentive = $this->generate_incentive_mock( '1111', IncentiveType::ACQUISITION, '', null, null );

		$cyo = $this->createMock( CyoIncentives::class );
		$cyo->method( 'getLowOffer' )->willReturn( $incentive );
		$cyo->method( 'getMediumOffer' )->willReturn( null );
		$cyo->method( 'getHighOffer' )->willReturn( null );

		$offer = $this->createMock( IncentiveOffer::class );
		$offer->method( 'hasType' )->willReturn( true );
		$offer->method( 'getType' )->willReturn( OfferType::CYO_INCENTIVE );
		$offer->method( 'getConsolidatedTermsAndConditionsUrl' )->willReturn( '' );
		$offer->method( 'hasCyoIncentives' )->willReturn( true );
		$offer->method( 'getCyoIncentives' )->willReturn( $cyo );

		$response = $this->createMock( FetchIncentiveResponse::class );
		$response->method( 'getIncentiveOffer' )->willReturn( $offer );

		$this->incentive_service->method( 'fetchIncentive' )->willReturn( $response );

		$result = $this->ads_incentives->fetch_incentives();
		$spend  = $result['incentives'][0]['requirement']['spend'];

		$this->assertEquals( '', $spend['awardAmount']['currencyCode'] );
		$this->assertEquals( '0', $spend['awardAmount']['units'] );
		$this->assertEquals( '0', $spend['requiredAmount']['units'] );
	}

	public function test_apply_incentive_returns_coupon_code_and_creation_time() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->options->expects( $this->never() )->method( 'update' );

		$api_response = $this->createMock( ApplyIncentiveResponse::class );
		$api_response->method( 'getCouponCode' )->willReturn( 'abc123' );
		$api_response->method( 'getCreationTime' )->willReturn( '2026-03-15 15:33:21' );

		$this->incentive_service->method( 'applyIncentive' )->willReturn( $api_response );

		$result = $this->ads_incentives->apply_incentive( self::TEST_INCENTIVE_ID, self::TEST_COUNTRY );

		$this->assertEquals( 'abc123', $result['coupon_code'] );
		$this->assertEquals( '2026-03-15 15:33:21', $result['creation_time'] );
	}

	public function test_apply_incentive_throws_exception_on_api_error() {
		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->options->expects( $this->never() )->method( 'update' );

		$this->incentive_service->method( 'applyIncentive' )
			->willThrowException( new ApiException( 'PERMISSION_DENIED', 7, 'PERMISSION_DENIED' ) );

		try {
			$this->ads_incentives->apply_incentive( self::TEST_INCENTIVE_ID, self::TEST_COUNTRY );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals( 403, $e->getCode() );
			$this->assertArrayHasKey( 'errors', $e->get_response_data( true ) );
		}
	}

	/**
	 * Creates a mocked Incentive with an optional spend requirement.
	 *
	 * @param string      $id         Incentive ID.
	 * @param int         $type       IncentiveType constant.
	 * @param string      $tc_url     Terms and conditions URL.
	 * @param string|null $award      Award amount units, or null to omit spend.
	 * @param string|null $required   Required amount units, or null to omit spend.
	 *
	 * @return MockObject|Incentive
	 */
	private function generate_incentive_mock( string $id, int $type, string $tc_url, ?string $award, ?string $required ): MockObject {
		$incentive = $this->createMock( Incentive::class );
		$incentive->method( 'getIncentiveId' )->willReturn( $id );
		$incentive->method( 'getType' )->willReturn( $type );
		$incentive->method( 'getIncentiveTermsAndConditionsUrl' )->willReturn( $tc_url );

		if ( null !== $award && null !== $required ) {
			$award_money = $this->createMock( Money::class );
			$award_money->method( 'getCurrencyCode' )->willReturn( 'USD' );
			$award_money->method( 'getUnits' )->willReturn( $award );

			$required_money = $this->createMock( Money::class );
			$required_money->method( 'getCurrencyCode' )->willReturn( 'USD' );
			$required_money->method( 'getUnits' )->willReturn( $required );

			$spend = $this->createMock( Spend::class );
			$spend->method( 'getAwardAmount' )->willReturn( $award_money );
			$spend->method( 'getRequiredAmount' )->willReturn( $required_money );

			$requirement = $this->createMock( Requirement::class );
			$requirement->method( 'hasSpend' )->willReturn( true );
			$requirement->method( 'getSpend' )->willReturn( $spend );

			$incentive->method( 'hasRequirement' )->willReturn( true );
			$incentive->method( 'getRequirement' )->willReturn( $requirement );
		} else {
			$spend = $this->createMock( Spend::class );
			$spend->method( 'getAwardAmount' )->willReturn( null );
			$spend->method( 'getRequiredAmount' )->willReturn( null );

			$requirement = $this->createMock( Requirement::class );
			$requirement->method( 'hasSpend' )->willReturn( true );
			$requirement->method( 'getSpend' )->willReturn( $spend );

			$incentive->method( 'hasRequirement' )->willReturn( true );
			$incentive->method( 'getRequirement' )->willReturn( $requirement );
		}

		return $incentive;
	}
}
