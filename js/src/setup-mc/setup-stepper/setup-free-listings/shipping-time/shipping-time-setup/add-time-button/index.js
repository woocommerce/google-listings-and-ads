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
import AddTimeModal from './add-time-modal';

const AddTimeButton = () => {
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
				{ __( 'Add another time', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<AddTimeModal onRequestClose={ handleModalRequestClose } />
			) }
		</>
	);
};

export default AddTimeButton;
