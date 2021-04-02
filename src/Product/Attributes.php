<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Attributes
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class Attributes implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;

	/** @var array */
	protected $attributes;

	/**
	 * @param string $attribute
	 *
	 * @return bool
	 */
	public function is_known_attribute( string $attribute ): bool {

	}

	protected function get_known_attributes(): array {
		if ( null === $this->attributes ) {
			$this->attributes = $this->options->get( OptionsInterface::KNOWN_ATTRIBUTES, [] );
		}

		return $this->attributes;
	}
}
