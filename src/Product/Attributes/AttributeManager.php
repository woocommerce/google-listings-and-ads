<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class AttributeManager
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class AttributeManager implements Service {

	use PluginHelper;

	/**
	 * @var string[]
	 */
	protected $attributes;

	/**
	 * AttributeManager constructor.
	 */
	public function __construct() {
		$this->attributes = [
			GTIN::get_id() => GTIN::class,
			MPN::get_id()  => MPN::class,
		];
	}

	/**
	 * @param int                $product_id
	 * @param AttributeInterface $attribute
	 */
	public function update( int $product_id, AttributeInterface $attribute ) {
		$this->validate_product_id( $product_id );

		update_post_meta( $product_id, $this->prefix_meta_key( $attribute::get_id() ), $attribute->get_value() );
	}

	/**
	 * @param int    $product_id
	 * @param string $attribute_id
	 *
	 * @return AttributeInterface
	 */
	public function get( int $product_id, string $attribute_id ): AttributeInterface {
		$this->validate_product_id( $product_id );
		$this->validate_attribute_id( $attribute_id );

		$attribute_class = $this->attributes[ $attribute_id ];
		$value           = get_post_meta( $product_id, $this->prefix_meta_key( $attribute_id ), true );

		return new $attribute_class( $value );
	}

	/**
	 * @param int $product_id
	 *
	 * @return AttributeInterface[]
	 */
	public function get_all( int $product_id ): array {
		return array_map(
			function ( $attribute_id ) use ( $product_id ) {
				return $this->get( $product_id, $attribute_id );
			},
			array_keys( $this->attributes )
		);
	}

	/**
	 * @param int    $product_id
	 * @param string $attribute_id
	 */
	public function delete( int $product_id, string $attribute_id ) {
		$this->validate_product_id( $product_id );
		$this->validate_attribute_id( $attribute_id );

		delete_post_meta( $product_id, $this->prefix_meta_key( $attribute_id ) );
	}

	/**
	 * @param int $product_id
	 *
	 * @throws InvalidArgument If the provided wc_product_id is not a valid WooCommerce product ID.
	 */
	protected function validate_product_id( int $product_id ) {
		if ( ! wc_get_product( $product_id ) instanceof WC_Product ) {
			throw new InvalidArgument( 'Invalid WooCommerce product ID provided.' );
		}
	}

	/**
	 * @param string $attribute_id
	 *
	 * @throws InvalidValue If the meta key is invalid.
	 */
	protected function validate_attribute_id( string $attribute_id ) {
		if ( ! isset( $this->attributes[ $attribute_id ] ) ) {
			throw InvalidValue::not_in_allowed_list( 'attribute_id', array_keys( $this->attributes ) );
		}
	}
}
