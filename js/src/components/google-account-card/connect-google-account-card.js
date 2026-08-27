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
import { glaData } from '~/constants';

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
	const { serviceBasedMerchant } = glaData;

	const description = serviceBasedMerchant
		? __( 'Required to sync with Google Ads.', 'google-listings-and-ads' )
		: __(
				'Required to sync with Google Merchant Center and Google Ads.',
				'google-listings-and-ads'
		  );

	return (
		<AccountCard
			alignIcon="top"
			alignIndicator="top"
			appearance={ APPEARANCE.GOOGLE }
			description={
				<>
					{ description }
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
			indicator={
				<AppButton
					eventName="gla_google_account_connect_button_click"
					eventProps={ {
						context: pageName,
						action: 'authorization',
					} }
					loading={ loading || data }
					onClick={ handleConnect }
					text={ __( 'Connect', 'google-listings-and-ads' ) }
					isSecondary
				/>
			}
		/>
	);
};

export default ConnectGoogleAccountCard;
