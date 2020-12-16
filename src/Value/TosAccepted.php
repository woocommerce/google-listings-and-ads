<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Class TosAccepted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class TosAccepted implements ValueInterface {

	/**
	 * @var bool
	 */
	protected $accepted;

	/**
	 * @var string
	 */
	protected $message;

	/**
	 * TosAccepted constructor.
	 *
	 * @param bool   $accepted
	 * @param string $message
	 */
	public function __construct( bool $accepted, string $message = '' ) {
		$this->accepted = $accepted;
		$this->message  = $message;
	}

	/**
	 * Get the value of the object.
	 *
	 * @return mixed
	 */
	public function get(): array {
		return [
			'accepted' => $this->accepted,
			'message'  => $this->message,
		];
	}

	/**
	 * @return bool
	 */
	public function accepted(): bool {
		return $this->accepted;
	}

	/**
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}
}
