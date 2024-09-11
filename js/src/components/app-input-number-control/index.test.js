/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppInputNumberControl from './';

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest
		.fn()
		.mockName( "getSetting( 'currency' )" )
		.mockReturnValue( {
			decimalSeparator: '.',
			thousandSeparator: ',',
		} ),
} ) );

describe( 'AppInputNumberControl', () => {
	let ControlledInput;
	let onChange;
	let onBlur;

	function getInput() {
		return screen.getByRole( 'textbox' );
	}

	beforeEach( () => {
		onChange = jest.fn();
		onBlur = jest.fn();

		ControlledInput = ( props ) => {
			const [ value, setValue ] = useState( 1 );

			return (
				<AppInputNumberControl
					value={ value }
					onChange={ onChange }
					onBlur={ ( e, nextValue ) => {
						onBlur( e, nextValue );
						setValue( nextValue );
					} }
					{ ...props }
				/>
			);
		};
	} );

	it( 'Should format the number according to the setting', async () => {
		const user = userEvent.setup();
		const { rerender } = render(
			<ControlledInput numberSettings={ { precision: 2 } } />
		);

		expect( getInput().value ).toBe( '1.00' );

		await user.clear( getInput() );
		await user.type( getInput(), '1234.56789' );
		await user.click( document.body );

		expect( getInput().value ).toBe( '1,234.57' );

		rerender(
			<ControlledInput
				numberSettings={ {
					precision: 4,
					decimalSeparator: ',',
					thousandSeparator: '.',
				} }
			/>
		);

		expect( getInput().value ).toBe( '1.234,5700' );

		await user.clear( getInput() );
		await user.type( getInput(), '9876,54321' );
		await user.click( document.body );

		expect( getInput().value ).toBe( '9.876,5432' );
	} );

	it( 'Should callback the value as a number type', async () => {
		const user = userEvent.setup();

		render( <ControlledInput numberSettings={ { precision: 2 } } /> );

		await user.clear( getInput() );

		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange ).toHaveBeenLastCalledWith( 0 );
		expect( onBlur ).toHaveBeenCalledTimes( 0 );

		await user.type( getInput(), '123' );

		expect( onChange ).toHaveBeenCalledTimes( 4 );
		expect( onChange ).toHaveBeenCalledWith( 1 );
		expect( onChange ).toHaveBeenCalledWith( 12 );
		expect( onChange ).toHaveBeenLastCalledWith( 123 );
		expect( onBlur ).toHaveBeenCalledTimes( 0 );

		await user.type( getInput(), '4' );

		expect( onChange ).toHaveBeenCalledTimes( 5 );
		expect( onChange ).toHaveBeenLastCalledWith( 1234 );
		expect( onBlur ).toHaveBeenCalledTimes( 0 );

		await user.type( getInput(), '.567' );

		expect( onChange ).toHaveBeenCalledTimes( 9 );
		expect( onChange ).toHaveBeenCalledWith( 1234 );
		expect( onChange ).toHaveBeenCalledWith( 1234.5 );
		expect( onChange ).toHaveBeenCalledWith( 1234.56 );
		expect( onChange ).toHaveBeenLastCalledWith( 1234.57 );
		expect( onBlur ).toHaveBeenCalledTimes( 0 );

		await user.click( document.body );

		expect( onChange ).toHaveBeenCalledTimes( 9 );
		expect( onBlur ).toHaveBeenCalledTimes( 1 );
		expect( onBlur ).toHaveBeenCalledWith( expect.any( Object ), 1234.57 );
	} );

	it( 'Should treat the cleared input value as number 0 after losing focus', async () => {
		const user = userEvent.setup();

		render( <ControlledInput numberSettings={ { precision: 3 } } /> );

		expect( getInput().value ).toBe( '1.000' );

		await user.clear( getInput() );

		expect( getInput().value ).toBe( '' );

		await user.click( document.body );

		expect( getInput().value ).toBe( '0.000' );

		// Clear again to test the case that it resumes the displayed value to '0.000'
		// after leaving a string value equivalent to number 0.
		await user.clear( getInput() );

		expect( getInput().value ).toBe( '' );

		await user.click( document.body );

		expect( getInput().value ).toBe( '0.000' );
	} );
} );
