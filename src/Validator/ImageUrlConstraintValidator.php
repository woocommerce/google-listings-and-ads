<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Validator;

use Normalizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\UrlValidator as UrlConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ImageUrlConstraintValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Validator
 */
class ImageUrlConstraintValidator extends UrlConstraintValidator {

	/**
	 * Checks if the passed value is valid.
	 *
	 * @param string     $value The value that should be validated
	 * @param Constraint $constraint
	 *
	 * @throws UnexpectedTypeException If invalid constraint provided.
	 * @throws UnexpectedValueException If invalid value provided.
	 */
	public function validate( $value, Constraint $constraint ) {
		if ( ! $constraint instanceof ImageUrlConstraint ) {
			throw new UnexpectedTypeException( $constraint, ImageUrlConstraint::class );
		}

		if ( null === $value || '' === $value ) {
			return;
		}

		if ( ! is_scalar( $value ) && ! ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
			throw new UnexpectedValueException( $value, 'string' );
		}

		$value = (string) $value;
		if ( '' === $value ) {
			return;
		}

		$value   = Normalizer::normalize( $value );
		$pattern = sprintf( static::PATTERN, implode( '|', $constraint->protocols ) );

		if ( ! preg_match( $pattern, $value ) ) {
			$this->context->buildViolation( $constraint->message )
				->setParameter( '{{ name }}', wp_basename( $value ) )
				->setCode( ImageUrlConstraint::INVALID_URL_ERROR )
				->addViolation();

			return;
		}
	}
}
