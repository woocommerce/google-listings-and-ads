/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

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
		<Modal
			title={ __( 'Before you pause…', 'google-listings-and-ads' ) }
			className="gla-pause-program-modal"
			onRequestClose={ onRequestClose }
		>
			<div>
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
			</div>
			<div className="gla-pause-program-modal__footer">
				<Button isSecondary onClick={ handleKeepActiveClick }>
					{ __( 'Keep Active', 'google-listings-and-ads' ) }
				</Button>
				<Button isPrimary onClick={ handlePauseCampaignClick }>
					{ __( 'Pause Campaign', 'google-listings-and-ads' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default PauseProgramModal;
