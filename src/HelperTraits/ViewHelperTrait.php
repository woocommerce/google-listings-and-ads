<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ViewHelperTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
trait ViewHelperTrait {

	use PluginHelper;

	/**
	 * Returns the list of allowed HTML tags used for view sanitization.
	 *
	 * @return array
	 */
	protected function get_allowed_html_form_tags(): array {
		$allowed_attributes = [
			'aria-describedby' => true,
			'aria-details'     => true,
			'aria-label'       => true,
			'aria-labelledby'  => true,
			'aria-hidden'      => true,
			'class'            => true,
			'id'               => true,
			'style'            => true,
			'title'            => true,
			'role'             => true,
			'data-*'           => true,
			'action'           => true,
			'value'            => true,
			'name'             => true,
			'selected'         => true,
			'type'             => true,
			'disabled'         => true,
		];

		return array_merge(
			wp_kses_allowed_html( 'post' ),
			[
				'form'   => $allowed_attributes,
				'input'  => $allowed_attributes,
				'select' => $allowed_attributes,
				'option' => $allowed_attributes,
			]
		);
	}

	/**
	 * Appends a prefix to the given ID and returns it.
	 *
	 * @param string $id
	 *
	 * @return string
	 *
	 * @since 1.1.0
	 */
	protected function prefix_id( string $id ): string {
		return "{$this->get_slug()}_$id";
	}
}
