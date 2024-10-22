/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AdsAccountSelectControl from '.~/components/ads-account-select-control';
import ContentButtonLayout from '.~/components/content-button-layout';
import ConnectButton from '.~/components/google-ads-account-card/connect-ads/connect-button';
import LoadingLabel from '.~/components/loading-label/loading-label';
import ConnectedIconLabel from '.~/components/connected-icon-label';

/**
 * ConnectAdsBody component.
 *
 * @param {Object} props Props.
 * @param {boolean} props.isConnected Whether the account is connected.
 * @param {Function} props.handleConnectClick Callback to handle the connect click.
 * @param {boolean} props.isLoading Whether the card is in a loading state.
 * @param {Function} props.setValue Callback to set the value.
 * @param {string} props.value Ads account ID.
 * @return {JSX.Element} Body component.
 */
const ConnectAdsBody = ( {
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
				nonInteractive={ isConnected }
			/>
			{ isLoading ? (
				<LoadingLabel
					text={ __( 'Connecting…', 'google-listings-and-ads' ) }
				/>
			) : (
				<>
					{ isConnected ? (
						<ConnectedIconLabel />
					) : (
						<ConnectButton
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
