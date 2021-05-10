<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

/**
 * Class BaseAsset
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
abstract class BaseAsset implements Asset {

	use PluginHelper;

	/**
	 * The file extension for the source.
	 *
	 * @var string
	 */
	protected $file_extension;

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
	 * Array of dependencies for the asset.
	 *
	 * @var array
	 */
	protected $dependencies = [];

	/**
	 * The version string for the asset.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * @var callable
	 */
	protected $enqueue_condition_callback;

	/**
	 * BaseAsset constructor.
	 *
	 * @param string        $file_extension                The asset file extension.
	 * @param string        $handle                        The asset handle.
	 * @param string        $uri                           The URI for the asset.
	 * @param array         $dependencies                  (Optional) Any dependencies the asset has.
	 * @param string        $version                       (Optional) A version string for the asset. Will default to
	 *                                                     the plugin version if not set.
	 * @param callable|null $enqueue_condition_callback    (Optional) The asset is always enqueued if this callback
	 *                                                     returns true or isn't set.
	 */
	public function __construct(
		string $file_extension,
		string $handle,
		string $uri,
		array $dependencies = [],
		string $version = '',
		callable $enqueue_condition_callback = null
	) {
		$this->file_extension             = $file_extension;
		$this->handle                     = $handle;
		$this->uri                        = $this->get_uri_from_path( $uri );
		$this->dependencies               = $dependencies;
		$this->version                    = $version ?: $this->get_version();
		$this->enqueue_condition_callback = $enqueue_condition_callback;
	}

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
	 * Get the condition callback to run when enqueuing the asset.
	 *
	 * The asset will only be enqueued if the callback returns true.
	 *
	 * @return bool
	 */
	public function can_enqueue(): bool {
		return (bool) call_user_func( $this->enqueue_condition_callback, $this );
	}

	/**
	 * Enqueue the asset within WordPress.
	 */
	public function enqueue(): void {
		if ( ! $this->can_enqueue() ) {
			return;
		}

		$this->defer_action(
			$this->get_enqueue_action(),
			$this->get_enqueue_callback(),
			$this->enqueue_priority
		);
	}

	/**
	 * Dequeue the asset within WordPress.
	 */
	public function dequeue(): void {
		$this->defer_action(
			$this->get_dequeue_action(),
			$this->get_dequeue_callback(),
			$this->dequeue_priority
		);
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->defer_action(
			$this->get_register_action(),
			$this->get_register_callback(),
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
	 * Get the dequeue action to use.
	 *
	 * @return string
	 */
	protected function get_dequeue_action(): string {
		return 'wp_print_scripts';
	}

	/**
	 * Add a callable to an action, or run it immediately if the action has already fired.
	 *
	 * @param string   $action
	 * @param callable $callback
	 * @param int      $priority
	 */
	protected function defer_action( string $action, callable $callback, int $priority = 10 ): void {
		if ( did_action( $action ) ) {
			$callback();

			return;
		}

		add_action( $action, $callback, $priority );
	}

	/**
	 * Convert a file path to a URI for a source.
	 *
	 * @param string $path The source file path.
	 *
	 * @return string
	 */
	protected function get_uri_from_path( string $path ): string {
		$path = $this->normalize_source_path( $path );
		$path = str_replace( $this->get_root_dir(), '', $path );

		return $this->get_plugin_url( $path );
	}

	/**
	 * Normalize a source path with a given file extension.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string
	 */
	protected function normalize_source_path( string $path ): string {
		$path = ltrim( $path, '/' );
		$path = $this->maybe_add_extension( $path );
		$path = "{$this->get_root_dir()}/{$path}";

		return $this->maybe_add_minimized_extension( $path );
	}

	/**
	 * Possibly add an extension to a path.
	 *
	 * @param string $path Path where an extension may be needed.
	 *
	 * @return string
	 */
	protected function maybe_add_extension( string $path ): string {
		$detected_extension = pathinfo( $path, PATHINFO_EXTENSION );
		if ( $this->file_extension !== $detected_extension ) {
			$path .= ".{$this->file_extension}";
		}

		return $path;
	}

	/**
	 * Possibly add a minimized extension to a path.
	 *
	 * @param string $path Path where a minimized extension may be needed.
	 *
	 * @return string
	 * @throws InvalidAsset When no asset can be found.
	 */
	protected function maybe_add_minimized_extension( string $path ): string {
		$minimized_path = str_replace( ".{$this->file_extension}", ".min.{$this->file_extension}", $path );

		// Validate that at least one version of the file exists.
		$path_readable      = is_readable( $path );
		$minimized_readable = is_readable( $minimized_path );
		if ( ! $path_readable && ! $minimized_readable ) {
			throw InvalidAsset::invalid_path( $path );
		}

		// If we only have one available, return the available one no matter what.
		if ( ! $minimized_readable ) {
			return $path;
		} elseif ( ! $path_readable ) {
			return $minimized_path;
		}

		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $path : $minimized_path;
	}

	/**
	 * Get the register callback to use.
	 *
	 * @return callable
	 */
	abstract protected function get_register_callback(): callable;

	/**
	 * Get the enqueue callback to use.
	 *
	 * @return callable
	 */
	abstract protected function get_enqueue_callback(): callable;

	/**
	 * Get the dequeue callback to use.
	 *
	 * @return callable
	 */
	abstract protected function get_dequeue_callback(): callable;
}
