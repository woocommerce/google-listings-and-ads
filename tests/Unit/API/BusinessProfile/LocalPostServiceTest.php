<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPostServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class LocalPostServiceTest extends UnitTest {

	/** @var MockObject|BusinessProfileApiClient $client */
	protected $client;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var MockObject|ISOUtility $iso_utility */
	protected $iso_utility;

	/** @var LocalPostService $service */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client = $this->createMock( BusinessProfileApiClient::class );
		$this->wp     = $this->createMock( WP::class );
		$this->wp->method( 'get_locale' )->willReturn( 'en_US' );

		$this->iso_utility = $this->createMock( ISOUtility::class );
		$this->iso_utility->method( 'wp_locale_to_bcp47' )->willReturn( 'en-US' );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get' )->with( OptionsInterface::BUSINESS_PROFILE, [] )->willReturn(
			[
				'account_id'  => '111',
				'location_id' => '222',
			]
		);

		$this->service = new LocalPostService( $this->client, $this->wp, $this->iso_utility );
		$this->service->set_options_object( $this->options );
	}

	public function test_create_sends_the_request_to_the_connected_account_and_location() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, 'Hello world' );

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( 'accounts/111/locations/222/localPosts', $this->anything() )
			->willReturn( [ 'name' => 'accounts/111/locations/222/localPosts/333' ] );

		$this->service->create( $post );
	}

	public function test_create_reads_the_summary_as_is_with_no_recomputation() {
		$post = $this->factory()->post->create_and_get( [ 'post_excerpt' => 'Not this' ] );
		update_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, 'Exactly this' );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertSame( 'Exactly this', $body['summary'] );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );
	}

	public function test_create_truncates_the_summary_at_the_real_character_limit() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, str_repeat( 'a', 1600 ) );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertSame( 1500, strlen( $body['summary'] ) );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );
	}

	public function test_create_sends_the_posts_own_permalink_as_the_call_to_action_url() {
		$post = $this->factory()->post->create_and_get();

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) use ( $post ) {
				$this->assertSame( get_permalink( $post ), $body['callToAction']['url'] );
				$this->assertSame( 'LEARN_MORE', $body['callToAction']['actionType'] );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );
	}

	public function test_create_uses_the_site_locale_normalized_to_bcp47() {
		$post = $this->factory()->post->create_and_get();

		$wp = $this->createMock( WP::class );
		$wp->method( 'get_locale' )->willReturn( 'fr_FR' );

		$iso_utility = $this->createMock( ISOUtility::class );
		$iso_utility->method( 'wp_locale_to_bcp47' )->with( 'fr_FR' )->willReturn( 'fr-FR' );

		$service = new LocalPostService( $this->client, $wp, $iso_utility );
		$service->set_options_object( $this->options );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertSame( 'fr-FR', $body['languageCode'] );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$service->create( $post );
	}

	public function test_create_omits_media_when_the_post_has_no_featured_image() {
		$post = $this->factory()->post->create_and_get();

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertArrayNotHasKey( 'media', $body );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );
	}

	public function test_create_includes_media_when_the_post_has_a_featured_image() {
		$post          = $this->factory()->post->create_and_get();
		$attachment_id = $this->factory()->attachment->create_object(
			'image.jpg',
			$post->ID,
			[ 'post_mime_type' => 'image/jpeg' ]
		);
		set_post_thumbnail( $post, $attachment_id );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) use ( $post ) {
				$this->assertArrayHasKey( 'media', $body );
				$this->assertSame(
					get_the_post_thumbnail_url( $post, 'full' ),
					$body['media'][0]['sourceUrl'] ?? null
				);

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );
	}

	public function test_create_stores_the_account_location_post_id_triple_and_search_url() {
		$post = $this->factory()->post->create_and_get();

		$this->client->method( 'post' )->willReturn(
			[
				'name'      => 'accounts/111/locations/222/localPosts/333',
				'searchUrl' => 'https://g.page/r/abc',
			]
		);

		$this->service->create( $post );

		$this->assertSame(
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			],
			get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true )
		);
	}

	public function test_register_post_meta_registers_both_keys_as_rest_visible() {
		$this->service->register_post_meta();

		$summary_meta = get_registered_meta_keys( 'post', 'post' )[ LocalPostService::SUMMARY_META_KEY ] ?? null;
		$result_meta  = get_registered_meta_keys( 'post', 'post' )[ LocalPostService::RESULT_META_KEY ] ?? null;

		$this->assertNotNull( $summary_meta );
		$this->assertNotNull( $result_meta );
		$this->assertNotFalse( $summary_meta['show_in_rest'] );
		$this->assertNotFalse( $result_meta['show_in_rest'] );
	}
}
