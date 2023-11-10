<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use JsonSerializable;
use Symfony\Component\Validator\ConstraintViolationListInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchInvalidProductEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchInvalidProductEntry implements JsonSerializable {

	/**
	 * @var int WooCommerce product ID.
	 */
	protected $wc_product_id;

	/**
	 * @var string|null Google product ID. Always defined if the method is delete.
	 */
	protected $google_product_id;

	/**
	 * @var string[]
	 */
	protected $errors;

	/**
	 * BatchInvalidProductEntry constructor.
	 *
	 * @param int         $wc_product_id
	 * @param string|null $google_product_id
	 * @param string[]    $errors
	 */
	public function __construct( int $wc_product_id, ?string $google_product_id = null, array $errors = [] ) {
		$this->wc_product_id     = $wc_product_id;
		$this->google_product_id = $google_product_id;
		$this->errors            = $errors;
	}

	/**
	 * @return int
	 */
	public function get_wc_product_id(): int {
		return $this->wc_product_id;
	}

	/**
	 * @return string|null
	 */
	public function get_google_product_id(): ?string {
		return $this->google_product_id;
	}

	/**
	 * @return string[]
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * @param string $error_reason
	 *
	 * @return bool
	 */
	public function has_error( string $error_reason ): bool {
		return ! empty( $this->errors[ $error_reason ] );
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

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$data = [
			'woocommerce_id' => $this->get_wc_product_id(),
			'errors'         => $this->get_errors(),
		];

		if ( null !== $this->get_google_product_id() ) {
			$data['google_id'] = $this->get_google_product_id();
		}

		return $data;
	}
}
