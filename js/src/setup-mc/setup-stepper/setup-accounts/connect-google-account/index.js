/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Subsection from '../../../../wcdl/subsection';
import SettingsCardLayout from '../../../../components/settings-card-layout';
import ContentButtonLayout from '../content-button-layout';

const ConnectGoogleAccount = () => {
	// TODO: call backend API upon clicking Connect button.
	const handleConnectClick = () => {};

	return (
		<SettingsCardLayout
			title={ __( 'Google account', 'google-listings-and-ads' ) }
			description={ __(
				'WooCommerce uses your Google account to sync with Google Merchant Center and Google Ads.',
				'google-listings-and-ads'
			) }
		>
			<ContentButtonLayout>
				<Subsection.Title>
					{ __(
						'Connect your Google account',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<Button isSecondary onClick={ handleConnectClick }>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</Button>
			</ContentButtonLayout>
		</SettingsCardLayout>
	);
};

export default ConnectGoogleAccount;
