/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import VerifyPhoneNumberContent from './verify-phone-number-content';
import { useAppDispatch } from '.~/data';

jest.mock( '.~/data', () => ( {
	...jest.requireActual( '.~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'VerifyPhoneNumberContent', () => {
	let requestPhoneVerificationCode;
	let verifyPhoneNumber;

	function getSwitchButton() {
		return screen.getByRole( 'button', {
			name: 'Switch verification method',
		} );
	}

	function getVerifyButton() {
		return screen.getByRole( 'button', { name: /Verify/ } );
	}

	beforeEach( () => {
		requestPhoneVerificationCode = jest
			.fn( () => Promise.resolve( { verificationId: '987654321' } ) )
			.mockName( 'requestPhoneVerificationCode' );

		verifyPhoneNumber = jest
			.fn( () => {
				return new Promise( ( resolve, reject ) => {
					setTimeout(
						() => reject( { display: 'error reason: JS test' } ),
						1000
					);
				} );
			} )
			.mockName( 'verifyPhoneNumber' );

		useAppDispatch.mockReturnValue( {
			requestPhoneVerificationCode,
			verifyPhoneNumber,
		} );
	} );

	it( 'Should render the given props with corresponding verification method', async () => {
		const user = userEvent.setup();

		render(
			<VerifyPhoneNumberContent
				verificationMethod="SMS"
				country="US"
				number="+12133734253"
				display="+1 213 373 4253"
			/>
		);

		const switchButton = getSwitchButton();

		expect( switchButton ).toBeInTheDocument();
		expect( switchButton.textContent ).toBe(
			'Or, receive a verification code through a phone call'
		);
		expect( screen.getByText( '+1 213 373 4253' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				/A text message with the 6-digit verification code has been sent/
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /Resend code/ } )
		).toBeInTheDocument();

		expect( requestPhoneVerificationCode ).toHaveBeenCalledTimes( 1 );

		// Switch to the PHONE_CALL method
		await user.click( switchButton );

		expect( switchButton.textContent ).toBe(
			'Or, receive a verification code through text message'
		);
		expect( screen.getByText( '+1 213 373 4253' ) ).toBeInTheDocument();
		expect(
			screen.getByText( /You will receive a phone call/ )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /Call again/ } )
		).toBeInTheDocument();

		expect( requestPhoneVerificationCode ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'When not yet entered 6-digit verification code, should disable the "Verify phone number" button', async () => {
		const user = userEvent.setup();

		await act( async () => {
			render(
				<VerifyPhoneNumberContent
					verificationMethod="SMS"
					country="US"
					number="+12133734253"
					display="+1 213 373 4253"
				/>
			);
		} );

		const button = getVerifyButton();
		const codeInputs = screen.getAllByRole( 'textbox' );

		expect( button ).toBeInTheDocument();
		expect( button ).toBeDisabled();

		for ( const [ i, codeInput ] of codeInputs.entries() ) {
			await user.type( codeInput, i.toString() );
		}

		expect( button ).toBeEnabled();
	} );

	it( 'When waiting for the countdown of each verification method, should disable the "Resend code/Call again" button', async () => {
		const user = userEvent.setup();

		render(
			<VerifyPhoneNumberContent
				verificationMethod="SMS"
				country="US"
				number="+12133734253"
				display="+1 213 373 4253"
			/>
		);

		const button = screen.getByRole( 'button', { name: /Resend code/ } );

		expect( button ).toBeDisabled();

		// Switch to the PHONE_CALL method
		await user.click( getSwitchButton() );

		expect( button ).toBeDisabled();
	} );

	it( 'When not waiting for the countdown of each verification method, should call `requestPhoneVerificationCode` with the country code, phone number and verification method', async () => {
		const user = userEvent.setup();

		render(
			<VerifyPhoneNumberContent
				verificationMethod="SMS"
				country="US"
				number="+12133734253"
				display="+1 213 373 4253"
			/>
		);

		const switchButton = getSwitchButton();

		expect( requestPhoneVerificationCode ).toHaveBeenCalledTimes( 1 );
		expect( requestPhoneVerificationCode ).toHaveBeenCalledWith(
			'US',
			'+12133734253',
			'SMS'
		);

		// Switch to the PHONE_CALL method
		await user.click( switchButton );

		expect( requestPhoneVerificationCode ).toHaveBeenCalledTimes( 2 );
		expect( requestPhoneVerificationCode ).toHaveBeenLastCalledWith(
			'US',
			'+12133734253',
			'PHONE_CALL'
		);

		// Switch back to the SMS method but it's still waiting for countdown
		await user.click( switchButton );

		expect( requestPhoneVerificationCode ).toHaveBeenCalledTimes( 2 );

		// Switch back to the PHONE_CALL method but it's still waiting for countdown
		await user.click( switchButton );

		expect( requestPhoneVerificationCode ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'When clicking the "Verify phone number" button, should call `verifyPhoneNumber` with the verification id, code and method', async () => {
		const user = userEvent.setup();

		await act( async () => {
			render(
				<VerifyPhoneNumberContent
					verificationMethod="SMS"
					country="US"
					number="+12133734253"
					display="+1 213 373 4253"
					onVerificationStateChange={ () => {} }
				/>
			);
		} );

		const button = getVerifyButton();
		const codeInputs = screen.getAllByRole( 'textbox' );

		for ( const [ i, codeInput ] of codeInputs.entries() ) {
			await user.type( codeInput, i.toString() );
		}

		expect( verifyPhoneNumber ).toHaveBeenCalledTimes( 0 );

		await user.click( button );

		expect( verifyPhoneNumber ).toHaveBeenCalledTimes( 1 );
		expect( verifyPhoneNumber ).toHaveBeenLastCalledWith(
			'987654321',
			'012345',
			'SMS'
		);
	} );

	it( 'Should call back to `onVerificationStateChange` according to the state and result of `verifyPhoneNumber`', async () => {
		jest.useFakeTimers();

		const user = userEvent.setup( {
			advanceTimers: jest.advanceTimersByTime,
		} );
		const onVerificationStateChange = jest
			.fn()
			.mockName( 'onVerificationStateChange' );

		await act( async () => {
			render(
				<VerifyPhoneNumberContent
					verificationMethod="SMS"
					country="US"
					number="+12133734253"
					display="+1 213 373 4253"
					onVerificationStateChange={ onVerificationStateChange }
				/>
			);
		} );

		const button = getVerifyButton();
		const codeInputs = screen.getAllByRole( 'textbox' );

		for ( const [ i, codeInput ] of codeInputs.entries() ) {
			await user.type( codeInput, i.toString() );
		}

		// -----------------------------------------
		// Failed `verifyPhoneNumber` calling result
		// -----------------------------------------
		expect( onVerificationStateChange ).toHaveBeenCalledTimes( 0 );

		await user.click( button );

		// First callback for loading state:
		// 1. isVerifying: true
		// 2. isVerified: false
		expect( onVerificationStateChange ).toHaveBeenCalledTimes( 1 );
		expect( onVerificationStateChange ).toHaveBeenCalledWith( true, false );

		await act( async () => {
			jest.runOnlyPendingTimers();
		} );

		// Second callback for failed result:
		// 1. isVerifying: false
		// 2. isVerified: false
		expect( onVerificationStateChange ).toHaveBeenCalledTimes( 2 );
		expect( onVerificationStateChange ).toHaveBeenLastCalledWith(
			false,
			false
		);

		// `Notice` component will insert another invisible text element under <body> with the same string.
		// So here filter out it.
		expect(
			screen.getByText( ( content, element ) => {
				return (
					element.parentElement.tagName !== 'BODY' &&
					content === 'error reason: JS test'
				);
			} )
		).toBeInTheDocument();

		// ---------------------------------------------
		// Successful `verifyPhoneNumber` calling result
		// ---------------------------------------------
		onVerificationStateChange.mockReset();

		verifyPhoneNumber.mockImplementation(
			() => new Promise( ( resolve ) => setTimeout( resolve, 1000 ) )
		);

		expect( onVerificationStateChange ).toHaveBeenCalledTimes( 0 );

		await user.click( button );

		// First callback for loading state:
		// 1. isVerifying: true
		// 2. isVerified: false
		expect( onVerificationStateChange ).toHaveBeenCalledTimes( 1 );
		expect( onVerificationStateChange ).toHaveBeenCalledWith( true, false );

		await act( async () => {
			jest.runOnlyPendingTimers();
		} );

		// Second callback for successful result:
		// 1. isVerifying: false
		// 2. isVerified: true
		expect( onVerificationStateChange ).toHaveBeenCalledTimes( 2 );
		expect( onVerificationStateChange ).toHaveBeenLastCalledWith(
			false,
			true
		);

		// The verify button should keep disabled after successfully verified
		expect( button ).toBeDisabled();

		jest.useRealTimers();
		jest.clearAllTimers();
	} );
} );
