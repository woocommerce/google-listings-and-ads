/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import { glaData } from '~/constants';

export default function LinkedAccountsSectionWrapper( props ) {
	const { serviceBasedMerchant } = glaData;

	const description = serviceBasedMerchant
		? __(
				'A WordPress.com account, Google account and Google Ads account are required to use this extension in WooCommerce.',
				'google-listings-and-ads'
		  )
		: __(
				'A WordPress.com account, Google account, Google Merchant Center account, and Google Ads account are required to use this extension in WooCommerce.',
				'google-listings-and-ads'
		  );

	return (
		<Section
			title={ __( 'Linked accounts', 'google-listings-and-ads' ) }
			description={ description }
			{ ...props }
		/>
	);
}
