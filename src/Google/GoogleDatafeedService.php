<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Datafeed;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\DatafeedTarget;

defined( 'ABSPATH' ) || exit;

/**
 * Manages Google Merchant Center datafeeds for multilingual markets.
 *
 * Each language-currency pair (e.g. "en-USD") needs a Content API datafeed with
 * the correct target countries so MC routes products to the right markets.
 *
 * Note: DatafeedTarget.feedLabel must be 1-20 uppercase letters (A-Z), digits, and
 * hyphens (-). We normalise to uppercase when writing and use case-insensitive
 * matching when reading, because MC may canonicalise differently.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class GoogleDatafeedService implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;

	/**
	 * @var ShoppingContent
	 */
	protected ShoppingContent $shopping_service;

	/**
	 * @param ShoppingContent $shopping_service
	 */
	public function __construct( ShoppingContent $shopping_service ) {
		$this->shopping_service = $shopping_service;
	}

	/**
	 * Returns all datafeeds for the merchant account.
	 *
	 * @return Datafeed[]
	 * @throws GoogleException
	 */
	public function get_all(): array {
		$response = $this->shopping_service->datafeeds->listDatafeeds( $this->options->get_merchant_id() );

		return $response->getResources() ?? [];
	}

	/**
	 * Finds the datafeed whose target feedLabel matches (case-insensitive).
	 *
	 * @param string $feed_label
	 *
	 * @return Datafeed|null
	 * @throws GoogleException
	 */
	public function find_by_feed_label( string $feed_label ): ?Datafeed {
		$needle = strtolower( $feed_label );
		foreach ( $this->get_all() as $datafeed ) {
			foreach ( $datafeed->getTargets() ?? [] as $target ) {
				if ( strtolower( $target->getFeedLabel() ) === $needle ) {
					return $datafeed;
				}
			}
		}

		return null;
	}

	/**
	 * Ensures a Content API datafeed exists for the given feedLabel.
	 * Creates one if absent; updates its target countries if already present.
	 * Errors are logged and swallowed so a Google API failure does not block market operations.
	 *
	 * @param string   $feed_label       e.g. "en-USD"
	 * @param string   $language         ISO 639-1 code, e.g. "en"
	 * @param string[] $target_countries ISO 3166-1 alpha-2 codes
	 */
	public function ensure_for_feed_label( string $feed_label, string $language, array $target_countries ): void {
		try {
			$merchant_id = $this->options->get_merchant_id();
			$existing    = $this->find_by_feed_label( $feed_label );
		} catch ( GoogleException $e ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Failed to list datafeeds while ensuring %s: %s', $feed_label, $e->getMessage() ),
				__METHOD__
			);
			return;
		}

		try {
			if ( null === $existing ) {
				$this->create( $merchant_id, $feed_label, $language, $target_countries );
			} else {
				$this->update_targets( $merchant_id, $existing->getId(), $feed_label, $language, $target_countries );
			}
		} catch ( GoogleException $e ) {
			$op = null === $existing ? 'create' : 'update';
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Failed to %s datafeed for %s: %s', $op, $feed_label, $e->getMessage() ),
				__METHOD__
			);
		}
	}

	/**
	 * Deletes the datafeed whose target matches the given feedLabel.
	 * No-op when no matching datafeed is found. Errors are logged and swallowed.
	 *
	 * @param string $feed_label
	 */
	public function delete_by_feed_label( string $feed_label ): void {
		try {
			$datafeed = $this->find_by_feed_label( $feed_label );

			if ( null !== $datafeed ) {
				$this->shopping_service->datafeeds->delete(
					$this->options->get_merchant_id(),
					$datafeed->getId()
				);
			}
		} catch ( GoogleException $e ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Failed to delete datafeed for %s: %s', $feed_label, $e->getMessage() ),
				__METHOD__
			);
		}
	}

	/**
	 * Creates a Content API datafeed with the given feedLabel and target countries.
	 *
	 * DatafeedTarget.feedLabel is normalised to uppercase because the MC Content API
	 * requires it to contain only A-Z, 0-9, and hyphens.
	 *
	 * @param int      $merchant_id
	 * @param string   $feed_label
	 * @param string   $language
	 * @param string[] $target_countries
	 *
	 * @return Datafeed
	 * @throws GoogleException
	 */
	protected function create( int $merchant_id, string $feed_label, string $language, array $target_countries ): Datafeed {
		$normalised = strtoupper( $feed_label );

		$target = new DatafeedTarget();
		$target->setFeedLabel( $normalised );
		$target->setLanguage( $language );
		$target->setTargetCountries( $target_countries );

		$datafeed = new Datafeed();
		$datafeed->setName( sprintf( 'GLA: %s', $feed_label ) );
		$datafeed->setFileName( sprintf( 'gla-%s.xml', strtolower( $feed_label ) ) );
		$datafeed->setContentType( 'products' );
		$datafeed->setTargets( [ $target ] );

		return $this->shopping_service->datafeeds->insert( $merchant_id, $datafeed );
	}

	/**
	 * Updates the target matching $feed_label on an existing datafeed to set language
	 * and target countries. Appends a new target if none matches the feedLabel.
	 *
	 * @param int      $merchant_id
	 * @param string   $datafeed_id
	 * @param string   $feed_label
	 * @param string   $language
	 * @param string[] $target_countries
	 *
	 * @return Datafeed
	 * @throws GoogleException
	 */
	protected function update_targets( int $merchant_id, string $datafeed_id, string $feed_label, string $language, array $target_countries ): Datafeed {
		$existing = $this->shopping_service->datafeeds->get( $merchant_id, $datafeed_id );
		$targets  = $existing->getTargets() ?? [];
		$needle   = strtolower( $feed_label );
		$updated  = false;

		foreach ( $targets as $target ) {
			if ( strtolower( $target->getFeedLabel() ) === $needle ) {
				$target->setLanguage( $language );
				$target->setTargetCountries( $target_countries );
				$updated = true;
				break;
			}
		}

		if ( ! $updated ) {
			$target = new DatafeedTarget();
			$target->setFeedLabel( strtoupper( $feed_label ) );
			$target->setLanguage( $language );
			$target->setTargetCountries( $target_countries );
			$targets[] = $target;
		}

		$existing->setTargets( $targets );

		return $this->shopping_service->datafeeds->update( $merchant_id, $datafeed_id, $existing );
	}
}
