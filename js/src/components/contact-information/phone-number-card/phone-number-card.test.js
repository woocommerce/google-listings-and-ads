/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { parsePhoneNumberFromString as parsePhoneNumber } from 'libphonenumber-js';

/**
 * Internal dependencies
 */
import PhoneNumberCard from './phone-number-card';

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Spinner: jest
		.fn( () => <div role="status" aria-label="spinner" /> )
		.mockName( 'Spinner' ),
} ) );

jest.mock( '.~/hooks/useCountryKeyNameMap', () =>
	jest
		.fn()
		.mockReturnValue( { US: 'United States' } )
		.mockName( 'useCountryKeyNameMap' )
);

jest.mock( '.~/data', () => ( {
	...jest.requireActual( '.~/data' ),
	useAppDispatch() {
		return {
			requestPhoneVerificationCode: jest
				.fn( () => Promise.resolve( { verificationId: 123 } ) )
				.mockName( 'requestPhoneVerificationCode' ),

			verifyPhoneNumber: jest
				.fn( () => Promise.resolve( {} ) )
				.mockName( 'verifyPhoneNumber' ),
		};
	},
} ) );

describe( 'PhoneNumberCard', () => {
	let phoneData;
	let phoneNumber;

	function mockPhoneData( fullPhoneNumber ) {
		const parsed = parsePhoneNumber( fullPhoneNumber );

		phoneData = {
			...parsed,
			display: parsed.formatInternational(),
			isValid: parsed.isValid(),
		};

		phoneNumber = {
			...phoneNumber,
			data: phoneData,
		};
	}

	function mockVerified( isVerified ) {
		phoneNumber = {
			...phoneNumber,
			data: {
				...phoneData,
				isVerified,
			},
		};
	}

	function mockLoaded( loaded ) {
		phoneNumber = {
			...phoneNumber,
			loaded,
		};
	}

	beforeEach( () => {
		mockPhoneData( '+12133734253' );
		mockVerified( true );
		mockLoaded( true );
	} );

	it( 'When not yet loaded, should render a loading spinner', () => {
		mockLoaded( false );

		render( <PhoneNumberCard phoneNumber={ phoneNumber } /> );

		const spinner = screen.getByRole( 'status', { name: 'spinner' } );
		const display = screen.queryByText( phoneData.display );
		const button = screen.queryByRole( 'button' );

		expect( spinner ).toBeInTheDocument();
		expect( display ).not.toBeInTheDocument();
		expect( button ).not.toBeInTheDocument();
	} );

	it( 'When `initEditing` is not specified, should render in display mode after loading a verified phone number', () => {
		mockLoaded( false );
		const { rerender } = render(
			<PhoneNumberCard phoneNumber={ phoneNumber } />
		);

		mockLoaded( true );
		rerender( <PhoneNumberCard phoneNumber={ phoneNumber } /> );

		const button = screen.getByRole( 'button', { name: 'Edit' } );

		expect( button ).toBeInTheDocument();
	} );

	it( 'When `initEditing` is not specified, should render in editing mode after loading an unverified phone number', () => {
		mockLoaded( false );
		const { rerender } = render(
			<PhoneNumberCard phoneNumber={ phoneNumber } />
		);

		mockVerified( false );
		mockLoaded( true );
		rerender( <PhoneNumberCard phoneNumber={ phoneNumber } /> );

		const button = screen.getByRole( 'button', { name: /Send/ } );

		expect( button ).toBeInTheDocument();
	} );

	it( 'When `initEditing` is true, should directly render in editing mode', () => {
		render( <PhoneNumberCard initEditing phoneNumber={ phoneNumber } /> );

		const button = screen.getByRole( 'button', { name: /Send/ } );

		expect( button ).toBeInTheDocument();
	} );

	it( 'When `initEditing` is false, should render in display mode regardless of verified or not', () => {
		// Start with a verified and valid phone number
		const { rerender } = render(
			<PhoneNumberCard
				initEditing={ false }
				phoneNumber={ phoneNumber }
			/>
		);

		expect( screen.getByText( phoneData.display ) ).toBeInTheDocument();

		// Set to an unverified and invalid phone number
		mockPhoneData( '+121' );
		mockVerified( false );

		rerender(
			<PhoneNumberCard
				initEditing={ false }
				phoneNumber={ phoneNumber }
			/>
		);

		expect( screen.getByText( phoneData.display ) ).toBeInTheDocument();
	} );

	it( 'When the phone number is loaded but not yet verified, should directly render in editing mode', () => {
		mockVerified( false );
		render( <PhoneNumberCard phoneNumber={ phoneNumber } /> );

		const button = screen.getByRole( 'button', { name: /Send/ } );

		expect( button ).toBeInTheDocument();
	} );

	it( 'When `showValidation` is true and the phone number is not yet verified, should show a validation error text', () => {
		const text = 'A verified phone number is required.';
		mockVerified( false );

		const { rerender } = render(
			<PhoneNumberCard phoneNumber={ phoneNumber } />
		);

		expect( screen.queryByText( text ) ).not.toBeInTheDocument();

		rerender(
			<PhoneNumberCard showValidation phoneNumber={ phoneNumber } />
		);

		expect( screen.getByText( text ) ).toBeInTheDocument();
	} );

	it( 'When `onEditClick` is specified and the Edit button is clicked, should callback `onEditClick`', () => {
		const onEditClick = jest.fn();
		render(
			<PhoneNumberCard
				phoneNumber={ phoneNumber }
				onEditClick={ onEditClick }
			/>
		);

		const button = screen.getByRole( 'button', { name: 'Edit' } );

		expect( button ).toBeInTheDocument();
		expect( onEditClick ).toHaveBeenCalledTimes( 0 );

		userEvent.click( button );

		expect( onEditClick ).toHaveBeenCalledTimes( 1 );
	} );

	describe( 'Should callback `onPhoneNumberVerified`', () => {
		it( 'When `initEditing` is not specified and loaded phone number has been verified', () => {
			const onPhoneNumberVerified = jest.fn();

			render(
				<PhoneNumberCard
					phoneNumber={ phoneNumber }
					onPhoneNumberVerified={ onPhoneNumberVerified }
				/>
			);

			expect( onPhoneNumberVerified ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'When `initEditing` is false and loaded phone number has been verified', () => {
			const onPhoneNumberVerified = jest.fn();
			render(
				<PhoneNumberCard
					initEditing={ false }
					phoneNumber={ phoneNumber }
					onPhoneNumberVerified={ onPhoneNumberVerified }
				/>
			);

			expect( onPhoneNumberVerified ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'When an unverified phone number is getting verified', async () => {
			const onPhoneNumberVerified = jest.fn();
			mockVerified( false );
			render(
				<PhoneNumberCard
					phoneNumber={ phoneNumber }
					onPhoneNumberVerified={ onPhoneNumberVerified }
				/>
			);

			expect( onPhoneNumberVerified ).toHaveBeenCalledTimes( 0 );

			await act( async () => {
				const button = screen.getByRole( 'button', { name: /Send/ } );
				userEvent.click( button );
			} );

			screen.getAllByRole( 'textbox' ).forEach( ( codeInput, i ) => {
				userEvent.type( codeInput, i.toString() );
			} );

			await act( async () => {
				const button = screen.getByRole( 'button', { name: /Verify/ } );
				userEvent.click( button );
			} );

			expect( onPhoneNumberVerified ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
