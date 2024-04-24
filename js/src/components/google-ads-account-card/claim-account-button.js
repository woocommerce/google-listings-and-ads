/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import LoadingLabel from '.~/components/loading-label';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';

const ClaimAccountButton = ( { loading = false, onClaimClick = noop } ) => {
	const { inviteLink } = useGoogleAdsAccountStatus();

	const handleClaimAccountClick = useCallback(
		( event ) => {
			onClaimClick();

			const { defaultView } = event.target.ownerDocument;
			const features = getWindowFeatures( defaultView, 600, 800 );

			defaultView.open( inviteLink, '_blank', features );
		},
		[ inviteLink, onClaimClick ]
	);

	return (
		<>
			{ loading ? (
				<LoadingLabel
					text={ __( 'Waiting…', 'google-listings-and-ads' ) }
				/>
			) : (
				<AppButton isSecondary onClick={ handleClaimAccountClick }>
					{ __( 'Claim Account', 'google-listings-and-ads' ) }
				</AppButton>
			) }
		</>
	);
};

export default ClaimAccountButton;
