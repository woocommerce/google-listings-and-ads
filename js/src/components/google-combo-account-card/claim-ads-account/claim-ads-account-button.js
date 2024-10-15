/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';

/**
 * Clicking on the button to open the invitation page for claiming the newly created Google Ads account.
 *
 * @event gla_open_ads_account_claim_invitation_button_click
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * Renders a button for opening a pop-up window to claim the newly created Google Ads account.
 *
 * @fires gla_open_ads_account_claim_invitation_button_click When the user clicks on the button to claim the account.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onClick] Function called back when the button is clicked.
 * @param {string} props.inviteLink The link to open in the pop-up window.
 * @param {boolean} [props.loading] Whether the button is in a loading state.
 * @param {Object} props.restProps Props to be forwarded to AppButton.
 */
const ClaimAdsAccountButton = ( {
	onClick = noop,
	inviteLink,
	loading,
	...restProps
} ) => {
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	const handleClaimAccountClick = ( event ) => {
		const { defaultView } = event.target.ownerDocument;
		const features = getWindowFeatures( defaultView, 600, 800 );
		defaultView.open( inviteLink, '_blank', features );
		onClick( event );
	};

	const text = ! loading
		? __( 'Claim your Google Ads account', 'google-listings-and-ads' )
		: __( 'Updating…', 'google-listings-and-ads' );

	return (
		<AppButton
			{ ...restProps }
			loading={ loading }
			isPrimary={ ! loading }
			text={ text }
			eventName="gla_open_ads_account_claim_invitation_button_click"
			eventProps={ getEventProps() }
			onClick={ handleClaimAccountClick }
		/>
	);
};

export default ClaimAdsAccountButton;
