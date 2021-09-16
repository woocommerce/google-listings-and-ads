<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneVerificationException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 *
 * @since x.x.x
 */
class PhoneVerificationException extends Exception implements GoogleListingsAndAdsException {
	/**
	 * @var string
	 */
	protected $reason;

	/**
	 * PhoneVerificationException constructor.
	 *
	 * @param string         $message
	 * @param int            $code
	 * @param Throwable|null $previous
	 * @param string         $reason
	 */
	public function __construct( string $message = '', int $code = 0, Throwable $previous = null, string $reason = '' ) {
		parent::__construct( $message, $code, $previous );
		$this->reason = $reason;
	}

	/**
	 * @return string
	 */
	public function get_reason(): string {
		return $this->reason;
	}

	/**
	 * @param string $reason
	 *
	 * @return PhoneVerificationException
	 */
	public function set_reason( string $reason ): PhoneVerificationException {
		$this->reason = $reason;

		return $this;
	}
}
