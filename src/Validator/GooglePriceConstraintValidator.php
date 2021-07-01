<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Validator;

use Google\Service\ShoppingContent\Price as GooglePrice;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

defined( 'ABSPATH' ) || exit;

/**
 * Class GooglePriceConstraintValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Validator
 */
class GooglePriceConstraintValidator extends ConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param GooglePrice $value The value that should be validated
	 * @param Constraint  $constraint
	 *
	 * @throws UnexpectedTypeException If invalid constraint provided.
	 * @throws UnexpectedValueException If invalid value provided.
	 */
	public function validate( $value, Constraint $constraint ) {
		if ( ! $constraint instanceof GooglePriceConstraint ) {
			throw new UnexpectedTypeException( $constraint, GooglePriceConstraint::class );
		}

		if ( null === $value || '' === $value ) {
			return;
		}

		if ( ! $value instanceof GooglePrice ) {
			throw new UnexpectedValueException( $value, GooglePrice::class );
		}

		if ( empty( $value->getValue() ) || empty( $value->getCurrency() ) ) {
			$this->context->buildViolation( $constraint->message )
							->atPath( 'value' )
							->addViolation();
		}
	}
}
