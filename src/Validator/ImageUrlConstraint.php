<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Validator;

use Symfony\Component\Validator\Constraints\Url as UrlConstraint;

defined( 'ABSPATH' ) || exit;

/**
 * Class ImageUrlConstraint
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Validator
 */
class ImageUrlConstraint extends UrlConstraint {
	/**
	 * Constructor.
	 *
	 * @param array|null $options Options.
	 */
	public function __construct( $options = null ) {
		parent::__construct( $options );
		$this->message = 'Product image "{{ name }}" is not a valid name.';
	}
}
