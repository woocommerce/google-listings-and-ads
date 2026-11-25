<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\BusinessProfileShareMetaBox;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\Stub;
use ReflectionClass;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileShareMetaBoxTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\MetaBox
 */
class BusinessProfileShareMetaBoxTest extends UnitTest {

	protected const SHARE_FIELD   = 'gla_business_profile_share_share';
	protected const SUMMARY_FIELD = 'gla_business_profile_share_summary';
	protected const NONCE_NAME    = 'gla_business_profile_share_nonce';
	protected const NONCE_ACTION  = 'gla_business_profile_share_save';

	/** @var Stub|Admin $admin */
	protected $admin;

	/** @var Stub|LocationService $location_service */
	protected $location_service;

	/** @var BusinessProfileShareMetaBox $meta_box */
	protected $meta_box;

	/** @var int $post_id */
	protected $post_id;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->admin            = $this->createStub( Admin::class );
		$this->location_service = $this->createStub( LocationService::class );

		$this->meta_box = new BusinessProfileShareMetaBox( $this->admin, $this->location_service );

		$this->post_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		unset( $_POST[ self::SHARE_FIELD ], $_POST[ self::SUMMARY_FIELD ], $_POST[ self::NONCE_NAME ] );

		parent::tearDown();
	}

	/**
	 * @dataProvider data_provider_connected_location
	 *
	 * @param array|null $location
	 * @param bool       $expected
	 */
	public function test_can_register_only_when_a_location_is_connected( ?array $location, bool $expected ) {
		$this->location_service->method( 'get_selected_location' )->willReturn( $location );

		$this->assertSame( $expected, $this->meta_box->can_register() );
	}

	/**
	 * @return array
	 */
	public function data_provider_connected_location(): array {
		return [
			'connected'     => [
				[
					'id'        => '2222',
					'accountId' => '106234255840114990952',
					'title'     => 'Trailmark Athletics',
					'address'   => '',
					'placeId'   => '',
					'mapsUri'   => '',
				],
				true,
			],
			'not connected' => [ null, false ],
		];
	}

	public function test_sits_in_the_side_column_of_the_post_editor_and_is_hidden_in_the_block_editor() {
		$this->assertSame( 'post', $this->meta_box->get_screen() );
		$this->assertSame( 'side', $this->meta_box->get_context() );
		$this->assertSame( 'core', $this->meta_box->get_priority() );
		$this->assertSame( [ '__back_compat_meta_box' => true ], $this->meta_box->get_callback_args() );
		$this->assertSame( 'Google Business Profile', $this->meta_box->get_title() );
	}

	public function test_get_view_context_returns_the_field_and_nonce_names() {
		$reflection = new ReflectionClass( $this->meta_box );
		$method     = $reflection->getMethod( 'get_view_context' );
		$method->setAccessible( true );

		$this->assertSame(
			[
				'share_field_id'   => self::SHARE_FIELD,
				'summary_field_id' => self::SUMMARY_FIELD,
				'nonce_action'     => self::NONCE_ACTION,
				'nonce_name'       => self::NONCE_NAME,
			],
			$method->invoke( $this->meta_box, get_post( $this->post_id ), [] )
		);
	}

	public function test_moves_itself_right_after_the_publish_box() {
		global $wp_meta_boxes;

		$wp_meta_boxes['post']['side']['core'] = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'submitdiv'              => [ 'id' => 'submitdiv' ],
			'categorydiv'            => [ 'id' => 'categorydiv' ],
			'tagsdiv-post_tag'       => [ 'id' => 'tagsdiv-post_tag' ],
			'business_profile_share' => [ 'id' => 'business_profile_share' ],
		];

		$this->meta_box->move_below_publish_box();

		$this->assertSame(
			[ 'submitdiv', 'business_profile_share', 'categorydiv', 'tagsdiv-post_tag' ],
			array_keys( $wp_meta_boxes['post']['side']['core'] )
		);

		unset( $wp_meta_boxes['post'] );
	}

	public function test_leaves_the_order_unchanged_when_the_meta_box_is_not_registered() {
		global $wp_meta_boxes;

		$wp_meta_boxes['post']['side']['core'] = [ // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'submitdiv'   => [ 'id' => 'submitdiv' ],
			'categorydiv' => [ 'id' => 'categorydiv' ],
		];

		$this->meta_box->move_below_publish_box();

		$this->assertSame( [ 'submitdiv', 'categorydiv' ], array_keys( $wp_meta_boxes['post']['side']['core'] ) );

		unset( $wp_meta_boxes['post'] );
	}

	public function test_register_hooks_the_save_path_and_the_placement() {
		$this->meta_box->register();

		$this->assertSame( 10, has_action( 'save_post_post', [ $this->meta_box, 'handle_submission' ] ) );
		$this->assertSame( 10, has_action( 'add_meta_boxes_post', [ $this->meta_box, 'move_below_publish_box' ] ) );
	}

	public function test_saves_the_share_choice_and_the_text_from_the_form() {
		$this->submit_form( '1', "New Shoes Coming Soon\nReserve your pair online." );

		$this->meta_box->handle_submission( $this->post_id, get_post( $this->post_id ) );

		$this->assertSame( '1', get_post_meta( $this->post_id, PostShareControl::SHARE_META_KEY, true ) );
		$this->assertSame(
			"New Shoes Coming Soon\nReserve your pair online.",
			get_post_meta( $this->post_id, LocalPostService::SUMMARY_META_KEY, true )
		);
	}

	public function test_saves_sharing_off_when_the_toggle_is_off() {
		update_post_meta( $this->post_id, PostShareControl::SHARE_META_KEY, true );
		$this->submit_form( '', 'My text' );

		$this->meta_box->handle_submission( $this->post_id, get_post( $this->post_id ) );

		$this->assertSame( '', get_post_meta( $this->post_id, PostShareControl::SHARE_META_KEY, true ) );
	}

	public function test_strips_tags_from_the_submitted_text() {
		$this->submit_form( '1', '<b>Sale</b> this weekend' );

		$this->meta_box->handle_submission( $this->post_id, get_post( $this->post_id ) );

		$this->assertSame( 'Sale this weekend', get_post_meta( $this->post_id, LocalPostService::SUMMARY_META_KEY, true ) );
	}

	public function test_does_not_save_without_a_valid_nonce() {
		$this->submit_form( '1', 'My text' );
		$_POST[ self::NONCE_NAME ] = 'invalid';

		$this->meta_box->handle_submission( $this->post_id, get_post( $this->post_id ) );

		$this->assertSame( '', get_post_meta( $this->post_id, LocalPostService::SUMMARY_META_KEY, true ) );
	}

	public function test_keeps_the_saved_values_when_the_fields_are_missing_from_the_form() {
		update_post_meta( $this->post_id, PostShareControl::SHARE_META_KEY, true );
		update_post_meta( $this->post_id, LocalPostService::SUMMARY_META_KEY, 'My saved text' );
		$_POST[ self::NONCE_NAME ] = wp_create_nonce( self::NONCE_ACTION );

		$this->meta_box->handle_submission( $this->post_id, get_post( $this->post_id ) );

		$this->assertSame( '1', get_post_meta( $this->post_id, PostShareControl::SHARE_META_KEY, true ) );
		$this->assertSame( 'My saved text', get_post_meta( $this->post_id, LocalPostService::SUMMARY_META_KEY, true ) );
	}

	public function test_does_not_save_without_a_nonce() {
		$_POST[ self::SHARE_FIELD ]   = '1';
		$_POST[ self::SUMMARY_FIELD ] = 'My text';

		$this->meta_box->handle_submission( $this->post_id, get_post( $this->post_id ) );

		$this->assertSame( '', get_post_meta( $this->post_id, LocalPostService::SUMMARY_META_KEY, true ) );
	}

	public function test_does_not_save_a_revision() {
		$revision_id = wp_save_post_revision( $this->post_id );
		$this->submit_form( '1', 'My text' );

		$this->meta_box->handle_submission( $revision_id, get_post( $revision_id ) );

		$this->assertSame( '', get_post_meta( $revision_id, LocalPostService::SUMMARY_META_KEY, true ) );
	}

	public function test_does_not_save_for_a_user_who_cannot_edit_the_post() {
		$this->submit_form( '1', 'My text' );
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->meta_box->handle_submission( $this->post_id, get_post( $this->post_id ) );

		$this->assertSame( '', get_post_meta( $this->post_id, LocalPostService::SUMMARY_META_KEY, true ) );
	}

	/**
	 * Fill `$_POST` as the Classic Editor's form would, with a valid nonce.
	 *
	 * @param string $share   The share field's value.
	 * @param string $summary The text field's value.
	 */
	private function submit_form( string $share, string $summary ): void {
		$_POST[ self::NONCE_NAME ]    = wp_create_nonce( self::NONCE_ACTION );
		$_POST[ self::SHARE_FIELD ]   = $share;
		$_POST[ self::SUMMARY_FIELD ] = $summary;
	}
}
