/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Stepper from './index';

describe( 'Stepper', () => {
	it( 'Should increase value when clicking on the plus button', async () => {
		const handleBlur = jest.fn();
		const handleIncrement = jest.fn();

		const { getByRole, getByDisplayValue } = render(
			<Stepper
				time={ 1 }
				handleBlur={ handleBlur }
				handleIncrement={ handleIncrement }
			/>
		);

		fireEvent.mouseDown( getByRole( 'button', { name: 'Increment' } ) );

		await waitFor( () => {
			expect( handleIncrement ).toHaveBeenCalledTimes( 1 );
		} );

		expect( handleIncrement ).toHaveBeenCalledWith( 2, 'time' );
		expect( getByDisplayValue( '2' ) ).toBeInTheDocument();
	} );

	it( 'Should decrease value when clicking on the minus button', async () => {
		const handleBlur = jest.fn();
		const handleIncrement = jest.fn();

		const { getByRole, getByDisplayValue } = render(
			<Stepper
				time={ 4 }
				handleBlur={ handleBlur }
				handleIncrement={ handleIncrement }
			/>
		);

		fireEvent.mouseDown( getByRole( 'button', { name: 'Decrement' } ) );

		await waitFor( () => {
			expect( handleIncrement ).toHaveBeenCalledTimes( 1 );
		} );

		expect( handleIncrement ).toHaveBeenCalledWith( 3, 'time' );
		expect( getByDisplayValue( '3' ) ).toBeInTheDocument();
	} );

	it( 'Should set empty value if value is 0', () => {
		const handleBlur = jest.fn();
		const handleIncrement = jest.fn();

		const { getByDisplayValue } = render(
			<Stepper
				time={ 0 }
				handleBlur={ handleBlur }
				handleIncrement={ handleIncrement }
			/>
		);

		expect( getByDisplayValue( '' ) ).toBeInTheDocument();
	} );
} );
