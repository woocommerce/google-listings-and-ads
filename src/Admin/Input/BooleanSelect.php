<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class BooleanSelect
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class BooleanSelect extends Select {

	/**
	 * @return array
	 */
	public function get_options(): array {
		return [
			''    => __( 'Default', 'google-listings-and-ads' ),
			'yes' => __( 'Yes', 'google-listings-and-ads' ),
			'no'  => __( 'No', 'google-listings-and-ads' ),
		];
	}

	/**
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		$view_data = parent::get_view_data();

		if ( is_bool( $view_data['value'] ) ) {
			$view_data['value'] = wc_bool_to_string( $view_data['value'] );
		}

		return $view_data;
	}
}
