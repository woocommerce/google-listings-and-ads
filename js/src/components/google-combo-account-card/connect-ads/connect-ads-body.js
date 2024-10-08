/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AdsAccountSelectControl from '.~/components/ads-account-select-control';
import AppButton from '.~/components/app-button';
import ContentButtonLayout from '.~/components/content-button-layout';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import LoadingLabel from '.~/components/loading-label/loading-label';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import { FILTER_ONBOARDING } from '.~/utils/tracks';

/**
 *
 * @param {Object} props Props.
 * @param {Array} props.accounts Accounts.
 * @param {Object} props.googleAdsAccount Google Ads account object.
 * @param {boolean} props.isConnected Whether the account is connected.
 * @param {Function} props.handleConnectClick Callback to handle the connect click.
 * @param {boolean} props.isLoading Whether the card is in a loading state.
 * @param {Function} props.setValue Callback to set the value.
 * @param {string} props.value Ads account ID.
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
	const initialValueRef = useRef( googleAdsAccount.id );
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	useEffect( () => {
		setValue( initialValueRef.current );
	}, [ initialValueRef, setValue ] );

	const ConnectCTA = () => {
		if ( isConnected && initialValueRef.current === Number( value ) ) {
			return <ConnectedIconLabel />;
		}

		return (
			<AppButton
				isSecondary
				disabled={ ! value }
				eventName="gla_ads_account_connect_button_click"
				eventProps={ getEventProps( {
					id: Number( value ),
				} ) }
				onClick={ handleConnectClick }
			>
				{ __( 'Connect', 'google-listings-and-ads' ) }
			</AppButton>
		);
	};

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
				<ConnectCTA />
			) }
		</ContentButtonLayout>
	);
};

export default ConnectAdsBody;
