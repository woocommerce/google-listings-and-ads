/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
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
		const user = userEvent.setup();

		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const input = screen.getAllByRole( 'textbox' )[ 0 ];

		await user.type( input, '2' );
		expect( input.value ).toBe( '2' );

		await user.clear( input );
		expect( input.value ).toBe( '' );

		await user.type( input, '5' );
		expect( input.value ).toBe( '5' );

		fireEvent.input( input, {
			target: { value: '3' },
		} );
		expect( input.value ).toBe( '3' );
	} );

	test( 'clears if a non digit is typed', async () => {
		const user = userEvent.setup();

		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const input = screen.getAllByRole( 'textbox' )[ 0 ];

		await user.type( input, '2' );

		fireEvent.input( input, {
			target: { value: 'x' },
		} );
		expect( input.value ).toBe( '' );
	} );

	test( 'moves the focus with cursor keys', async () => {
		const user = userEvent.setup();

		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		await user.keyboard( '{ArrowRight}' );
		expect( inputs[ 1 ] ).toHaveFocus();

		await user.keyboard( '{ArrowLeft}' );
		expect( inputs[ 0 ] ).toHaveFocus();
	} );

	test( 'moves the focus with typing and backspace', async () => {
		const user = userEvent.setup();

		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		await user.type( inputs[ 0 ], '2' );
		expect( inputs[ 1 ] ).toHaveFocus();

		await user.keyboard( '{Backspace}' );
		expect( inputs[ 0 ] ).toHaveFocus();
	} );

	test( "last input doesn't move the cursor forwards", async () => {
		const user = userEvent.setup();

		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );
		const lastInput = inputs.at( -1 );

		await user.click( lastInput );
		await user.keyboard( '{ArrowRight}' );
		expect( lastInput ).toHaveFocus();

		await user.type( lastInput, '2' );
		expect( lastInput ).toHaveFocus();
	} );

	test( "first input doesn't move the cursor backwards", async () => {
		const user = userEvent.setup();

		render( <VerificationCodeControl onCodeChange={ () => {} } /> );
		const inputs = screen.getAllByRole( 'textbox' );
		const firstInput = inputs.at( 0 );

		await user.keyboard( '{ArrowLeft}' );
		expect( firstInput ).toHaveFocus();

		await user.keyboard( '{Backspace}' );
		expect( firstInput ).toHaveFocus();
	} );

	test( 'typing calls onChange callback', async () => {
		const user = userEvent.setup();
		const onChange = jest.fn().mockName( 'On change callback' );
		render( <VerificationCodeControl onCodeChange={ onChange } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		// Notice the initial render calls it one time due a useEffect function
		expect( onChange ).toHaveBeenCalledTimes( 1 );

		await user.type( inputs[ 0 ], '1' );
		expect( onChange ).toHaveBeenCalledTimes( 2 );

		await user.type( inputs[ 1 ], '1' );
		expect( onChange ).toHaveBeenCalledTimes( 3 );
	} );

	test( 'typing Enter submits enclosing form', async () => {
		const user = userEvent.setup();
		const onSubmit = jest.fn().mockName( 'On form submit callback' );
		render(
			<form onSubmit={ onSubmit }>
				<VerificationCodeControl onCodeChange={ () => {} } />
				<button type="submit">submit</button>
			</form>
		);

		await user.keyboard( '{Enter}' );

		expect( onSubmit ).toHaveBeenCalled();
	} );
} );
