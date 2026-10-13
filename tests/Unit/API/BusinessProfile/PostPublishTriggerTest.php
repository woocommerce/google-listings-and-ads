<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostPublishTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Post;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostPublishTriggerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class PostPublishTriggerTest extends UnitTest {

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var PostPublishTrigger $trigger */
	protected $trigger;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->local_post_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => false,
				'search_url' => '',
			]
		);

		$this->location_service = $this->createMock( LocationService::class );
		$this->location_service->method( 'get_selected_location' )->willReturn( [ 'title' => 'Jane\'s Bakery' ] );

		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->location_service );
	}

	public function test_register_hooks_wp_after_insert_post_to_publish_to_business_profile() {
		$this->trigger->register();

		$this->assertSame(
			10,
			has_action( 'wp_after_insert_post', [ $this->trigger, 'publish_to_business_profile' ] )
		);
	}

	public function test_sends_a_shared_post_on_a_genuine_publish() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	/**
	 * @dataProvider data_provider_non_publish_transitions
	 *
	 * @param string $new_status
	 * @param string $old_status
	 */
	public function test_does_not_send_on_a_transition_that_is_not_a_genuine_publish( string $new_status, string $old_status ) {
		$post = $this->create_post_with_status( $new_status, [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		$post_before = $this->create_post_with_status( $old_status );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	/**
	 * @return array
	 */
	public function data_provider_non_publish_transitions(): array {
		return [
			'into draft'                 => [ 'draft', 'auto-draft' ],
			'into pending'               => [ 'pending', 'draft' ],
			'into future (scheduled)'    => [ 'future', 'draft' ],
			'into private'               => [ 'private', 'draft' ],
			'already published (a save)' => [ 'publish', 'publish' ],
		];
	}

	public function test_sends_a_scheduled_posts_automatic_publish_the_same_way_as_a_manual_publish() {
		$post        = $this->create_shared_post();
		$post_before = $this->create_post_with_status( 'future' );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		// wp_publish_post(), run by WP-Cron for a scheduled post, fires wp_after_insert_post the
		// same way wp_insert_post() does for a manual publish -- no separate code path to trigger.
		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_sends_only_once_even_when_the_hook_fires_more_than_once_for_one_publish_action() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_send_a_post_whose_sharing_toggle_is_off() {
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, false );
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_send_a_post_of_an_unsupported_post_type() {
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_send_when_no_location_is_connected() {
		$this->location_service = $this->createMock( LocationService::class );
		$this->location_service->method( 'get_selected_location' )->willReturn( null );
		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->location_service );

		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_send_a_post_that_is_already_shared() {
		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->local_post_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			]
		);
		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->location_service );

		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_propagate_an_exception_from_the_create_call_and_reports_it() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->method( 'create' )->willThrowException( new Exception( 'Network error.' ) );

		$before = did_action( 'woocommerce_gla_exception' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertSame( $before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_sends_a_post_whose_sharing_toggle_is_saved_in_the_same_rest_request_as_the_publish() {
		$this->trigger->register();
		$this->register_share_meta();
		$this->login_as_administrator();
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->once() )->method( 'create' );

		// The block editor's own save: status and the share toggle travel in one REST request.
		// wp_update_post()'s meta_input is written before transition_post_status fires too, so it
		// wouldn't have caught the original bug -- only a real request-shaped write, where the
		// meta is written by WP_REST_Posts_Controller after the status change, does.
		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params(
			[
				'status' => 'publish',
				'meta'   => [ PostShareControl::SHARE_META_KEY => true ],
			]
		);
		rest_do_request( $request );
	}

	/**
	 * Register the share meta's REST schema, as PostShareControl does on `init`, so a REST
	 * request can write it.
	 */
	private function register_share_meta(): void {
		( new PostShareControl(
			$this->createMock( AssetsHandlerInterface::class ),
			$this->local_post_service,
			$this->location_service
		) )->register_post_meta();
	}

	/**
	 * Create a post of a supported type with the sharing toggle switched on.
	 *
	 * @return WP_Post
	 */
	private function create_shared_post(): WP_Post {
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );

		return $post;
	}

	/**
	 * Create a post with a given status. A `future` status needs a future `post_date`, or
	 * wp_insert_post() silently publishes it immediately instead.
	 *
	 * @param string $status
	 * @param array  $args Additional factory args.
	 *
	 * @return WP_Post
	 */
	private function create_post_with_status( string $status, array $args = [] ): WP_Post {
		if ( 'future' === $status ) {
			$args['post_date'] = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		}

		return $this->factory()->post->create_and_get( array_merge( [ 'post_status' => $status ], $args ) );
	}
}
