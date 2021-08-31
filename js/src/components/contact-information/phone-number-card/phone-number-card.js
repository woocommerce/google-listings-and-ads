/**
 * External dependencies
 */
import {
	getCountryCallingCode,
	parsePhoneNumberFromString as parsePhoneNumber,
} from 'libphonenumber-js';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock, CardDivider } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useCountryCallingCodeOptions from '.~/hooks/useCountryCallingCodeOptions';
import Section from '.~/wcdl/section';
import SelectControl from '.~/wcdl/select-control';
import AppInputControl from '.~/components/app-input-control';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import AppSpinner from '.~/components/app-spinner';
import './phone-number-card.scss';

const noop = () => {};

const basePhoneNumberCardProps = {
	className: 'gla-phone-number-card',
	appearance: APPEARANCE.PHONE,
};

/**
 * @typedef { import(".~/hooks/useGoogleMCPhoneNumber").PhoneNumber } PhoneNumber
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

function PhoneNumberContent( {
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

function EditPhoneNumberCard( { phoneNumber, onPhoneNumberChange } ) {
	const { loaded, data } = phoneNumber;
	const phoneNumberContent = loaded ? (
		<PhoneNumberContent
			initCountry={ data.country }
			initNationalNumber={ data.nationalNumber }
			onPhoneNumberChange={ onPhoneNumberChange }
		/>
	) : (
		<AppSpinner />
	);

	return (
		<AccountCard
			{ ...basePhoneNumberCardProps }
			description={ __(
				'Please enter a phone number to be used for verification.',
				'google-listings-and-ads'
			) }
		>
			<CardDivider />
			{ phoneNumberContent }
		</AccountCard>
	);
}

/**
 * Renders phone number data in Card UI and is able to edit.
 *
 * @param {Object} props React props.
 * @param {string} props.view The view the card is in.
 * @param {PhoneNumber} props.phoneNumber Phone number data.
 * @param {boolean} [props.isPreview=false] Whether to display as preview UI.
 * @param {boolean|null} [props.initEditing=null] Specify the inital UI state. This prop would be ignored if `isPreview` is true.
 *     `true`: initialize with the editing UI.
 *     `false`: initialize with the viewing UI.
 *     `null`: determine the initial UI state according to the `data.isValid` after the `phoneNumber` loaded.
 * @param {Function} [props.onEditClick] Called when clicking on "Edit" button.
 *     If this callback is omitted, it will enter edit mode when clicking on "Edit" button.
 * @param {onPhoneNumberChange} [props.onPhoneNumberChange] Called when inputs of phone number are changed in edit mode.
 */
export default function PhoneNumberCard( {
	view,
	phoneNumber,
	isPreview = false,
	initEditing = null,
	onEditClick,
	onPhoneNumberChange = noop,
} ) {
	const { loaded, data } = phoneNumber;
	const [ isEditing, setEditing ] = useState(
		isPreview ? false : initEditing
	);

	// Handle the initial UI state of `initEditing = null`.
	// The `isEditing` state is on hold. Determine it after the `phoneNumber` loaded.
	useEffect( () => {
		if ( loaded && isEditing === null ) {
			setEditing( ! data.isValid );
		}
	}, [ loaded, data.isValid, isEditing ] );

	// Return a simple loading AccountCard since the initial edit state is unknown before loaded.
	if ( isEditing === null ) {
		return (
			<AccountCard
				{ ...basePhoneNumberCardProps }
				indicator={ <Spinner /> }
			/>
		);
	}

	if ( isEditing ) {
		return (
			<EditPhoneNumberCard
				phoneNumber={ phoneNumber }
				onPhoneNumberChange={ onPhoneNumberChange }
			/>
		);
	}

	let description = null;
	let indicator = <Spinner />;

	if ( loaded ) {
		description = data.display;
		indicator = (
			<AppButton
				isSecondary
				eventName="gla_mc_phone_number_edit_button_click"
				eventProps={ {
					view,
				} }
				onClick={ () => {
					if ( onEditClick ) {
						onEditClick();
					} else {
						setEditing( true );
					}
				} }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</AppButton>
		);
	}

	return (
		<AccountCard
			{ ...basePhoneNumberCardProps }
			description={ description }
			hideIcon={ isPreview }
			indicator={ indicator }
		/>
	);
}
