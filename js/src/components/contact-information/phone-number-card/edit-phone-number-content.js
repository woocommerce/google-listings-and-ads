/**
 * External dependencies
 */
import { parsePhoneNumberFromString as parsePhoneNumber } from 'libphonenumber-js';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock, RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCountryCallingCodeOptions from '.~/hooks/useCountryCallingCodeOptions';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import SelectControl from '.~/wcdl/select-control';
import AppInputControl from '.~/components/app-input-control';
import AppButton from '.~/components/app-button';
import { VERIFICATION_METHOD } from './constants';

/**
 * @typedef { import(".~/hooks/useGoogleMCPhoneNumber").PhoneNumberData } PhoneNumberData
 */

/**
 * @typedef {Object} ExtraPhoneNumberData
 * @property {string} number The phone number string in E.164 format. Example: '+12133734253'. Available if the input data is parsable.
 * @property {string} display The phone number string in international format. Example: '+1 213 373 4253'.
 * @property {string} verificationMethod Selected verification method.
 *
 * @typedef {PhoneNumberData & ExtraPhoneNumberData} CallbackPhoneNumberData
 */

/**
 * @callback onSendVerificationCodeClick
 * @param {CallbackPhoneNumberData} phoneNumberData The changed phone number data.
 */

const verificationOptions = [
	{
		label: __( 'Text message', 'google-listings-and-ads' ),
		value: VERIFICATION_METHOD.SMS,
	},
	{
		label: __( 'Phone call', 'google-listings-and-ads' ),
		value: VERIFICATION_METHOD.PHONE_CALL,
	},
];

/**
 * Renders inputs for editing phone number in Card.Body UI.
 *
 * @param {Object} props React props.
 * @param {string} props.initCountry The initial country code for the country selection. Example: 'US'.
 * @param {string} props.initNationalNumber The initial national (significant) number for its input field. Example: '2133734253'.
 * @param {onSendVerificationCodeClick} props.onSendVerificationCodeClick Called when clicking on the "Send verification code" button.
 */
export default function EditPhoneNumberContent( {
	initCountry,
	initNationalNumber,
	onSendVerificationCodeClick,
} ) {
	const countryCallingCodeOptions = useCountryCallingCodeOptions();
	const [ country, setCountry ] = useState( initCountry );
	const [ number, setNumber ] = useState( initNationalNumber );
	const [ verificationMethod, setVerificationMethod ] = useState(
		VERIFICATION_METHOD.SMS
	);
	const [ phoneNumber, setPhoneNumber ] = useState( null );

	useEffect( () => {
		const parsed = parsePhoneNumber( number, country );
		const isValid = parsed ? parsed.isValid() : false;

		if ( parsed ) {
			setPhoneNumber( {
				...parsed,
				isValid,
				display: parsed.formatInternational(),
				verificationMethod,
			} );
		} else {
			setPhoneNumber( {
				isValid,
				country,
				number: '',
				display: '',
				verificationMethod,
			} );
		}
	}, [ number, country, verificationMethod ] );

	const onSendClick = () => onSendVerificationCodeClick( phoneNumber );

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
						onChange={ setCountry }
					/>
				</FlexItem>
				<FlexBlock>
					<AppInputControl
						label={ __(
							'Phone number',
							'google-listings-and-ads'
						) }
						value={ number }
						onChange={ setNumber }
					/>
				</FlexBlock>
			</Flex>
			<Subsection>
				<Subsection.Title>
					{ __(
						'Select verification method',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<RadioControl
					selected={ verificationMethod }
					options={ verificationOptions }
					onChange={ setVerificationMethod }
				/>
			</Subsection>
			<Subsection>
				<AppButton
					isSecondary
					disabled={ ! phoneNumber?.isValid }
					text={ __(
						'Send verification code',
						'google-listings-and-ads'
					) }
					onClick={ onSendClick }
				/>
			</Subsection>
		</Section.Card.Body>
	);
}
