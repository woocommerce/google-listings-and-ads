<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractInput
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
abstract class AbstractInput implements InputInterface {

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * AbstractInput constructor.
	 *
	 * @param string $type
	 */
	public function __construct( string $type ) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * @return mixed
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @param string $id
	 *
	 * @return InputInterface
	 */
	public function set_id( string $id ): InputInterface {
		$this->id = $id;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return InputInterface
	 */
	public function set_name( string $name ): InputInterface {
		$this->name = $name;

		return $this;
	}

	/**
	 * @param string $label
	 *
	 * @return InputInterface
	 */
	public function set_label( string $label ): InputInterface {
		$this->label = $label;

		return $this;
	}

	/**
	 * @param string $description
	 *
	 * @return InputInterface
	 */
	public function set_description( string $description ): InputInterface {
		$this->description = $description;

		return $this;
	}

	/**
	 * @param mixed $value
	 *
	 * @return InputInterface
	 */
	public function set_value( $value ): InputInterface {
		$this->value = $value;

		return $this;
	}

}
