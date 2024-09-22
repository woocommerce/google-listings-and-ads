/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CountriesTimeInput from './index';

describe( 'CountriesTimeInput', () => {
	describe( 'Test Same delivery placeholder', () => {
		it( 'Shouldnt have placeholder if times are null', () => {
			const { getByText, getByRole, getAllByRole } = render(
				<CountriesTimeInput
					value={ { countries: [ 'ES' ], time: null, maxTime: null } }
					audienceCountries={ [ 'ES' ] }
					onChange={ jest.fn() }
					onDelete={ jest.fn() }
				/>
			);

			expect( getByText( 'Shipping time for' ) ).toBeInTheDocument();

			expect(
				getByRole( 'button', { name: 'Edit' } )
			).toBeInTheDocument();

			const inputs = getAllByRole( 'textbox' );

			expect( inputs ).toHaveLength( 2 );

			for ( const input of inputs ) {
				expect( input ).toHaveAttribute( 'placeholder', '' );
			}
		} );

		it( 'Should set placeholders if times are different than 0', () => {
			const { getByDisplayValue, queryAllByPlaceholderText } = render(
				<CountriesTimeInput
					value={ { countries: [ 'ES' ], time: 0, maxTime: 32 } }
					audienceCountries={ [ 'ES' ] }
					onChange={ jest.fn() }
					onDelete={ jest.fn() }
				/>
			);

			expect( queryAllByPlaceholderText( 'Same Day' ) ).toHaveLength( 2 );

			expect( getByDisplayValue( '32' ) ).toBeInTheDocument();

			// The 0 is changed to an empty string, allowing the placeholder/default value to be displayed.
			expect( getByDisplayValue( '' ) ).toBeInTheDocument();
		} );
	} );
	describe( 'Test Stepper', () => {
		it( 'Should call onChange when increasing an decreasing the days', async () => {
			const onChange = jest.fn();
			const { queryAllByRole } = render(
				<CountriesTimeInput
					value={ { countries: [ 'ES' ], time: 1, maxTime: 32 } }
					audienceCountries={ [ 'ES' ] }
					onChange={ onChange }
					onDelete={ jest.fn() }
				/>
			);

			const incrementButtons = queryAllByRole( 'button', {
				name: 'Increment',
			} );
			const decrementButtons = queryAllByRole( 'button', {
				name: 'Decrement',
			} );

			// One for the min and one for the max = 2 increment buttons and 2 decrement buttons
			expect( incrementButtons ).toHaveLength( 2 );
			expect( decrementButtons ).toHaveLength( 2 );

			//Increasing
			for ( const button of incrementButtons ) {
				fireEvent.mouseDown( button );
			}

			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 2 );
			} );

			expect( onChange.mock.calls[ 0 ][ 0 ].time ).toBe( 2 );
			expect( onChange.mock.calls[ 1 ][ 0 ].maxTime ).toBe( 33 );

			//Decreasing
			for ( const button of decrementButtons ) {
				fireEvent.mouseDown( button );
			}

			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 4 );
			} );

			expect( onChange.mock.calls[ 2 ][ 0 ].time ).toBe( 1 );
			expect( onChange.mock.calls[ 3 ][ 0 ].maxTime ).toBe( 32 );
		} );
	} );

	describe( 'Test handleBlur', () => {
		it( 'Test onChange when handleBlur is called', async () => {
			const onChange = jest.fn();
			const onDelete = jest.fn();
			const { queryAllByRole } = render(
				<CountriesTimeInput
					value={ { countries: [ 'ES' ], time: 1, maxTime: 32 } }
					audienceCountries={ [ 'ES' ] }
					onChange={ onChange }
					onDelete={ onDelete }
				/>
			);

			const inputs = queryAllByRole( 'textbox' );

			expect( inputs ).toHaveLength( 2 );

			const [ timeInput, maxTimeInput ] = inputs;

			// The value is the same, so the onChange function shouldnt be called
			fireEvent.blur( timeInput, { target: { value: '1' } } );
			expect( onChange ).toHaveBeenCalledTimes( 0 );

			// The value is different, so the onChange function should be called
			fireEvent.blur( timeInput, { target: { value: '2' } } );
			expect( onChange ).toHaveBeenCalledTimes( 1 );
			// It should update the time property.
			expect( onChange.mock.calls[ 0 ][ 0 ].time ).toBe( 2 );

			onChange.mockClear();

			// The value is the same, so the onChange function shouldnt be called
			fireEvent.blur( maxTimeInput, { target: { value: '32' } } );
			expect( onChange ).toHaveBeenCalledTimes( 0 );

			// The value is different, so the onChange function should be called
			fireEvent.blur( maxTimeInput, { target: { value: '10' } } );
			expect( onChange ).toHaveBeenCalledTimes( 1 );
			// It should update the maxTime property.
			expect( onChange.mock.calls[ 0 ][ 0 ].maxTime ).toBe( 10 );
		} );
	} );
} );
