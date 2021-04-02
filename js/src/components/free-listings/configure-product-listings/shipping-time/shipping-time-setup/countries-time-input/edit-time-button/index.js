/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import EditTimeModal from './edit-time-modal';
import './index.scss';

/**
 * Triggering button and modal with the
 * form to edit time for selected country(-ies).
 *
 * @param {Object} props
 * @param {AggregatedShippingTime} props.time
 * @param {(newTime: AggregatedShippingTime, deletedCountries: Array<CountryCode>) => void} props.onChange Called once the time is submitted.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const EditTimeButton = ( { time, onChange, onDelete } ) => {
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
				className="gla-edit-time-button"
				isTertiary
				onClick={ handleClick }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<EditTimeModal
					time={ time }
					onSubmit={ handleChange }
					onDelete={ handleDelete }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default EditTimeButton;

/**
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
