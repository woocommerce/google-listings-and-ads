<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Class GooglePriceConstraint
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Validator
 */
class GooglePriceConstraint extends Constraint {
	/**
	 * @var string
	 */
	public $message = 'Product must have a valid price and currency.';
}
