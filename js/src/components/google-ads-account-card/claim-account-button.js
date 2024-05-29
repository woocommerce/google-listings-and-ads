/**
 * External dependencies
 */
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';

/**
 * Renders a button for opening a pop-up window to claim the newly created Google Ads account.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onClick] Function called back when the button is clicked.
 * @param {Object} props.restProps Props to be forwarded to AppButton.
 */
const ClaimAccountButton = ( { onClick = noop, ...restProps } ) => {
	const { inviteLink } = useGoogleAdsAccountStatus();

	const handleClaimAccountClick = ( event ) => {
		const { defaultView } = event.target.ownerDocument;
		const features = getWindowFeatures( defaultView, 600, 800 );

		defaultView.open( inviteLink, '_blank', features );

		onClick( event );
	};

	return <AppButton { ...restProps } onClick={ handleClaimAccountClick } />;
};

export default ClaimAccountButton;
