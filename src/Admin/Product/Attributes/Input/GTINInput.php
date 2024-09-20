<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

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
	 * @var string
	 */
	private $disabled_from = '2024-09-20';

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
	 * If WooCommerce >= 9.2 is installed then the field will be:
	 * - Disabled if GLA was installed after this feature was added
	 * - Readonly if GLA was installed before this feature was added
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function conditionally_restrict(): void {
		if ( version_compare( WC_VERSION, '9.2', '>=' ) ) {
			if ( $this->gla_installed_after( $this->disabled_from ) ) {
				$this->set_disabled( true );
				return;
			}

			$this->set_readonly( true );
			$this->set_description( __( 'The Global Trade Item Number (GTIN) for your item can now be entered on the "Inventory" tab', 'google-listings-and-ads' ) );
		}
	}
}
