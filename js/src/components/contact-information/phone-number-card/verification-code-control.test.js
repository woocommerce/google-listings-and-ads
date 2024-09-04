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
		const onSubmit = jest
			.fn( ( e ) => e.preventDefault() )
			.mockName( 'On form submit callback' );

		render(
			<form onSubmit={ onSubmit }>
				<VerificationCodeControl onCodeChange={ () => {} } />
				<button type="submit">submit</button>
			</form>
		);

		await user.keyboard( '{Enter}' );

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

	test( 'updates validation code prop on paste', async () => {
		const onCodeChange = jest
			.fn( ( data ) => data )
			.mockName( 'onCodeChange' );

		render( <VerificationCodeControl onCodeChange={ onCodeChange } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		expect( onCodeChange.mock.results[ 0 ].value ).toStrictEqual( {
			code: '',
			isFilled: false,
		} );

		let paste = createEvent.paste( inputs[ 0 ], {
			clipboardData: {
				getData: () => '222',
			},
		} );

		fireEvent( inputs[ 0 ], paste );

		expect( onCodeChange.mock.results[ 1 ].value ).toStrictEqual( {
			code: '222',
			isFilled: false,
		} );

		paste = createEvent.paste( inputs[ 0 ], {
			clipboardData: {
				getData: () => '333333',
			},
		} );

		fireEvent( inputs[ 0 ], paste );

		expect( onCodeChange.mock.results[ 2 ].value ).toStrictEqual( {
			code: '333333',
			isFilled: true,
		} );
	} );
	test( 'updates validation code prop on input', async () => {
		const onCodeChange = jest
			.fn( ( data ) => data )
			.mockName( 'onCodeChange' );

		render( <VerificationCodeControl onCodeChange={ onCodeChange } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		expect( onCodeChange.mock.results[ 0 ].value ).toStrictEqual( {
			code: '',
			isFilled: false,
		} );

		let currentCode = '';
		for ( const input of inputs ) {
			const i = inputs.indexOf( input ) + 1;
			await userEvent.type( input, '1' );
			currentCode += '1';
			expect( onCodeChange.mock.results[ i ].value ).toStrictEqual( {
				code: currentCode,
				isFilled: i === 6,
			} );
		}
	} );
} );
