<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Interface AttributeInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
interface AttributeInterface {

	/**
	 * @return string
	 */
	public static function get_id(): string;

	/**
	 * @return string
	 */
	public static function get_name(): string;

	/**
	 * Return the attribute's value type. Must be a valid PHP type.
	 *
	 * @return string
	 *
	 * @link https://www.php.net/manual/en/function.settype.php
	 */
	public static function get_value_type(): string;

	/**
	 * @return string
	 */
	public static function get_description(): string;

	/**
	 * Return an array of WooCommerce product types that this attribute can be applied to.
	 *
	 * @return array
	 */
	public static function get_applicable_product_types(): array;

	/**
	 * @return mixed
	 */
	public function get_value();

}
