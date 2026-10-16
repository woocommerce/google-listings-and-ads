<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInput;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductInputPatch;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductInputPatchTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models
 */
class ProductInputPatchTest extends UnitTest {

	public function test_exposes_input_and_update_mask() {
		$input = new ProductInput( 'sku42', 'en', 'US', [ 'title' => 'New title' ] );
		$mask  = [ 'productAttributes.title', 'productAttributes.price' ];

		$patch = new ProductInputPatch( $input, $mask );

		$this->assertSame( $input, $patch->get_input() );
		$this->assertSame( $mask, $patch->get_update_mask() );
	}

	public function test_accepts_empty_update_mask() {
		$patch = new ProductInputPatch( new ProductInput( 'sku42', 'en', 'US' ), [] );

		$this->assertSame( [], $patch->get_update_mask() );
	}
}
