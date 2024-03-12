<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Interface InputInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
interface InputInterface extends FormInterface {

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
	 * Return the id used for the input's view.
	 *
	 * @return string
	 */
	public function get_view_id(): string;

	/**
	 * Return the name of a generic product block in WooCommerce core or a custom block in this extension.
	 *
	 * @return string
	 */
	public function get_block_name(): string;

	/**
	 * Add or update a block attribute used for block config.
	 *
	 * @param string $key   The attribute key defined in the corresponding block.json
	 * @param mixed  $value The attribute value defined in the corresponding block.json
	 *
	 * @return InputInterface
	 */
	public function set_block_attribute( string $key, $value ): InputInterface;

	/**
	 * Return the attributes of block config used for the input's view within the Product Block Editor.
	 *
	 * @return array
	 */
	public function get_block_attributes(): array;

	/**
	 * Return the block config used for the input's view within the Product Block Editor.
	 *
	 * @return array
	 */
	public function get_block_config(): array;
}
