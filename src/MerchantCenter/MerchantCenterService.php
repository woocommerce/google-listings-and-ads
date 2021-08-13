<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
use DateTime;
use Google\Service\ShoppingContent\AccountAddress;
use Google\Service\ShoppingContent\AccountBusinessInformation;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterService
 *
 * ContainerAware used to access:
 * - AddressUtility
 * - ContactInformation
 * - MerchantAccountState
 * - MerchantStatuses
 * - Settings
 * - ShippingRateTable
 * - ShippingTimeTable
 * - WC
 * - WP
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MerchantCenterService implements ContainerAwareInterface, OptionsAwareInterface, Service {

	use ContainerAwareTrait;
	use GoogleHelper;
	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * MerchantCenterService constructor.
	 */
	public function __construct() {
		add_filter(
			'woocommerce_gla_custom_merchant_issues',
			function( array $issues, DateTime $cache_created_time ) {
				return $this->maybe_add_contact_info_issue( $issues, $cache_created_time );
			},
			10,
			2
		);
	}

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	public function is_setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::MC_SETUP_COMPLETED_AT, false ) );
	}

	/**
	 * Get whether Merchant Center is connected.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		return $this->is_google_connected() && $this->is_setup_complete();
	}

	/**
	 * Get whether the dependent Google account is connected.
	 *
	 * @return bool
	 */
	public function is_google_connected(): bool {
		return boolval( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) );
	}

	/**
	 * Get whether the country is supported by the Merchant Center.
	 *
	 * @param  string $country Optional - to check a country other than the site country.
	 * @return bool True if the country is in the list of MC-supported countries.
	 */
	public function is_country_supported( string $country = '' ): bool {
		// Default to WooCommerce store country
		if ( empty( $country ) ) {
			$country = $this->container->get( WC::class )->get_base_country();
		}

		return array_key_exists(
			strtoupper( $country ),
			$this->get_mc_supported_countries_currencies()
		);
	}

	/**
	 * Get whether the language is supported by the Merchant Center.
	 *
	 * @param  string $language Optional - to check a language other than the site language.
	 * @return bool True if the language is in the list of MC-supported languages.
	 */
	public function is_language_supported( string $language = '' ): bool {
		// Default to base site language
		if ( empty( $language ) ) {
			$language = substr( $this->container->get( WP::class )->get_locale(), 0, 2 );
		}

		return array_key_exists(
			strtolower( $language ),
			$this->get_mc_supported_languages()
		);
	}

	/**
	 * Get whether the contact information has been setup.
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function is_contact_information_setup(): bool {
		if ( true === boolval( $this->options->get( OptionsInterface::CONTACT_INFO_SETUP, false ) ) ) {
			return true;
		}

		// Additional check for users that have already gone through on-boarding.
		if ( $this->is_setup_complete() ) {
			$is_mc_setup = $this->is_mc_contact_information_setup();
			$this->options->update( OptionsInterface::CONTACT_INFO_SETUP, $is_mc_setup );
			return $is_mc_setup;
		}

		return false;
	}

	/**
	 * @return string[] List of target countries specified in options. Defaults to WooCommerce store base country.
	 */
	public function get_target_countries(): array {
		$target_countries = [ $this->container->get( WC::class )->get_base_country() ];

		$target_audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );
		if ( empty( $target_audience['location'] ) && empty( $target_audience['countries'] ) ) {
			return $target_countries;
		}

		$location = strtolower( $target_audience['location'] );
		if ( 'all' === $location ) {
			$target_countries = $this->get_mc_supported_countries();
		} elseif ( 'selected' === $location && ! empty( $target_audience['countries'] ) ) {
			$target_countries = $target_audience['countries'];
		}

		return $target_countries;
	}

	/**
	 * Return the main target country (default Store country).
	 * If the store country is not included then use the first target country.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_main_target_country(): string {
		$target_countries = $this->get_target_countries();
		$shop_country     = $this->container->get( WC::class )->get_base_country();

		return in_array( $shop_country, $target_countries, true ) ? $shop_country : $target_countries[0];
	}

	/**
	 * Get the connected merchant account.
	 *
	 * @return array
	 */
	public function get_connected_status(): array {
		$id     = $this->options->get_merchant_id();
		$status = [
			'id'     => $id,
			'status' => $id ? 'connected' : 'disconnected',
		];

		$incomplete = $this->container->get( MerchantAccountState::class )->last_incomplete_step();
		if ( ! empty( $incomplete ) ) {
			$status['status'] = 'incomplete';
			$status['step']   = $incomplete;
		}

		return $status;
	}

	/**
	 * Return the setup status to determine what step to continue at.
	 *
	 * @return array
	 */
	public function get_setup_status(): array {
		if ( $this->is_setup_complete() ) {
			return [ 'status' => 'complete' ];
		}

		$step = 'accounts';
		if ( $this->connected_account() ) {
			$step = 'target_audience';

			if ( $this->saved_target_audience() ) {
				$step = 'shipping_and_taxes';

				if ( $this->saved_shipping_and_tax_options() ) {
					$step = 'store_requirements';
				}
			}
		}

		return [
			'status' => 'incomplete',
			'step'   => $step,
		];
	}

	/**
	 * Disconnect Merchant Center account
	 */
	public function disconnect() {
		$this->options->delete( OptionsInterface::CONTACT_INFO_SETUP );
		$this->options->delete( OptionsInterface::MC_SETUP_COMPLETED_AT );
		$this->options->delete( OptionsInterface::MERCHANT_ACCOUNT_STATE );
		$this->options->delete( OptionsInterface::MERCHANT_CENTER );
		$this->options->delete( OptionsInterface::SITE_VERIFICATION );
		$this->options->delete( OptionsInterface::TARGET_AUDIENCE );
		$this->options->delete( OptionsInterface::MERCHANT_ID );

		$this->container->get( MerchantStatuses::class )->delete();

		$this->container->get( MerchantIssueTable::class )->truncate();
		$this->container->get( ShippingRateTable::class )->truncate();
		$this->container->get( ShippingTimeTable::class )->truncate();
	}

	/**
	 * Check if account has been connected.
	 *
	 * @return bool
	 */
	protected function connected_account(): bool {
		$id = $this->options->get_merchant_id();
		return $id && ! $this->container->get( MerchantAccountState::class )->last_incomplete_step();
	}

	/**
	 * Check if target audience has been saved (with a valid selection of countries).
	 *
	 * @return bool
	 */
	protected function saved_target_audience(): bool {
		$audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );

		if ( empty( $audience ) || ! isset( $audience['location'] ) ) {
			return false;
		}

		$empty_selection = 'selected' === $audience['location'] && empty( $audience['countries'] );
		return ! $empty_selection;
	}

	/**
	 * Checks if we should add an issue when the contact information is not setup.
	 *
	 * @since 1.4.0
	 *
	 * @param array    $issues             The current array of custom issues
	 * @param DateTime $cache_created_time The time of the cache/issues generation.
	 *
	 * @return array
	 */
	protected function maybe_add_contact_info_issue( array $issues, DateTime $cache_created_time ): array {
		if ( $this->is_setup_complete() && ! $this->is_contact_information_setup() ) {
			$issues[] = [
				'product_id' => 0,
				'product'    => 'All products',
				'code'       => 'missing_contact_information',
				'issue'      => __( 'No contact information.', 'google-listings-and-ads' ),
				'action'     => __( 'Add store contact information', 'google-listings-and-ads' ),
				'action_url' => $this->get_contact_information_setup_url(),
				'created_at' => $cache_created_time->format( 'Y-m-d H:i:s' ),
				'type'       => MerchantStatuses::TYPE_ACCOUNT,
				'severity'   => 'error',
				'source'     => 'filter',
			];
		}

		return $issues;
	}

	/**
	 * Check if the Merchant Center contact information has been setup already.
	 *
	 * @since 1.4.0
	 *
	 * @return boolean
	 */
	protected function is_mc_contact_information_setup(): bool {
		$is_setup = [
			'phone_number' => false,
			'address'      => false,
		];

		try {
			$contact_info = $this->container->get( ContactInformation::class )->get_contact_information();
		} catch ( MerchantApiException $exception ) {
			do_action(
				'woocommerce_gla_debug_message',
				'Error retrieving Merchant Center account\'s business information.',
				__METHOD__
			);

			return false;
		}

		if ( $contact_info instanceof AccountBusinessInformation ) {
			$is_setup['phone_number'] = ! empty( $contact_info->getPhoneNumber() );

			/** @var Settings $settings */
			$settings = $this->container->get( Settings::class );

			if ( $contact_info->getAddress() instanceof AccountAddress && $settings->get_store_address() instanceof AccountAddress ) {
				$is_setup['address'] = $this->container->get( AddressUtility::class )->compare_addresses(
					$contact_info->getAddress(),
					$settings->get_store_address()
				);
			}
		}

		return $is_setup['phone_number'] && $is_setup['address'];
	}

	/**
	 * Check if the taxes + shipping rate and time + free shipping settings have been saved.
	 *
	 * @return bool If all required settings have been provided.
	 *
	 * @since 1.4.0
	 */
	protected function saved_shipping_and_tax_options(): bool {
		$merchant_center_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
		$target_countries         = $this->get_target_countries();

		// Tax options saved if: not US (no taxes) or tax_rate has been set
		if ( in_array( 'US', $target_countries, true ) && empty( $merchant_center_settings['tax_rate'] ) ) {
			return false;
		}

		// Free shipping saved if: not offered, OR offered and threshold not null
		if ( ! empty( $merchant_center_settings['offers_free_shipping'] ) && ! isset( $merchant_center_settings['free_shipping_threshold'] ) ) {
			return false;
		}

		// Shipping options saved if: 'manual' OR records for all countries
		if ( isset( $merchant_center_settings['shipping_time'] ) && 'manual' === $merchant_center_settings['shipping_time'] ) {
			$saved_shipping_time = true;
		} else {
			$shipping_time_rows  = $this->container->get( ShippingTimeQuery::class )->get_count();
			$saved_shipping_time = $shipping_time_rows === count( $target_countries );
		}

		if ( isset( $merchant_center_settings['shipping_rate'] ) && 'manual' === $merchant_center_settings['shipping_rate'] ) {
			$saved_shipping_rate = true;
		} else {
			$shipping_rate_rows  = $this->container->get( ShippingRateQuery::class )->get_count();
			$saved_shipping_rate = $shipping_rate_rows === count( $target_countries );
		}

		return $saved_shipping_rate && $saved_shipping_time;
	}
}
