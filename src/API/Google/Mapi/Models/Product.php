<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models
 */
class Product {

	/** @var string */
	protected $id = '';

	/** @var string|null */
	protected $offer_id;

	/** @var string|null */
	protected $title;

	/** @var string|null */
	protected $target_country;

	/** @var ProductStatus|null */
	protected $product_status;

	/**
	 * Hydrate from a MAPI response array.
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$instance = new self();

		$instance->id             = self::extract_id_from_name( $data['name'] ?? '' );
		$instance->offer_id       = $data['offerId'] ?? null;
		$instance->target_country = $data['feedLabel'] ?? null;
		// Product data (title, description, etc.) lives under `productAttributes`, not at the top level.
		$instance->title = $data['productAttributes']['title'] ?? null;

		if ( isset( $data['productStatus'] ) && is_array( $data['productStatus'] ) ) {
			$instance->product_status = ProductStatus::from_array( $data['productStatus'] );
		}

		return $instance;
	}

	/**
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * @return string|null
	 */
	public function get_offer_id(): ?string {
		return $this->offer_id;
	}

	/**
	 * @return string|null
	 */
	public function get_title(): ?string {
		return $this->title;
	}

	/**
	 * @return string|null
	 */
	public function get_target_country(): ?string {
		return $this->target_country;
	}

	/**
	 * @return ProductStatus|null
	 */
	public function get_product_status(): ?ProductStatus {
		return $this->product_status;
	}

	/**
	 * Extract the trailing segment of MAPI's resource path.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected static function extract_id_from_name( string $name ): string {
		if ( '' === $name ) {
			return '';
		}

		$parts = explode( '/', $name );
		return (string) end( $parts );
	}
}
