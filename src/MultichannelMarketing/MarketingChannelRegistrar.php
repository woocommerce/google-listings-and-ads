<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MultichannelMarketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingChannels;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class MarketingChannelRegistrar
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MultichannelMarketing
 *
 * @since   x.x.x
 */
class MarketingChannelRegistrar implements Service, Registerable {
	/**
	 * @var MarketingChannels
	 */
	protected $marketing_channels;

	/**
	 * @var GLAChannel
	 */
	protected $channel;

	/**
	 * MarketingChannelRegistrar constructor.
	 *
	 * @param GLAChannel $channel
	 */
	public function __construct( GLAChannel $channel ) {
		$this->marketing_channels = wc_get_container()->get( MarketingChannels::class );
		$this->channel            = $channel;
	}

	/**
	 * Register as a WooCommerce marketing channel.
	 */
	public function register(): void {
		$this->marketing_channels->register( $this->channel );
	}
}
