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

defined( 'ABSPATH' ) || exit;

/**
 * Class PostShareControl
 *
 * Registers the post meta that records whether a post is shared to the connected Google Business
 * Profile location, and loads the block editor panel that lets the merchant turn sharing on and
 * write the text sent to Google.
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
	 * Load the block editor panel on a supported post type, only when a location is connected.
	 */
	public function enqueue_assets(): void {
		if ( ! $this->is_supported_block_editor_screen() ) {
			return;
		}

		$location = $this->get_connected_location();

		if ( null === $location ) {
			return;
		}

		$build_dir = "{$this->get_root_dir()}/js/build";

		$script = ( new AdminScriptWithBuiltDependenciesAsset(
			self::SCRIPT_HANDLE,
			'js/build/business-profile-share',
			"{$build_dir}/business-profile-share.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => $this->get_version(),
				]
			)
		) )->add_inline_script(
			'glaData',
			[
				'businessProfileLocation' => $location,
			]
		);

		$style = new AdminStyleAsset(
			self::SCRIPT_HANDLE . '-css',
			'js/build/business-profile-share',
			[],
			(string) filemtime( "{$build_dir}/business-profile-share.css" )
		);

		$this->assets_handler->register_many( [ $script, $style ] );
		$this->assets_handler->enqueue_many( [ $script, $style ] );
	}

	/**
	 * Whether the current screen is the block editor for a supported post type.
	 *
	 * @return bool
	 */
	protected function is_supported_block_editor_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return null !== $screen
			&& $screen->is_block_editor()
			&& in_array( $screen->post_type, self::POST_TYPES, true );
	}

	/**
	 * Get the connected location's name and address, or null when no location is connected.
	 *
	 * @return array|null Shaped `{ title, address }`.
	 */
	protected function get_connected_location(): ?array {
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
