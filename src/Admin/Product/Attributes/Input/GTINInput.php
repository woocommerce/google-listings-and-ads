<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
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
	 * Version since we start hiding the GTIN field.
	 *
	 * @var string
	 */
	private $hidden_since_version = '2.8.5';

	/**
	 * GTINInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Global Trade Item Number (GTIN)', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Global Trade Item Number (GTIN) for your item. These identifiers include UPC (in North America), EAN (in Europe), JAN (in Japan), and ISBN (for books)', 'google-listings-and-ads' ) );
		$this->set_field_visibility();
	}

	/**
	 * Controls the inputs visibility based on the WooCommerce version and the
	 * initial version of Google for WooCommerce at the time of installation.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function set_field_visibility(): void {
		$options         = woogle_get_container()->get( OptionsInterface::class );
		$initial_version = $options->get( OptionsInterface::INSTALL_VERSION, false );
		// 9.2 is the version when GTIN field was added in Woo Core. So we need to hide or set the field as read-only since then.
		if ( version_compare( WC_VERSION, '9.2', '>=' ) ) {
			// For versions after 2.8.5 hide the GTIN field from G4W tab. Otherwise, set as readonly.
			if ( $initial_version && version_compare( $initial_version, $this->hidden_since_version, '>=' ) ) {
				$this->set_hidden( true );
			} else {
				$this->set_readonly( true );
				$this->set_description( __( 'The Global Trade Item Number (GTIN) for your item can now be entered on the "Inventory" tab', 'google-listings-and-ads' ) );
			}
		}
	}
}
