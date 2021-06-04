<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\ViewHelperTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\View\ViewException;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractMetaBox
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox
 */
abstract class AbstractMetaBox implements MetaBoxInterface {

	use AdminConditional;
	use ViewHelperTrait;

	protected const VIEW_PATH = 'meta-box';

	/**
	 * @var Admin
	 */
	protected $admin;

	/**
	 * AbstractMetaBox constructor.
	 *
	 * @param Admin $admin
	 */
	protected function __construct( Admin $admin ) {
		$this->admin = $admin;
	}

	/**
	 * The context within the screen where the box should display. Available contexts vary from screen to
	 * screen. Post edit screen contexts include 'normal', 'side', and 'advanced'. Comments screen contexts
	 * include 'normal' and 'side'. Menus meta boxes (accordion sections) all use the 'side' context.
	 *
	 * Global default is 'advanced'.
	 *
	 * @return string
	 */
	public function get_context(): string {
		return self::CONTEXT_ADVANCED;
	}

	/***
	 * The priority within the context where the box should show.
	 *
	 * Accepts 'high', 'core', 'default', or 'low'. Default 'default'.
	 *
	 * @return string
	 */
	public function get_priority(): string {
		return self::PRIORITY_DEFAULT;
	}

	/**
	 * Data that should be set as the $args property of the box array (which is the second parameter passed to your callback).
	 *
	 * @return array
	 */
	public function get_callback_args(): array {
		return [];
	}

	/**
	 * Returns an array of CSS classes to apply to the box.
	 *
	 * @return array
	 */
	public function get_classes(): array {
		return [];
	}

	/**
	 * Function that fills the box with the desired content.
	 *
	 * The function should echo its output.
	 *
	 * @return callable
	 */
	public function get_callback(): callable {
		return [ $this, 'handle_callback' ];
	}

	/**
	 * Called by WordPress when rendering the meta box.
	 *
	 * The function should echo its output.
	 *
	 * @param WP_Post $post The WordPress post object the box is loaded for.
	 * @param array   $data Array of box data passed to the callback by WordPress.
	 *
	 * @return void
	 *
	 * @throws ViewException If the meta box view can't be rendered.
	 */
	public function handle_callback( WP_Post $post, array $data ): void {
		$args    = $data['args'] ?? [];
		$context = $this->get_view_context( $post, $args );

		echo wp_kses( $this->render( $context ), $this->get_allowed_html_form_tags() );
	}

	/**
	 * Render the meta box.
	 *
	 * The view templates need to be placed under 'views/meta-box' and named
	 * using the meta box ID specified by the `get_id` method.
	 *
	 * @param array $context Optional. Contextual information to use while
	 *                       rendering. Defaults to an empty array.
	 *
	 * @return string Rendered result.
	 *
	 * @throws ViewException If the view doesn't exist or can't be loaded.
	 *
	 * @see self::get_id To see and modify the view file name.
	 */
	public function render( array $context = [] ): string {
		$view_path = path_join( self::VIEW_PATH, $this->get_id() );

		return $this->admin->get_view( $view_path, $context );
	}

	/**
	 * Returns an array of variables to be used in the view.
	 *
	 * @param WP_Post $post The WordPress post object the box is loaded for.
	 * @param array   $args Array of data passed to the callback. Defined by `get_callback_args`.
	 *
	 * @return array
	 */
	abstract protected function get_view_context( WP_Post $post, array $args ): array;
}
