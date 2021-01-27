<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ClaimWebsiteController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ClaimWebsiteController extends BaseController {

	/** @var ContainerInterface $container */
	protected $container;

	/**
	 * BaseController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->container = $container;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'mc/claimwebsite',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_claimwebsite_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for the connection request.
	 *
	 * @return callable
	 */
	protected function get_claimwebsite_callback(): callable {
		return function () {
			try {
				$this->container->get( Merchant::class )->claimwebsite();
			} catch ( \Exception $e ) {
				do_action( $this->get_slug() . '_claimwebsite_failure', [] );

				return new \WP_REST_Response(
					[
						'status'  => 'failure',
						'message' => __( 'Unable to claim website.', 'google-listings-and-ads' ),
					],
					$e->getCode()
				);
			}

			return [
				'status'  => 'success',
				'message' => __( 'Successfully claimed website.', 'google-listings-and-ads' ),
			];
		};
	}
}
