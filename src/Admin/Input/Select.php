<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Select
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Select extends Input {
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

	/**
	 * Return the data used for the input's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		$view_data            = parent::get_view_data();
		$view_data['options'] = $this->get_options();

		// add custom class
		$view_data['class'] = 'select short';

		return $view_data;
	}
}
