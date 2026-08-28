/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import './pause-program-modal.scss';

/**
 * Modal window to confirm pausing a campaign.
 *
 * @param {Object} props
 * @param {string} props.programId Id of a program to be paused.
 * @param {Function} props.onRequestClose Callback to be performed once close action is requested.
 */
const PauseProgramModal = ( props ) => {
	const { onPauseCampaign = () => {}, onRequestClose } = props;

	const handleKeepActiveClick = () => {
		onRequestClose();
	};

	const handlePauseCampaignClick = () => {
		onPauseCampaign();
	};

	return (
		<AppModal
			buttons={ [
				<AppButton
					key="1"
					onClick={ handleKeepActiveClick }
					isSecondary
				>
					{ __( 'Keep Active', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="2"
					onClick={ handlePauseCampaignClick }
					isPrimary
				>
					{ __( 'Pause Campaign', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			className="gla-pause-program-modal"
			onRequestClose={ onRequestClose }
			title={ __( 'Before you pause…', 'google-listings-and-ads' ) }
		>
			<p>
				{ __(
					'Results typically improve with time. If you pause, your products won’t be shown to people looking for what you offer.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'Pausing a campaign will result in the loss of any optimisations learned from those campaigns.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default PauseProgramModal;
