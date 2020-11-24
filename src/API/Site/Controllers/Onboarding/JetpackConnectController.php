<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\PermissionsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class JetpackConnectController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding
 */
class JetpackConnectController extends BaseController {

	use PermissionsTrait;

	/**
	 * @var Manager
	 */
	protected $manager;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param Manager    $manager
	 */
	public function __construct( RESTServer $server, Manager $manager ) {
		parent::__construct( $server );
		$this->manager = $manager;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'jetpack/connected',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => function() {
						return [ 'active' => $this->is_jetpack_connected() ];
					},
					'permission_callback' => function() {
						return $this->can_manage();
					},
				],
			]
		);
	}

	/**
	 * Determine whether jetpack is connected.
	 *
	 * @return string
	 */
	protected function is_jetpack_connected(): string {
		return $this->manager->is_active() ? 'yes' : 'no';
	}
}
