/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import getWindowFeatures from '.~/utils/getWindowFeatures';

const ClaimAccountModal = ( { onRequestClose = noop } ) => {
	const { inviteLink, hasAccess } = useGoogleAdsAccountStatus();

	useEffect( () => {
		// Close the modal if access has been granted.
		if ( hasAccess ) {
			onRequestClose();
		}
	}, [ onRequestClose, hasAccess ] );

	const handleAcceptInvitationClick = useCallback(
		( event ) => {
			const { defaultView } = event.target.ownerDocument;
			const features = getWindowFeatures( defaultView, 600, 800 );

			defaultView.open( inviteLink, '_blank', features );

			onRequestClose();
		},
		[ inviteLink, onRequestClose ]
	);

	return (
		<AppModal
			className="gla-ads-invite-modal"
			title={ __(
				'Claim your Google Ads account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="1"
					isPrimary
					onClick={ handleAcceptInvitationClick }
					// @todo: Review and add eventName prop
				>
					{ __(
						'Claim account in Google Ads',
						'google-listings-and-ads'
					) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p>
				{ __(
					'Claiming your account lets you access Google Ads and sets up conversion measurement. You must claim your account in the next 20 days.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'When you claim your account, you’ll be asked to set up billing. This step is optional and you only need to complete it if you want to create Google Ads campaigns. If you don’t want to set up billing, close the window after you’ve clicked ‘Continue’ on the next page.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default ClaimAccountModal;
