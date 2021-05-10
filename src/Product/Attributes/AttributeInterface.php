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
	 * @return string
	 */
	public static function get_description(): string;

	/**
	 * @return mixed
	 */
	public function get_value();

}
