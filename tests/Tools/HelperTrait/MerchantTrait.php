<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Google\Service\ShoppingContent\Account;
use Google\Service\ShoppingContent\AccountBusinessInformation;
use Exception;
use WP_REST_Response;

/**
 * Trait MerchantTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 *
 * @since x.x.x
 */
trait MerchantTrait {

	protected $valid_account_phone_number = '+18008675309';
	protected $valid_account_id = '123581321';

	public function get_account_exception( int $code = 400 ) {
		return new Exception( __( 'Unable to retrieve Merchant Center account.', 'google-listings-and-ads' ), $code );
	}

	public function get_empty_account(): Account {
		return new Account();
	}

	public function get_valid_account(): Account {
		$account = new Account();
		$business_info = new AccountBusinessInformation();
		$business_info->setPhoneNumber( $this->valid_account_phone_number );
		$account->setBusinessInformation( $business_info );
		return $account;
	}

	public function get_valid_account_json( $server, $request ) {

		$response = <<<STR
{
  "kind": "content#account",
  "businessInformation": {
    "phoneNumber": {$this->valid_account_phone_number}"
  }
}'
STR;
		return false; //new WP_REST_Response( json_decode($response) );

	}
}
