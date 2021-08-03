/**
 * External dependencies
 */
import { getCountryCallingCode } from 'libphonenumber-js';
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
		setCountry( nextCountry );
		setNumber( nextNumber );

		const countryCallingCode = nextCountry
			? getCountryCallingCode( nextCountry )
			: '';
		onPhoneNumberChange( countryCallingCode, nextNumber, nextCountry );
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
