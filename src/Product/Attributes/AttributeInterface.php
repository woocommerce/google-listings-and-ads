<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\InputInterface;

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
	 * Return the input class used for this attribute.
	 *
	 * Must be an instance of InputInterface
	 *
	 * @return string
	 *
	 * @see InputInterface
	 */
	public static function get_input_type(): string;

	/**
	 * @return mixed
	 */
	public function get_value();

}
