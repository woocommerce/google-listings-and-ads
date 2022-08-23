<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsConversionAction;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Exception;
use Google\Ads\GoogleAds\V11\Enums\ConversionActionStatusEnum\ConversionActionStatus;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsConversionActionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface  $options
 * @property AdsConversionAction          $conversion_action
 */
class AdsConversionActionTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_CONVERSION_ACTION_ID = 1234567890;
	protected const TEST_CONVERSION_NAME      = '[a12b] Google Listings and Ads purchase action';
	protected const TEST_CONVERSION_ID        = 'AW-12345';
	protected const TEST_CONVERSION_LABEL     = 'abCD12ef';
	protected const TEST_CONVERSION_SNIPPET   = "'send_to': 'AW-12345/abCD12ef'";

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );

		$this->conversion_action = new AdsConversionAction( $this->client );
		$this->conversion_action->set_options_object( $this->options );

		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );
	}

	public function test_create_conversion_action() {
		$this->generate_conversion_action_mutate_mock(
			'create',
			self::TEST_CONVERSION_ACTION_ID
		);
		$this->generate_conversion_action_query_mock(
			[
				'id'      => self::TEST_CONVERSION_ACTION_ID,
				'name'    => self::TEST_CONVERSION_NAME,
				'status'  => ConversionActionStatus::ENABLED,
				'snippet' => self::TEST_CONVERSION_SNIPPET,
			]
		);

		$this->assertEquals(
			[
				'id'               => self::TEST_CONVERSION_ACTION_ID,
				'name'             => self::TEST_CONVERSION_NAME,
				'status'           => 'ENABLED',
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			],
			$this->conversion_action->create_conversion_action()
		);
	}

	public function test_create_conversion_action_exception_duplicate_name() {
		$errors = [
			'errors' => [
				[
					'errorCode' => [
						'conversionActionError' => 'DUPLICATE_NAME',
					],
					'message'   => 'Duplicate name',
				],
			],
		];

		$this->generate_conversion_action_mutate_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT', [ 'metadata' => [ $errors ] ] )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'A conversion action with this name already exists' );

		$this->conversion_action->create_conversion_action();
	}

	public function test_create_conversion_action_api_exception() {
		$this->generate_conversion_action_mutate_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT' )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Error creating conversion action: invalid' );

		$this->conversion_action->create_conversion_action();
	}

	public function test_create_conversion_action_exception() {
		$this->generate_conversion_action_mutate_exception(
			new Exception( 'unauthorized', 401 )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 401 );
		$this->expectExceptionMessage( 'Error creating conversion action: unauthorized' );

		$this->conversion_action->create_conversion_action();
	}

	public function test_get_conversion_action_by_id() {
		$this->generate_conversion_action_query_mock(
			[
				'id'      => self::TEST_CONVERSION_ACTION_ID,
				'name'    => self::TEST_CONVERSION_NAME,
				'status'  => ConversionActionStatus::ENABLED,
				'snippet' => self::TEST_CONVERSION_SNIPPET,
			]
		);

		$this->assertEquals(
			[
				'id'               => self::TEST_CONVERSION_ACTION_ID,
				'name'             => self::TEST_CONVERSION_NAME,
				'status'           => 'ENABLED',
				'conversion_id'    => self::TEST_CONVERSION_ID,
				'conversion_label' => self::TEST_CONVERSION_LABEL,
			],
			$this->conversion_action->get_conversion_action( self::TEST_CONVERSION_ACTION_ID )
		);
	}

	public function test_get_conversion_action_api_exception() {
		$this->generate_ads_query_mock_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT' )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Error retrieving conversion action: invalid' );

		$this->conversion_action->get_conversion_action( self::TEST_CONVERSION_ACTION_ID );
	}

}
