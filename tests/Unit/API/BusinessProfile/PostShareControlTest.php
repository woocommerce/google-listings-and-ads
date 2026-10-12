<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use PHPUnit\Framework\MockObject\MockObject;
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

	/** @var MockObject|OptionsInterface $options */
	protected $options;

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

		$this->assets_handler = $this->createMock( AssetsHandlerInterface::class );
		$this->options        = $this->createMock( OptionsInterface::class );

		$this->control = new PostShareControl( $this->assets_handler, $this->options );

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
		unregister_meta_key( 'post', PostShareControl::SHARE_META_KEY, 'post' );
		unset( $GLOBALS['post'], $GLOBALS['wp_rest_additional_fields']['post'][ LocalPostService::SHARE_STATUS_FIELD_NAME ] );
		$GLOBALS['wp_rest_server'] = null;

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
		$this->assertTrue( $meta['show_in_rest'] );
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

	public function test_passes_the_connected_location_to_the_script() {
		$this->mock_connected_location();

		// This test checks WordPress's actual registered script data, so it needs a real handler.
		$control = new PostShareControl( new AssetsHandler(), $this->options );

		$control->register();
		$this->set_block_editor_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );

		$inline_script = implode( '', (array) wp_scripts()->get_data( 'gla-business-profile-share', 'before' ) );

		$this->assertStringContainsString( 'var glaData = ', $inline_script );
		$this->assertStringContainsString( '"businessProfileLocation":{"title":"Jane\'s Bakery - Riverside","address":"456 Riverside Ave, San Francisco, 94107"}', $inline_script );
	}

	public function test_does_not_enqueue_assets_without_a_connected_location() {
		$this->options->method( 'get' )->with( OptionsInterface::BUSINESS_PROFILE )->willReturn( [] );

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
		$GLOBALS['post'] = get_post( $this->factory()->post->create() );

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
		set_current_screen( 'post' );
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
		$GLOBALS['post'] = get_post( $post_id );
		$this->register_share_status_field();

		$control = new PostShareControl( new AssetsHandler(), $this->options );
		$control->register();
		set_current_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );

		$inline_script = implode( '', (array) wp_scripts()->get_data( 'gla-business-profile-share-meta-box', 'before' ) );
		$data          = json_decode( substr( $inline_script, strlen( 'var glaData = ' ) ), true );

		$this->assertSame(
			[
				'status'           => 'draft',
				'title'            => 'New Shoes Coming Soon',
				'hasManualExcerpt' => true,
				'excerpt'          => 'Reserve your pair online.',
				'isShareEnabled'   => true,
				'summary'          => 'My text',
				'isShared'         => true,
				'searchUrl'        => 'https://local.google.com/place?id=1&use=posts',
			],
			$data['businessProfilePost']
		);
		$this->assertSame( 'Jane\'s Bakery - Riverside', $data['businessProfileLocation']['title'] );
	}

	public function test_passes_a_never_saved_post_without_an_excerpt_or_share_status() {
		$this->mock_connected_location();
		$GLOBALS['post'] = get_post( $this->factory()->post->create( [ 'post_status' => 'auto-draft' ] ) );

		$control = new PostShareControl( new AssetsHandler(), $this->options );
		$control->register();
		set_current_screen( 'post' );
		do_action( 'admin_enqueue_scripts' );

		$inline_script = implode( '', (array) wp_scripts()->get_data( 'gla-business-profile-share-meta-box', 'before' ) );
		$data          = json_decode( substr( $inline_script, strlen( 'var glaData = ' ) ), true );

		$this->assertSame( 'auto-draft', $data['businessProfilePost']['status'] );
		$this->assertSame( '', $data['businessProfilePost']['excerpt'] );
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

	public function test_returns_the_connected_location() {
		$this->mock_connected_location();

		$this->assertSame(
			[
				'title'   => "Jane's Bakery - Riverside",
				'address' => '456 Riverside Ave, San Francisco, 94107',
			],
			$this->control->get_connected_location()
		);
	}

	/**
	 * Mock a connected location in the stored Business Profile option.
	 */
	private function mock_connected_location(): void {
		$this->options->method( 'get' )
			->with( OptionsInterface::BUSINESS_PROFILE )
			->willReturn(
				[
					'account_id'       => '106234255840114990952',
					'location_id'      => '2222',
					'location_title'   => "Jane's Bakery - Riverside",
					'location_address' => '456 Riverside Ave, San Francisco, 94107',
					'place_id'         => 'place-2222',
					'maps_uri'         => 'https://maps.google.com/?cid=2222',
				]
			);
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

	/**
	 * Register the share status REST field the meta box reads, as the plugin does on `rest_api_init`.
	 */
	private function register_share_status_field(): void {
		( new LocalPostService(
			$this->createMock( BusinessProfileApiClient::class ),
			$this->createMock( WP::class ),
			$this->createMock( ISOUtility::class )
		) )->register_rest_fields();
	}
}
