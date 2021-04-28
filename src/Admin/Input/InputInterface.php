<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Interface InputInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
interface InputInterface {

	/**
	 * @return string|null
	 */
	public function get_id(): ?string;

	/**
	 * @param string|null $id
	 *
	 * @return InputInterface
	 */
	public function set_id( ?string $id ): InputInterface;

	/**
	 * @return string
	 */
	public function get_name(): ?string;

	/**
	 * @param string|null $name
	 *
	 * @return InputInterface
	 */
	public function set_name( ?string $name ): InputInterface;

	/**
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * @return string|null
	 */
	public function get_label(): ?string;

	/**
	 * @param string|null $label
	 *
	 * @return InputInterface
	 */
	public function set_label( ?string $label ): InputInterface;

	/**
	 * @return string|null
	 */
	public function get_description(): ?string;

	/**
	 * @param string|null $description
	 *
	 * @return InputInterface
	 */
	public function set_description( ?string $description ): InputInterface;

	/**
	 * @return mixed
	 */
	public function get_value();

	/**
	 * @param mixed $value
	 *
	 * @return InputInterface
	 */
	public function set_value( $value ): InputInterface;

	/**
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array;
}
