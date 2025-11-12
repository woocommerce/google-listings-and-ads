<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WCS\ConnectionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Features;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class FeaturesTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 */
class FeaturesTest extends UnitTest {

	/** @var MockObject|Features $features */
	protected $features;

	/** @var MockObject|ConnectionService $options */
	protected $connection;

	/** @var MockObject|TransientInterface $options */
	protected $transients;

	protected const TEST_INPUT_DATA = [
		'version'  => '2025-10-30T09:00:00+00:00',
		'features' => [
			'google_tag_gateway' => [
				'enabled'    => true,
				'default'    => true,
				'percentage' => 100,
			],
		],
	];

	protected const TEST_OUTPUT_DATA = [
		'version'  => '2025-10-30T09:00:00+00:00',
		'features' => [
			'google_tag_gateway' => [
				'enabled'    => true,
				'attributes' => [
					'enabled'    => true,
					'default'    => true,
					'percentage' => 100,
				],
			],
		],
	];

	/**
	 * Setup tests
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->connection  = $this->createMock( ConnectionService::class );
		$this->transients = $this->createMock( TransientsInterface::class );
		$this->features = new Features( $this->connection );
		$this->features->set_transients_object( $this->transients );
	}

	public function test_update_formats_features_array_correctly() {
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::WCS_FEATURE_FLAGS )
			->willReturn( null );

		$this->connection->expects( $this->once() )
			->method( 'get_features' )
			->willReturn( self::TEST_INPUT_DATA );


		$features = $this->features->get_features();

		$this->assertEquals( $features, self::TEST_OUTPUT_DATA );
	}

	public function test_invalid_feature_is_not_included_on_update() {
		$input = self::TEST_INPUT_DATA;

		$input['features']['invalid_feature'] = [
			'enabled'    => true,
			'default'    => true,
			'percentage' => 100,
		];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::WCS_FEATURE_FLAGS )
			->willReturn( null );

		$this->connection->expects( $this->once() )
			->method( 'get_features' )
			->willReturn( $input );

		$features = $this->features->get_features();

		$this->assertEquals( $features, self::TEST_OUTPUT_DATA );
	}

	public function test_is_enabled_returns_correct_value_for_feature() {
		$option = self::TEST_OUTPUT_DATA;

		$option['features']['test_feature'] = [
			'enabled' => false,
		];

		$this->transients->expects( $this->any() )
			->method( 'get' )
			->with( TransientsInterface::WCS_FEATURE_FLAGS )
			->willReturn( $option );

		$this->assertTrue( $this->features->is_enabled( Features::GOOGLE_TAG_GATEWAY ) );
		$this->assertFalse( $this->features->is_enabled( 'test_feature' ) );
	}
}
