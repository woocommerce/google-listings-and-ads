/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { useAppDispatch } from '~/data';

/**
 * Content of the DataViews modal of the Remove program action, confirming before removing a campaign.
 *
 * @param {Object} props React props.
 * @param {Array<Object>} props.items The programs the action is performed on. Only the first one is used.
 * @param {Function} props.closeModal Callback to close the modal.
 */
const RemoveProgramModal = ( { items, closeModal } ) => {
	const [ program ] = items;
	const [ isDeleting, setDeleting ] = useState( false );
	const { deleteAdsCampaign } = useAppDispatch();

	const handleRemoveCampaignClick = () => {
		setDeleting( true );
		deleteAdsCampaign( program.id )
			.then( () => closeModal() )
			.catch( () => setDeleting( false ) );
	};

	return (
		<>
			<p>
				{ __(
					'Results typically improve with time. Removing a campaign will result in the loss of any optimisations learned from those campaigns.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'Once a campaign is removed, it cannot be re-enabled.',
					'google-listings-and-ads'
				) }
			</p>
			<Flex justify="flex-end">
				<AppButton
					isSecondary
					disabled={ isDeleting }
					onClick={ closeModal }
				>
					{ __( 'Keep Campaign', 'google-listings-and-ads' ) }
				</AppButton>
				<AppButton
					isPrimary
					isDestructive
					loading={ isDeleting }
					onClick={ handleRemoveCampaignClick }
				>
					{ __( 'Remove Campaign', 'google-listings-and-ads' ) }
				</AppButton>
			</Flex>
		</>
	);
};

export default RemoveProgramModal;
