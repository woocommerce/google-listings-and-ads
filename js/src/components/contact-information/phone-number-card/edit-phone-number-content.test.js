/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import EditPhoneNumberContent from './edit-phone-number-content';

jest.mock( '.~/hooks/useCountryKeyNameMap', () =>
	jest
		.fn()
		.mockReturnValue( { US: 'United States', JP: 'Japan' } )
		.mockName( 'useCountryKeyNameMap' )
);

describe( 'PhoneNumberCard', () => {
	it( 'Should take the initial country and national number as the initial values of inputs', () => {
		render(
			<EditPhoneNumberContent
				initCountry="US"
				initNationalNumber="2133734253"
			/>
		);

		const country = screen.getByRole( 'combobox' );
		const phone = screen.getByRole( 'textbox' );

		expect( country.value ).toBe( 'United States (+1)' );
		expect( phone.value ).toBe( '2133734253' );
	} );

	it( 'When an entered phone number is invalid, should disable "Send verification code" button', async () => {
		const user = userEvent.setup();

		render(
			<EditPhoneNumberContent
				initCountry="US"
				initNationalNumber="2133734253"
			/>
		);

		const phone = screen.getByRole( 'textbox' );
		const submit = screen.getByRole( 'button' );

		expect( submit ).toBeEnabled();

		await user.click( phone );
		await user.keyboard( '{Backspace}' );

		expect( submit ).toBeDisabled();

		await user.type( phone, '1' );

		expect( submit ).toBeEnabled();

		await user.type( phone, '2' );

		expect( submit ).toBeDisabled();
	} );

	it( 'Should call back `onSendVerificationCodeClick` with input values and verification method when clicking on "Send verification code" button', async () => {
		const user = userEvent.setup();
		const onSendVerificationCodeClick = jest
			.fn()
			.mockName( 'onSendVerificationCodeClick' );

		render(
			<EditPhoneNumberContent
				initCountry=""
				initNationalNumber=""
				onSendVerificationCodeClick={ onSendVerificationCodeClick }
			/>
		);

		const country = screen.getByRole( 'combobox' );
		const phone = screen.getByRole( 'textbox' );
		const submit = screen.getByRole( 'button' );

		expect( onSendVerificationCodeClick ).toHaveBeenCalledTimes( 0 );

		// Select and enter a U.S. phone number
		await user.type( country, 'uni' );
		await user.click( await screen.findByRole( 'option' ) );
		await user.clear( phone );
		await user.type( phone, '2133734253' );

		await user.click( submit );

		expect( onSendVerificationCodeClick ).toHaveBeenCalledTimes( 1 );
		expect( onSendVerificationCodeClick ).toHaveBeenCalledWith(
			expect.objectContaining( {
				country: 'US',
				countryCallingCode: '1',
				nationalNumber: '2133734253',
				isValid: true,
				display: '+1 213 373 4253',
				number: '+12133734253',
				verificationMethod: 'SMS',
			} )
		);

		// Select and enter a Japanese phone number
		await user.clear( country );
		await user.type( country, 'jap' );
		await user.click( await screen.findByRole( 'option' ) );
		await user.clear( phone );
		await user.type( phone, '570550634' );

		// Select verification method to PHONE_CALL
		await user.click( screen.getByRole( 'radio', { name: 'Phone call' } ) );

		await user.click( submit );

		expect( onSendVerificationCodeClick ).toHaveBeenCalledTimes( 2 );
		expect( onSendVerificationCodeClick ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				country: 'JP',
				countryCallingCode: '81',
				nationalNumber: '570550634',
				isValid: true,
				display: '+81 570 550 634',
				number: '+81570550634',
				verificationMethod: 'PHONE_CALL',
			} )
		);
	} );
} );
