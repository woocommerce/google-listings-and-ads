<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class BatchInvalidProductEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchInvalidProductEntry {

	/**
	 * @var int|null WooCommerce product ID.
	 */
	protected $wc_product_id;

	/**
	 * @var string[]|null
	 */
	protected $errors;

	/**
	 * BatchInvalidProductEntry constructor.
	 *
	 * @param int|null      $product_id
	 * @param string[]|null $errors
	 */
	public function __construct( $product_id = null, $errors = null ) {
		$this->wc_product_id = $product_id;
		$this->errors        = $errors;
	}

	/**
	 * @return int|null
	 */
	public function get_wc_product_id() {
		return $this->wc_product_id;
	}

	/**
	 * @param int|null $wc_product_id
	 *
	 * @return BatchInvalidProductEntry
	 */
	public function set_wc_product_id( $wc_product_id ): BatchInvalidProductEntry {
		$this->wc_product_id = $wc_product_id;

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
	 * @return BatchInvalidProductEntry
	 */
	public function set_errors( $errors ): BatchInvalidProductEntry {
		$this->errors = $errors;

		return $this;
	}

	/**
	 * @param ConstraintViolationListInterface $violations
	 *
	 * @return BatchInvalidProductEntry
	 */
	public function map_validation_violations( ConstraintViolationListInterface $violations ): BatchInvalidProductEntry {
		$validation_errors = [];
		foreach ( $violations as $violation ) {
			$validation_errors[] = sprintf( '[%s] %s', $violation->getPropertyPath(), $violation->getMessage() );
		}

		$this->set_errors( $validation_errors );

		return $this;
	}
}
