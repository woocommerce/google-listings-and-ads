/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AdsAccountSelectControl from '.~/components/ads-account-select-control';
import ContentButtonLayout from '.~/components/content-button-layout';
import ConnectCTA from './connect-cta';
import LoadingLabel from '.~/components/loading-label/loading-label';

/**
 * Clicking on the button to connect an existing Google Ads account.
 *
 * @event gla_ads_account_connect_button_click
 * @property {number} id The account ID to be connected.
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * ConnectAdsBody component.
 *
 * @param {Object} props Props.
 * @param {Array} props.accounts Accounts.
 * @param {Object} props.googleAdsAccount Google Ads account object.
 * @param {boolean} props.isConnected Whether the account is connected.
 * @param {Function} props.handleConnectClick Callback to handle the connect click.
 * @param {boolean} props.isLoading Whether the card is in a loading state.
 * @param {Function} props.setValue Callback to set the value.
 * @param {string} props.value Ads account ID.
 * @fires gla_ads_account_connect_button_click when "Connect" button is clicked.
 * @return {JSX.Element} Body component.
 */
const ConnectAdsBody = ( {
	accounts,
	googleAdsAccount,
	isConnected,
	handleConnectClick,
	isLoading,
	setValue,
	value,
} ) => {
	return (
		<ContentButtonLayout>
			<AdsAccountSelectControl
				accounts={ accounts }
				value={ value }
				onChange={ setValue }
			/>
			{ isLoading ? (
				<LoadingLabel
					text={ __( 'Connecting…', 'google-listings-and-ads' ) }
				/>
			) : (
				<ConnectCTA
					isConnected={ isConnected }
					id={ googleAdsAccount.id }
					handleConnectClick={ handleConnectClick }
					value={ value }
				/>
			) }
		</ContentButtonLayout>
	);
};

export default ConnectAdsBody;
