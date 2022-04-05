/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppButton from '.~/components/app-button';
import { useAppDispatch } from '.~/data';
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
	const [ isDeleting, setDeleting ] = useState( false );
	const dispatcher = useAppDispatch();

	const handleRequestClose = () => {
		if ( isDeleting ) {
			return;
		}
		onRequestClose();
	};

	const handleRemoveCampaignClick = () => {
		setDeleting( true );
		dispatcher
			.deleteAdsCampaign( programId )
			.then( () => onRequestClose() )
			.catch( () => setDeleting( false ) );
	};

	return (
		<AppModal
			className="gla-remove-program-modal"
			title={ __( 'Permanently Remove?', 'google-listings-and-ads' ) }
			isDismissible={ ! isDeleting }
			buttons={ [
				<Button
					key="keep"
					isSecondary
					disabled={ isDeleting }
					onClick={ handleRequestClose }
				>
					{ __( 'Keep Campaign', 'google-listings-and-ads' ) }
				</Button>,
				<AppButton
					key="remove"
					isPrimary
					isDestructive
					loading={ isDeleting }
					onClick={ handleRemoveCampaignClick }
				>
					{ __( 'Remove Campaign', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ handleRequestClose }
		>
			<p>
				{ __(
					'Results typically improve with time with Google’s paid ad campaigns. Removing a paid ad campaign will result in the loss of any optimisations learned from those campaigns.',
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
