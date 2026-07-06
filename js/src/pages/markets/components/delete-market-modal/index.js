/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';

/**
 * Confirmation modal for deleting a non-primary market.
 *
 * @param {Object} props
 * @param {{ id: string, country?: string }} props.market The non-primary market being deleted.
 * @param {() => void} props.onRequestClose Called when the user cancels or after a successful delete.
 */
const DeleteMarketModal = ( { market, onRequestClose } ) => {
	const { deleteMarket, invalidateResolution, syncSettings } =
		useAppDispatch();
	const countryNames = useCountryKeyNameMap();
	const [ deleting, setDeleting ] = useState( false );

	const marketName = countryNames[ market.country ];

	const handleConfirm = async () => {
		setDeleting( true );
		try {
			await deleteMarket( market.id );
		} catch ( error ) {
			// `handleApiError` in the store action already dispatches an error
			// notice. We just need to re-enable the buttons so the user can
			// retry or cancel.
			setDeleting( false );
			return;
		}

		try {
			// Push the deletion to Merchant Center right away: the deleted
			// market's shipping service must be replaced or removed there,
			// and the background job alone can be delayed or gated off.
			await syncSettings();
		} catch ( error ) {
			// The market itself was deleted, so close as normal; the sync
			// failure has already surfaced as an error notice from the store
			// action.
		}

		invalidateResolution( 'getTargetAudience', [] );
		onRequestClose();
	};

	return (
		<AppModal
			title={ __( 'Delete market', 'google-listings-and-ads' ) }
			onRequestClose={ deleting ? () => {} : onRequestClose }
			buttons={ [
				<AppButton
					key="cancel"
					variant="tertiary"
					onClick={ onRequestClose }
					disabled={ deleting }
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="delete"
					variant="primary"
					onClick={ handleConfirm }
					disabled={ deleting }
					loading={ deleting }
					isDestructive
				>
					{ __( 'Delete', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			<p>
				{ sprintf(
					/* translators: %s: market name */
					__(
						'Are you sure you want to delete %s? This action cannot be undone.',
						'google-listings-and-ads'
					),
					marketName
				) }
			</p>
		</AppModal>
	);
};

export default DeleteMarketModal;
