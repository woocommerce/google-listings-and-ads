/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import TimeStepper from './';

describe( 'TimeStepper', () => {
	it( 'Should increase value when clicking on the plus button', async () => {
		const handleBlur = jest.fn();
		const handleIncrement = jest.fn();

		const { getByRole, getByDisplayValue } = render(
			<TimeStepper
				handleBlur={ handleBlur }
				handleIncrement={ handleIncrement }
				time={ 1 }
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
			<TimeStepper
				handleBlur={ handleBlur }
				handleIncrement={ handleIncrement }
				time={ 4 }
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
			<TimeStepper
				handleBlur={ handleBlur }
				handleIncrement={ handleIncrement }
				time={ 0 }
			/>
		);

		expect( getByDisplayValue( '' ) ).toBeInTheDocument();
	} );
} );
