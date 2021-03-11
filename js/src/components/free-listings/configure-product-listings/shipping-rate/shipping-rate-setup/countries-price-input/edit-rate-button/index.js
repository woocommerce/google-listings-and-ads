/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import EditRateModal from './edit-rate-modal';
import './index.scss';

/**
 * Triggering button and modal with the
 * form to edit rate for selected country(-ies).
 *
 * @param {Object} props
 * @param {Object} props.rate
 * @param {Function} props.onChange Called with list of countries once Delete was requested.
 * @param {Function} props.onDelete Called with updated `{ countryCodes, currency, price }` value.
 */
const EditRateButton = ( { rate, onChange, onDelete } ) => {
	const [ isOpen, setOpen ] = useState( false );

	const handleClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	const handleChange = ( ...args ) => {
		onChange( ...args );
		setOpen( false );
	};
	const handleDelete = ( value ) => {
		onDelete( value );
		setOpen( false );
	};

	return (
		<>
			<Button
				className="gla-edit-rate-button"
				isTertiary
				onClick={ handleClick }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<EditRateModal
					rate={ rate }
					onSubmit={ handleChange }
					onDelete={ handleDelete }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default EditRateButton;
