/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { Icon, pencil, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import { CAMPAIGN_TYPE_PMAX } from '~/constants';
import EditProgramModal from './edit-program-modal';
import PauseProgramModal from './pause-program-modal';
import RemoveProgramModal from './remove-program-modal';

const isCampaign = ( item ) => ! item.isFreeListings;

/**
 * Provides the DataViews actions of the dashboard programs table.
 * The free listings program has no actions.
 *
 * @return {Array<Object>} DataViews actions.
 */
const useProgramActions = () => {
	const { updateAdsCampaign } = useAppDispatch();
	const { handleError } = useEuPoliticalDeclarationContext();

	return useMemo(
		() => [
			{
				id: 'edit',
				label: __( 'Edit', 'google-listings-and-ads' ),
				icon: <Icon icon={ pencil } width={ 24 } height={ 24 } />,
				isPrimary: true,
				isEligible: ( item ) =>
					isCampaign( item ) && item.type === CAMPAIGN_TYPE_PMAX,
				modalHeader: __(
					'Before you edit…',
					'google-listings-and-ads'
				),
				modalSize: 'medium',
				RenderModal: EditProgramModal,
			},
			{
				id: 'pause',
				label: __( 'Pause', 'google-listings-and-ads' ),
				isEligible: ( item ) =>
					isCampaign( item ) && item.status === 'enabled',
				modalHeader: __(
					'Before you pause…',
					'google-listings-and-ads'
				),
				modalSize: 'medium',
				RenderModal: PauseProgramModal,
			},
			{
				id: 'resume',
				label: __( 'Resume', 'google-listings-and-ads' ),
				isEligible: ( item ) =>
					isCampaign( item ) && item.status === 'paused',
				callback: ( [ item ] ) =>
					updateAdsCampaign( item.id, { status: 'enabled' } ).catch(
						handleError
					),
			},
			{
				id: 'remove',
				label: __( 'Remove', 'google-listings-and-ads' ),
				icon: <Icon icon={ trash } width={ 24 } height={ 24 } />,
				isDestructive: true,
				isEligible: isCampaign,
				modalHeader: __(
					'Permanently Remove?',
					'google-listings-and-ads'
				),
				modalSize: 'medium',
				RenderModal: RemoveProgramModal,
			},
		],
		[ updateAdsCampaign, handleError ]
	);
};

export default useProgramActions;
