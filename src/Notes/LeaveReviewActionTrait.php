<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notes;

use Automattic\WooCommerce\Admin\Notes\Note as NoteEntry;

/**
 * Trait LeaveReviewActionTrait
 *
 * @since 1.7.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notes
 */
trait LeaveReviewActionTrait {

	/**
	 * Add the 'leave a review' action to a note.
	 *
	 * Randomly chooses whether to show the WP.org vs WC.com link.
	 *
	 * @param NoteEntry $note
	 */
	protected function add_leave_review_note_action( NoteEntry $note ) {
		$wp_link = 'https://wordpress.org/support/plugin/google-listings-and-ads/reviews/#new-post';
		$wc_link = 'https://woocommerce.com/products/google-listings-and-ads/#reviews';

		$note->add_action(
			'leave-review',
			__( 'Leave a review', 'google-listings-and-ads' ),
			rand( 0, 1 ) ? $wp_link : $wc_link
		);
	}

}
