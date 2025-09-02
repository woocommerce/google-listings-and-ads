<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

trait OptionsTrait {
	public function create_options_mock() {
		$wp = $this->createStub( WP::class );

		$wp->method( 'get_option' )->willReturnArgument( 1 );
		$wp->method( 'add_option' )->willReturn( true );
		$wp->method( 'update_option' )->willReturn( true );
		$wp->method( 'delete_option' )->willReturn( true );

		$options = $this->getMockBuilder( Options::class )
			->onlyMethods( [ 'get_merchant_id', 'get_ads_id' ] )
			->getMock();

		$options->set_wp_proxy_object( $wp );

		return $options;
	}
}
