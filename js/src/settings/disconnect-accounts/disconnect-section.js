/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import Section from '.~/wcdl/section';

export default function DisconnectSection( {
	showLoadingSpinnerOnly,
	children,
} ) {
	return (
		<Section
			title={ __( 'Linked accounts', 'google-listings-and-ads' ) }
			description={ __(
				'A Wordpress.com account, Google account, and Google Merchant Center account are required to use this extension in WooCommerce.',
				'google-listings-and-ads'
			) }
		>
			<Section.Card>
				{ showLoadingSpinnerOnly ? <AppSpinner /> : children }
			</Section.Card>
		</Section>
	);
}
