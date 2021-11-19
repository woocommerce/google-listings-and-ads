/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import VerifyPhoneNumberContent from './verify-phone-number-content';

describe( 'VerifyPhoneNumberContent component', () => {
	test( 'typing Enter submits enclosing form', async () => {
		const onVerificationStateChange = jest.fn();

		render(
			<VerifyPhoneNumberContent
				verificationMethod="SMS"
				onVerificationStateChange={ onVerificationStateChange }
			/>
		);

		await act( async () => {
			await userEvent.type(
				screen.getAllByRole( 'textbox' )[ 0 ],
				'{enter}'
			);
		} );

		expect( onVerificationStateChange ).toHaveBeenCalled();
	} );
} );
