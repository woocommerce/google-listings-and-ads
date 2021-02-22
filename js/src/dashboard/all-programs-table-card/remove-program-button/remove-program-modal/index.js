/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '../../../../components/app-modal';
import './index.scss';

/**
 * Modal window to confirm removing a campaign.
 *
 * @param {Object} props
 * @param {string} props.programId Id of a program to be paused.
 * @param {Function} props.onRequestClose Callback to be performed once close action is requested.
 *
 */
const RemoveProgramModal = ( props ) => {
	const { programId, onRequestClose } = props;

	const handleKeepCampaignClick = () => {
		onRequestClose();
	};

	// TODO: call backend API to remove campaign based on the programId.
	// might need to have a "busy / loading" indicator.
	// dismiss modal when remove process is done.
	const handleRemoveCampaignClick = () => {
		// eslint-disable-next-line no-console
		console.warn(
			'The actual remove action is not implemented/integrated yet.',
			programId
		);

		onRequestClose();
	};

	return (
		<AppModal
			className="gla-remove-program-modal"
			title={ __( 'Permanently Remove?', 'google-listings-and-ads' ) }
			buttons={ [
				<Button
					key="keep"
					isSecondary
					onClick={ handleKeepCampaignClick }
				>
					{ __( 'Keep Campaign', 'google-listings-and-ads' ) }
				</Button>,
				<Button
					key="remove"
					isPrimary
					isDestructive
					onClick={ handleRemoveCampaignClick }
				>
					{ __( 'Remove Campaign', 'google-listings-and-ads' ) }
				</Button>,
			] }
			onRequestClose={ onRequestClose }
		>
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
		</AppModal>
	);
};

export default RemoveProgramModal;
