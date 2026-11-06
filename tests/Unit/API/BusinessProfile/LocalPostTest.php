<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPost;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPostTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class LocalPostTest extends UnitTest {

	public function test_to_array_serializes_a_post_with_a_featured_image() {
		$post = new LocalPost( 'Hello world', 'en-US', 'https://example.com/post/', 'https://example.com/image.jpg' );

		$this->assertSame(
			[
				'languageCode' => 'en-US',
				'summary'      => 'Hello world',
				'topicType'    => 'STANDARD',
				'callToAction' => [
					'actionType' => 'LEARN_MORE',
					'url'        => 'https://example.com/post/',
				],
				'media'        => [
					[
						'mediaFormat' => 'PHOTO',
						'sourceUrl'   => 'https://example.com/image.jpg',
					],
				],
			],
			$post->to_array()
		);
	}

	public function test_to_array_omits_media_when_there_is_no_featured_image() {
		$post = new LocalPost( 'Hello world', 'en-US', 'https://example.com/post/' );

		$data = $post->to_array();

		$this->assertArrayNotHasKey( 'media', $data );
	}

	public function test_from_array_parses_a_response_including_name_and_search_url() {
		$post = LocalPost::from_array(
			[
				'name'         => 'accounts/1/locations/2/localPosts/3',
				'languageCode' => 'en-US',
				'summary'      => 'Hello world',
				'callToAction' => [
					'actionType' => 'LEARN_MORE',
					'url'        => 'https://example.com/post/',
				],
				'media'        => [
					[
						'mediaFormat' => 'PHOTO',
						'googleUrl'   => 'https://lh3.googleusercontent.com/abc',
					],
				],
				'searchUrl'    => 'https://g.page/r/abc',
			]
		);

		$this->assertSame( 'accounts/1/locations/2/localPosts/3', $post->get_name() );
		$this->assertSame( 'Hello world', $post->get_summary() );
		$this->assertSame( 'en-US', $post->get_language_code() );
		$this->assertSame( 'https://example.com/post/', $post->get_call_to_action_url() );
		$this->assertSame( 'https://lh3.googleusercontent.com/abc', $post->get_media_url() );
		$this->assertSame( 'https://g.page/r/abc', $post->get_search_url() );
	}

	public function test_from_array_handles_a_response_with_no_media() {
		$post = LocalPost::from_array(
			[
				'name'         => 'accounts/1/locations/2/localPosts/3',
				'languageCode' => 'en-US',
				'summary'      => 'Hello world',
				'callToAction' => [
					'actionType' => 'LEARN_MORE',
					'url'        => 'https://example.com/post/',
				],
			]
		);

		$this->assertSame( '', $post->get_media_url() );
	}

	public function test_get_name_and_search_url_are_null_before_a_response() {
		$post = new LocalPost( 'Hello world', 'en-US', 'https://example.com/post/' );

		$this->assertNull( $post->get_name() );
		$this->assertNull( $post->get_search_url() );
	}
}
