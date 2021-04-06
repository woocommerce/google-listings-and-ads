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
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * @param string $id
	 */
	public function set_id( string $id );

	/**
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * @param string $name
	 */
	public function set_name( string $name );

	/**
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * @param string $label
	 */
	public function set_label( string $label );

	/**
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * @param string $description
	 */
	public function set_description( string $description );

	/**
	 * @return mixed
	 */
	public function get_value();

	/**
	 * @param mixed $value
	 */
	public function set_value( $value );
}
