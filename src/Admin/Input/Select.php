<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Select
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Select extends AbstractInput {
	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * Select constructor.
	 */
	public function __construct() {
		parent::__construct( 'select' );
	}

	/**
	 * @return array
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * @param array $options
	 *
	 * @return $this
	 */
	public function set_options( array $options ): Select {
		$this->options = $options;

		return $this;
	}
}
