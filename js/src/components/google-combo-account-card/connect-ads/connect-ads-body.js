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
import ConnectedIconLabel from '.~/components/connected-icon-label';
import { NON_INTERACTABLE_PROPS } from '.~/constants';

/**
 * ConnectAdsBody component.
 *
 * @param {Object} props Props.
 * @param {Object} props.googleAdsAccount Google Ads account object.
 * @param {boolean} props.isConnected Whether the account is connected.
 * @param {Function} props.handleConnectClick Callback to handle the connect click.
 * @param {boolean} props.isLoading Whether the card is in a loading state.
 * @param {Function} props.setValue Callback to set the value.
 * @param {string} props.value Ads account ID.
 * @return {JSX.Element} Body component.
 */
const ConnectAdsBody = ( {
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
				value={ value }
				onChange={ setValue }
				autoSelectFirstOption={ true }
				{ ...( isConnected && NON_INTERACTABLE_PROPS ) }
			/>
			{ isLoading ? (
				<LoadingLabel
					text={ __( 'Connecting…', 'google-listings-and-ads' ) }
				/>
			) : (
				<>
					{ isConnected &&
						googleAdsAccount.id === Number( value ) && (
							<ConnectedIconLabel />
						) }
					{ ! isConnected && (
						<ConnectCTA
							isConnected={ isConnected }
							id={ googleAdsAccount.id }
							handleConnectClick={ handleConnectClick }
							value={ value }
						/>
					) }
				</>
			) }
		</ContentButtonLayout>
	);
};

export default ConnectAdsBody;
