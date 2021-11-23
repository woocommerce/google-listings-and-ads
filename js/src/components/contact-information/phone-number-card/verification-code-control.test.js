/**
 * External dependencies
 */
import { createEvent, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import VerificationCodeControl from './verification-code-control';

describe( 'VerificationCodeControl component', () => {
	test( 'digit inputs are visible and empty', () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );

		const inputs = screen.getAllByRole( 'textbox' );
		expect( inputs?.length ).toBe( 6 );
		inputs.forEach( ( { value } ) => {
			expect( value ).toBe( '' );
		} );
	} );

	test( 'digit typing applies to the input', async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const input = screen.getAllByRole( 'textbox' )[ 0 ];

		await userEvent.type( input, '2' );
		expect( input.value ).toBe( '2' );

		await userEvent.clear( input );
		expect( input.value ).toBe( '' );

		await userEvent.type( input, '5' );
		expect( input.value ).toBe( '5' );

		fireEvent.input( input, {
			target: { value: '3' },
		} );
		expect( input.value ).toBe( '3' );
	} );

	test( 'clears if a non digit is typed', async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const input = screen.getAllByRole( 'textbox' )[ 0 ];

		await userEvent.type( input, '2' );

		fireEvent.input( input, {
			target: { value: 'x' },
		} );
		expect( input.value ).toBe( '' );
	} );

	test( 'moves the focus with cursor keys', async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		await userEvent.type( inputs[ 0 ], '{arrowright}' );
		expect( inputs[ 1 ] ).toHaveFocus();

		await userEvent.type( inputs[ 1 ], '{arrowleft}' );
		expect( inputs[ 0 ] ).toHaveFocus();
	} );

	test( 'moves the focus with typing and backspace', async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		await userEvent.type( inputs[ 0 ], '2' );
		expect( inputs[ 1 ] ).toHaveFocus();

		await userEvent.type( inputs[ 1 ], '{backspace}' );
		expect( inputs[ 0 ] ).toHaveFocus();
	} );

	test( "last input doesn't move the cursor forwards", async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		await userEvent.type( inputs[ inputs.length - 1 ], '{arrowright}' );
		expect( inputs[ inputs.length - 1 ] ).toHaveFocus();

		await userEvent.type( inputs[ inputs.length - 1 ], '2' );
		expect( inputs[ inputs.length - 1 ] ).toHaveFocus();
	} );

	test( "first input doesn't move the cursor backwards", async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		await userEvent.type( inputs[ 0 ], '{arrowleft}' );
		expect( inputs[ 0 ] ).toHaveFocus();

		await userEvent.type( inputs[ 0 ], '{backspace}' );
		expect( inputs[ 0 ] ).toHaveFocus();
	} );

	test( 'typing calls onChange callback', async () => {
		const onChange = jest.fn().mockName( 'On change callback' );
		render( <VerificationCodeControl onCodeChange={ onChange } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		// Notice the initial render calls it one time due a useEffect function
		expect( onChange ).toHaveBeenCalledTimes( 1 );

		await userEvent.type( inputs[ 0 ], '1' );
		expect( onChange ).toHaveBeenCalledTimes( 2 );

		await userEvent.type( inputs[ 1 ], '1' );
		expect( onChange ).toHaveBeenCalledTimes( 3 );
	} );

	test( 'typing Enter submits enclosing form', async () => {
		const onSubmit = jest.fn().mockName( 'On form submit callback' );
		render(
			<form onSubmit={ onSubmit }>
				<VerificationCodeControl onCodeChange={ () => {} } />
				<button type="submit">submit</button>
			</form>
		);

		await userEvent.type(
			screen.getAllByRole( 'textbox' )[ 0 ],
			'{enter}'
		);

		expect( onSubmit ).toHaveBeenCalled();
	} );

	test( 'it is possible to paste multiple digits', async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		const paste = createEvent.paste( inputs[ 0 ], {
			clipboardData: {
				getData: () => '222222',
			},
		} );

		fireEvent( inputs[ 0 ], paste );

		inputs.forEach( ( el ) => expect( el.value ).toBe( '2' ) );
	} );

	test( 'it pastes a maximum of {DIGIT_LENGTH} digits', async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		const paste = createEvent.paste( inputs[ 0 ], {
			clipboardData: {
				getData: () => '22222233333333',
			},
		} );

		fireEvent( inputs[ 0 ], paste );

		inputs.forEach( ( el ) => expect( el.value ).toBe( '2' ) );
	} );

	test( 'it pastes with other indexes and number of digits', async () => {
		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		const paste = createEvent.paste( inputs[ 1 ], {
			clipboardData: {
				getData: () => '22',
			},
		} );

		fireEvent( inputs[ 1 ], paste );

		inputs.forEach( ( el, idx ) => {
			const expectedValue = idx > 0 && idx < 3 ? '2' : '';
			expect( el.value ).toBe( expectedValue );
		} );
	} );
} );
