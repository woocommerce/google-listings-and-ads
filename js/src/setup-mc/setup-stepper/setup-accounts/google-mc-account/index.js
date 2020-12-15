/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import Subsection from '../../../../wcdl/subsection';
import ContentButtonLayout from '../content-button-layout';

const GoogleMCAccount = () => {
	// TODO: call backend API upon clicking Connect button.
	const handleConnectClick = () => {};

	return (
		<Section
			title={ __(
				'Google Merchant Center account',
				'google-listings-and-ads'
			) }
			description={ __(
				'WooCommerce products synced to your Merchant Center product feed will allow you to list your products on Google.',
				'google-listings-and-ads'
			) }
		>
			<Section.Card>
				<ContentButtonLayout>
					<Subsection.Title>
						{ __(
							'Connect your Merchant Center',
							'google-listings-and-ads'
						) }
					</Subsection.Title>
					<Button isSecondary onClick={ handleConnectClick }>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</Button>
				</ContentButtonLayout>
			</Section.Card>
		</Section>
	);
};

export default GoogleMCAccount;
