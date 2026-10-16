/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import { handleApiError } from '~/utils/handleError';
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
	const deletedRef = useRef( false );

	const marketName = countryNames[ market.country ];

	const handleConfirm = async () => {
		setDeleting( true );
		try {
			// The guard keeps a retry after a failed sync from re-sending
			// the DELETE request for a market that no longer exists. The
			// deletion changes the target audience server-side whether or
			// not the sync succeeds, so the invalidation belongs with it.
			if ( ! deletedRef.current ) {
				await deleteMarket( market.id );
				deletedRef.current = true;
				invalidateResolution( 'getTargetAudience', [] );
			}

			// Push the deletion to Merchant Center right away: the deleted
			// market's shipping service must be replaced or removed there,
			// and the background job alone can be delayed or gated off.
			try {
				await syncSettings();
			} catch ( error ) {
				handleApiError(
					error,
					__(
						'The market was deleted, but there was an error synchronizing the change to Google Merchant Center. Please try again.',
						'google-listings-and-ads'
					)
				);
				throw error;
			}

			onRequestClose();
		} catch ( error ) {
			// Every awaited action has already dispatched its own error
			// notice; this catch only keeps the modal open for retry/cancel.
		} finally {
			setDeleting( false );
		}
	};

	return (
		<AppModal
			title={ __( 'Delete market?', 'google-listings-and-ads' ) }
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
					{ __( 'Delete market', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			<p>
				{ sprintf(
					/* translators: %s: market/country name */
					__(
						"Deleting this market will stop its product feed from syncing with Google Merchant Center. To continue selling in %s, you'll need to add it to your Primary market or create a separate market for it.",
						'google-listings-and-ads'
					),
					marketName
				) }
			</p>
		</AppModal>
	);
};

export default DeleteMarketModal;
