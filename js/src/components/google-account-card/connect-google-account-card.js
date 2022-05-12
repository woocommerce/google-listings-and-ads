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
import AppButton from '.~/components/app-button';
import readMoreLink from './read-more-link';
import useGoogleConnectFlow from './use-google-connect-flow';

/**
 * @param {Object} props React props
 * @param {boolean} props.disabled
 * @fires gla_google_account_connect_button_click with `{ action: 'authorization', context: 'reconnect' | 'setup-mc' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'required-google-permissions', href: 'https://docs.woocommerce.com/document/google-listings-and-ads/#required-google-permissions' }`
 */
const ConnectGoogleAccountCard = ( { disabled } ) => {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const [ handleConnect, { loading, data } ] = useGoogleConnectFlow(
		pageName
	);

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
									link: readMoreLink,
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
					eventProps={ {
						context: pageName,
						action: 'authorization',
					} }
					text={ __( 'Connect', 'google-listings-and-ads' ) }
					onClick={ handleConnect }
				/>
			}
		/>
	);
};

export default ConnectGoogleAccountCard;
