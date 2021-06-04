<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Renderable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

interface MetaBoxInterface extends Service, Conditional, Renderable {
	public const SCREEN_PRODUCT = 'product';

	public const CONTEXT_NORMAL   = 'normal';
	public const CONTEXT_SIDE     = 'side';
	public const CONTEXT_ADVANCED = 'advanced';

	public const PRIORITY_DEFAULT = 'default';
	public const PRIORITY_LOW     = 'low';
	public const PRIORITY_CORE    = 'core';
	public const PRIORITY_HIGH    = 'high';

	/**
	 * Meta box ID (used in the 'id' attribute for the meta box).
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Title of the meta box.
	 *
	 * @return string
	 */
	public function get_title(): string;

	/**
	 * Function that fills the box with the desired content.
	 *
	 * The function should echo its output.
	 *
	 * @return callable
	 */
	public function get_callback(): callable;

	/**
	 * The screen or screens on which to show the box (such as a post type, 'link', or 'comment').
	 *
	 * Default is the current screen.
	 *
	 * @return string
	 */
	public function get_screen(): string;

	/**
	 * The context within the screen where the box should display. Available contexts vary from screen to
	 * screen. Post edit screen contexts include 'normal', 'side', and 'advanced'. Comments screen contexts
	 * include 'normal' and 'side'. Menus meta boxes (accordion sections) all use the 'side' context.
	 *
	 * Global default is 'advanced'.
	 *
	 * @return string
	 */
	public function get_context(): string;

	/***
	 * The priority within the context where the box should show.
	 *
	 * Accepts 'high', 'core', 'default', or 'low'. Default 'default'.
	 *
	 * @return string
	 */
	public function get_priority(): string;

	/**
	 * Data that should be set as the $args property of the box array (which is the second parameter passed to your callback).
	 *
	 * @return array
	 */
	public function get_callback_args(): array;

	/**
	 * Returns an array of CSS classes to apply to the box.
	 *
	 * @return array
	 */
	public function get_classes(): array;
}
