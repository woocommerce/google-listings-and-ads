<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductInput
 *
 * Payload for the Merchant API `accounts.productInputs.insert` endpoint.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models
 */
class ProductInput {

	/** @var string|null Resource name, populated on the response. */
	protected $name;

	/** @var string */
	protected $offer_id;

	/** @var string */
	protected $content_language;

	/** @var string */
	protected $feed_label;

	/** @var array */
	protected $attributes;

	/** @var array */
	protected $custom_attributes;

	/**
	 * ProductInput constructor.
	 *
	 * @param string $offer_id          Merchant-provided product ID.
	 * @param string $content_language  Language code.
	 * @param string $feed_label        Feed label (typically the target country).
	 * @param array  $attributes        Product attributes (title, description, price, etc.).
	 * @param array  $custom_attributes Custom attributes.
	 */
	public function __construct(
		string $offer_id,
		string $content_language,
		string $feed_label,
		array $attributes = [],
		array $custom_attributes = []
	) {
		$this->offer_id          = $offer_id;
		$this->content_language  = $content_language;
		$this->feed_label        = $feed_label;
		$this->attributes        = $attributes;
		$this->custom_attributes = $custom_attributes;
	}

	/**
	 * Hydrate from a MAPI response array.
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$instance = new self(
			$data['offerId'] ?? '',
			$data['contentLanguage'] ?? '',
			$data['feedLabel'] ?? '',
			$data['productAttributes'] ?? [],
			$data['customAttributes'] ?? []
		);

		$instance->name = $data['name'] ?? null;

		return $instance;
	}

	/**
	 * Serialize to an array for an outbound request body.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$data = [
			'offerId'         => $this->offer_id,
			'contentLanguage' => $this->content_language,
			'feedLabel'       => $this->feed_label,
		];

		if ( ! empty( $this->attributes ) ) {
			$data['productAttributes'] = $this->attributes;
		}

		if ( ! empty( $this->custom_attributes ) ) {
			$data['customAttributes'] = $this->custom_attributes;
		}

		return $data;
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
	public function get_offer_id(): string {
		return $this->offer_id;
	}

	/**
	 * @return string
	 */
	public function get_content_language(): string {
		return $this->content_language;
	}

	/**
	 * @return string
	 */
	public function get_feed_label(): string {
		return $this->feed_label;
	}

	/**
	 * @return array
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * @return array
	 */
	public function get_custom_attributes(): array {
		return $this->custom_attributes;
	}
}
