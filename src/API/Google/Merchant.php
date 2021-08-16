<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Exception as GoogleException;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Account;
use Google\Service\ShoppingContent\AccountAdsLink;
use Google\Service\ShoppingContent\AccountStatus;
use Google\Service\ShoppingContent\ProductstatusesCustomBatchResponse;
use Google\Service\ShoppingContent\ProductstatusesCustomBatchRequest;
use Google\Service\ShoppingContent\ProductstatusesListResponse;
use Google\Service\ShoppingContent\Product;
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
	 * @var ShoppingContent
	 */
	protected $service;

	/**
	 * Merchant constructor.
	 *
	 * @param ShoppingContent $service
	 */
	public function __construct( ShoppingContent $service ) {
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
			do_action( 'woocommerce_gla_site_claim_success', [ 'details' => 'google_proxy' ] );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			do_action( 'woocommerce_gla_site_claim_failure', [ 'details' => 'google_proxy' ] );

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
	 *
	 * @return Account The user's Merchant Center account.
	 * @throws MerchantApiException If the account can't be retrieved.
	 */
	public function get_account( int $id = 0 ): Account {
		$id = $id ?: $this->options->get_merchant_id();

		try {
			$mc_account = $this->service->accounts->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw MerchantApiException::account_retrieve_failed( $e->getCode() );
		}
		return $mc_account;
	}

	/**
	 * Retrieve the user's Merchant Center account information.
	 *
	 * @param int $id Optional - the Merchant Center account to retrieve
	 * @return AccountStatus The user's Merchant Center account status.
	 * @throws Exception If the account can't be retrieved.
	 */
	public function get_accountstatus( int $id = 0 ): AccountStatus {
		$id = $id ?: $this->options->get_merchant_id();

		try {
			$mc_account_status = $this->service->accountstatuses->get( $id, $id );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve Merchant Center account status.', 'google-listings-and-ads' ), $e->getCode() );
		}
		return $mc_account_status;
	}

	/**
	 * Retrieve a page of the user's Merchant Center product statuses.
	 *
	 * @param string|null $page_token
	 *
	 * @return ProductstatusesListResponse A page of the Merchant Center product statuses.
	 */
	public function get_productstatuses( ?string $page_token ): ProductstatusesListResponse {
		$merchant_id = $this->options->get_merchant_id();
		$opt_params  = [ 'maxResults' => 250 ];
		if ( ! empty( $page_token ) ) {
			$opt_params['pageToken'] = $page_token;
		}
		return $this->service->productstatuses->listProductstatuses( $merchant_id, $opt_params );
	}

	/**
	 * Retrieve a batch of Merchant Center Product Statuses using the provided Merchant Center product IDs.
	 *
	 * @since 1.1.0
	 *
	 * @param string[] $mc_product_ids
	 *
	 * @return ProductstatusesCustomBatchResponse;
	 */
	public function get_productstatuses_batch( array $mc_product_ids ): ProductstatusesCustomBatchResponse {
		$merchant_id = $this->options->get_merchant_id();
		$entries     = [];
		foreach ( $mc_product_ids as $index => $id ) {
			$entries[] = [
				'batchId'    => $index + 1,
				'productId'  => $id,
				'method'     => 'GET',
				'merchantId' => $merchant_id,
			];
		}

		// Retrieve batch.
		$request = new ProductstatusesCustomBatchRequest();
		$request->setEntries( $entries );
		return $this->service->productstatuses->custombatch( $request );
	}

	/**
	 * Update the provided Merchant Center account information.
	 *
	 * @param Account $account The Account data to update.
	 *
	 * @return Account The user's Merchant Center account.
	 * @throws MerchantApiException If the account can't be retrieved.
	 */
	public function update_account( Account $account ): Account {
		try {
			$account = $this->service->accounts->update( $account->getId(), $account->getId(), $account );
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			throw MerchantApiException::account_update_failed( $e->getCode() );
		}
		return $account;
	}

	/**
	 * Link a Google Ads ID to this Merchant account.
	 *
	 * @param int $ads_id Google Ads ID to link.
	 *
	 * @return bool
	 * @throws MerchantApiException When unable to retrieve or update account data.
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

		$link = new AccountAdsLink();
		$link->setAdsId( $ads_id );
		$link->setStatus( 'active' );
		$account->setAdsLinks( array_merge( $ads_links, [ $link ] ) );
		$this->update_account( $account );

		return true;
	}

	/**
	 * Check if we have access to the merchant account.
	 *
	 * @param string $email Email address of the connected account.
	 *
	 * @return bool
	 */
	public function has_access( string $email ): bool {
		$id = $this->options->get_merchant_id();

		try {
			$account = $this->service->accounts->get( $id, $id );

			foreach ( $account->getUsers() as $user ) {
				if ( $email === $user->getEmailAddress() && $user->getAdmin() ) {
					return true;
				}
			}
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
		}

		return false;
	}
}
