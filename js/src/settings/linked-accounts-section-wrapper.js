/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';

export default function LinkedAccountsSectionWrapper( props ) {
	return (
		<Section
			title={ __( 'Linked accounts', 'google-listings-and-ads' ) }
			description={ __(
				'A WordPress.com account, Google account, Google Merchant Center account, and Google Ads account are required to use this extension in WooCommerce.',
				'google-listings-and-ads'
			) }
			{ ...props }
		/>
	);
}
