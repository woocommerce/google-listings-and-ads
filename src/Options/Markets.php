<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class Markets
 *
 * Simple options wrapper for the markets configuration.
 *
 * TODO: This is a placeholder — to be extended when the Markets REST
 * Controller (GOOWOO-560) is built. The interface of this class should
 * be reviewed by the tech lead during GOOWOO-559 review.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class Markets implements Service, OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Returns all configured markets.
	 *
	 * @return array[]
	 */
	public function get(): array {
		return $this->options->get( OptionsInterface::MARKETS, [] );
	}

	/**
	 * Persists a new markets configuration.
	 *
	 * @param array[] $markets
	 *
	 * @return bool
	 */
	public function update( array $markets ): bool {
		return $this->options->update( OptionsInterface::MARKETS, $markets );
	}
}
