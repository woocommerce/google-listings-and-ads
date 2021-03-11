/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';

/**
 * Internal dependencies
 */
import AddRateModal from './add-rate-modal';

/**
 * Triggering button and modal with the
 * form to add a new rate for selected country(-ies).
 *
 * @param {Object} props Props to be forwarded to `AddRateModal`.
 */
const AddRateButton = ( props ) => {
	const [ isOpen, setOpen ] = useState( false );

	const handleClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<Button
				isSecondary
				icon={ <GridiconPlusSmall /> }
				onClick={ handleClick }
			>
				{ __( 'Add another rate', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<AddRateModal
					onRequestClose={ handleModalRequestClose }
					{ ...props }
				/>
			) }
		</>
	);
};

export default AddRateButton;
