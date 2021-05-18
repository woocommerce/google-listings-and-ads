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
	 * Return the form's data.
	 *
	 * @return mixed
	 */
	public function get_data();

	/**
	 * Set the form's data.
	 *
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function set_data( $data ): void;

	/**
	 * Return the form name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Set the form's name.
	 *
	 * @param string $name
	 *
	 * @return FormInterface
	 */
	public function set_name( string $name ): FormInterface;

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

	/**
	 * Return the name used for the form's view.
	 *
	 * @return string
	 */
	public function get_view_name(): string;

	/**
	 * @return FormInterface[]
	 */
	public function get_children(): array;

	/**
	 * Add a child form.
	 *
	 * @param FormInterface $form
	 *
	 * @return FormInterface
	 */
	public function add( FormInterface $form ): FormInterface;

	/**
	 * Remove a child with the given name from the form's children.
	 *
	 * @param string $name
	 *
	 * @return FormInterface
	 */
	public function remove( string $name ): FormInterface;

	/**
	 * Whether the form contains a child with the given name.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has( string $name ): bool;

	/**
	 * @param FormInterface|null $form
	 *
	 * @return void
	 */
	public function set_parent( ?FormInterface $form ): void;

	/**
	 * @return FormInterface|null
	 */
	public function get_parent(): ?FormInterface;

	/**
	 * If this is the root form (i.e. has no parents)
	 *
	 * @return bool
	 */
	public function is_root(): bool;

	/**
	 * Whether the form has been already submitted.
	 *
	 * @return bool
	 */
	public function is_submitted(): bool;
}
