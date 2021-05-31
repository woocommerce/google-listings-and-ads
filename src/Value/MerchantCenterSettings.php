<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterSettings
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class MerchantCenterSettings extends ArrayWithRequiredKeys implements ValueInterface {

	/**
	 * Array of required keys. Should be in key => value format.
	 *
	 * @var array
	 */
	protected $required_keys = [
		'shipping_rate'           => true,
		'offers_free_shipping'    => true,
		'free_shipping_threshold' => true,
		'shipping_time'           => true,
	];

	/**
	 * ArrayWithRequiredKeys constructor.
	 *
	 * @param array $data
	 */
	public function __construct( array $data ) {
		parent::__construct( $data );

		$this->data = array_merge(
			[
				'shipping_rate'           => 'flat',
				'offers_free_shipping'    => false,
				'free_shipping_threshold' => 0,
				'shipping_time'           => 'flat',
				'tax_rate'                => 'destination',
				'website_live'            => false,
				'checkout_process_secure' => false,
				'payment_methods_visible' => false,
				'refund_tos_visible'      => false,
				'contact_info_visible'    => false,
			],
			$this->data
		);
	}

	/**
	 * Get the value of the object.
	 *
	 * @return array
	 */
	public function get(): array {
		return $this->data;
	}
}
