<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Form
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Form implements FormInterface {

	use ValidateInterface;

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var mixed
	 */
	protected $data;

	/**
	 * @var FormInterface[]
	 */
	protected $children = [];

	/**
	 * @var FormInterface
	 */
	protected $parent;

	/**
	 * @var bool
	 */
	protected $is_submitted = false;

	/**
	 * Form constructor.
	 *
	 * @param mixed $data
	 */
	public function __construct( $data = null ) {
		$this->set_data( $data );
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @param string $name
	 *
	 * @return FormInterface
	 */
	public function set_name( string $name ): FormInterface {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return FormInterface[]
	 */
	public function get_children(): array {
		return $this->children;
	}

	/**
	 * Add a child form.
	 *
	 * @param FormInterface $form
	 *
	 * @return FormInterface
	 *
	 * @throws FormException If form is already submitted.
	 */
	public function add( FormInterface $form ): FormInterface {
		if ( $this->is_submitted ) {
			throw FormException::cannot_modify_submitted();
		}

		$this->children[ $form->get_name() ] = $form;
		$form->set_parent( $this );

		return $this;
	}

	/**
	 * Remove a child with the given name from the form's children.
	 *
	 * @param string $name
	 *
	 * @return FormInterface
	 *
	 * @throws FormException If form is already submitted.
	 */
	public function remove( string $name ): FormInterface {
		if ( $this->is_submitted ) {
			throw FormException::cannot_modify_submitted();
		}

		if ( isset( $this->children[ $name ] ) ) {
			$this->children[ $name ]->set_parent( null );
			unset( $this->children[ $name ] );
		}

		return $this;
	}

	/**
	 * Whether the form contains a child with the given name.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has( string $name ): bool {
		return isset( $this->children[ $name ] );
	}

	/**
	 * @param FormInterface|null $form
	 *
	 * @return void
	 */
	public function set_parent( ?FormInterface $form ): void {
		$this->parent = $form;
	}

	/**
	 * @return FormInterface|null
	 */
	public function get_parent(): ?FormInterface {
		return $this->parent;
	}

	/**
	 * Return the form's data.
	 *
	 * @return mixed
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Set the form's data.
	 *
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function set_data( $data ): void {
		if ( is_array( $data ) && ! empty( $this->children ) ) {
			$this->data = $this->map_children_data( $data );
		} else {
			if ( is_string( $data ) ) {
				$data = trim( $data );
			}
			$this->data = $data;
		}
	}

	/**
	 * Maps the data to each child and returns the mapped data.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function map_children_data( array $data ): array {
		$children_data = [];
		foreach ( $data as $key => $datum ) {
			if ( isset( $this->children[ $key ] ) ) {
				$this->children[ $key ]->set_data( $datum );
				$children_data[ $key ] = $this->children[ $key ]->get_data();
			}
		}

		return $children_data;
	}

	/**
	 * Submit the form.
	 *
	 * @param array $submitted_data
	 */
	public function submit( array $submitted_data = [] ): void {
		// todo: add form validation
		if ( ! $this->is_submitted ) {
			$this->is_submitted = true;
			$this->set_data( $submitted_data );
		}
	}

	/**
	 * Return the data used for the form's view.
	 *
	 * @return array
	 */
	public function get_view_data(): array {
		$view_data = [];

		$view_data['name'] = $this->get_view_name();

		$view_data['is_root'] = $this->is_root();

		foreach ( $this->get_children() as $index => $form ) {
			$view_data['children'][ $index ] = $form->get_view_data();
		}

		return $view_data;
	}

	/**
	 * Return the name used for the form's view.
	 *
	 * @return string
	 */
	public function get_view_name(): string {
		return $this->is_root() ? sprintf( 'gla_%s', $this->get_name() ) : sprintf( '%s[%s]', $this->get_parent()->get_view_name(), $this->get_name() );
	}

	/**
	 * Whether this is the root form (i.e. has no parents).
	 *
	 * @return bool
	 */
	public function is_root(): bool {
		return null === $this->parent;
	}

	/**
	 * Whether the form has been already submitted.
	 *
	 * @return bool
	 */
	public function is_submitted(): bool {
		return $this->is_submitted;
	}
}
