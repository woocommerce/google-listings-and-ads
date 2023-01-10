/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import EditTimeModal from './edit-time-modal';
import './index.scss';

/**
 * Triggering button and modal with the
 * form to edit time for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.audienceCountries List of all audience countries.
 * @param {AggregatedShippingTime} props.time
 * @param {(newTime: AggregatedShippingTime, deletedCountries: Array<CountryCode>) => void} props.onChange Called once the time is submitted.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const EditTimeButton = ( { audienceCountries, time, onChange, onDelete } ) => {
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
			<AppButton
				className="gla-edit-time-button"
				isTertiary
				onClick={ handleClick }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</AppButton>
			{ isOpen && (
				<EditTimeModal
					audienceCountries={ audienceCountries }
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
