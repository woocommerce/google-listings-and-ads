<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MapiPaths;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiPromotionsService
 *
 * Writes merchant promotions for the connected account through the Merchant API
 * promotions resource. promotions:insert is an upsert keyed by promotionId, so it
 * serves both creating a promotion and expiring one (disable-by-recreate).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services
 */
class MapiPromotionsService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var MerchantApiClient */
	protected $client;

	/**
	 * MapiPromotionsService constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Insert (create or replace) a promotion for the connected merchant account.
	 *
	 * The promotions:insert custom method is an upsert keyed by promotionId and
	 * requires the target promotion data source.
	 *
	 * @param string $data_source The promotion data source resource name.
	 * @param array  $promotion   The Promotion resource to write.
	 *
	 * @return array The stored Promotion resource decoded as an array.
	 * @throws MerchantApiException On a non-2xx MAPI response.
	 */
	public function insert_promotion( string $data_source, array $promotion ): array {
		// promotions.insert takes dataSource in the request body (InsertPromotionRequest),
		// unlike productInputs.insert which takes it as a ?dataSource= query param.
		return $this->client->post(
			$this->build_path() . ':insert',
			[
				'dataSource' => $data_source,
				'promotion'  => $promotion,
			]
		);
	}

	/**
	 * Build the promotions resource path for the connected merchant account.
	 *
	 * @return string
	 */
	protected function build_path(): string {
		return sprintf(
			'%s/accounts/%s/promotions',
			MapiPaths::PROMOTIONS,
			$this->options->get_merchant_id()
		);
	}
}
