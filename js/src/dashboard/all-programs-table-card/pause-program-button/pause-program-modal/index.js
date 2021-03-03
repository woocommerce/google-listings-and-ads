/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import './index.scss';

/**
 * Modal window to confirm pausing a campaign.
 *
 * @param {Object} props
 * @param {string} props.programId Id of a program to be paused.
 * @param {Function} props.onRequestClose Callback to be performed once close action is requested.
 *
 */
const PauseProgramModal = ( props ) => {
	const { programId, onRequestClose } = props;

	const handleKeepActiveClick = () => {
		onRequestClose();
	};

	// TODO: call backend API to pause campaign based on the programId.
	// might need to have a "busy / loading" indicator.
	// dismiss modal when pause process is done.
	const handlePauseCampaignClick = () => {
		// eslint-disable-next-line no-console
		console.warn(
			'The actual pause action is not implemented/integrated yet.',
			programId
		);

		onRequestClose();
	};

	return (
		<AppModal
			className="gla-pause-program-modal"
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
