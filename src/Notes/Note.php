<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Note interface.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
interface Note extends Service {

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Check whether the note should be added.
	 */
	public function should_be_added(): bool;

	/**
	 * Add the note.
	 */
	public function add(): void;

}
