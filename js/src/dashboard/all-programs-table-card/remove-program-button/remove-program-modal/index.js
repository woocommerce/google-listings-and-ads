/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

const RemoveProgramModal = ( props ) => {
	const { programId, onRequestClose } = props;

	const handleKeepCampaignClick = () => {
		onRequestClose();
	};

	// TODO: call backend API to remove campaign based on the programId.
	// might need to have a "busy / loading" indicator.
	// dismiss modal when remove process is done.
	const handleRemoveCampaignClick = () => {
		onRequestClose();
	};

	return (
		<Modal
			title={ __( 'Permanently Remove?', 'google-listings-and-ads' ) }
			className="gla-remove-program-modal"
			onRequestClose={ onRequestClose }
		>
			<div>
				<p>
					{ __(
						'Results typically improve with time with Google’s Smart Shopping campaigns. Removing a Smart Shopping campaign will result in the loss of any optimisations learned from those campaigns.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ __(
						'Once a campaign is removed, it cannot be re-enabled.',
						'google-listings-and-ads'
					) }
				</p>
			</div>
			<div className="gla-remove-program-modal__footer">
				<Button isSecondary onClick={ handleKeepCampaignClick }>
					{ __( 'Keep Campaign', 'google-listings-and-ads' ) }
				</Button>
				<Button
					isPrimary
					isDestructive
					onClick={ handleRemoveCampaignClick }
				>
					{ __( 'Remove Campaign', 'google-listings-and-ads' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default RemoveProgramModal;
