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
	const { deleteMarket } = useAppDispatch();
	const countryNames = useCountryKeyNameMap();
	const [ deleting, setDeleting ] = useState( false );

	const marketName = countryNames[ market.country ];

	const handleConfirm = async () => {
		setDeleting( true );
		try {
			await deleteMarket( market.id );
			onRequestClose();
		} catch ( error ) {
			// `handleApiError` in the store action already dispatches an error
			// notice. We just need to re-enable the buttons so the user can
			// retry or cancel.
			setDeleting( false );
		}
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
					isDestructive
					onClick={ handleConfirm }
					disabled={ deleting }
					loading={ deleting }
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
