<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container;

use Closure;
use ReflectionClass;

defined( 'ABSPATH' ) || exit;

/**
 * A binding definition.
 *
 * Holds the metadata a service provider declares via share()/add(): the id,
 * the concrete value (class name, closure, object, or literal), the
 * constructor-argument list, method-call list, tags, and shared flag.
 *
 * `build()` produces the actual instance when the container asks for it.
 */
class Definition {

	/**
	 * @var string
	 */
	private $id;

	/**
	 * What this definition produces when resolved. May be a class name
	 * string, a Closure factory, an already-constructed object, or a literal
	 * value (scalar, array, or non-class string).
	 *
	 * @var mixed
	 */
	private $concrete;

	/**
	 * Positional constructor arguments (or factory arguments for closures).
	 *
	 * @var array<int, mixed>
	 */
	private $arguments = [];

	/**
	 * Method calls to run after construction.
	 *
	 * @var array<int, array{method:string, args:array}>
	 */
	private $method_calls = [];

	/**
	 * @var array<int, string>
	 */
	private $tags = [];

	/**
	 * @var bool
	 */
	private $shared = false;

	/**
	 * @param string $id       The container identifier this definition is bound to.
	 * @param mixed  $concrete The concrete value. null means "use $id as a class name".
	 */
	public function __construct( string $id, $concrete = null ) {
		$this->id = $id;

		// Unwrap nested Definition wrappers. Providers sometimes build a
		// Definition to pass to share_concrete():
		//   share_concrete( X::class, new Definition( X::class, $closure ) )
		// We flatten that into this definition so the caller can still chain
		// addMethodCall() etc. on what they receive.
		if ( $concrete instanceof self ) {
			$this->concrete     = $concrete->concrete;
			$this->arguments    = $concrete->arguments;
			$this->method_calls = $concrete->method_calls;
			$this->tags         = $concrete->tags;
			return;
		}

		$this->concrete = $concrete ?? $id;
	}

	public function getId(): string {
		return $this->id;
	}

	public function isShared(): bool {
		return $this->shared;
	}

	public function setShared( bool $shared ): self {
		$this->shared = $shared;
		return $this;
	}

	/**
	 * @return array<int, string>
	 */
	public function getTags(): array {
		return $this->tags;
	}

	public function hasTag( string $tag ): bool {
		return in_array( $tag, $this->tags, true );
	}

	public function addTag( string $tag ): self {
		if ( ! in_array( $tag, $this->tags, true ) ) {
			$this->tags[] = $tag;
		}
		return $this;
	}

	/**
	 * Append a single positional argument.
	 *
	 * @param mixed $argument
	 */
	public function addArgument( $argument ): self {
		$this->arguments[] = $argument;
		return $this;
	}

	/**
	 * Append multiple positional arguments.
	 *
	 * @param array<int, mixed> $arguments
	 */
	public function addArguments( array $arguments ): self {
		foreach ( $arguments as $argument ) {
			$this->arguments[] = $argument;
		}
		return $this;
	}

	/**
	 * Register a method to call on the built instance, with arguments that
	 * will be resolved through the container at build time.
	 *
	 * @param string             $method
	 * @param array<int, mixed>  $args
	 */
	public function addMethodCall( string $method, array $args = [] ): self {
		$this->method_calls[] = [
			'method' => $method,
			'args'   => $args,
		];
		return $this;
	}

	/**
	 * Build the instance (or return the literal value) this definition
	 * represents.
	 *
	 * @return mixed
	 */
	public function build( PluginContainer $container ) {
		$concrete = $this->concrete;

		// Closure → factory. Pass the container for classes that want to
		// resolve things dynamically inside their factory.
		if ( $concrete instanceof Closure ) {
			$instance = $concrete( $container );
			return $this->apply_method_calls( $instance, $container );
		}

		// Already an object → use it as-is.
		if ( is_object( $concrete ) ) {
			return $this->apply_method_calls( $concrete, $container );
		}

		// String concretes that name an actual class or interface → build
		// via reflection with the configured arguments.
		if ( is_string( $concrete ) && ( class_exists( $concrete ) || interface_exists( $concrete ) ) ) {
			$instance = $this->instantiate_class( $concrete, $container );
			return $this->apply_method_calls( $instance, $container );
		}

		// Everything else (non-class strings, scalars, arrays, null) is a
		// literal value. Return as-is; method calls don't make sense here.
		return $concrete;
	}

	/**
	 * @return object
	 */
	private function instantiate_class( string $class, PluginContainer $container ) {
		$reflection = new ReflectionClass( $class );

		if ( ! $reflection->isInstantiable() ) {
			throw new ContainerException(
				sprintf(
					'Cannot instantiate %s while building "%s": class is abstract, an interface, or has a non-public constructor.',
					$class,
					$this->id
				)
			);
		}

		$constructor = $reflection->getConstructor();

		// No constructor → nothing to resolve, nothing to re-enter.
		if ( null === $constructor ) {
			if ( ! empty( $this->arguments ) ) {
				throw new ContainerException(
					sprintf(
						'%s has no constructor but %d argument(s) were declared in the definition for "%s".',
						$class,
						count( $this->arguments ),
						$this->id
					)
				);
			}
			return $reflection->newInstance();
		}

		// Verify we either have args for every required parameter, or those
		// parameters have defaults we can rely on.
		$required = 0;
		foreach ( $constructor->getParameters() as $param ) {
			if ( ! $param->isOptional() ) {
				++$required;
			}
		}
		if ( count( $this->arguments ) < $required ) {
			throw new ContainerException(
				sprintf(
					'Not enough arguments to instantiate %s (binding "%s"): %d required, %d provided. ' .
					'Declare the missing dependencies in the provider\'s share()/add() call.',
					$class,
					$this->id,
					$required,
					count( $this->arguments )
				)
			);
		}

		$resolved = $container->resolve_arguments(
			$this->arguments,
			sprintf( '%s::__construct', $class )
		);

		return $reflection->newInstanceArgs( $resolved );
	}

	/**
	 * @param object $instance
	 * @return object
	 */
	private function apply_method_calls( $instance, PluginContainer $container ) {
		if ( empty( $this->method_calls ) ) {
			return $instance;
		}
		if ( ! is_object( $instance ) ) {
			return $instance;
		}
		foreach ( $this->method_calls as $call ) {
			$resolved = $container->resolve_arguments(
				$call['args'],
				sprintf( '%s::%s()', get_class( $instance ), $call['method'] )
			);
			$instance->{$call['method']}( ...$resolved );
		}
		return $instance;
	}
}
