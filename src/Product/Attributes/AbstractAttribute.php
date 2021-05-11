<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractAttribute
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
abstract class AbstractAttribute implements AttributeInterface {

	/**
	 * @var mixed
	 */
	protected $value = null;

	/**
	 * AbstractAttribute constructor.
	 *
	 * @param mixed $value
	 */
	public function __construct( $value = null ) {
		$this->value = $value;
	}

	/**
	 * @return mixed
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function set_value( $value ): AbstractAttribute {
		$this->value = $value;

		return $this;
	}

	/**
	 * Return an array of WooCommerce product types that this attribute can be applied to.
	 *
	 * @return array
	 */
	public static function get_applicable_product_types(): array {
		return [ 'simple', 'variable', 'variation' ];
	}

	/**
	 * Return the input class used for this attribute.
	 *
	 * Must be an instance of InputInterface
	 *
	 * @return string FQN of the input class
	 *
	 * @see InputInterface
	 */
	public static function get_input_type(): string {
		return Text::class;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return (string) $this->get_value();
	}

}
