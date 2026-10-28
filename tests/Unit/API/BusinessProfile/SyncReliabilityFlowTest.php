<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\AccountManagementApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessInformationApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostImageService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostPublishTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostSyncNotice;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\VerificationsApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Exception\ConnectException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\Create;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\HandlerStack;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Psr7\Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncReliabilityFlowTest
 *
 * Publishes a shared post through the real publish trigger, post service, location service and
 * notice, with only Google's HTTP responses mocked, and reads what the merchant would see: the
 * block editor's save response and the Classic Editor's notice.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class SyncReliabilityFlowTest extends UnitTest {

	protected const PENDING_MESSAGE     = 'Your Google Business Profile is currently unverified. This post has been submitted and will publish automatically once your profile is verified.';
	protected const UNREACHABLE_MESSAGE = 'The post couldn\'t be published to Google Business Profile. Google couldn\'t be reached. Update the post to try again.';
	protected const RECONNECT_MESSAGE   = 'The post couldn\'t be published to Google Business Profile. Reconnect your Google account, then update the post to try again.';
	protected const REJECTION_MESSAGE   = 'This update was rejected by Google\'s content policies. Please check your email or Google Business Profile dashboard for details and appeal options. To try again, edit the Business Profile text and update the post.';
	protected const RESTRICTION_MESSAGE = 'Your Google Account or Business Profile currently has administrative restrictions that prevent publishing updates. Please log in to your Google Business Profile dashboard to review account alerts and resolve any notices. Once resolved, update the post to try again.';

	/** @var array Requests sent to Google, as `METHOD path`. */
	protected $sent = [];

	/** @var array Queued responses for the verification read. */
	protected $verification_responses = [];

	/** @var array Queued responses for the local post create call. */
	protected $create_responses = [];

	/** @var PostPublishTrigger */
	protected $trigger;

	/** @var PostSyncNotice */
	protected $notice;

	/** @var LocalPostService */
	protected $local_post_service;

	/** @var LocalPostImageService|MockObject */
	protected $image_service;

	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )->willReturnCallback(
			function ( $key, $default_value = null ) {
				if ( OptionsInterface::BUSINESS_PROFILE === $key ) {
					return [
						'account_id'     => '111',
						'location_id'    => '222',
						'location_title' => 'Jane\'s Bakery',
					];
				}

				return $default_value;
			}
		);

		$verifications = new VerificationsApiClient( $this->http_client( 'verification_responses' ), 'https://wcs.test/google/google-gbp-verifications/v1' );
		$posts         = new BusinessProfileApiClient( $this->http_client( 'create_responses' ), 'https://wcs.test/google/google-gbp/v4' );

		$location_service = new LocationService(
			$this->createMock( AccountManagementApiClient::class ),
			$this->createMock( BusinessInformationApiClient::class ),
			$verifications
		);
		$location_service->set_options_object( $options );

		$wp = $this->createMock( WP::class );
		$wp->method( 'get_locale' )->willReturn( 'en_US' );
		$iso_utility = $this->createMock( ISOUtility::class );
		$iso_utility->method( 'wp_locale_to_bcp47' )->willReturn( 'en-US' );

		$this->image_service = $this->createMock( LocalPostImageService::class );
		$this->image_service->method( 'get_image_url' )->willReturn( '' );

		$this->local_post_service = new LocalPostService( $posts, $wp, $iso_utility, $this->image_service, $location_service, $this->createMock( WPML::class ) );
		$this->local_post_service->set_options_object( $options );

		$this->trigger = new PostPublishTrigger( $this->local_post_service, $location_service );
		$this->notice  = new PostSyncNotice( $this->local_post_service, $this->trigger );

		// Only these instances send and keep notices, not the ones the plugin registered at boot.
		remove_all_actions( 'wp_after_insert_post' );
		$this->trigger->register();
		$this->notice->register();

		rest_get_server();
		$this->local_post_service->register_rest_fields();
		$this->notice->register_rest_fields();
	}

	public function tearDown(): void {
		unset(
			$GLOBALS['post'],
			$GLOBALS['wp_rest_additional_fields']['post'][ PostSyncNotice::FIELD_NAME ],
			$GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SHARE_STATUS_FIELD_NAME ],
			$GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SYNC_OUTCOME_FIELD_NAME ]
		);
		$GLOBALS['wp_rest_server'] = null;

		parent::tearDown();
	}

	public function test_block_editor_publish_to_an_unverified_location_sends_and_shows_the_pending_warning() {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/unverified' ) );
		$this->create_responses[]       = $this->created_post();
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertSame(
			[
				'status'  => 'warning',
				'message' => self::PENDING_MESSAGE,
			],
			$data[ PostSyncNotice::FIELD_NAME ]
		);
		$this->assertTrue( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
		$this->assertSame(
			[
				'GET locations/222/VoiceOfMerchantState',
				'POST accounts/111/locations/222/localPosts',
			],
			$this->sent
		);
	}

	public function test_classic_editor_publish_to_an_unverified_location_shows_the_pending_warning_once() {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/unverified' ) );
		$this->create_responses[]       = $this->created_post();
		$post_id                        = $this->create_shared_draft();

		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'publish',
			]
		);

		$this->open_edit_screen( $post_id );

		$this->expectOutputString( '<div class="notice notice-warning is-dismissible"><p>' . esc_html( self::PENDING_MESSAGE ) . '</p></div>' );
		$this->notice->render_notice();
		$this->notice->render_notice();
	}

	public function test_saving_a_post_pending_verification_again_does_not_send_it_again() {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/unverified' ) );
		$this->create_responses[]       = $this->created_post();
		$post_id                        = $this->create_shared_draft();
		$this->publish_over_rest( $post_id );

		$this->assertSame( SyncOutcome::SENT_PENDING_VERIFICATION, $this->local_post_service->get_sync_outcome( $post_id )['outcome'] );

		$this->start_new_request();
		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'title' => 'Edited title' ] );
		$data = rest_do_request( $request )->get_data();

		$this->assertCount( 1, array_filter( $this->sent, [ $this, 'is_create' ] ) );
		$this->assertNull( $data[ PostSyncNotice::FIELD_NAME ] );
	}

	/**
	 * @dataProvider data_provider_restricted_location_fixtures
	 *
	 * @param string $fixture Name of the `voice-of-merchant` proxy fixture.
	 */
	public function test_block_editor_publish_to_a_restricted_location_sends_and_shows_the_restriction_warning( string $fixture ) {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( "accounts/locations/voice-of-merchant/{$fixture}" ) );
		$this->create_responses[]       = $this->created_post();
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertSame(
			[
				'status'  => 'error',
				'message' => self::RESTRICTION_MESSAGE,
			],
			$data[ PostSyncNotice::FIELD_NAME ]
		);
		// Unlike a send-time account restriction, Google did accept this post -- it's the
		// location, not the post, that's the problem, so the post really is shared.
		$this->assertTrue( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
	}

	/**
	 * @return array
	 */
	public function data_provider_restricted_location_fixtures(): array {
		return [
			'suspended'          => [ 'suspended' ],
			'under review'       => [ 'under-review' ],
			'ownership conflict' => [ 'ownership-conflict' ],
		];
	}

	public function test_block_editor_publish_to_a_verified_location_shows_no_notice() {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/eligible' ) );
		$this->create_responses[]       = $this->created_post();
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertNull( $data[ PostSyncNotice::FIELD_NAME ] );
		$this->assertTrue( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
	}

	public function test_a_failed_verification_read_still_sends_the_post_as_a_plain_success() {
		$this->verification_responses[] = $this->json( 503, [ 'error' => [ 'code' => 503 ] ] );
		$this->create_responses[]       = $this->created_post();
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertNull( $data[ PostSyncNotice::FIELD_NAME ] );
		$this->assertTrue( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
	}

	public function test_an_account_restriction_keeps_the_post_published_with_no_success_state_or_link() {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/eligible' ) );
		$this->create_responses[]       = $this->json( 400, $this->proxy_mock( 'local-posts/errors/account-restricted' ) );
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertSame(
			[
				'status'  => 'error',
				'message' => self::RESTRICTION_MESSAGE,
			],
			$data[ PostSyncNotice::FIELD_NAME ]
		);
		$this->assertSame(
			[
				'is_shared'  => false,
				'search_url' => '',
			],
			$data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]
		);
	}

	public function test_a_content_rejection_keeps_the_post_published_with_no_success_state_or_link() {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/eligible' ) );
		$this->create_responses[]       = $this->json(
			400,
			[
				'error' => [
					'code'    => 400,
					'message' => 'Request contains an invalid argument.',
					'status'  => 'INVALID_ARGUMENT',
				],
			]
		);
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertSame( self::REJECTION_MESSAGE, $data[ PostSyncNotice::FIELD_NAME ]['message'] );
		$this->assertFalse( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
		$this->assertSame( '', $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['search_url'] );
	}

	public function test_unreachable_google_keeps_the_post_published_and_tells_the_merchant() {
		$this->verification_responses[] = 'connect';
		$this->create_responses[]       = 'connect';
		$post_id                        = $this->create_shared_draft();
		$before                         = get_post( $post_id );
		$scheduled_before               = $this->scheduled_hooks();

		$response = $this->publish_over_rest_response( $post_id );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertSame( $before->post_title, get_post( $post_id )->post_title );
		$this->assertSame( $before->post_content, get_post( $post_id )->post_content );
		// WordPress itself schedules its pings on publish; nothing else may be added.
		$this->assertSame( [ 'do_pings' ], array_values( array_diff( $this->scheduled_hooks(), $scheduled_before ) ), 'A failed send schedules no background retry.' );
		$this->assertSame(
			[
				'status'  => 'error',
				'message' => self::UNREACHABLE_MESSAGE,
			],
			$data[ PostSyncNotice::FIELD_NAME ]
		);
		$this->assertFalse( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
		$this->assertCount( 1, array_filter( $this->sent, [ $this, 'is_create' ] ) );
	}

	public function test_saving_after_a_failure_retries_once_with_the_toggle_still_on() {
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/eligible' ) );
		$this->create_responses[]       = 'connect';
		$post_id                        = $this->create_shared_draft();
		$this->publish_over_rest( $post_id );

		$this->assertTrue( (bool) get_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true ) );

		$this->start_new_request();
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/eligible' ) );
		$this->create_responses[]       = $this->created_post();

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'title' => 'Edited title' ] );
		$data = rest_do_request( $request )->get_data();

		$this->assertCount( 2, array_filter( $this->sent, [ $this, 'is_create' ] ) );
		$this->assertTrue( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
		$this->assertNull( $data[ PostSyncNotice::FIELD_NAME ] );
	}

	public function test_a_lost_google_grant_keeps_the_post_published_and_asks_the_merchant_to_reconnect() {
		$this->verification_responses[] = AccountReconnect::google_disconnected();
		$this->create_responses[]       = AccountReconnect::google_disconnected();
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertSame(
			[
				'status'  => 'error',
				'message' => self::RECONNECT_MESSAGE,
			],
			$data[ PostSyncNotice::FIELD_NAME ]
		);
		$this->assertSame( SyncOutcome::ACCOUNT_DISCONNECTED, $data[ LocalPostService::SYNC_OUTCOME_FIELD_NAME ]['outcome'] );
		$this->assertFalse( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
	}

	public function test_saving_after_reconnecting_google_retries_once() {
		$this->verification_responses[] = AccountReconnect::google_disconnected();
		$this->create_responses[]       = AccountReconnect::google_disconnected();
		$post_id                        = $this->create_shared_draft();
		$this->publish_over_rest( $post_id );

		$this->start_new_request();
		$this->verification_responses[] = $this->json( 200, $this->proxy_mock( 'accounts/locations/voice-of-merchant/eligible' ) );
		$this->create_responses[]       = $this->created_post();

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'title' => 'Edited title' ] );
		$data = rest_do_request( $request )->get_data();

		$this->assertCount( 2, array_filter( $this->sent, [ $this, 'is_create' ] ) );
		$this->assertTrue( $data[ LocalPostService::SHARE_STATUS_FIELD_NAME ]['is_shared'] );
		$this->assertNull( $data[ PostSyncNotice::FIELD_NAME ] );
	}

	public function test_a_lost_jetpack_connection_still_says_google_could_not_be_reached() {
		$this->verification_responses[] = AccountReconnect::jetpack_disconnected();
		$this->create_responses[]       = AccountReconnect::jetpack_disconnected();
		$post_id                        = $this->create_shared_draft();

		$data = $this->publish_over_rest( $post_id );

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertSame(
			[
				'status'  => 'error',
				'message' => self::UNREACHABLE_MESSAGE,
			],
			$data[ PostSyncNotice::FIELD_NAME ]
		);
	}

	/**
	 * @param string $sent
	 *
	 * @return bool
	 */
	public function is_create( string $sent ): bool {
		return 'POST accounts/111/locations/222/localPosts' === $sent;
	}

	/**
	 * @return string[] Names of the hooks scheduled in WP-Cron.
	 */
	protected function scheduled_hooks(): array {
		$hooks = [];

		foreach ( (array) _get_cron_array() as $events ) {
			$hooks = array_merge( $hooks, array_keys( (array) $events ) );
		}

		return array_unique( $hooks );
	}

	/**
	 * Build a Guzzle client that answers from one of this test's response queues.
	 *
	 * @param string $queue Property name of the queue.
	 *
	 * @return Client
	 */
	protected function http_client( string $queue ): Client {
		$handler = function ( RequestInterface $request ) use ( $queue ) {
			$path         = $request->getUri()->getPath();
			$this->sent[] = $request->getMethod() . ' ' . preg_replace( '#^/google/[^/]+/v\d+/#', '', $path );
			$next         = array_shift( $this->{$queue} );

			if ( 'connect' === $next || null === $next ) {
				return Create::rejectionFor( new ConnectException( 'Could not resolve host.', $request ) );
			}

			// Stands in for the Connect Server client's middleware, which throws on a 401.
			if ( $next instanceof Throwable ) {
				return Create::rejectionFor( $next );
			}

			return Create::promiseFor( $next );
		};

		return new Client( [ 'handler' => HandlerStack::create( $handler ) ] );
	}

	/**
	 * Start a new request, as a later save would, so the per-request send guard doesn't apply.
	 */
	protected function start_new_request(): void {
		remove_all_actions( 'wp_after_insert_post' );
		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->get_location_service() );
		$this->notice  = new PostSyncNotice( $this->local_post_service, $this->trigger );
		$this->trigger->register();
		$this->notice->register();
		$this->notice->register_rest_fields();
	}

	/**
	 * @return LocationService
	 */
	protected function get_location_service(): LocationService {
		$property = new ReflectionProperty( PostPublishTrigger::class, 'location_service' );
		$property->setAccessible( true );

		return $property->getValue( $this->trigger );
	}

	/**
	 * @return int
	 */
	protected function create_shared_draft(): int {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );

		return $post_id;
	}

	/**
	 * @param int $post_id
	 *
	 * @return array The edit-context response data the block editor receives.
	 */
	protected function publish_over_rest( int $post_id ): array {
		return $this->publish_over_rest_response( $post_id )->get_data();
	}

	/**
	 * @param int $post_id
	 *
	 * @return WP_REST_Response
	 */
	protected function publish_over_rest_response( int $post_id ) {
		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'status' => 'publish' ] );

		return rest_do_request( $request );
	}

	/**
	 * @param int $post_id
	 */
	protected function open_edit_screen( int $post_id ): void {
		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$screen = WP_Screen::get( 'post' );
		$screen->is_block_editor( false );
		set_current_screen( $screen );
	}

	/**
	 * @return Response
	 */
	protected function created_post(): Response {
		return $this->json(
			200,
			[
				'name'      => 'accounts/111/locations/222/localPosts/333',
				'searchUrl' => 'https://local.google.com/place?id=1&use=posts&lpsid=333',
			]
		);
	}

	/**
	 * @param int   $status
	 * @param array $body
	 *
	 * @return Response
	 */
	protected function json( int $status, array $body ): Response {
		return new Response( $status, [ 'Content-Type' => 'application/json' ], (string) wp_json_encode( $body ) );
	}

	/**
	 * @param string $name Path under the test proxy's `mocks/gbp/`, without `.json`.
	 *
	 * @return array The test proxy's mocked response body.
	 */
	protected function proxy_mock( string $name ): array {
		return wp_json_file_decode( dirname( __DIR__, 3 ) . "/proxy/mocks/gbp/{$name}.json", [ 'associative' => true ] );
	}
}
