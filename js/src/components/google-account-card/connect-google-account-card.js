/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import readMoreLink from './read-more-link';
import useGoogleConnectFlow from './useGoogleConnectFlow';

/**
 * Renders a card to connect to Google Account.
 *
 * Please note that this component is only used on the Reconnection page.
 * For the onboarding flow, the `GoogleComboAccountCard` component is used instead.
 *
 * @fires gla_google_account_connect_button_click with `{ action: 'authorization', context: 'reconnect' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'required-google-permissions', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/setup-and-configuration/#required-google-permissions' }`
 */
const ConnectGoogleAccountCard = () => {
	const pageName = 'reconnect';
	const [ handleConnect, { loading, data } ] =
		useGoogleConnectFlow( pageName );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
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
