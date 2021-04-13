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
 * @param {Array<CountryCode>} props.audienceCountries List of available audience countries.
 * @param {AggregatedShippingRate} props.rate
 * @param {(newRate: AggregatedShippingRate, deletedCountries: Array<CountryCode>) => void} props.onChange Called once the rate is submitted.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 */
const EditRateButton = ( { audienceCountries, rate, onChange, onDelete } ) => {
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
					audienceCountries={ audienceCountries }
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

/**
 * @typedef {import("../../countries-form.js").AggregatedShippingRate} AggregatedShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
