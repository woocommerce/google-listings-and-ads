<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

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
	 * MetaBoxInitializer constructor.
	 *
	 * @param Admin              $admin
	 * @param MetaBoxInterface[] $meta_boxes
	 */
	public function __construct( Admin $admin, array $meta_boxes ) {
		$this->admin      = $admin;
		$this->meta_boxes = $meta_boxes;
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
		array_walk( $this->meta_boxes, [ $this->admin, 'add_meta_box' ] );
	}
}
