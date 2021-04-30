<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Input
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Input extends Form implements InputInterface {

	/**
	 * @var string
	 */
	protected $id;

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
	 * Input constructor.
	 *
	 * @param string $type
	 */
	public function __construct( string $type ) {
		$this->type = $type;
		parent::__construct();
	}

	/**
	 * @return string|null
	 */
	public function get_id(): ?string {
		return $this->id;
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
	 * @return mixed
	 */
	public function get_value() {
		return $this->get_data();
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
		$this->set_data( $value );

		return $this;
	}

	/**
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		$view_data = [
			'id'          => $this->get_view_id(),
			'type'        => $this->get_type(),
			'label'       => $this->get_label(),
			'value'       => $this->get_value(),
			'description' => $this->get_description(),
			'desc_tip'    => true,
		];

		return array_merge( parent::get_view_data(), $view_data );
	}

	/**
	 * Return the id used for the input's view.
	 *
	 * @return string
	 */
	public function get_view_id(): string {
		$parent = $this->get_parent();
		if ( $parent instanceof InputInterface ) {
			return sprintf( '%s_%s', $parent->get_view_id(), $this->get_id() );
		} elseif ( $parent instanceof FormInterface ) {
			return sprintf( '%s_%s', $parent->get_view_name(), $this->get_id() );
		}

		return sprintf( 'gla_%s', $this->get_name() );
	}
}
