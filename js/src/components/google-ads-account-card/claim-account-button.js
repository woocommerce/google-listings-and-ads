/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';

const ClaimAccountButton = () => {
	const { inviteLink } = useGoogleAdsAccountStatus();

	const handleClaimAccountClick = useCallback(
		( event ) => {
			const { defaultView } = event.target.ownerDocument;
			const features = getWindowFeatures( defaultView, 600, 800 );

			defaultView.open( inviteLink, '_blank', features );
		},
		[ inviteLink ]
	);

	return (
		<AppButton isSecondary onClick={ handleClaimAccountClick }>
			{ __( 'Claim Account', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default ClaimAccountButton;
