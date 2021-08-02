/**
 * External dependencies
 */
import {
	getCountryCallingCode,
	parsePhoneNumberFromString as parsePhoneNumber,
} from 'libphonenumber-js';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock, CardDivider } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useCountryCallingCodes from '.~/hooks/useCountryCallingCodes';
import useGoogleMCPhoneNumber from '.~/hooks/useGoogleMCPhoneNumber';
import Section from '.~/wcdl/section';
import SelectControl from '.~/wcdl/select-control';
import AppInputControl from '.~/components/app-input-control';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import AppSpinner from '.~/components/app-spinner';
import './phone-number-card.scss';

const noop = () => {};

function PhoneNumberContent( {
	initCountry,
	initNationalNumber,
	onPhoneNumberChange,
} ) {
	const countryCallingCodes = useCountryCallingCodes( 'select-options' );
	const [ country, setCountry ] = useState( initCountry );
	const [ number, setNumber ] = useState( initNationalNumber );

	const handleChange = ( nextCountry, nextNumber ) => {
		if ( nextCountry !== country ) {
			setCountry( nextCountry );
		}
		if ( nextNumber !== number ) {
			setNumber( nextNumber );
		}

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
			} );
		}
	};

	const handleCountryChange = ( v ) => handleChange( v, number );
	const handleNumberChange = ( v ) => handleChange( country, v );

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
						options={ countryCallingCodes }
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

export default function PhoneNumberCard( {
	isPreview,
	initEditing,
	onEditClick,
	onPhoneNumberChange = noop,
} ) {
	const [ isEditing, setEditing ] = useState( initEditing );
	const { loaded, data } = useGoogleMCPhoneNumber();

	const editButton = isEditing ? null : (
		<AppButton
			isSecondary
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

	let description = null;
	let phoneNumberContent = null;

	if ( isEditing ) {
		description = __(
			'Please enter a phone number to be used for verification.',
			'google-listings-and-ads'
		);

		if ( loaded ) {
			phoneNumberContent = (
				<>
					<CardDivider />
					<PhoneNumberContent
						initCountry={ data.country }
						initNationalNumber={ data.nationalNumber }
						onPhoneNumberChange={ onPhoneNumberChange }
					/>
				</>
			);
		} else {
			phoneNumberContent = <AppSpinner />;
		}
	} else {
		description = loaded ? data.display : <Spinner />;
	}

	return (
		<AccountCard
			className="gla-phone-number-card"
			appearance={ APPEARANCE.PHONE }
			description={ description }
			hideIcon={ isPreview }
			indicator={ editButton }
		>
			{ phoneNumberContent }
		</AccountCard>
	);
}
