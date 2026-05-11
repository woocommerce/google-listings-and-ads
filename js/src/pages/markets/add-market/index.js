/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AddMarketModal from './add-market-modal';

/**
 * Owns the open / close state for `AddMarketModal`. The modal itself is a
 * placeholder today; the follow-up task will replace its body with a real
 * country / shipping form and a save handler.
 */
const AddMarket = () => {
	const [ isOpen, setIsOpen ] = useState( false );

	const handleOpen = useCallback( () => setIsOpen( true ), [] );
	const handleClose = useCallback( () => setIsOpen( false ), [] );

	return (
		<>
			<AppButton variant="primary" onClick={ handleOpen }>
				{ __( 'Add market', 'google-listings-and-ads' ) }
			</AppButton>
			{ isOpen && <AddMarketModal onRequestClose={ handleClose } /> }
		</>
	);
};

export default AddMarket;
