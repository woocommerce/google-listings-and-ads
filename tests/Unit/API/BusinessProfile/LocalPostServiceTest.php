<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostImageService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\MissingLocalPostIdException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Request;

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

	/** @var MockObject|LocalPostImageService $image_service */
	protected $image_service;

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var MockObject|WPML $wpml */
	protected $wpml;

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

		$this->image_service    = $this->createMock( LocalPostImageService::class );
		$this->location_service = $this->createMock( LocationService::class );

		$this->wpml = $this->createMock( WPML::class );
		$this->wpml->method( 'get_post_language' )->willReturn( '' );

		$this->service = new LocalPostService( $this->client, $this->wp, $this->iso_utility, $this->image_service, $this->location_service, $this->wpml );
		$this->service->set_options_object( $this->options );
	}

	public function tearDown(): void {
		unset( $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SHARE_STATUS_FIELD_NAME ] );
		unset( $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SYNC_OUTCOME_FIELD_NAME ] );
		unregister_meta_key( 'post', LocalPostService::SUMMARY_META_KEY );
		unregister_meta_key( 'post', LocalPostService::RESULT_META_KEY );
		unregister_meta_key( 'post', LocalPostService::SYNC_OUTCOME_META_KEY );
		$GLOBALS['wp_rest_server'] = null;

		parent::tearDown();
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

	public function test_create_truncates_a_multibyte_summary_by_character_not_by_byte() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, str_repeat( 'é', 1600 ) );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertSame( 1500, mb_strlen( $body['summary'] ) );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );
	}

	public function test_create_throws_when_no_location_is_connected() {
		$post = $this->factory()->post->create_and_get();

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )->with( OptionsInterface::BUSINESS_PROFILE, [] )->willReturn( [] );

		$service = new LocalPostService( $this->client, $this->wp, $this->iso_utility, $this->image_service, $this->location_service, $this->wpml );
		$service->set_options_object( $options );

		$this->client->expects( $this->never() )->method( 'post' );
		$this->location_service->expects( $this->never() )->method( 'is_location_unverified' );
		$this->expectException( Exception::class );

		$service->create( $post );
	}

	public function test_create_throws_when_google_returns_no_usable_post_identifier() {
		$post = $this->factory()->post->create_and_get();

		$this->client->method( 'post' )->willReturn( [ 'searchUrl' => 'https://g.page/r/abc' ] );

		try {
			$this->service->create( $post );
			$this->fail( 'Expected an exception when Google returns no post identifier.' );
		} catch ( MissingLocalPostIdException $e ) {
			$this->assertSame( '', get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true ) );
		}
	}

	/**
	 * @dataProvider data_provider_image_urls
	 *
	 * @param string $image_url
	 * @param bool   $expected_omitted
	 */
	public function test_create_reports_an_omitted_image_when_google_returns_no_post_identifier( string $image_url, bool $expected_omitted ) {
		$post = $this->factory()->post->create_and_get();
		set_post_thumbnail( $post, $this->create_image_attachment( $post->ID ) );
		$this->image_service->method( 'get_image_url' )->willReturn( $image_url );
		$this->client->method( 'post' )->willReturn( [ 'searchUrl' => 'https://g.page/r/abc' ] );

		try {
			$this->service->create( $post );
			$this->fail( 'Expected an exception when Google returns no post identifier.' );
		} catch ( MissingLocalPostIdException $e ) {
			$this->assertSame( $expected_omitted, $e->is_image_omitted() );
		}
	}

	/**
	 * @return array
	 */
	public function data_provider_image_urls(): array {
		return [
			'image left out' => [ '', true ],
			'image sent'     => [ 'https://example.com/image.jpg', false ],
		];
	}

	public function test_create_stores_nothing_when_the_api_call_fails() {
		$post = $this->factory()->post->create_and_get();

		$this->client->method( 'post' )->willThrowException(
			new BusinessProfileApiException( 503, [], __METHOD__ )
		);

		try {
			$this->service->create( $post );
			$this->fail( 'Expected a BusinessProfileApiException.' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( '', get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true ) );
		}
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

		$service = new LocalPostService( $this->client, $wp, $iso_utility, $this->image_service, $this->location_service, $this->wpml );
		$service->set_options_object( $this->options );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertSame( 'fr-FR', $body['languageCode'] );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$service->create( $post );
	}

	public function test_create_uses_the_posts_own_wpml_language_over_the_site_locale() {
		$post = $this->factory()->post->create_and_get();

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'get_post_language' )->with( $post->ID )->willReturn( 'es' );

		$iso_utility = $this->createMock( ISOUtility::class );
		$iso_utility->method( 'wp_locale_to_bcp47' )->with( 'es' )->willReturn( 'es' );

		$service = new LocalPostService( $this->client, $this->wp, $iso_utility, $this->image_service, $this->location_service, $wpml );
		$service->set_options_object( $this->options );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertSame( 'es', $body['languageCode'] );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$service->create( $post );
	}

	public function test_create_falls_back_to_site_locale_when_wpml_has_no_language_for_the_post() {
		$post = $this->factory()->post->create_and_get();

		$iso_utility = $this->createMock( ISOUtility::class );
		$iso_utility->expects( $this->once() )->method( 'wp_locale_to_bcp47' )->with( 'en_US' )->willReturn( 'en-US' );

		$service = new LocalPostService( $this->client, $this->wp, $iso_utility, $this->image_service, $this->location_service, $this->wpml );
		$service->set_options_object( $this->options );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertSame( 'en-US', $body['languageCode'] );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$service->create( $post );
	}

	public function test_create_omits_media_when_the_post_has_no_usable_featured_image() {
		$post = $this->factory()->post->create_and_get();
		$this->image_service->method( 'get_image_url' )->with( $post )->willReturn( '' );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertArrayNotHasKey( 'media', $body );

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );
	}

	public function test_create_includes_the_image_google_accepts_as_media() {
		$post = $this->factory()->post->create_and_get();
		$this->image_service->method( 'get_image_url' )->with( $post )->willReturn( 'https://example.com/image-768x512.jpg' );

		$this->client->method( 'post' )->willReturnCallback(
			function ( string $path, array $body ) {
				$this->assertArrayHasKey( 'media', $body );
				$this->assertSame( 'https://example.com/image-768x512.jpg', $body['media'][0]['sourceUrl'] ?? null );

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

	public function test_delete_sends_the_request_for_the_stored_account_location_post_id() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			]
		);

		$this->client->expects( $this->once() )
			->method( 'delete' )
			->with( 'accounts/111/locations/222/localPosts/333' )
			->willReturn( [] );

		$this->service->delete( $post->ID );
	}

	public function test_delete_clears_the_stored_result_on_success() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			]
		);

		$this->client->method( 'delete' )->willReturn( [] );

		$this->service->delete( $post->ID );

		$this->assertSame( '', get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true ) );
	}

	/**
	 * @dataProvider data_provider_incomplete_results
	 *
	 * @param array $result
	 */
	public function test_has_complete_result_is_false_for_a_partial_triple( array $result ) {
		$this->assertFalse( LocalPostService::has_complete_result( $result ) );
	}

	/**
	 * @return array
	 */
	public function data_provider_incomplete_results(): array {
		return [
			'empty'               => [ [] ],
			'missing account_id'  => [
				[
					'location_id' => '222',
					'post_id'     => '333',
				],
			],
			'missing location_id' => [
				[
					'account_id' => '111',
					'post_id'    => '333',
				],
			],
			'missing post_id'     => [
				[
					'account_id'  => '111',
					'location_id' => '222',
				],
			],
		];
	}

	public function test_has_complete_result_is_true_for_the_full_triple() {
		$this->assertTrue(
			LocalPostService::has_complete_result(
				[
					'account_id'  => '111',
					'location_id' => '222',
					'post_id'     => '333',
				]
			)
		);
	}

	public function test_delete_does_nothing_when_the_stored_result_is_missing_an_id() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'location_id' => '222',
				'post_id'     => '333',
			]
		);

		$this->client->expects( $this->never() )->method( 'delete' );

		$this->service->delete( $post->ID );
	}

	public function test_delete_does_nothing_when_no_result_is_stored() {
		$post = $this->factory()->post->create_and_get();

		$this->client->expects( $this->never() )->method( 'delete' );

		$this->service->delete( $post->ID );
	}

	public function test_delete_treats_not_found_as_success() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			]
		);

		$this->client->method( 'delete' )->willThrowException(
			new BusinessProfileApiException(
				404,
				[
					'error' => [
						'code'    => 404,
						'message' => 'Requested entity was not found.',
						'status'  => 'NOT_FOUND',
					],
				],
				__METHOD__
			)
		);

		$this->service->delete( $post->ID );

		$this->assertSame( '', get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true ) );
	}

	public function test_delete_rethrows_a_bare_404_without_googles_not_found_status() {
		$post          = $this->factory()->post->create_and_get();
		$stored_result = [
			'account_id'  => '111',
			'location_id' => '222',
			'post_id'     => '333',
			'search_url'  => 'https://g.page/r/abc',
		];
		update_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, $stored_result );

		// A proxy-level 404 (a bad path, no DELETE support on that route) carries no Google
		// error envelope at all -- it must not be mistaken for Google's own NOT_FOUND.
		$this->client->method( 'delete' )->willThrowException(
			new BusinessProfileApiException( 404, [ 'message' => 'Unsupported path' ], __METHOD__ )
		);

		try {
			$this->service->delete( $post->ID );
			$this->fail( 'Expected a BusinessProfileApiException.' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( $stored_result, get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true ) );
		}
	}

	public function test_delete_rethrows_a_non_not_found_error_and_keeps_the_stored_result() {
		$post          = $this->factory()->post->create_and_get();
		$stored_result = [
			'account_id'  => '111',
			'location_id' => '222',
			'post_id'     => '333',
			'search_url'  => 'https://g.page/r/abc',
		];
		update_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, $stored_result );

		$this->client->method( 'delete' )->willThrowException(
			new BusinessProfileApiException( 503, [], __METHOD__ )
		);

		try {
			$this->service->delete( $post->ID );
			$this->fail( 'Expected a BusinessProfileApiException.' );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertSame( $stored_result, get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true ) );
		}
	}

	public function test_the_container_resolves_and_registers_the_service_at_boot() {
		$resolved = woogle_get_container()->get( LocalPostService::class );

		$this->assertInstanceOf( LocalPostService::class, $resolved );
		$this->assertSame( 10, has_action( 'init', [ $resolved, 'register_post_meta' ] ) );
	}

	public function test_register_hooks_register_post_meta_to_init() {
		$this->service->register();

		$this->assertSame( 10, has_action( 'init', [ $this->service, 'register_post_meta' ] ) );
	}

	public function test_register_post_meta_registers_the_summary_key_as_rest_visible_and_writable() {
		$this->service->register_post_meta();

		$summary_meta = get_registered_meta_keys( 'post', 'post' )[ LocalPostService::SUMMARY_META_KEY ] ?? null;

		$this->assertNotNull( $summary_meta );
		$this->assertTrue( $summary_meta['single'] );
		$this->assertSame( 'string', $summary_meta['type'] );
		$this->assertNotFalse( $summary_meta['show_in_rest'] );
		$this->assertIsCallable( $summary_meta['show_in_rest']['prepare_callback'] );
		$this->assertSame( 'sanitize_textarea_field', $summary_meta['sanitize_callback'] );
	}

	public function test_register_post_meta_registers_the_result_key_as_not_rest_visible() {
		$this->service->register_post_meta();

		$result_meta = get_registered_meta_keys( 'post', 'post' )[ LocalPostService::RESULT_META_KEY ] ?? null;

		$this->assertNotNull( $result_meta );
		$this->assertTrue( $result_meta['single'] );
		$this->assertSame( 'object', $result_meta['type'] );
		$this->assertFalse( $result_meta['show_in_rest'] );
	}

	public function test_summary_field_is_redacted_from_a_default_context_post_response() {
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		update_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, 'secret draft text' );
		$this->service->register_post_meta();

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post->ID ) );

		$this->assertSame( '', $response->get_data()['meta'][ LocalPostService::SUMMARY_META_KEY ] );
	}

	public function test_summary_field_is_visible_on_an_edit_context_post_response() {
		$this->login_as_administrator();
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		update_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, 'secret draft text' );
		$this->service->register_post_meta();

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post->ID );
		$request->set_param( 'context', 'edit' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 'secret draft text', $response->get_data()['meta'][ LocalPostService::SUMMARY_META_KEY ] );
	}

	public function test_summary_field_prepare_callback_checks_capability_independent_of_context() {
		// The collection route only checks the generic `edit_posts` capability, not a per-post
		// one, so a user who can edit *their own* posts could still reach this callback for a
		// post they can't individually edit -- test the callback's own guard directly, the same
		// way test_share_status_field_is_null_for_a_user_who_cannot_edit_the_post does.
		$post = $this->factory()->post->create_and_get();
		$this->service->register_post_meta();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$callback = get_registered_meta_keys( 'post', 'post' )[ LocalPostService::SUMMARY_META_KEY ]['show_in_rest']['prepare_callback'];
		$request  = new WP_REST_Request();
		$request->set_param( 'id', $post->ID );
		$request->set_param( 'context', 'edit' );

		$this->assertSame( '', $callback( 'secret draft text', $request ) );
	}

	public function test_register_hooks_register_rest_fields_to_rest_api_init() {
		$this->service->register();

		$this->assertSame( 10, has_action( 'rest_api_init', [ $this->service, 'register_rest_fields' ] ) );
	}

	public function test_register_rest_fields_registers_a_read_only_edit_context_field() {
		$this->service->register_rest_fields();

		$field = $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SHARE_STATUS_FIELD_NAME ];

		$this->assertNull( $field['update_callback'] );
		$this->assertTrue( $field['schema']['readonly'] );
		$this->assertSame( [ 'edit' ], $field['schema']['context'] );
		$this->assertSame( [ 'object', 'null' ], $field['schema']['type'] );
		$this->assertArrayHasKey( 'is_shared', $field['schema']['properties'] );
		$this->assertArrayHasKey( 'search_url', $field['schema']['properties'] );
	}

	public function test_share_status_field_reports_not_shared_when_no_result_is_stored() {
		$this->login_as_administrator();
		$post = $this->factory()->post->create_and_get();
		$this->service->register_rest_fields();

		$value = $this->get_share_status_field_value( $post->ID );

		$this->assertSame(
			[
				'is_shared'  => false,
				'search_url' => '',
			],
			$value
		);
	}

	public function test_share_status_field_reports_shared_with_the_search_url_once_a_result_is_stored() {
		$this->login_as_administrator();
		$post = $this->factory()->post->create_and_get();
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			]
		);
		$this->service->register_rest_fields();

		$value = $this->get_share_status_field_value( $post->ID );

		$this->assertSame(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			],
			$value
		);
	}

	public function test_share_status_field_is_null_for_a_user_who_cannot_edit_the_post() {
		$post = $this->factory()->post->create_and_get();
		$this->service->register_rest_fields();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertNull( $this->get_share_status_field_value( $post->ID ) );
	}

	public function test_share_status_field_is_absent_from_a_default_context_post_response() {
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$this->service->register_rest_fields();

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post->ID ) );

		$this->assertArrayNotHasKey( LocalPostService::SHARE_STATUS_FIELD_NAME, $response->get_data() );
	}

	public function test_share_status_field_is_present_on_an_edit_context_post_response() {
		$this->login_as_administrator();
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			]
		);
		$this->service->register_rest_fields();

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post->ID );
		$request->set_param( 'context', 'edit' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			],
			$response->get_data()[ LocalPostService::SHARE_STATUS_FIELD_NAME ]
		);
	}

	public function test_get_share_status_reports_not_shared_when_no_result_is_stored() {
		$post = $this->factory()->post->create_and_get();

		$this->assertSame(
			[
				'is_shared'  => false,
				'search_url' => '',
			],
			$this->service->get_share_status( $post->ID )
		);
	}

	public function test_get_share_status_reports_shared_without_a_search_url_until_google_provides_one() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, [ 'post_id' => '333' ] );

		$this->assertSame(
			[
				'is_shared'  => true,
				'search_url' => '',
			],
			$this->service->get_share_status( $post->ID )
		);
	}

	public function test_get_share_status_reports_shared_with_the_search_url_once_a_result_is_stored() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'post_id'    => '333',
				'search_url' => 'https://g.page/r/abc',
			]
		);

		$this->assertSame(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			],
			$this->service->get_share_status( $post->ID )
		);
	}

	public function test_create_stores_a_success_outcome_once_google_accepts_the_post() {
		$post = $this->factory()->post->create_and_get();
		$this->client->method( 'post' )->willReturn( [ 'name' => 'accounts/111/locations/222/localPosts/333' ] );

		$this->service->create( $post );

		$this->assertSame(
			[
				'outcome'       => SyncOutcome::SUCCESS,
				'message'       => '',
				'image_omitted' => false,
			],
			$this->service->get_sync_outcome( $post->ID )
		);
	}

	public function test_create_stores_pending_verification_once_google_accepts_a_post_for_an_unverified_location() {
		$post = $this->factory()->post->create_and_get();
		$this->location_service->method( 'is_location_unverified' )->willReturn( true );
		$this->client->method( 'post' )->willReturn( [ 'name' => 'accounts/111/locations/222/localPosts/333' ] );

		$this->service->create( $post );

		$this->assertSame(
			[
				'outcome'       => SyncOutcome::SENT_PENDING_VERIFICATION,
				'message'       => '',
				'image_omitted' => false,
			],
			$this->service->get_sync_outcome( $post->ID )
		);
	}

	public function test_create_checks_the_connected_location_once_before_sending() {
		$post  = $this->factory()->post->create_and_get();
		$calls = [];

		$this->location_service->expects( $this->once() )
			->method( 'is_location_unverified' )
			->with( '222' )
			->willReturnCallback(
				function () use ( &$calls ) {
					$calls[] = 'check';

					return false;
				}
			);
		$this->client->method( 'post' )->willReturnCallback(
			function () use ( &$calls ) {
				$calls[] = 'send';

				return [ 'name' => 'accounts/111/locations/222/localPosts/333' ];
			}
		);

		$this->service->create( $post );

		$this->assertSame( [ 'check', 'send' ], $calls );
	}

	/**
	 * @dataProvider data_provider_location_unverified
	 *
	 * @param bool   $location_unverified
	 * @param string $expected_outcome
	 */
	public function test_create_reports_the_outcome_when_google_returns_no_post_identifier( bool $location_unverified, string $expected_outcome ) {
		$post = $this->factory()->post->create_and_get();
		$this->location_service->method( 'is_location_unverified' )->willReturn( $location_unverified );
		$this->client->method( 'post' )->willReturn( [ 'searchUrl' => 'https://g.page/r/abc' ] );

		try {
			$this->service->create( $post );
			$this->fail( 'Expected an exception when Google returns no post identifier.' );
		} catch ( MissingLocalPostIdException $e ) {
			$this->assertSame( $expected_outcome, $e->get_outcome() );
		}
	}

	/**
	 * @return array
	 */
	public function data_provider_location_unverified(): array {
		return [
			'unverified'     => [ true, SyncOutcome::SENT_PENDING_VERIFICATION ],
			'not unverified' => [ false, SyncOutcome::SUCCESS ],
		];
	}

	public function test_create_records_a_featured_image_left_out_because_no_version_was_usable() {
		$post = $this->factory()->post->create_and_get();
		set_post_thumbnail( $post, $this->create_image_attachment( $post->ID ) );
		$this->image_service->method( 'get_image_url' )->willReturn( '' );
		$this->client->method( 'post' )->willReturn( [ 'name' => 'accounts/111/locations/222/localPosts/333' ] );

		$this->service->create( $post );

		$this->assertTrue( $this->service->get_sync_outcome( $post->ID )['image_omitted'] );
	}

	public function test_create_does_not_record_an_omitted_image_when_the_featured_image_was_sent() {
		$post = $this->factory()->post->create_and_get();
		set_post_thumbnail( $post, $this->create_image_attachment( $post->ID ) );
		$this->image_service->method( 'get_image_url' )->willReturn( 'https://example.com/image.jpg' );
		$this->client->method( 'post' )->willReturn( [ 'name' => 'accounts/111/locations/222/localPosts/333' ] );

		$this->service->create( $post );

		$this->assertFalse( $this->service->get_sync_outcome( $post->ID )['image_omitted'] );
	}

	public function test_create_stores_no_outcome_when_the_api_call_fails() {
		$post = $this->factory()->post->create_and_get();
		$this->client->method( 'post' )->willThrowException( new BusinessProfileApiException( 503, [], __METHOD__ ) );

		try {
			$this->service->create( $post );
		} catch ( BusinessProfileApiException $e ) {
			$this->assertNull( $this->service->get_sync_outcome( $post->ID ) );
		}
	}

	public function test_register_rest_fields_exposes_whether_the_image_was_omitted() {
		$this->service->register_rest_fields();

		$field = $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SYNC_OUTCOME_FIELD_NAME ];

		$this->assertSame( 'boolean', $field['schema']['properties']['image_omitted']['type'] );
	}

	public function test_register_post_meta_registers_the_sync_outcome_key_as_not_rest_visible() {
		$this->service->register_post_meta();

		$outcome_meta = get_registered_meta_keys( 'post', 'post' )[ LocalPostService::SYNC_OUTCOME_META_KEY ] ?? null;

		$this->assertNotNull( $outcome_meta );
		$this->assertTrue( $outcome_meta['single'] );
		$this->assertSame( 'object', $outcome_meta['type'] );
		$this->assertFalse( $outcome_meta['show_in_rest'] );
	}

	public function test_get_sync_outcome_is_null_for_a_post_never_sent() {
		$post = $this->factory()->post->create_and_get();

		$this->assertNull( $this->service->get_sync_outcome( $post->ID ) );
	}

	/**
	 * @dataProvider data_provider_sync_outcomes
	 *
	 * @param string $outcome
	 */
	public function test_set_sync_outcome_stores_every_outcome( string $outcome ) {
		$post = $this->factory()->post->create_and_get();

		$this->service->set_sync_outcome( $post->ID, $outcome, 'Message from Google.' );

		$this->assertSame(
			[
				'outcome'       => $outcome,
				'message'       => 'Message from Google.',
				'image_omitted' => false,
			],
			$this->service->get_sync_outcome( $post->ID )
		);
	}

	public function data_provider_sync_outcomes(): array {
		return array_map(
			static function ( string $outcome ) {
				return [ $outcome ];
			},
			SyncOutcome::ALL
		);
	}

	public function test_set_sync_outcome_stores_googles_message_unchanged() {
		$post    = $this->factory()->post->create_and_get();
		$message = 'Field \\"summary\\" isn\'t <valid> & can\'t be "empty".';

		$this->service->set_sync_outcome( $post->ID, SyncOutcome::CONTENT_REJECTION, $message );

		$this->assertSame( $message, $this->service->get_sync_outcome( $post->ID )['message'] );
	}

	public function test_set_sync_outcome_replaces_the_previous_outcome() {
		$post = $this->factory()->post->create_and_get();

		$this->service->set_sync_outcome( $post->ID, SyncOutcome::REQUEST_FAILURE, 'Unable to reach Google.' );
		$this->service->set_sync_outcome( $post->ID, SyncOutcome::SUCCESS );

		$this->assertSame(
			[
				'outcome'       => SyncOutcome::SUCCESS,
				'message'       => '',
				'image_omitted' => false,
			],
			$this->service->get_sync_outcome( $post->ID )
		);
	}

	public function test_set_sync_outcome_throws_on_an_unknown_outcome() {
		$post = $this->factory()->post->create_and_get();

		$this->expectException( InvalidArgumentException::class );

		$this->service->set_sync_outcome( $post->ID, 'unknown' );
	}

	public function test_get_sync_outcome_is_null_when_the_stored_outcome_is_unknown() {
		$post = $this->factory()->post->create_and_get();
		update_post_meta( $post->ID, LocalPostService::SYNC_OUTCOME_META_KEY, [ 'outcome' => 'unknown' ] );

		$this->assertNull( $this->service->get_sync_outcome( $post->ID ) );
	}

	public function test_register_rest_fields_registers_a_read_only_edit_context_sync_outcome_field() {
		$this->service->register_rest_fields();

		$field = $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SYNC_OUTCOME_FIELD_NAME ];

		$this->assertNull( $field['update_callback'] );
		$this->assertTrue( $field['schema']['readonly'] );
		$this->assertSame( [ 'edit' ], $field['schema']['context'] );
		$this->assertSame( [ 'object', 'null' ], $field['schema']['type'] );
		$this->assertSame( SyncOutcome::ALL, $field['schema']['properties']['outcome']['enum'] );
	}

	public function test_sync_outcome_field_is_present_on_an_edit_context_post_response() {
		$this->login_as_administrator();
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$this->service->set_sync_outcome( $post->ID, SyncOutcome::ACCOUNT_RESTRICTION, 'The profile is suspended.' );
		$this->service->register_rest_fields();

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post->ID );
		$request->set_param( 'context', 'edit' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame(
			[
				'outcome'       => SyncOutcome::ACCOUNT_RESTRICTION,
				'message'       => 'The profile is suspended.',
				'image_omitted' => false,
			],
			$response->get_data()[ LocalPostService::SYNC_OUTCOME_FIELD_NAME ]
		);
	}

	public function test_sync_outcome_field_is_absent_from_a_default_context_post_response() {
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$this->service->set_sync_outcome( $post->ID, SyncOutcome::REQUEST_FAILURE );
		$this->service->register_rest_fields();

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post->ID ) );

		$this->assertArrayNotHasKey( LocalPostService::SYNC_OUTCOME_FIELD_NAME, $response->get_data() );
	}

	public function test_sync_outcome_field_is_null_for_a_user_who_cannot_edit_the_post() {
		$post = $this->factory()->post->create_and_get();
		$this->service->set_sync_outcome( $post->ID, SyncOutcome::REQUEST_FAILURE );
		$this->service->register_rest_fields();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$callback = $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SYNC_OUTCOME_FIELD_NAME ]['get_callback'];

		$this->assertNull( $callback( [ 'id' => $post->ID ], LocalPostService::SYNC_OUTCOME_FIELD_NAME, new WP_REST_Request(), 'post' ) );
	}

	/**
	 * Create an image attachment for a post.
	 *
	 * @param int $post_id
	 *
	 * @return int The attachment ID.
	 */
	private function create_image_attachment( int $post_id ): int {
		return $this->factory()->attachment->create_object(
			'image.jpg',
			$post_id,
			[ 'post_mime_type' => 'image/jpeg' ]
		);
	}

	/**
	 * Invoke the registered field's own get_callback the way WP core's REST controller does.
	 *
	 * @param int $post_id
	 *
	 * @return mixed
	 */
	private function get_share_status_field_value( int $post_id ) {
		$callback = $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SHARE_STATUS_FIELD_NAME ]['get_callback'];

		return $callback( [ 'id' => $post_id ], LocalPostService::SHARE_STATUS_FIELD_NAME, new WP_REST_Request(), 'post' );
	}
}
