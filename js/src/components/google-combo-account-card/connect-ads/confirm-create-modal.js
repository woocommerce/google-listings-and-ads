/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import WarningIcon from '~/components/warning-icon';
import './confirm-create-modal.scss';

/**
 * Google Ads account creation confirmation modal.
 * This modal is shown when the user tries to create a new Google Ads account.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onContinue Callback to continue with account creation.
 * @param {Function} props.onRequestClose Callback to close the modal.
 * @return {JSX.Element} Confirmation modal.
 */
const ConfirmCreateModal = ( { onContinue, onRequestClose } ) => {
	return (
		<AppModal
			buttons={ [
				<AppButton key="confirm" onClick={ onContinue } isSecondary>
					{ __(
						'Yes, I want a new account',
						'google-listings-and-ads'
					) }
				</AppButton>,
				<AppButton key="cancel" onClick={ onRequestClose } isPrimary>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			className="gla-ads-warning-modal"
			onRequestClose={ onRequestClose }
			title={ __(
				'Create Google Ads Account',
				'google-listings-and-ads'
			) }
		>
			<p className="gla-ads-warning-modal__warning-text">
				<WarningIcon />
				<span>
					{ __(
						'Are you sure you want to create a new Google Ads account?',
						'google-listings-and-ads'
					) }
				</span>
			</p>
			<p>
				{ __(
					'You already have another Ads account associated with this Google account.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'If you create a new Google Ads account, you will need to accept an invite to the account before it can be used.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default ConfirmCreateModal;
