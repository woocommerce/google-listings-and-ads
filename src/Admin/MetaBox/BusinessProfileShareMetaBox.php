<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileShareMetaBox
 *
 * The Classic Editor's "Google Business Profile" meta box, holding the controls for sharing a post
 * to the connected location. It sits right under the Publish box, appears only when a location is
 * connected, and is hidden in the block editor, which has its own panel. The share choice and the
 * text are saved with the post's form.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox
 */
class BusinessProfileShareMetaBox extends AbstractMetaBox implements Registerable {

	public const ID = 'business_profile_share';

	public const FIELD_SHARE   = 'share';
	public const FIELD_SUMMARY = 'summary';

	/** @var string Post type the meta box is shown on. */
	protected const SCREEN = 'post';

	/** @var string ID of WordPress's own Publish box. */
	protected const PUBLISH_BOX_ID = 'submitdiv';

	protected const NONCE_ACTION = 'gla_business_profile_share_save';
	protected const NONCE_NAME   = 'gla_business_profile_share_nonce';

	/** @var PostShareControl */
	protected $post_share_control;

	/**
	 * BusinessProfileShareMetaBox constructor.
	 *
	 * @param Admin            $admin
	 * @param PostShareControl $post_share_control
	 */
	public function __construct( Admin $admin, PostShareControl $post_share_control ) {
		$this->post_share_control = $post_share_control;
		parent::__construct( $admin );
	}

	/**
	 * Register the save path and the placement under the Publish box.
	 */
	public function register(): void {
		add_action( 'save_post_' . self::SCREEN, [ $this, 'handle_submission' ], 10, 2 );
		add_action( 'add_meta_boxes_' . self::SCREEN, [ $this, 'move_below_publish_box' ] );
	}

	/**
	 * Only register the meta box when a location is connected.
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return null !== $this->post_share_control->get_connected_location();
	}

	/**
	 * @return string
	 */
	public function get_id(): string {
		return self::ID;
	}

	/**
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Google Business Profile', 'google-listings-and-ads' );
	}

	/**
	 * @return string
	 */
	public function get_screen(): string {
		return self::SCREEN;
	}

	/**
	 * @return string
	 */
	public function get_context(): string {
		return self::CONTEXT_SIDE;
	}

	/**
	 * Registered with core priority so it can be moved next to the Publish box, which shares it.
	 *
	 * @return string
	 */
	public function get_priority(): string {
		return self::PRIORITY_CORE;
	}

	/**
	 * Hide the meta box in the block editor, which shows its own panel instead.
	 *
	 * @return array
	 */
	public function get_callback_args(): array {
		return [ '__back_compat_meta_box' => true ];
	}

	/**
	 * @return array
	 */
	public function get_classes(): array {
		return [ 'gla_meta_box' ];
	}

	/**
	 * Move the meta box right after the Publish box. A merchant's own dragged order still wins.
	 */
	public function move_below_publish_box(): void {
		global $wp_meta_boxes;

		$boxes = $wp_meta_boxes[ self::SCREEN ][ self::CONTEXT_SIDE ][ self::PRIORITY_CORE ] ?? null;

		if ( ! is_array( $boxes ) || ! isset( $boxes[ self::ID ], $boxes[ self::PUBLISH_BOX_ID ] ) ) {
			return;
		}

		$meta_box = $boxes[ self::ID ];
		unset( $boxes[ self::ID ] );

		$reordered = [];
		foreach ( $boxes as $id => $box ) {
			$reordered[ $id ] = $box;

			if ( self::PUBLISH_BOX_ID === $id ) {
				$reordered[ self::ID ] = $meta_box;
			}
		}

		$wp_meta_boxes[ self::SCREEN ][ self::CONTEXT_SIDE ][ self::PRIORITY_CORE ] = $reordered;
	}

	/**
	 * Save the share choice and the text when the Classic Editor's form is submitted.
	 *
	 * @param int|mixed     $post_id
	 * @param WP_Post|mixed $post
	 */
	public function handle_submission( $post_id, $post ): void {
		$post_id = absint( $post_id );

		// phpcs:disable WordPress.Security.NonceVerification
		// nonce is verified by self::verify_nonce
		if ( ! $post instanceof WP_Post || ! $this->verify_nonce() ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// The fields are rendered by the meta box's script; without them, keep what's saved rather
		// than clearing it.
		if ( ! isset( $_POST[ $this->get_share_field_id() ], $_POST[ $this->get_summary_field_id() ] ) ) {
			return;
		}

		$is_share_enabled = ! empty( $_POST[ $this->get_share_field_id() ] );
		$summary          = sanitize_textarea_field( wp_unslash( $_POST[ $this->get_summary_field_id() ] ) );
		// phpcs:enable WordPress.Security.NonceVerification

		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, $is_share_enabled );
		update_post_meta( $post_id, LocalPostService::SUMMARY_META_KEY, $summary );
	}

	/**
	 * @param WP_Post $post
	 * @param array   $args
	 *
	 * @return array
	 */
	protected function get_view_context( WP_Post $post, array $args ): array {
		return [
			'share_field_id'   => $this->get_share_field_id(),
			'summary_field_id' => $this->get_summary_field_id(),
			'nonce_action'     => self::NONCE_ACTION,
			'nonce_name'       => self::NONCE_NAME,
		];
	}

	/**
	 * Whether the submitted form carries this meta box's nonce.
	 *
	 * @return bool
	 */
	protected function verify_nonce(): bool {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the nonce is checked, not stored.
		return ! empty( $_POST[ self::NONCE_NAME ] ) && wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION );
	}

	/**
	 * @return string
	 */
	protected function get_share_field_id(): string {
		return $this->prefix_field_id( self::FIELD_SHARE );
	}

	/**
	 * @return string
	 */
	protected function get_summary_field_id(): string {
		return $this->prefix_field_id( self::FIELD_SUMMARY );
	}
}
