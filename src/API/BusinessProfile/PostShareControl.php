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

	/** @var string Handle of the block editor panel's script. */
	protected const SCRIPT_HANDLE = 'gla-business-profile-share';

	/** @var string Handle of the Classic Editor meta box's script. */
	protected const META_BOX_SCRIPT_HANDLE = 'gla-business-profile-share-meta-box';

	/**
	 * @var string Key the editors send a post ID under in a Heartbeat request, and read the post's
	 *             send status from in the response.
	 */
	public const HEARTBEAT_SYNC_STATUS_KEY = 'gla_gbp_sync_status';

	/** @var AssetsHandlerInterface */
	protected $assets_handler;

	/** @var LocalPostService */
	protected $local_post_service;

	/** @var LocationService */
	protected $location_service;

	/** @var OptionsInterface */
	protected $options;

	/**
	 * PostShareControl constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param LocalPostService       $local_post_service
	 * @param LocationService        $location_service
	 * @param OptionsInterface       $options
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, LocalPostService $local_post_service, LocationService $location_service, OptionsInterface $options ) {
		$this->assets_handler     = $assets_handler;
		$this->local_post_service = $local_post_service;
		$this->location_service   = $location_service;
		$this->options            = $options;
	}

	/**
	 * Register the share meta, the assets of the block editor panel or the Classic Editor meta box,
	 * and the send status the editors read from Heartbeat while a send is queued.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'heartbeat_received', [ $this, 'add_sync_status_to_heartbeat' ], 10, 2 );
	}

	/**
	 * Add a post's send status to the Heartbeat response, when the editor asks for it and the
	 * current user can edit the post. The editors ask while a send to Google is queued, and use the
	 * response to show the outcome without a reload.
	 *
	 * @param array $response The Heartbeat response.
	 * @param array $data     The data the editor sent with the Heartbeat request.
	 *
	 * @return array The response, with the post's send status under `HEARTBEAT_SYNC_STATUS_KEY`
	 *               shaped `{ syncOutcome, shareStatus }` when asked for.
	 */
	public function add_sync_status_to_heartbeat( $response, $data ) {
		$post_id = absint( $data[ self::HEARTBEAT_SYNC_STATUS_KEY ] ?? 0 );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return $response;
		}

		$response[ self::HEARTBEAT_SYNC_STATUS_KEY ] = [
			'syncOutcome' => $this->local_post_service->get_sync_outcome_for_display( $post_id ),
			'shareStatus' => $this->local_post_service->get_share_status( $post_id ),
		];

		return $response;
	}

	/**
	 * Register the share meta on every supported post type. It's REST-visible so the block editor
	 * can read and write it, and only users who can edit the post can change it.
	 */
	public function register_post_meta(): void {
		foreach ( LocalPostService::SUPPORTED_POST_TYPES as $post_type ) {
			register_post_meta(
				$post_type,
				self::SHARE_META_KEY,
				[
					'single'            => true,
					'type'              => 'boolean',
					'default'           => false,
					// `show_in_rest` exposes meta in every context, so the prepare_callback hides the
					// share choice outside the edit context, or from a user who can't edit this post.
					'show_in_rest'      => [
						'prepare_callback' => static function ( $value, $request ) {
							$post_id = (int) get_the_ID();

							if ( 'edit' !== $request->get_param( 'context' ) || ! current_user_can( 'edit_post', $post_id ) ) {
								return false;
							}

							return (bool) $value;
						},
					],
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

		$location = $this->get_location_data();

		if ( null === $location ) {
			return;
		}

		if ( $screen->is_block_editor() ) {
			$this->enqueue_entry(
				self::SCRIPT_HANDLE,
				'business-profile-share',
				[
					'businessProfileLocation'         => $location,
					'businessProfileSummaryMaxLength' => LocalPostService::SUMMARY_CHAR_LIMIT,
				]
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
				'businessProfileLocation'         => $location,
				'businessProfileSummaryMaxLength' => LocalPostService::SUMMARY_CHAR_LIMIT,
				'businessProfilePost'             => $this->get_post_data( $post ),
			]
		);
	}

	/**
	 * Register and enqueue a built entry's script and style, passing the plugin's base tracking
	 * data as `glaData`, and the given data as `glaBusinessProfileData`. Kept off `glaData` itself
	 * so it doesn't overload the global every admin screen's own `glaData` output shares.
	 *
	 * @param string $handle                 Script handle; the style uses it with a `-css` suffix.
	 * @param string $entry                  Name of the built entry in `js/build`.
	 * @param array  $business_profile_data  Data available to the script as `glaBusinessProfileData`.
	 */
	protected function enqueue_entry( string $handle, string $entry, array $business_profile_data ): void {
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
		) )
			->add_inline_script( 'glaData', $this->get_tracking_data() )
			->add_inline_script( 'glaBusinessProfileData', $business_profile_data );

		$style = new AdminStyleAsset(
			"{$handle}-css",
			"js/build/{$entry}",
			[],
			(string) filemtime( "{$build_dir}/{$entry}.css" )
		);

		// The scripts read a queued send's status from Heartbeat. Core already loads it in both
		// editors; this keeps it there if a plugin removes it from the editor. The plugin's asset
		// classes take dependencies only from the built asset file, so it's enqueued rather than
		// added as a dependency.
		wp_enqueue_script( 'heartbeat' );

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

		if ( null === $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return null;
		}

		return $screen;
	}

	/**
	 * Get the state of a post the Classic Editor meta box needs: its ID, its saved share choice and
	 * text, its share status, and its last sync outcome. The share status and sync outcome have the
	 * same shape as the post's REST fields and the Heartbeat response. "Generate from post" reads the
	 * editor's live content directly in JS, so this never needs the post's title or excerpt.
	 *
	 * @param WP_Post $post The post being edited.
	 *
	 * @return array
	 */
	protected function get_post_data( WP_Post $post ): array {
		return [
			'postId'         => $post->ID,
			'isShareEnabled' => (bool) get_post_meta( $post->ID, self::SHARE_META_KEY, true ),
			'summary'        => (string) get_post_meta( $post->ID, LocalPostService::SUMMARY_META_KEY, true ),
			'shareStatus'    => $this->local_post_service->get_share_status( $post->ID ),
			'syncOutcome'    => $this->local_post_service->get_sync_outcome_for_display( $post->ID ),
		];
	}

	/**
	 * Get the connected location's name and address for the scripts, or null when no location is
	 * connected. Passed from here rather than read from the `wc/gla` REST API, so every user who
	 * can edit the post gets the controls, not only those who can manage WooCommerce.
	 *
	 * @return array|null Shaped `{ title, address }`.
	 */
	protected function get_location_data(): ?array {
		$location = $this->location_service->get_selected_location();

		if ( null === $location ) {
			return null;
		}

		return [
			'title'   => $location['title'],
			'address' => $location['address'],
		];
	}

	/**
	 * Get the base data the plugin's tracking events read from `glaData`, as on its other admin screens.
	 *
	 * @return array
	 */
	protected function get_tracking_data(): array {
		return [
			'slug'          => $this->get_slug(),
			'initialWpData' => [
				'version' => $this->get_version(),
				'mcId'    => $this->options->get_merchant_id() ?: null,
				'adsId'   => $this->options->get_ads_id() ?: null,
			],
		];
	}
}
