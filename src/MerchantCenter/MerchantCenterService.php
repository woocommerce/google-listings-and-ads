<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
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
 * - WC
 * - WP
 * - TargetAudience
 * - GoogleHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MerchantCenterService implements ContainerAwareInterface, OptionsAwareInterface, Service {

	use ContainerAwareTrait;
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
	 * @return bool True if the country is in the list of MC-supported countries.
	 *
	 * @since 1.9.0
	 */
	public function is_store_country_supported(): bool {
		$country = $this->container->get( WC::class )->get_base_country();

		/** @var GoogleHelper $google_helper */
		$google_helper = $this->container->get( GoogleHelper::class );

		return $google_helper->is_country_supported( $country );
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

		/** @var GoogleHelper $google_helper */
		$google_helper = $this->container->get( GoogleHelper::class );

		return array_key_exists(
			strtolower( $language ),
			$google_helper->get_mc_supported_languages()
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
	 * Return if the given country is supported to have promotions on Google.
	 *
	 * @param string $country
	 *
	 * @return bool
	 */
	public function is_promotion_supported_country( string $country = '' ): bool {
		// Default to WooCommerce store country
		if ( empty( $country ) ) {
			$country = $this->container->get( WC::class )->get_base_country();
		}

		/** @var GoogleHelper $google_helper */
		$google_helper = $this->container->get( GoogleHelper::class );

		return in_array( $country, $google_helper->get_mc_promotion_supported_countries(), true );
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
				'action_url' => $this->get_settings_url(),
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
		$target_countries         = $this->container->get( TargetAudience::class )->get_target_countries();

		// Tax options saved if: not US (no taxes) or tax_rate has been set
		if ( in_array( 'US', $target_countries, true ) && empty( $merchant_center_settings['tax_rate'] ) ) {
			return false;
		}

		// Shipping time saved if: 'manual' OR records for all countries
		if ( isset( $merchant_center_settings['shipping_time'] ) && 'manual' === $merchant_center_settings['shipping_time'] ) {
			$saved_shipping_time = true;
		} else {
			$shipping_time_rows = $this->container->get( ShippingTimeQuery::class )->get_results();

			// Get the name of countries that have saved shipping times.
			$saved_time_countries = array_column( $shipping_time_rows, 'country' );

			// Check if all target countries have a shipping time.
			$saved_shipping_time = count( $shipping_time_rows ) === count( $target_countries ) &&
								   empty( array_diff( $target_countries, $saved_time_countries ) );
		}

		// Shipping rates saved if: 'manual', 'automatic', OR there are records for all countries
		if (
			isset( $merchant_center_settings['shipping_rate'] ) &&
			in_array( $merchant_center_settings['shipping_rate'], [ 'manual', 'automatic' ], true )
		) {
			$saved_shipping_rate = true;
		} else {
			// Get the list of saved shipping rates grouped by country.
			/**
			 * @var ShippingRateQuery $shipping_rate_query
			 */
			$shipping_rate_query = $this->container->get( ShippingRateQuery::class );
			$shipping_rate_query->group_by( 'country' );
			$shipping_rate_rows = $shipping_rate_query->get_results();

			// Get the name of countries that have saved shipping rates.
			$saved_rates_countries = array_column( $shipping_rate_rows, 'country' );

			// Check if all target countries have a shipping rate.
			$saved_shipping_rate = count( $shipping_rate_rows ) === count( $target_countries ) &&
								   empty( array_diff( $target_countries, $saved_rates_countries ) );
		}

		return $saved_shipping_rate && $saved_shipping_time;
	}

	/**
	 * Determine whether there are any account-level issues.
	 *
	 * @since 1.11.0
	 * @return bool
	 */
	public function has_account_issues(): bool {
		$issues = $this->container->get( MerchantStatuses::class )->get_issues( MerchantStatuses::TYPE_ACCOUNT );

		return isset( $issues['issues'] ) && count( $issues['issues'] ) >= 1;
	}

	/**
	 * Determine whether there is at least one synced product.
	 *
	 * @since 1.11.0
	 * @return bool
	 */
	public function has_at_least_one_synced_product(): bool {
		$statuses = $this->container->get( MerchantStatuses::class )->get_product_statistics();

		return $statuses['statistics']['active'] >= 1;
	}
}
