<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Shows an inbox notification informing about the new Attribute Mapping feature
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 *
 * @since x.x.x
 */
class AttributeMappingNewFeature extends AbstractNote implements OptionsAwareInterface {

	use PluginHelper;
	use OptionsAwareTrait;

	/**
	 * Get the note's unique name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'gla-attribute-mapping-new-feature';
	}

	/**
	 * Get the note entry.
	 *
	 * @return NoteEntry
	 */
	public function get_entry(): NoteEntry {
		$note = new NoteEntry();

		$note->set_title( __( 'Our latest improvement to the Google Listings & Ads extension: Custom Attribute Mapping', 'google-listings-and-ads' ) );
		$note->set_content(
			__(
				'You spoke, we listened. This new feature enables you to easily upload your products, customize your product attributes in one place, and target shoppers with more relevant ads. Extend how far your ad dollars go with each campaign.',
				'google-listings-and-ads'
			)
		);

		$note->set_name( $this->get_name() );
		$note->set_source( $this->get_slug() );
		$note->add_action(
			'learn-more-attribute-mapping',
			__( 'Learn more', 'google-listings-and-ads' ),
			'admin.php?page=wc-admin&path=/google/attribute-mapping'
		);

		return $note;
	}


	/**
	 * Checks if a note should be added and the user is an existing user.
	 *
	 * @return bool
	 */
	public function should_be_added(): bool {
		return ! $this->has_been_added() && $this->options->get( OptionsInterface::FILE_VERSION ) <= '2.2.1';

	}
}
