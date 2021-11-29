/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import WarningIcon from '.~/components/warning-icon';
import './index.scss';

const WarningModal = ( props ) => {
	const {
		existingAccount,
		onContinue = () => {},
		onRequestClose = () => {},
	} = props;

	const handleCreateAccountClick = () => {
		onContinue();
	};

	return (
		<AppModal
			className="gla-mc-warning-modal"
			title={ __(
				'Create Google Merchant Center Account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="confirm"
					isSecondary
					eventName="gla_mc_account_warning_modal_confirm_button_click"
					onClick={ handleCreateAccountClick }
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
			<p className="gla-mc-warning-modal__warning-text">
				<WarningIcon />
				<span>
					{ __(
						'Are you sure you want to create a new Google Merchant Center account?',
						'google-listings-and-ads'
					) }
				</span>
			</p>
			<p>
				{ createInterpolateElement(
					__(
						'You already have another verified account, <storename />, which is connected to this store’s URL, <storeurl />.',
						'google-listings-and-ads'
					),
					{
						storename: <strong>{ existingAccount.name }</strong>,
						storeurl: <strong>{ existingAccount.domain }</strong>,
					}
				) }
			</p>
			<p>
				{ __(
					'If you create a new Google Merchant Center account, you will have to reclaim this store’s URL with the new account. This will cause any existing product listings or ads to stop running, and the other verified account will lose its claim.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default WarningModal;
