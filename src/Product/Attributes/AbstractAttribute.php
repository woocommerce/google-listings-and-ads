<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

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
	public function __construct( $value ) {
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
	 * @return string
	 */
	public function __toString() {
		return (string) $this->get_value();
	}

}
