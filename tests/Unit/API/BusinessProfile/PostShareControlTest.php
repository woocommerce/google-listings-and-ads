<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostImageService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Request;
use WP_Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostShareControlTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class PostShareControlTest extends UnitTest {

	/** @var MockObject|AssetsHandlerInterface $assets_handler */
	protected $assets_handler;

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var PostShareControl $control */
	protected $control;

	/** @var string $build_dir */
	protected $build_dir;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// Detach pre-existing admin_enqueue_scripts callbacks so firing the action runs only
		// what register() adds.
		remove_all_actions( 'admin_enqueue_scripts' );

		$this->login_as_administrator();

		$this->assets_handler   = $this->createMock( AssetsHandlerInterface::class );
		$this->location_service = $this->createMock( LocationService::class );
		$this->options          = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( 1234 );
		$this->options->method( 'get_ads_id' )->willReturn( 0 );

		$this->local_post_service = new LocalPostService(
			$this->createMock( BusinessProfileApiClient::class ),
			$this->createMock( WP::class ),
			$this->createMock( ISOUtility::class ),
			$this->createMock( LocalPostImageService::class ),
			$this->location_service,
			$this->createMock( WPML::class )
		);

		$this->control = new PostShareControl( $this->assets_handler, $this->local_post_service, $this->location_service, $this->options );

		$this->build_dir = dirname( __DIR__, 4 ) . '/js/build';
		wp_mkdir_p( $this->build_dir );
		$this->stub_build_artifacts(
			[
				'business-profile-share.js',
				'business-profile-share.css',
				'business-profile-share.asset.php',
				'business-profile-share-meta-box.js',
				'business-profile-share-meta-box.css',
				'business-profile-share-meta-box.asset.php',
			]
		);
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		// Deregistering (not just dequeuing) drops each handle's accumulated inline-script
		// data, so a later test's wp_add_inline_script() call doesn't append to a previous
		// test's JSON and leave wp_scripts()->get_data() with two concatenated `var glaData =`
		// blocks that json_decode() can't parse.
		wp_deregister_script( 'gla-business-profile-share' );
		wp_deregister_script( 'gla-business-profile-share-meta-box' );

		unregister_meta_key( 'post', PostShareControl::SHARE_META_KEY, 'post' );
		unset( $GLOBALS['post'] );

		parent::tearDown();
	}

	public function test_register_hooks_post_meta_registration_to_init() {
		$this->control->register();

		$this->assertSame( 10, has_action( 'init', [ $this->control, 'register_post_meta' ] ) );
	}

	public function test_registers_the_share_meta_as_a_rest_visible_boolean_off_by_default() {
		$this->control->register_post_meta();

		$meta = get_registered_meta_keys( 'post', 'post' )[ PostShareControl::SHARE_META_KEY ] ?? null;

		$this->assertNotNull( $meta );
		$this->assertSame( 'boolean', $meta['type'] );
		$this->assertTrue( $meta['single'] );
		$this->assertFalse( $meta['default'] );
		$this->assertIsCallable( $meta['show_in_rest']['prepare_callback'] );
	}

	public function test_rest_returns_the_share_meta_in_the_edit_context_to_a_user_who_can_edit_the_post() {
		$this->assertTrue( $this->prepare_share_meta_for_rest( 'edit' ) );
	}

	public function test_rest_hides_the_share_meta_outside_the_edit_context() {
		$this->assertFalse( $this->prepare_share_meta_for_rest( 'view' ) );
	}

	public function test_rest_hides_the_share_meta_from_a_user_who_cannot_edit_the_post() {
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertFalse( $this->prepare_share_meta_for_rest( 'edit' ) );
	}

	public function test_only_users_who_can_edit_the_post_can_change_the_share_meta() {
		$this->control->register_post_meta();

		$post_id = $this->factory()->post->create();

		$this->assertTrue( current_user_can( 'edit_post_meta', $post_id, PostShareControl::SHARE_META_KEY ) );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertFalse( current_user_can( 'edit_post_meta', $post_id, PostShareControl::SHARE_META_KEY ) );
	}

	public function test_enqueues_assets_with_the_connected_location_on_the_block_editor() {
		$this->mock_connected_location();

		$this->assets_handler->expects( $this->once() )
			->method( 'register_many' )
			->with(
				$this->callback(
					function ( array $assets ) {
						$this->assertCount( 2, $assets );
						$this->assert_asset_handles(
							$assets,
							[
								'gla-business-profile-share',
								'gla-business-profile-share-css',
							]
						);

						return true;
					}
				)
			);
		$this->assets_handler->expects( $this->once() )->method( 'enqueue_many' );

		$this->control->register();
		$this->set_block_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_passes_the_tracking_data_to_gla_data() {
		$this->mock_connected_location();

		// This test checks WordPress's actual registered script data, so it needs a real handler.
		$control = new PostShareControl( new AssetsHandler(), $this->local_post_service, $this->location_service, $this->options );

		$control->register();
		$this->set_block_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );

		$data = $this->get_inline_script_data( 'gla-business-profile-share', 'glaData' );

		$this->assertSame( 'gla', $data['slug'] );
		$this->assertArrayHasKey( 'initialWpData', $data );
		$this->assertArrayNotHasKey( 'businessProfileLocation', $data );
		$this->assertArrayNotHasKey( 'businessProfileSummaryMaxLength', $data );
	}

	public function test_passes_the_connected_location_and_summary_max_length_to_gla_business_profile_data() {
		$this->mock_connected_location();

		$control = new PostShareControl( new AssetsHandler(), $this->local_post_service, $this->location_service, $this->options );

		$control->register();
		$this->set_block_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );

		$data = $this->get_inline_script_data( 'gla-business-profile-share', 'glaBusinessProfileData' );

		$this->assertSame(
			[
				'title'   => "Jane's Bakery - Riverside",
				'address' => '456 Riverside Ave, San Francisco, 94107',
			],
			$data['businessProfileLocation']
		);
		$this->assertSame( 1500, $data['businessProfileSummaryMaxLength'] );
		$this->assertArrayNotHasKey( 'slug', $data );
	}

	public function test_does_not_enqueue_assets_without_a_connected_location() {
		$this->location_service->method( 'get_selected_location' )->willReturn( null );

		$this->assets_handler->expects( $this->never() )->method( 'register_many' );
		$this->assets_handler->expects( $this->never() )->method( 'enqueue_many' );

		$this->control->register();
		$this->set_block_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_does_not_enqueue_assets_on_an_unsupported_post_type() {
		$this->mock_connected_location();

		$this->assets_handler->expects( $this->never() )->method( 'register_many' );

		$this->control->register();
		$this->set_block_editor_screen( 'page' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_enqueues_the_meta_box_assets_on_the_classic_editor() {
		$this->mock_connected_location();
		$GLOBALS['post'] = get_post( $this->factory()->post->create() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->assets_handler->expects( $this->once() )
			->method( 'register_many' )
			->with(
				$this->callback(
					function ( array $assets ) {
						$this->assertCount( 2, $assets );
						$this->assert_asset_handles(
							$assets,
							[
								'gla-business-profile-share-meta-box',
								'gla-business-profile-share-meta-box-css',
							]
						);

						return true;
					}
				)
			);

		$this->control->register();
		$this->set_classic_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function test_passes_the_saved_post_to_the_meta_box_script() {
		$this->mock_connected_location();
		$post_id = $this->factory()->post->create(
			[
				'post_title'   => 'New Shoes Coming Soon',
				'post_excerpt' => 'Reserve your pair online.',
				'post_status'  => 'draft',
			]
		);
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );
		update_post_meta( $post_id, LocalPostService::SUMMARY_META_KEY, 'My text' );
		update_post_meta(
			$post_id,
			LocalPostService::RESULT_META_KEY,
			[
				'post_id'    => '123',
				'search_url' => 'https://local.google.com/place?id=1&use=posts',
			]
		);
		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$control = new PostShareControl( new AssetsHandler(), $this->local_post_service, $this->location_service, $this->options );
		$control->register();
		$this->set_classic_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );

		$gla_data              = $this->get_inline_script_data( 'gla-business-profile-share-meta-box', 'glaData' );
		$business_profile_data = $this->get_inline_script_data( 'gla-business-profile-share-meta-box', 'glaBusinessProfileData' );

		$this->assertSame(
			[
				'isShareEnabled' => true,
				'summary'        => 'My text',
				'isShared'       => true,
				'searchUrl'      => 'https://local.google.com/place?id=1&use=posts',
			],
			$business_profile_data['businessProfilePost']
		);
		$this->assertSame( 1500, $business_profile_data['businessProfileSummaryMaxLength'] );
		$this->assertSame( "Jane's Bakery - Riverside", $business_profile_data['businessProfileLocation']['title'] );
		$this->assertSame( 'gla', $gla_data['slug'] );
		$this->assertSame(
			[
				'version' => WC_GLA_VERSION,
				'mcId'    => 1234,
				'adsId'   => null,
			],
			$gla_data['initialWpData']
		);
	}

	public function test_passes_a_never_saved_post_without_a_share_status() {
		$this->mock_connected_location();
		$GLOBALS['post'] = get_post( $this->factory()->post->create( [ 'post_status' => 'auto-draft' ] ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$control = new PostShareControl( new AssetsHandler(), $this->local_post_service, $this->location_service, $this->options );
		$control->register();
		$this->set_classic_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );

		$data = $this->get_inline_script_data( 'gla-business-profile-share-meta-box', 'glaBusinessProfileData' );

		$this->assertFalse( $data['businessProfilePost']['isShareEnabled'] );
		$this->assertFalse( $data['businessProfilePost']['isShared'] );
		$this->assertSame( '', $data['businessProfilePost']['searchUrl'] );
	}

	public function test_does_not_enqueue_assets_outside_the_post_editor() {
		$this->mock_connected_location();

		$this->assets_handler->expects( $this->never() )->method( 'register_many' );

		$this->control->register();
		set_current_screen( 'edit-post' );
		do_action( 'admin_enqueue_scripts' );
	}

	/**
	 * Get the decoded data of one `var $variable_name = {...}` inline script block, among possibly
	 * several registered on the same handle's `before` position.
	 *
	 * @param string $handle        Script handle.
	 * @param string $variable_name The inline script's JS variable name, e.g. `glaData`.
	 *
	 * @return array
	 */
	private function get_inline_script_data( string $handle, string $variable_name ): array {
		$prefix = "var {$variable_name} = ";

		foreach ( (array) wp_scripts()->get_data( $handle, 'before' ) as $inline_script ) {
			// `WP_Scripts::add_inline_script()` casts a fresh (not yet set) `false` to an array
			// before appending to it, so the first entry here is `false`, not a string.
			if ( is_string( $inline_script ) && str_starts_with( $inline_script, $prefix ) ) {
				return json_decode( substr( $inline_script, strlen( $prefix ) ), true );
			}
		}

		$this->fail( "No inline script found for \${$variable_name} on \"{$handle}\"." );
	}

	/**
	 * Mock a connected location.
	 */
	private function mock_connected_location(): void {
		$this->location_service->method( 'get_selected_location' )
			->willReturn(
				[
					'id'        => '2222',
					'accountId' => '106234255840114990952',
					'title'     => "Jane's Bakery - Riverside",
					'address'   => '456 Riverside Ave, San Francisco, 94107',
					'placeId'   => 'place-2222',
					'mapsUri'   => 'https://maps.google.com/?cid=2222',
				]
			);
	}

	/**
	 * Run the share meta's REST prepare_callback for a saved, shared post.
	 *
	 * @param string $context The REST request context.
	 *
	 * @return mixed The value the REST response would carry.
	 */
	private function prepare_share_meta_for_rest( string $context ) {
		$this->control->register_post_meta();

		$post_id = $this->factory()->post->create();
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );
		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post_id}" );
		$request->set_param( 'context', $context );

		$meta = get_registered_meta_keys( 'post', 'post' )[ PostShareControl::SHARE_META_KEY ];

		return $meta['show_in_rest']['prepare_callback']( true, $request );
	}

	/**
	 * Set the current screen to the block editor for a post type.
	 *
	 * @param string $post_type
	 */
	private function set_block_editor_screen( string $post_type ): void {
		$screen = WP_Screen::get( $post_type );
		$screen->is_block_editor( true );
		set_current_screen( $screen );
	}

	/**
	 * Set the current screen to the Classic Editor for a post type. Set explicitly, because
	 * `WP_Screen::get()` returns the current screen unchanged when it has the same ID, so it can
	 * still be marked as the block editor by an earlier test.
	 *
	 * @param string $post_type
	 */
	private function set_classic_editor_screen( string $post_type ): void {
		$screen = WP_Screen::get( $post_type );
		$screen->is_block_editor( false );
		set_current_screen( $screen );
	}

	/**
	 * Assert that assets contain the expected handles.
	 *
	 * @param Asset[]  $assets
	 * @param string[] $expected_handles
	 */
	private function assert_asset_handles( array $assets, array $expected_handles ): void {
		$handles = array_map(
			static function ( Asset $asset ) {
				return $asset->get_handle();
			},
			$assets
		);

		foreach ( $expected_handles as $expected_handle ) {
			$this->assertContains( $expected_handle, $handles );
		}
	}

	/**
	 * Create stub build artifacts required by asset registration, without overwriting a real build.
	 *
	 * @param string[] $files
	 */
	private function stub_build_artifacts( array $files ): void {
		foreach ( $files as $file ) {
			$path = "{$this->build_dir}/{$file}";

			if ( file_exists( $path ) ) {
				continue;
			}

			if ( str_ends_with( $file, '.asset.php' ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $path, "<?php return array( 'dependencies' => array(), 'version' => 'test' );" );
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $path, '' );
		}
	}
}
