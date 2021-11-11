<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use JsonSerializable;
use Symfony\Component\Validator\ConstraintViolationListInterface;
defined( 'ABSPATH' ) || exit();

/**
 * Class InvalidCouponEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class InvalidCouponEntry implements JsonSerializable {

	/**
	 *
	 * @var int WooCommerce coupon ID.
	 */
	protected $wc_coupon_id;

	/**
	 *
	 * @var string target country of the promotion.
	 */
	protected $target_country;

	/**
	 *
	 * @var string|null Google promotion ID.
	 */
	protected $google_promotion_id;

	/**
	 *
	 * @var string[]
	 */
	protected $errors;

	/**
	 * InvalidCouponEntry constructor.
	 *
	 * @param int         $wc_coupon_id
	 * @param string[]    $errors
	 * @param string      $target_country
	 * @param string|null $google_promotion_id
	 */
	public function __construct(
		int $wc_coupon_id,
		array $errors = [],
		string $target_country = null,
		string $google_promotion_id = null ) {
		$this->wc_coupon_id        = $wc_coupon_id;
		$this->target_country      = $target_country;
		$this->google_promotion_id = $google_promotion_id;
		$this->errors              = $errors;
	}

	/**
	 *
	 * @return int
	 */
	public function get_wc_coupon_id(): int {
		return $this->wc_coupon_id;
	}

	/**
	 *
	 * @return string|null
	 */
	public function get_google_promotion_id(): ?string {
		return $this->google_promotion_id;
	}

	/**
	 *
	 * @return string|null
	 */
	public function get_target_country(): ?string {
		return $this->target_country;
	}

	/**
	 *
	 * @return string[]
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 *
	 * @param int $error_code
	 *
	 * @return bool
	 */
	public function has_error( int $error_code ): bool {
		return ! empty( $this->errors[ $error_code ] );
	}

	/**
	 *
	 * @param ConstraintViolationListInterface $violations
	 *
	 * @return InvalidCouponEntry
	 */
	public function map_validation_violations(
		ConstraintViolationListInterface $violations ): InvalidCouponEntry {
		$validation_errors = [];
		foreach ( $violations as $violation ) {
			array_push(
				$validation_errors,
				sprintf(
					'[%s] %s',
					$violation->getPropertyPath(),
					$violation->getMessage()
				)
			);
		}

		$this->errors = $validation_errors;

		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function jsonSerialize(): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$data = [
			'woocommerce_id' => $this->get_wc_coupon_id(),
			'errors'         => $this->get_errors(),
		];

		if ( null !== $this->get_google_promotion_id() ) {
			$data['google_id'] = $this->get_google_promotion_id();
		}

		if ( null !== $this->get_target_country() ) {
			$data['google_target_country'] = $this->get_target_country();
		}

		return $data;
	}
}
