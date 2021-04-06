<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AdsService implements Service {

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * MerchantCenterService constructor.
	 *
	 * @param OptionsInterface $options
	 */
	public function __construct( OptionsInterface $options ) {
		$this->options = $options;
	}

	/**
	 * Get whether Ads setup is completed.
	 *
	 * @return bool
	 */
	public function is_setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::ADS_SETUP_COMPLETED_AT, false ) );
	}

}
