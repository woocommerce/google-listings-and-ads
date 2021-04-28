<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Interface FormInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
interface FormInterface {

	/**
	 * Return a list of inputs provided by the form.
	 *
	 * @return InputInterface[]
	 */
	public function get_inputs(): array;

	/**
	 * Return the form's data.
	 *
	 * @return array
	 */
	public function get_data(): array;

	/**
	 * Set the form's data.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function set_data( array $data = [] ): void;

	/**
	 * Return the form name.
	 *
	 * This name is used as a prefix for the form's field names.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Submit the form.
	 *
	 * @param array $submitted_data
	 *
	 * @return void
	 */
	public function submit( array $submitted_data = [] ): void;

	/**
	 * Return the data used for the form's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array;

}
