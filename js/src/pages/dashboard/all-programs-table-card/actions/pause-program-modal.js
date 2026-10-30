/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { useAppDispatch } from '~/data';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import { STATUS_PAUSED } from '../useProgramsDataViewsConfig';

/**
 * Content of the DataViews modal of the Pause program action, confirming before pausing a campaign.
 *
 * @param {Object} props React props.
 * @param {Array<Object>} props.items The programs the action is performed on. Only the first one is used.
 * @param {Function} props.closeModal Callback to close the modal.
 */
const PauseProgramModal = ( { items, closeModal } ) => {
	const [ program ] = items;
	const { updateAdsCampaign } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const { handleError } = useEuPoliticalDeclarationContext();

	const handlePauseCampaignClick = () => {
		closeModal();
		updateAdsCampaign( program.id, { status: STATUS_PAUSED } ).then(
			() =>
				createNotice(
					'success',
					__( 'Campaign paused.', 'google-listings-and-ads' ),
					{ type: 'snackbar' }
				),
			handleError
		);
	};

	return (
		<>
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
			<Flex justify="flex-end">
				<AppButton isSecondary onClick={ closeModal }>
					{ __( 'Keep Active', 'google-listings-and-ads' ) }
				</AppButton>
				<AppButton isPrimary onClick={ handlePauseCampaignClick }>
					{ __( 'Pause Campaign', 'google-listings-and-ads' ) }
				</AppButton>
			</Flex>
		</>
	);
};

export default PauseProgramModal;
