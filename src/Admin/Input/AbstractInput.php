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
	 * @return string|null
	 */
	public function get_id(): ?string {
		return $this->id;
	}

	/**
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * @return string|null
	 */
	public function get_label(): ?string {
		return $this->label;
	}

	/**
	 * @return string|null
	 */
	public function get_description(): ?string {
		return $this->description;
	}

	/**
	 * @return mixed|null
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @param string|null $id
	 *
	 * @return InputInterface
	 */
	public function set_id( ?string $id ): InputInterface {
		$this->id = $id;

		return $this;
	}

	/**
	 * @param string|null $name
	 *
	 * @return InputInterface
	 */
	public function set_name( ?string $name ): InputInterface {
		$this->name = $name;

		return $this;
	}

	/**
	 * @param string|null $label
	 *
	 * @return InputInterface
	 */
	public function set_label( ?string $label ): InputInterface {
		$this->label = $label;

		return $this;
	}

	/**
	 * @param string|null $description
	 *
	 * @return InputInterface
	 */
	public function set_description( ?string $description ): InputInterface {
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

	/**
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		return [
			'id'          => $this->get_id(),
			'name'        => $this->get_name(),
			'type'        => $this->get_type(),
			'label'       => $this->get_label(),
			'value'       => $this->get_value(),
			'description' => $this->get_description(),
			'desc_tip'    => true,
		];
	}
}
