<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class InvalidProductEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class InvalidProductEntry {

	/**
	 * @var int|null WooCommerce product ID.
	 */
	protected $product_id;

	/**
	 * @var string[]|null
	 */
	protected $errors;

	/**
	 * InvalidProductEntry constructor.
	 *
	 * @param int|null      $product_id
	 * @param string[]|null $errors
	 */
	public function __construct( $product_id = null, $errors = null ) {
		$this->product_id = $product_id;
		$this->errors     = $errors;
	}

	/**
	 * @return int|null
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * @param int|null $product_id
	 *
	 * @return InvalidProductEntry
	 */
	public function set_product_id( $product_id ): InvalidProductEntry {
		$this->product_id = $product_id;

		return $this;
	}

	/**
	 * @return string[]|null
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * @param string[]|null $errors
	 *
	 * @return InvalidProductEntry
	 */
	public function set_errors( $errors ): InvalidProductEntry {
		$this->errors = $errors;

		return $this;
	}

	/**
	 * @param ConstraintViolationListInterface $violations
	 *
	 * @return InvalidProductEntry
	 */
	public function map_validation_violations( ConstraintViolationListInterface $violations ): InvalidProductEntry {
		$validation_errors = [];
		foreach ( $violations as $violation ) {
			$validation_errors[] = sprintf( '[%s] %s', $violation->getPropertyPath(), $violation->getMessage() );
		}

		$this->set_errors( $validation_errors );

		return $this;
	}
}
