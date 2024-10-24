/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import { FILTER_ONBOARDING } from '.~/utils/tracks';

/**
 * Clicking on the button to connect an existing Google Ads account.
 *
 * @event gla_ads_account_connect_button_click
 * @property {number} id The account ID to be connected.
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * Connect CTA component.
 *
 * @param {Object} props Props.
 * @param {Function} props.handleConnectClick Callback to handle the connect click.
 * @param {string} props.value Connected account ID.
 * @fires gla_ads_account_connect_button_click when "Connect" button is clicked.
 * @return {JSX.Element} Connect CTA component.
 */
const ConnectButton = ( { handleConnectClick, value } ) => {
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

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

export default ConnectButton;
