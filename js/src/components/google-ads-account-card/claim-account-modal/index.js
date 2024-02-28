/**
 * External dependencies
 */
import noop from 'lodash/noop';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import getWindowFeatures from '.~/utils/getWindowFeatures';
import './index.scss';

const ClaimAccountModal = ( { onRequestClose = noop } ) => {
	const { inviteLink, hasAccess } = useGoogleAdsAccountStatus();

	useEffect( () => {
		// Close the modal if access has been granted.
		if ( hasAccess ) {
			onRequestClose();
		}
	}, [ onRequestClose, hasAccess ] );

	const handleOnRequestClose = useCallback( () => {
		onRequestClose();
	}, [ onRequestClose ] );

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
			className="gla-ads-claim-account-modal"
			title={ __(
				'Accept invitation to claim your Google Ads account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="1"
					isPrimary
					eventName="gla_ads_accept_invitation_button_click" // @todo: review event name
					onClick={ handleAcceptInvitationClick }
				>
					{ __( 'Accept invitation', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ handleOnRequestClose }
		>
			<p>
				<strong>
					{ __(
						'Your new Google Ads Account has been created! Accept the invitation to access your account and continue onboarding',
						'google-listings-and-ads'
					) }
				</strong>
			</p>
			<p>
				{ __(
					'Claiming your account is easy! Simply click “Accept Invitation” below, then click “Continue” in the pop-up screen with the invitation. This will give you access to your new account, and will allow us to automatically set-up conversion tracking for you.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'This step is required to continue setting up Google Listings & Ads. If you do not claim access to your account in 20 days, your invite will expire and you will no longer be able to access your newly created account.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'Once you have accepted the claim, you will also be directed to set-up billing. While billing setup is required for creating campaigns, you do not need to complete this step at this time and may close the pop-up after clicking “Continue” to claim your account.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default ClaimAccountModal;
