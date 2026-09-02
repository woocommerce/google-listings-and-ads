<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class JetpackAuthCircuitBreaker
 *
 * Pauses syncing after the Connect Server rejects the site's Jetpack token.
 *
 * The Connect Server authenticates every proxied request with the Jetpack token, so a
 * rejection applies to every request the site makes, and running sync jobs against it
 * only produces more rejections. A failure pauses syncing for one hour, without backoff
 * and without extension by further rejections: the next attempt after the window is a
 * single probe, and any accepted response ends the pause early, so a repaired connection
 * or a transient failure is picked up within the hour. The failure is also remembered
 * for the rest of the current PHP request, so a loop making one request per product
 * stops after the first rejection.
 *
 * The pause is stored in an option rather than a transient so that an object cache
 * eviction cannot silently drop it.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class JetpackAuthCircuitBreaker implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Whether a failure was recorded during the current request.
	 *
	 * @var bool
	 */
	private $tripped_in_request = false;

	/**
	 * Record an authentication failure.
	 */
	public function trip(): void {
		$this->tripped_in_request = true;

		// A rejection while already open (e.g. from an admin page load) keeps the current window.
		if ( ! $this->is_open() ) {
			$this->options->update( OptionsInterface::JETPACK_AUTH_FAILED_AT, time() );
		}
	}

	/**
	 * End the pause after an accepted response.
	 */
	public function reset(): void {
		$this->tripped_in_request = false;

		if ( $this->options->get( OptionsInterface::JETPACK_AUTH_FAILED_AT ) ) {
			$this->options->delete( OptionsInterface::JETPACK_AUTH_FAILED_AT );
		}
	}

	/**
	 * Whether the breaker is open, i.e. syncing is paused.
	 *
	 * @return bool
	 */
	public function is_open(): bool {
		return $this->get_retry_time() > time();
	}

	/**
	 * Whether trip() ran earlier in this PHP request; used to fail fast for the rest of it.
	 *
	 * @return bool
	 */
	public function was_tripped_in_request(): bool {
		return $this->tripped_in_request;
	}

	/**
	 * The timestamp at which the pause ends, or 0 when no failure is recorded.
	 *
	 * @return int
	 */
	public function get_retry_time(): int {
		$failed_at = (int) $this->options->get( OptionsInterface::JETPACK_AUTH_FAILED_AT, 0 );

		return $failed_at > 0 ? $failed_at + HOUR_IN_SECONDS : 0;
	}
}
