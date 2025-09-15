/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
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
	const { disconnectGoogleAdsAccount } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );

	const handleOnClick = () => {
		setDisconnecting( true );
		disconnectGoogleAdsAccount( true )
			.then( () => onContinue() )
			.catch( () => setDisconnecting( false ) );
	};

	return (
		<AppModal
			className="gla-ads-warning-modal"
			title={ __(
				'Create Google Ads Account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="confirm"
					isSecondary
					onClick={ handleOnClick }
					loading={ isDisconnecting }
				>
					{ __(
						'Yes, I want a new account',
						'google-listings-and-ads'
					) }
				</AppButton>,
				<AppButton key="cancel" isPrimary onClick={ onRequestClose }>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
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
