<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_Account as MC_Account;
use Google_Service_ShoppingContent_AccountAdsLink as MC_Account_Ads_Link;
use Google_Service_ShoppingContent_AccountStatus as MC_Account_Status;
use Google_Service_ShoppingContent_ProductstatusesListResponse as MC_Product_Status_List_Response;
use Google_Service_ShoppingContent_Product as Product;
use Google\Exception as GoogleException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Merchant
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Merchant implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingService
	 */
	protected $service;

	/**
	 * Merchant constructor.
	 *
	 * @param ShoppingService $service
	 */
	public function __construct( ShoppingService $service ) {
		$this->service = $service;
	}

	/**
	 * @return Product[]
	 */
	public function get_products(): array {
		$products = $this->service->products->listProducts( $this->options->get_merchant_id() );
		$return   = [];

		while ( ! empty( $products->getResources() ) ) {

			foreach ( $products->getResources() as $product ) {
				$return[] = $product;
			}

			if ( empty( $products->getNextPageToken() ) ) {
				break;
			}

			$products = $this->service->products->listProducts(
				$this->options->get_merchant_id(),
				[ 'pageToken' => $products->getNextPageToken() ]
			);
		}

		return $return;
	}


	/**
	 * Claim a website for the user's Merchant Center account.
	 *
	 * @param bool $overwrite Whether to include the overwrite directive.
	 * @return bool
	 * @throws Exception If the website claim fails.
	 */
	public function claimwebsite( bool $overwrite = false ): bool {
		try {
			$id     = $this->options->get_merchant_id();
			$params = $overwrite ? [ 'overwrite' => true ] : [];
			$this->service->accounts->claimwebsite( $id, $id, $params );
			do_action( 'gla_site_claim_success', [ 'details' => 'google_proxy' ] );
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			do_action( 'gla_site_claim_failure', [ 'details' => 'google_proxy' ] );

			$error_message = __( 'Unable to claim website.', 'google-listings-and-ads' );
			if ( 403 === $e->getCode() ) {
				$error_message = __( 'Website already claimed, use overwrite to complete the process.', 'google-listings-and-ads' );
			}
			throw new Exception( $error_message, $e->getCode() );
		}
		return true;
	}

	/**
	 * Retrieve the user's Merchant Center account information.
	 *
	 * @param int $id Optional - the Merchant Center account to retrieve
	 * @return MC_Account The user's Merchant Center account.
	 * @throws Exception If the account can't be retrieved.
	 */
	public function get_account( int $id = 0 ): MC_Account {
		$id = $id ?: $this->options->get_merchant_id();

		try {
			$mc_account = $this->service->accounts->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve merchant center account.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account;
	}

	/**
	 * Retrieve the user's Merchant Center account information.
	 *
	 * @param int $id Optional - the Merchant Center account to retrieve
	 * @return MC_Account_Status The user's Merchant Center account.
	 * @throws Exception If the account can't be retrieved.
	 */
	public function get_accountstatus( int $id = 0 ): MC_Account_Status {
		$id = $id ?: $this->options->get_merchant_id();

		try {
			$mc_account_status = $this->service->accountstatuses->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve merchant center account status.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account_status;
	}

	/**
	 * Retrieve a page of the user's Merchant Center product statuses.
	 *
	 * @param string|null $page_token
	 *
	 * @return MC_Product_Status_List_Response A page of the Merchant Center product statuses.
	 */
	public function get_productstatuses( ?string $page_token ): MC_Product_Status_List_Response {
		$merchant_id = $this->options->get_merchant_id();
		$opt_params  = [ 'maxResults' => 250 ];
		if ( ! empty( $page_token ) ) {
			$opt_params['pageToken'] = $page_token;
		}
		return $this->service->productstatuses->listProductstatuses( $merchant_id, $opt_params );
	}

	/**
	 * Update the provided Merchant Center account information.
	 *
	 * @param MC_Account $mc_account The Account data to update.
	 *
	 * @return MC_Account The user's Merchant Center account.
	 * @throws Exception If the account can't be retrieved.
	 */
	public function update_account( MC_Account $mc_account ): MC_Account {
		try {
			$mc_account = $this->service->accounts->update( $mc_account->getId(), $mc_account->getId(), $mc_account );
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to update merchant center account.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account;
	}

	/**
	 * Link a Google Ads ID to this Merchant account.
	 *
	 * @param int $ads_id Google Ads ID to link.
	 *
	 * @return bool
	 * @throws Exception When unable to retrieve or update account data.
	 */
	public function link_ads_id( int $ads_id ): bool {
		$account   = $this->get_account();
		$ads_links = $account->getAdsLinks();

		// Stop early if we already have a link setup.
		foreach ( $ads_links as $link ) {
			if ( $ads_id === absint( $link->getAdsId() ) ) {
				return false;
			}
		}

		$link = new MC_Account_Ads_Link();
		$link->setAdsId( $ads_id );
		$link->setStatus( 'active' );
		$account->setAdsLinks( array_merge( $ads_links, [ $link ] ) );
		$this->update_account( $account );

		return true;
	}
}
