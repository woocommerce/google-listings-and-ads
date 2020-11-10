<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Closure;

/**
 * Class BaseAsset
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
abstract class BaseAsset implements Asset {

	/**
	 * Priority for registering an asset.
	 *
	 * @var int
	 */
	protected $registration_priority = 1;

	/**
	 * Priority for enqueuing an asset.
	 *
	 * @var int
	 */
	protected $enqueue_priority = 10;

	/**
	 * Priority for dequeuing an asset.
	 *
	 * @var int
	 */
	protected $dequeue_priority = 50;

	/**
	 * The asset handle.
	 *
	 * @var string
	 */
	protected $handle;

	/**
	 * The full URI to the asset.
	 *
	 * @var string
	 */
	protected $uri;

	/**
	 * Get the handle of the asset. The handle serves as the ID within WordPress.
	 *
	 * @return string
	 */
	public function get_handle(): string {
		return $this->handle;
	}

	/**
	 * Get the URI for the asset.
	 *
	 * @return string
	 */
	public function get_uri(): string {
		return $this->uri;
	}

	/**
	 * Enqueue the asset within WordPress.
	 */
	public function enqueue(): void {
		$this->defer_action(
			$this->get_enqueue_action(),
			$this->get_enqueue_closure(),
			$this->enqueue_priority
		);
	}

	/**
	 * Dequeue the asset within WordPress.
	 */
	public function dequeue(): void {
		$this->defer_action(
			$this->get_dequeue_action(),
			$this->get_dequeue_closure(),
			$this->dequeue_priority
		);
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->defer_action(
			$this->get_register_action(),
			$this->get_register_closure(),
			$this->registration_priority
		);
	}

	/**
	 * Get the register action to use.
	 *
	 * @since 0.1.0
	 *
	 * @return string Register action to use.
	 */
	protected function get_register_action(): string {
		return $this->get_enqueue_action();
	}

	/**
	 * Get the enqueue action to use.
	 *
	 * @return string
	 */
	protected function get_enqueue_action(): string {
		return 'wp_enqueue_scripts';
	}

	/**
	 * Get the enqueue action to use.
	 *
	 * @return string
	 */
	protected function get_dequeue_action(): string {
		return 'wp_print_scripts';
	}

	/**
	 * Add a closure to an action, or run it immediately if the action has already fired.
	 *
	 * @param string  $action
	 * @param Closure $closure
	 * @param int     $priority
	 */
	protected function defer_action( string $action, Closure $closure, int $priority = 10 ): void {
		if ( did_action( $action ) ) {
			$closure();
			return;
		}

		add_action( $action, $closure, $priority );
	}

	/**
	 * Get the enqueue closure to use.
	 *
	 * @return Closure
	 */
	abstract protected function get_register_closure(): Closure;

	/**
	 * Get the enqueue closure to use.
	 *
	 * @return Closure
	 */
	abstract protected function get_enqueue_closure(): Closure;

	/**
	 * Get the dequeue closure to use.
	 *
	 * @return Closure
	 */
	abstract protected function get_dequeue_closure(): Closure;
}
