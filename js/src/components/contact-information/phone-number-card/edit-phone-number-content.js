/**
 * External dependencies
 */
import {
	getCountryCallingCode,
	parsePhoneNumberFromString as parsePhoneNumber,
} from 'libphonenumber-js';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCountryCallingCodeOptions from '.~/hooks/useCountryCallingCodeOptions';
import Section from '.~/wcdl/section';
import SelectControl from '.~/wcdl/select-control';
import AppInputControl from '.~/components/app-input-control';

/**
 * @typedef { import(".~/hooks/useGoogleMCPhoneNumber").PhoneNumberData } PhoneNumberData
 */

/**
 * @typedef {Object} ExtraPhoneNumberData
 * @property {boolean} isDirty Whether the phone number data contain unsaved changes.
 *
 * @typedef {PhoneNumberData & ExtraPhoneNumberData} CallbackPhoneNumberData
 */

/**
 * @callback onPhoneNumberChange
 * @param {CallbackPhoneNumberData} phoneNumberData The changed phone number data.
 */

/**
 * Renders inputs for editing phone number in Card.Body UI.
 *
 * @param {Object} props React props.
 * @param {string} props.initCountry The initial country code for the country selection. Example: 'US'.
 * @param {string} props.initNationalNumber The initial national (significant) number for its input field. Example: '2133734253'.
 * @param {onPhoneNumberChange} props.onPhoneNumberChange Called when inputs of phone number are changed.
 */
export default function EditPhoneNumberContent( {
	initCountry,
	initNationalNumber,
	onPhoneNumberChange,
} ) {
	const countryCallingCodeOptions = useCountryCallingCodeOptions();
	const [ country, setCountry ] = useState( initCountry );
	const [ number, setNumber ] = useState( initNationalNumber );

	const handleChange = ( nextCountry, nextNumber ) => {
		setCountry( nextCountry );
		setNumber( nextNumber );

		const parsed = parsePhoneNumber( nextNumber, nextCountry );
		const isValid = parsed ? parsed.isValid() : false;

		if ( parsed ) {
			const isDirty =
				nextCountry !== initCountry ||
				parsed.nationalNumber !== initNationalNumber;

			onPhoneNumberChange( {
				...parsed,
				isValid,
				isDirty,
			} );
		} else {
			const isDirty =
				nextCountry !== initCountry ||
				nextNumber !== initNationalNumber;

			const countryCallingCode = nextCountry
				? getCountryCallingCode( nextCountry )
				: '';

			onPhoneNumberChange( {
				isValid,
				isDirty,
				countryCallingCode,
				nationalNumber: nextNumber,
				country: nextCountry,
			} );
		}
	};

	const handleCountryChange = ( nextCountry ) =>
		handleChange( nextCountry, number );

	const handleNumberChange = ( nextNumber ) =>
		handleChange( country, nextNumber );

	return (
		<Section.Card.Body>
			<Flex gap={ 4 }>
				<FlexItem>
					<SelectControl
						label={ __(
							'Country code',
							'google-listings-and-ads'
						) }
						isSearchable
						excludeSelectedOptions={ false }
						options={ countryCallingCodeOptions }
						selected={ country }
						onChange={ handleCountryChange }
					/>
				</FlexItem>
				<FlexBlock>
					<AppInputControl
						label={ __(
							'Phone number',
							'google-listings-and-ads'
						) }
						value={ number }
						onChange={ handleNumberChange }
					/>
				</FlexBlock>
			</Flex>
		</Section.Card.Body>
	);
}
