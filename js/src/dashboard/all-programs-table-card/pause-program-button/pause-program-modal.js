/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '../../../components/app-modal';

const PauseProgramModal = ( props ) => {
	const { programId, onRequestClose } = props;

	const handleKeepActiveClick = () => {
		onRequestClose();
	};

	// TODO: call backend API to pause campaign based on the programId.
	// might need to have a "busy / loading" indicator.
	// dismiss modal when pause process is done.
	const handlePauseCampaignClick = () => {
		onRequestClose();
	};

	return (
		<AppModal
			title={ __( 'Before you pause…', 'google-listings-and-ads' ) }
			buttons={ [
				<Button key="1" isSecondary onClick={ handleKeepActiveClick }>
					{ __( 'Keep Active', 'google-listings-and-ads' ) }
				</Button>,
				<Button key="2" isPrimary onClick={ handlePauseCampaignClick }>
					{ __( 'Pause Campaign', 'google-listings-and-ads' ) }
				</Button>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p>
				{ __(
					'Results typically improve with time with Google’s Smart Shopping campaigns. If you pause, your products won’t be shown to people looking for what you offer.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'Pausing a Smart Shopping campaign will result in the loss of any optimisations learned from those campaigns.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default PauseProgramModal;
