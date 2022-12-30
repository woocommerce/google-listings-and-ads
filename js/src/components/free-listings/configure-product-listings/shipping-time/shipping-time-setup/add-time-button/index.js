/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import GridiconPlusSmall from 'gridicons/dist/plus-small';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AddTimeModal from './add-time-modal';

/**
 * Triggering button and modal with the
 * form to add a new time for selected country(-ies).
 *
 * @param {Object} props Props to be forwarded to `AddTimeModal`.
 */
const AddTimeButton = ( props ) => {
	const [ isOpen, setOpen ] = useState( false );

	const handleClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<AppButton
				isSecondary
				icon={ <GridiconPlusSmall /> }
				onClick={ handleClick }
			>
				{ __( 'Add another time', 'google-listings-and-ads' ) }
			</AppButton>
			{ isOpen && (
				<AddTimeModal
					onRequestClose={ handleModalRequestClose }
					{ ...props }
				/>
			) }
		</>
	);
};

export default AddTimeButton;
