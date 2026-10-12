<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use WP_Post;
use WP_Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostShareControl
 *
 * Registers the post meta that records whether a post is shared to the connected Google Business
 * Profile location, and loads the block editor panel or the Classic Editor meta box that lets the
 * merchant turn sharing on and write the text sent to Google.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostShareControl implements Service, Registerable {

	use PluginHelper;

	/** @var string Post meta key holding whether the post is shared to Google Business Profile when published. */
	public const SHARE_META_KEY = '_wc_gla_gbp_share';

	/** @var string[] Post types the share control is available on. */
	public const POST_TYPES = [ 'post' ];

	/** @var string Handle of the block editor panel's script. */
	protected const SCRIPT_HANDLE = 'gla-business-profile-share';

	/** @var string Handle of the Classic Editor meta box's script. */
	protected const META_BOX_SCRIPT_HANDLE = 'gla-business-profile-share-meta-box';

	/** @var string Status of a post that has never been saved. */
	protected const NEVER_SAVED_POST_STATUS = 'auto-draft';

	/** @var AssetsHandlerInterface */
	protected $assets_handler;

	/** @var OptionsInterface */
	protected $options;

	/**
	 * PostShareControl constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param OptionsInterface       $options
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, OptionsInterface $options ) {
		$this->assets_handler = $assets_handler;
		$this->options        = $options;
	}

	/**
	 * Register the share meta and the block editor panel's assets.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register the share meta on every supported post type. It's REST-visible so the block editor
	 * can read and write it, and only users who can edit the post can change it.
	 */
	public function register_post_meta(): void {
		foreach ( self::POST_TYPES as $post_type ) {
			register_post_meta(
				$post_type,
				self::SHARE_META_KEY,
				[
					'single'            => true,
					'type'              => 'boolean',
					'default'           => false,
					'show_in_rest'      => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				]
			);
		}
	}

	/**
	 * Load the block editor panel, or the Classic Editor meta box's script, on a supported post
	 * type, only when a location is connected.
	 */
	public function enqueue_assets(): void {
		$screen = $this->get_supported_editor_screen();

		if ( null === $screen ) {
			return;
		}

		$location = $this->get_connected_location();

		if ( null === $location ) {
			return;
		}

		if ( $screen->is_block_editor() ) {
			$this->enqueue_entry(
				self::SCRIPT_HANDLE,
				'business-profile-share',
				[ 'businessProfileLocation' => $location ]
			);
			return;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$this->enqueue_entry(
			self::META_BOX_SCRIPT_HANDLE,
			'business-profile-share-meta-box',
			[
				'businessProfileLocation' => $location,
				'businessProfilePost'     => $this->get_post_data( $post ),
			]
		);
	}

	/**
	 * Register and enqueue a built entry's script and style, passing data to the script as `glaData`.
	 *
	 * @param string $handle Script handle; the style uses it with a `-css` suffix.
	 * @param string $entry  Name of the built entry in `js/build`.
	 * @param array  $data   Data available to the script as `glaData`.
	 */
	protected function enqueue_entry( string $handle, string $entry, array $data ): void {
		$build_dir = "{$this->get_root_dir()}/js/build";

		$script = ( new AdminScriptWithBuiltDependenciesAsset(
			$handle,
			"js/build/{$entry}",
			"{$build_dir}/{$entry}.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => $this->get_version(),
				]
			)
		) )->add_inline_script( 'glaData', $data );

		$style = new AdminStyleAsset(
			"{$handle}-css",
			"js/build/{$entry}",
			[],
			(string) filemtime( "{$build_dir}/{$entry}.css" )
		);

		$this->assets_handler->register_many( [ $script, $style ] );
		$this->assets_handler->enqueue_many( [ $script, $style ] );
	}

	/**
	 * Get the current screen when it's the editor for a supported post type, block or Classic.
	 *
	 * @return WP_Screen|null
	 */
	protected function get_supported_editor_screen(): ?WP_Screen {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, self::POST_TYPES, true ) ) {
			return null;
		}

		return $screen;
	}

	/**
	 * Get the saved state of a post the Classic Editor meta box needs, so "Generate from post" uses
	 * the post as last saved.
	 *
	 * @param WP_Post $post The post being edited.
	 *
	 * @return array
	 */
	protected function get_post_data( WP_Post $post ): array {
		$is_never_saved = self::NEVER_SAVED_POST_STATUS === $post->post_status;
		$result         = get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true );
		$result         = is_array( $result ) ? $result : [];

		return [
			'status'           => $post->post_status,
			'title'            => $post->post_title,
			'hasManualExcerpt' => has_excerpt( $post ),
			'excerpt'          => $is_never_saved ? '' : get_the_excerpt( $post ),
			'isShareEnabled'   => (bool) get_post_meta( $post->ID, self::SHARE_META_KEY, true ),
			'summary'          => (string) get_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, true ),
			'isShared'         => '' !== (string) ( $result['post_id'] ?? '' ),
			'searchUrl'        => (string) ( $result['search_url'] ?? '' ),
		];
	}

	/**
	 * Get the connected location's name and address, or null when no location is connected.
	 *
	 * @return array|null Shaped `{ title, address }`.
	 */
	public function get_connected_location(): ?array {
		$data = $this->options->get( OptionsInterface::BUSINESS_PROFILE, [] );

		if ( ! is_array( $data ) || empty( $data['location_id'] ) ) {
			return null;
		}

		return [
			'title'   => (string) ( $data['location_title'] ?? '' ),
			'address' => (string) ( $data['location_address'] ?? '' ),
		];
	}
}
