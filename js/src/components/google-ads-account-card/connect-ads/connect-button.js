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
 * Google Ads account connection button.
 *
 * @param {Object} props Props.
 * @param {number} props.accountID The Google Ads account ID to be connected.
 * @param {Object} props.restProps Rest props. Forwarded to AppButton.
 * @fires gla_ads_account_connect_button_click when "Connect" button is clicked.
 * @return {JSX.Element} Google Ads connect button component.
 */
const ConnectButton = ( { accountID, ...restProps } ) => {
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	return (
		<AppButton
			isSecondary
			disabled={ ! accountID }
			eventName="gla_ads_account_connect_button_click"
			eventProps={ getEventProps( {
				id: Number( accountID ),
			} ) }
			{ ...restProps }
		>
			{ __( 'Connect', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default ConnectButton;
