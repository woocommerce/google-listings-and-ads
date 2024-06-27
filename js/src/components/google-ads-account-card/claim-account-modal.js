/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import AppModal from '.~/components/app-modal';
import ClaimAccountButton from './claim-account-button';

/**
 * Renders a modal for opening a pop-up window to claim the newly created Google Ads account.
 * The modal is displayed when the user has successfully created a Google Ads account and needs to claim it.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function called back when the modal is requested to be closed.
 */
const ClaimAccountModal = ( { onRequestClose } ) => {
	const { hasAccess } = useGoogleAdsAccountStatus();

	useEffect( () => {
		// Close the modal if access has been granted.
		if ( hasAccess ) {
			onRequestClose();
		}
	}, [ onRequestClose, hasAccess ] );

	return (
		<AppModal
			className="gla-ads-invite-modal"
			title={ __(
				'Claim your Google Ads account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<ClaimAccountButton
					key="1"
					isPrimary
					onClick={ onRequestClose }
				>
					{ __(
						'Claim account in Google Ads',
						'google-listings-and-ads'
					) }
				</ClaimAccountButton>,
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
