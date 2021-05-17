/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const statusLabelMap = {
	approved: __( 'Approved', 'google-listings-and-ads' ),
	partially_approved: __( 'Partially approved', 'google-listings-and-ads' ),
	expiring: __( 'Expiring', 'google-listings-and-ads' ),
	pending: __( 'Pending', 'google-listings-and-ads' ),
	disapproved: __( 'Disapproved', 'google-listings-and-ads' ),
	not_synced: __( 'Not synced', 'google-listings-and-ads' ),
};

export default statusLabelMap;
