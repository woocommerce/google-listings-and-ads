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

const AddRateButton = () => {
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
				<AddRateModal onRequestClose={ handleModalRequestClose } />
			) }
		</>
	);
};

export default AddRateButton;
