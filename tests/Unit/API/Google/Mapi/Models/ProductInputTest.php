<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductInputTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models
 */
class ProductInputTest extends UnitTest {

	public function test_to_array_serializes_writable_fields() {
		$input = new ProductInput(
			'sku42',
			'en',
			'US',
			[ 'title' => 'Test product' ],
			[
				[
					'name'  => 'custom',
					'value' => 'x',
				],
			]
		);

		$this->assertSame(
			[
				'offerId'           => 'sku42',
				'contentLanguage'   => 'en',
				'feedLabel'         => 'US',
				'productAttributes' => [ 'title' => 'Test product' ],
				'customAttributes'  => [
					[
						'name'  => 'custom',
						'value' => 'x',
					],
				],
			],
			$input->to_array()
		);
	}

	public function test_to_array_omits_empty_attribute_collections() {
		$input = new ProductInput( 'sku42', 'en', 'US' );

		$this->assertSame(
			[
				'offerId'         => 'sku42',
				'contentLanguage' => 'en',
				'feedLabel'       => 'US',
			],
			$input->to_array()
		);
	}

	public function test_from_array_parses_response_including_name() {
		$input = ProductInput::from_array(
			[
				'name'              => 'accounts/123/productInputs/online~en~US~sku42',
				'offerId'           => 'sku42',
				'contentLanguage'   => 'en',
				'feedLabel'         => 'US',
				'productAttributes' => [ 'title' => 'Test product' ],
				'customAttributes'  => [
					[
						'name'  => 'custom',
						'value' => 'x',
					],
				],
			]
		);

		$this->assertSame( 'accounts/123/productInputs/online~en~US~sku42', $input->get_name() );
		$this->assertSame( 'sku42', $input->get_offer_id() );
		$this->assertSame( 'en', $input->get_content_language() );
		$this->assertSame( 'US', $input->get_feed_label() );
		$this->assertSame( [ 'title' => 'Test product' ], $input->get_attributes() );
		$this->assertSame(
			[
				[
					'name'  => 'custom',
					'value' => 'x',
				],
			],
			$input->get_custom_attributes()
		);
	}

	public function test_round_trips_through_to_array_and_from_array() {
		$input = new ProductInput(
			'sku42',
			'en',
			'US',
			[ 'title' => 'Test product' ]
		);

		$round_tripped = ProductInput::from_array( $input->to_array() );

		$this->assertSame( $input->to_array(), $round_tripped->to_array() );
	}

	public function test_get_name_is_null_before_response() {
		$input = new ProductInput( 'sku42', 'en', 'US' );

		$this->assertNull( $input->get_name() );
	}
}
