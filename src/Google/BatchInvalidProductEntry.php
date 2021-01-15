<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Symfony\Component\Validator\ConstraintViolationListInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchInvalidProductEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchInvalidProductEntry {

	/**
	 * @var int WooCommerce product ID.
	 */
	protected $wc_product_id;

	/**
	 * @var string[]
	 */
	protected $errors;

	/**
	 * BatchInvalidProductEntry constructor.
	 *
	 * @param int      $wc_product_id
	 * @param string[] $errors
	 */
	public function __construct( int $wc_product_id, $errors = [] ) {
		$this->wc_product_id = $wc_product_id;
		$this->errors        = $errors;
	}

	/**
	 * @return int
	 */
	public function get_wc_product_id(): int {
		return $this->wc_product_id;
	}

	/**
	 * @return string[]
	 */
	public function get_errors(): array {
		return $this->errors;
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

		$this->errors = $validation_errors;

		return $this;
	}
}
