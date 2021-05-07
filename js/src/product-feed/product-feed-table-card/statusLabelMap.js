/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const statusLabelMap = {
	pending: __( 'Pending', 'google-listings-and-ads' ),
	synced: __( 'Synced', 'google-listings-and-ads' ),
	'not-synced': __( 'Not synced', 'google-listings-and-ads' ),
	'has-errors': __( 'Has errors', 'google-listings-and-ads' ),
};

export default statusLabelMap;
