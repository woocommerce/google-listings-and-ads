<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class GTIN
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class GTINInput extends Text {

	/**
	 * Initial install version to disable the GTIN field for.
	 *
	 * @var string
	 */
	private $hidden_from = '2.8.5';

	/**
	 * GTINInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Global Trade Item Number (GTIN)', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Global Trade Item Number (GTIN) for your item. These identifiers include UPC (in North America), EAN (in Europe), JAN (in Japan), and ISBN (for books)', 'google-listings-and-ads' ) );

		$this->conditionally_restrict();
	}

	/**
	 * Controls the inputs visibility based on the WooCommerce version and the
	 * initial version of Google for WooCommerce at the time of installation.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function conditionally_restrict(): void {
		$initial_version = $this->options->get( OptionsInterface::INSTALL_VERSION, false );

		if ( version_compare( WC_VERSION, '9.2', '>=' ) ) {
			if ( version_compare( $initial_version, $this->hidden_from, '>=' ) ) {
				$this->set_disabled( true );
			} else {
				$this->set_readonly( true );
				$this->set_description( __( 'The Global Trade Item Number (GTIN) for your item can now be entered on the "Inventory" tab', 'google-listings-and-ads' ) );
			}
		}
	}
}
