<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Post-construction setter injection for every instance that implements a
 * given interface.
 *
 * Used by providers via:
 *
 *     $this->getContainer()
 *         ->inflector( OptionsAwareInterface::class )
 *         ->invokeMethod( 'set_options_object', [ OptionsInterface::class ] );
 *
 * The container calls Inflector::inflect() on every just-built instance, so
 * every *AwareInterface-implementing service gets its setter called with the
 * resolved dependency, without having to thread that dependency through its
 * constructor.
 */
class Inflector {

	/**
	 * The interface (or parent class) an instance must implement for this
	 * inflector to apply.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * @var array<int, array{method:string, args:array}>
	 */
	private $method_calls = [];

	public function __construct( string $type ) {
		$this->type = $type;
	}

	public function getType(): string {
		return $this->type;
	}

	/**
	 * Register a setter method to call on matching instances. Arguments are
	 * resolved through the container at inflection time, just like
	 * constructor arguments.
	 *
	 * @param string            $method
	 * @param array<int, mixed> $args
	 */
	public function invokeMethod( string $method, array $args = [] ): self {
		$this->method_calls[] = [
			'method' => $method,
			'args'   => $args,
		];
		return $this;
	}

	public function applies( object $instance ): bool {
		return $instance instanceof $this->type;
	}

	public function inflect( object $instance, PluginContainer $container ): void {
		foreach ( $this->method_calls as $call ) {
			$resolved = $container->resolve_arguments(
				$call['args'],
				sprintf( 'inflector %s::%s()', $this->type, $call['method'] )
			);
			$instance->{$call['method']}( ...$resolved );
		}
	}
}
