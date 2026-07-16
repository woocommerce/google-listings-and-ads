<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ItemLevelIssue;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class ItemLevelIssueTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models
 */
class ItemLevelIssueTest extends UnitTest {

	public function test_from_array_populates_all_fields() {
		$issue = ItemLevelIssue::from_array(
			[
				'code'                => 'missing_image',
				'description'         => 'Missing image link',
				'detail'              => 'The image_link attribute is required',
				'documentation'       => 'https://support.google.com/...',
				'resolution'          => 'merchant_action',
				'severity'            => 'DISAPPROVED',
				'applicableCountries' => [ 'US', 'CA' ],
			]
		);

		$this->assertSame( 'missing_image', $issue->get_code() );
		$this->assertSame( 'Missing image link', $issue->get_description() );
		$this->assertSame( 'The image_link attribute is required', $issue->get_detail() );
		$this->assertSame( 'https://support.google.com/...', $issue->get_documentation() );
		$this->assertSame( 'merchant_action', $issue->get_resolution() );
		$this->assertSame( 'DISAPPROVED', $issue->get_severity() );
		$this->assertSame( [ 'US', 'CA' ], $issue->get_applicable_countries() );
	}

	public function test_from_array_with_missing_fields_defaults_to_null() {
		$issue = ItemLevelIssue::from_array( [] );

		$this->assertNull( $issue->get_code() );
		$this->assertNull( $issue->get_description() );
		$this->assertNull( $issue->get_detail() );
		$this->assertNull( $issue->get_documentation() );
		$this->assertNull( $issue->get_resolution() );
		$this->assertNull( $issue->get_severity() );
		$this->assertSame( [], $issue->get_applicable_countries() );
	}
}
