<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

use DateTimeZone;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateTime
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 *
 * @since 1.5.0
 */
class DateTime extends Input {
	/**
	 * DateTime constructor.
	 */
	public function __construct() {
		parent::__construct( 'datetime', 'google-listings-and-ads/product-date-time-field' );
	}

	/**
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		$view_data = parent::get_view_data();

		if ( ! empty( $this->get_value() ) ) {
			try {
				// Display the time in site's local timezone.
				$datetime = new WC_DateTime( $this->get_value(), new DateTimeZone( 'UTC' ) );
				$datetime->setTimezone( new DateTimeZone( $this->get_local_tz_string() ) );
				$view_data['value'] = $datetime->format( 'Y-m-d H:i:s' );
				$view_data['date']  = $datetime->format( 'Y-m-d' );
				$view_data['time']  = $datetime->format( 'H:i' );
			} catch ( Exception $e ) {
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

				$view_data['value'] = '';
				$view_data['date']  = '';
				$view_data['time']  = '';
			}
		}

		return $view_data;
	}

	/**
	 * Set the form's data.
	 *
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function set_data( $data ): void {
		if ( is_array( $data ) ) {
			if ( ! empty( $data['date'] ) ) {
				$date = $data['date'] ?? '';
				$time = $data['time'] ?? '';
				$data = sprintf( '%s%s', $date, $time );
			} else {
				$data = '';
			}
		}

		if ( ! empty( $data ) ) {
			try {
				// Store the time in UTC.
				$datetime = new WC_DateTime( $data, new DateTimeZone( $this->get_local_tz_string() ) );
				$datetime->setTimezone( new DateTimeZone( 'UTC' ) );
				$data = (string) $datetime;
			} catch ( Exception $e ) {
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

				$data = '';
			}
		}

		parent::set_data( $data );
	}

	/**
	 * Get site's local timezone string from WordPress settings.
	 *
	 * @return string
	 */
	protected function get_local_tz_string(): string {
		return wp_timezone_string();
	}
}
