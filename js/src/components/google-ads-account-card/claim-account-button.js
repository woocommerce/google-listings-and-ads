/**
 * External dependencies
 */
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
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
 * @fires gla_open_ads_account_claim_invitation_button_click When the user clicks on the button to claim the account with `{ context: 'setup-mc' | 'setup-ads' }`.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onClick] Function called back when the button is clicked.
 * @param {Object} props.restProps Props to be forwarded to AppButton.
 */
const ClaimAccountButton = ( { onClick = noop, ...restProps } ) => {
	const { inviteLink } = useGoogleAdsAccountStatus();
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	const handleClaimAccountClick = ( event ) => {
		const { defaultView } = event.target.ownerDocument;
		const features = getWindowFeatures( defaultView, 600, 800 );

		defaultView.open( inviteLink, '_blank', features );

		onClick( event );
	};

	return (
		<AppButton
			{ ...restProps }
			eventName="gla_open_ads_account_claim_invitation_button_click"
			eventProps={ getEventProps() }
			onClick={ handleClaimAccountClick }
		/>
	);
};

export default ClaimAccountButton;
