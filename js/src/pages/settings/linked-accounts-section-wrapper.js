/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import Section from '~/components/section';

export default function LinkedAccountsSectionWrapper( props ) {
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	const description = hasGoogleMCConnection
		? __(
				'A WordPress.com account, Google account, Google Merchant Center account, and Google Ads account are required to use this extension in WooCommerce.',
				'google-listings-and-ads'
		  )
		: __(
				'A WordPress.com account, Google account and Google Ads account are required to use this extension in WooCommerce.',
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
