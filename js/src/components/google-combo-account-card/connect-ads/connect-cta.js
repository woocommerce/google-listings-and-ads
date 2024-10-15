/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import { FILTER_ONBOARDING } from '.~/utils/tracks';

/**
 * Connect CTA component.
 *
 * @param {Object} props Props.
 * @param {boolean} props.isConnected Whether the account is connected.
 * @param {number} props.id Account ID.
 * @param {Function} props.handleConnectClick Callback to handle the connect click.
 * @param {string} props.value Connected account ID.
 *
 * @return {JSX.Element} Connect CTA component.
 */
const ConnectCTA = ( { isConnected, id, handleConnectClick, value } ) => {
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	if ( isConnected && id === Number( value ) ) {
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

export default ConnectCTA;
