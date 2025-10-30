<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\Features;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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

	/** @var MockObject|OptionsInterface $options */
	protected $options;

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

	protected const TEST_OPTION_DATA = [
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

		$this->options  = $this->createMock( OptionsInterface::class );
		$this->features = new Features();
		$this->features->set_options_object( $this->options );
	}

	public function test_update_formats_features_array_correctly() {
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::WCS_FEATURE_FLAGS,
				self::TEST_OPTION_DATA,
			);

		$this->features->update( self::TEST_INPUT_DATA );
	}

	public function test_invalid_feature_is_not_included_on_update() {
		$input = self::TEST_INPUT_DATA;

		$input['features']['invalid_feature'] = [
			'enabled'    => true,
			'attributes' => [
				'enabled'    => true,
				'default'    => true,
				'percentage' => 100,
			],
		];

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::WCS_FEATURE_FLAGS,
				self::TEST_OPTION_DATA,
			);

		$this->features->update( $input );
	}

	public function test_is_enabled_returns_correct_value_for_feature() {
		$option = self::TEST_OPTION_DATA;

		$option['features']['test_feature'] = [
			'enabled' => false,
		];

		$this->options->expects( $this->any() )
			->method( 'get' )
			->willReturn( $option );

		$this->assertTrue( $this->features->is_enabled( Features::GOOGLE_TAG_GATEWAY ) );
		$this->assertFalse( $this->features->is_enabled( 'test_feature' ) );
	}
}
