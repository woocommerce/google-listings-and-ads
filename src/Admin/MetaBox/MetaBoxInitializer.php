<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;

defined( 'ABSPATH' ) || exit;

/**
 * Class MetaBoxInitializer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox
 */
class MetaBoxInitializer implements Service, Registerable, Conditional {

	use AdminConditional;

	/**
	 * @var Admin
	 */
	protected $admin;

	/**
	 * @var MetaBoxInterface[]
	 */
	protected $meta_boxes;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * MetaBoxInitializer constructor.
	 *
	 * @param Admin                 $admin
	 * @param MetaBoxInterface[]    $meta_boxes
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( Admin $admin, array $meta_boxes, MerchantCenterService $merchant_center ) {
		$this->admin           = $admin;
		$this->meta_boxes      = $meta_boxes;
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
	}

	/**
	 * Registers the meta boxes.
	 */
	public function register_meta_boxes() {
		foreach ( $this->meta_boxes as $meta_box ) {
			if ( ! $meta_box->can_register() ) {
				continue;
			}

			$this->admin->add_meta_box( $meta_box );
		}
	}
}
