<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsSetupCompleted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class AttributeMappingRules implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;

	private const OPTION = OptionsInterface::MAPPING_RULES;

	/**
	 *
	 * Setter for the option
	 *
	 * @param array $rules The rules to save in the DB
	 */
	public function set( array $rules ) {
		$this->options->update( self::OPTION, $rules );
	}

	/**
	 * Getter for the option
	 *
	 * @return array The rules saved in the option
	 */
	public function get(): array {
		return $this->options->get( self::OPTION, [] );
	}

}
