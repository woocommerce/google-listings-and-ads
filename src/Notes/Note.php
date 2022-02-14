<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Note interface.
 *
 * @since 1.7.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
interface Note {

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
	 * Get the note entry.
	 */
	public function get_entry(): NoteEntry;

}
