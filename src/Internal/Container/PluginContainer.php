<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * The plugin's dependency-injection container.
 *
 * Replaces league/container with a simpler implementation tuned to how this
 * plugin actually uses DI. The public API is deliberately compatible with
 * the subset of League's API the service providers rely on — share()/add(),
 * addShared()/add() with a concrete, addServiceProvider(), and inflector() —
 * so providers don't need to change.
 *
 * Key design differences from League:
 *
 *   1. Eager provider registration. addServiceProvider() immediately calls
 *      the provider's register() method. There is no deferred-boot mechanism
 *      and no $provides array. This eliminates a whole class of ordering
 *      bugs where has() would return false for a deferred binding and cause
 *      its string ID to be passed literally as a constructor argument.
 *
 *   2. Strict argument resolution. When a definition lists a string as a
 *      constructor/method argument, the container treats it as a container
 *      ID if a binding exists, passes it through as a literal if it's not
 *      a known class or interface name, and throws NotFoundException if it
 *      IS a known class/interface but no binding exists. This makes the
 *      BatchProductHelper → AttributeMappingRulesQuery class of bugs
 *      impossible — you get an explicit error at the point of the missing
 *      binding instead of a mystery TypeError later.
 *
 *   3. No autowiring. Constructor arguments must be declared explicitly in
 *      the provider's share()/add() call. (This matches the current plugin
 *      behaviour, which never enabled League's ReflectionContainer delegate.)
 *
 *   4. No cycle detection. A service whose constructor triggers WordPress
 *      hooks that transitively need the same service is a legitimate
 *      pattern (see RESTServer + rest_api_init) and the recursion
 *      terminates naturally. Genuine circular dependencies (A ctor wants
 *      B, B ctor wants A) would infinite-recurse and be surfaced by PHP's
 *      stack limit — the same behaviour as league/container.
 */
class PluginContainer implements ContainerInterface {

	/**
	 * Map of id → Definition.
	 *
	 * @var array<string, Definition>
	 */
	private $definitions = [];

	/**
	 * Cache of already-built instances for shared definitions.
	 *
	 * @var array<string, mixed>
	 */
	private $shared_instances = [];

	/**
	 * Map of tag name → list of ids tagged with it.
	 *
	 * @var array<string, array<int, string>>
	 */
	private $tags = [];

	/**
	 * Inflectors, keyed by the interface/class they match.
	 *
	 * @var array<string, Inflector>
	 */
	private $inflectors = [];


	/**
	 * Whether the tag index may be stale. Set to true whenever a definition
	 * is added (which might be followed by Definition::addTag() chaining
	 * that the container can't observe directly). Reset to false whenever
	 * the tag index is fully rebuilt.
	 *
	 * @var bool
	 */
	private $tags_dirty = false;

	// -------------------------------------------------------------------- //
	// Registration API (called by providers during register())              //
	// -------------------------------------------------------------------- //

	/**
	 * Register a transient binding. Each get() returns a new instance.
	 *
	 * @param string $id       Container identifier (typically a class or interface name).
	 * @param mixed  $concrete Optional concrete. If null, $id is treated as the class to instantiate.
	 */
	public function add( string $id, $concrete = null ): Definition {
		$definition = new Definition( $id, $concrete );
		$definition->set_shared( false );
		$this->definitions[ $id ] = $definition;
		$this->tags_dirty         = true;
		return $definition;
	}

	/**
	 * Register a shared (singleton) binding. The instance is built on first
	 * get() and cached for subsequent calls.
	 *
	 * @param string $id       Container identifier.
	 * @param mixed  $concrete Optional concrete. If null, $id is treated as the class to instantiate.
	 */
	public function addShared( string $id, $concrete = null ): Definition {
		$definition = new Definition( $id, $concrete );
		$definition->set_shared( true );
		$this->definitions[ $id ] = $definition;
		$this->tags_dirty         = true;
		return $definition;
	}

	/**
	 * Return the inflector for the given type, creating one on first use.
	 *
	 * @param string $type Interface or class name. Instances matching this
	 *                     type will have the inflector's method calls
	 *                     applied after construction.
	 */
	public function inflector( string $type ): Inflector {
		if ( ! isset( $this->inflectors[ $type ] ) ) {
			$this->inflectors[ $type ] = new Inflector( $type );
		}
		return $this->inflectors[ $type ];
	}

	/**
	 * Register a service provider and eagerly call its register() method.
	 *
	 * Unlike League, there is no deferred booting — providers are registered
	 * in the order they are added.
	 *
	 * @param ServiceProvider $provider
	 */
	public function addServiceProvider( ServiceProvider $provider ): void {
		$provider->set_container( $this );
		$provider->register();
	}

	// -------------------------------------------------------------------- //
	// PSR-11 resolution API                                                  //
	// -------------------------------------------------------------------- //

	/**
	 * Resolve an identifier.
	 *
	 * Resolution order:
	 *   1. Pre-built shared instance cache.
	 *   2. Direct definition (built now, cached if shared).
	 *   3. Tag collection (array of all instances tagged with $id).
	 *
	 * @param string $id
	 * @return mixed
	 * @throws NotFoundException When no binding, cached instance, or tag collection matches $id.
	 */
	public function get( $id ) {
		if ( array_key_exists( $id, $this->shared_instances ) ) {
			return $this->shared_instances[ $id ];
		}

		if ( isset( $this->definitions[ $id ] ) ) {
			return $this->build_from_definition( $id );
		}

		$this->ensure_tags_indexed();
		if ( isset( $this->tags[ $id ] ) ) {
			return $this->build_tag_collection( $id );
		}

		throw new NotFoundException(
			sprintf( 'No binding registered for "%s".', $id )
		);
	}

	/**
	 * Whether the container can resolve an identifier.
	 *
	 * Returns true for direct definitions, pre-cached shared instances,
	 * and tag collections.
	 *
	 * @param string $id
	 */
	public function has( $id ): bool {
		if ( array_key_exists( $id, $this->shared_instances ) ) {
			return true;
		}
		if ( isset( $this->definitions[ $id ] ) ) {
			return true;
		}
		$this->ensure_tags_indexed();
		return isset( $this->tags[ $id ] );
	}

	// -------------------------------------------------------------------- //
	// Internal helpers                                                       //
	// -------------------------------------------------------------------- //

	/**
	 * Resolve a list of raw arguments, turning strings that match bindings
	 * into their resolved values and passing everything else through.
	 *
	 * Rules:
	 *   - Non-string values (objects, scalars, arrays) pass through literally.
	 *   - Strings matching an existing binding are resolved via get().
	 *   - Strings that name a real class or interface but have NO binding
	 *     throw NotFoundException — this is the strictness that prevents
	 *     cross-provider dependency bugs.
	 *   - Other strings (e.g. URLs, slugs) pass through literally.
	 *
	 * @param array<int, mixed> $arguments
	 * @param string            $context   Human description of where the
	 *                                     arguments come from, used in error
	 *                                     messages (e.g. "Foo::__construct").
	 *
	 * @return array<int, mixed>
	 *
	 * @internal Used by Definition and Inflector.
	 */
	public function resolve_arguments( array $arguments, string $context ): array {
		$resolved = [];
		foreach ( $arguments as $index => $argument ) {
			$resolved[] = $this->resolve_argument( $argument, $context, $index );
		}
		return $resolved;
	}

	/**
	 * @param mixed  $argument
	 * @param string $context
	 * @param int    $index
	 * @return mixed
	 * @throws NotFoundException When a string argument is a known class/interface with no binding.
	 */
	private function resolve_argument( $argument, string $context, int $index ) {
		if ( ! is_string( $argument ) ) {
			return $argument;
		}

		if ( $this->has( $argument ) ) {
			return $this->get( $argument );
		}

		if ( class_exists( $argument ) || interface_exists( $argument ) ) {
			throw new NotFoundException(
				sprintf(
					'%s: argument #%d references "%s", which is a known class or interface but has no binding in the container. Declare it via share()/add() in a ServiceProvider::register().',
					$context,
					$index,
					$argument
				)
			);
		}

		// Literal string (slug, URL, etc.).
		return $argument;
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	private function build_from_definition( string $id ) {
		$definition = $this->definitions[ $id ];

		// No cycle detection here on purpose. A service's constructor may
		// legitimately trigger re-entrant get() calls for the same id via
		// WordPress hooks (e.g. RESTServer's __construct calls
		// rest_get_server() which fires rest_api_init whose callbacks
		// iterate the rest_controller tag, each of whose constructors
		// takes a RESTServer). In that pattern the recursion terminates
		// because rest_get_server() is idempotent after its first call.
		// For a genuine circular dependency (A ctor wants B, B ctor wants
		// A), recursion is unbounded and PHP's stack overflow will
		// surface it. league/container behaved the same way.
		$instance = $definition->build( $this );

		// Cache BEFORE applying inflectors so a re-entrant get() for this
		// id from inside a setter finds the instance instead of starting
		// another build.
		if ( $definition->is_shared() ) {
			$this->shared_instances[ $id ] = $instance;
		}

		// Apply every matching inflector to objects only. Literal values
		// (scalars, non-class strings) obviously don't get inflected.
		if ( is_object( $instance ) ) {
			$this->apply_inflectors( $instance );
		}

		return $instance;
	}

	/**
	 * Resolve every id in a tag into an array of instances.
	 *
	 * @param string $tag
	 * @return array<int, mixed>
	 */
	private function build_tag_collection( string $tag ): array {
		$instances = [];
		foreach ( $this->tags[ $tag ] as $id ) {
			$instances[] = $this->get( $id );
		}
		return $instances;
	}

	/**
	 * @param object $instance
	 */
	private function apply_inflectors( object $instance ): void {
		foreach ( $this->inflectors as $inflector ) {
			if ( $inflector->applies( $instance ) ) {
				$inflector->inflect( $instance, $this );
			}
		}
	}

	/**
	 * Rebuild the tag index from every known definition if any definitions
	 * have been added or modified since the last index build.
	 *
	 * Deferred rebuilding is necessary because providers use chained
	 * addTag() calls on the Definition returned from addShared()/add(), and
	 * the container has no hook to observe those fluent modifications. The
	 * tags_dirty flag covers the worst case: any container mutation
	 * invalidates the index until we rebuild.
	 */
	private function ensure_tags_indexed(): void {
		if ( ! $this->tags_dirty ) {
			return;
		}
		$this->tags = [];
		foreach ( $this->definitions as $id => $definition ) {
			foreach ( $definition->get_tags() as $tag ) {
				$this->tags[ $tag ][] = $id;
			}
		}
		$this->tags_dirty = false;
	}
}
