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
	 * @param array $args
	 *
	 * @return InputInterface[]
	 */
	public function get_inputs( array $args ): array;

	/**
	 * Return the form's submitted data.
	 *
	 * @return array
	 */
	public function get_data(): array;

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
	 * @param array $args
	 *
	 * @return void
	 */
	public function submit( array $args ): void;

}
