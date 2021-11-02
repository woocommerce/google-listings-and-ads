/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import AppDocumentationLink from '.~/components/app-documentation-link';

export default function ConnectGoogleAccountCard( { disabled } ) {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useGoogleAuthorization(
		pageName
	);

	const handleConnectClick = async () => {
		try {
			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			disabled={ disabled }
			alignIcon="top"
			description={
				<>
					{ __(
						'Required to sync with Google Merchant Center and Google Ads.',
						'google-listings-and-ads'
					) }
					<p>
						<em>
							{ createInterpolateElement(
								__(
									'You will be prompted to give WooCommerce access to your Google account. Please check all the checkboxes to give WooCommerce all required permissions. <link>Read more</link>',
									'google-listings-and-ads'
								),
								{
									link: (
										<AppDocumentationLink
											context="setup-mc-accounts"
											linkId="required-google-permissions"
											href="https://docs.woocommerce.com/document/google-listings-and-ads/#required-google-permissions"
										/>
									),
								}
							) }
						</em>
					</p>
				</>
			}
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					disabled={ disabled }
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
					text={ __( 'Connect', 'google-listings-and-ads' ) }
					onClick={ handleConnectClick }
				/>
			}
		/>
	);
}
