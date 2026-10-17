/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CountriesTimeInput from './';
import { useAdaptiveFormInputProps } from '~/components/adaptive-form';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormInputProps: jest.fn(),
} ) );

function setupMocks( { time = 1, maxTime = 32 } = {} ) {
	const onMinTimeChange = jest.fn();
	const onMaxTimeChange = jest.fn();

	useAdaptiveFormInputProps.mockImplementation( ( key ) => {
		if ( key === 'flat_shipping_min_time' ) {
			return { value: time, onChange: onMinTimeChange };
		}
		return { value: maxTime, onChange: onMaxTimeChange };
	} );

	return { onMinTimeChange, onMaxTimeChange };
}

describe( 'CountriesTimeInput', () => {
	it( 'renders two inputs with values from form context', () => {
		setupMocks( { time: 3, maxTime: 7 } );

		const { getAllByRole } = render( <CountriesTimeInput /> );
		const inputs = getAllByRole( 'textbox' );

		expect( inputs ).toHaveLength( 2 );
		expect( inputs[ 0 ] ).toHaveValue( '3' );
		expect( inputs[ 1 ] ).toHaveValue( '7' );
	} );

	it( 'shows an empty input for time value of 0 (Same Day placeholder)', () => {
		setupMocks( { time: 0, maxTime: 5 } );

		const { getAllByRole } = render( <CountriesTimeInput /> );
		const [ minInput ] = getAllByRole( 'textbox' );

		expect( minInput ).toHaveValue( '' );
	} );

	describe( 'handleBlur', () => {
		it( 'calls onMinTimeChange with the new value when min input blurs with a different value', () => {
			const { onMinTimeChange } = setupMocks( { time: 1 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const [ minInput ] = getAllByRole( 'textbox' );

			fireEvent.blur( minInput, { target: { value: '5' } } );

			expect( onMinTimeChange ).toHaveBeenCalledTimes( 1 );
			expect( onMinTimeChange ).toHaveBeenCalledWith( 5 );
		} );

		it( 'does not call onMinTimeChange when min input blurs with the same value', () => {
			const { onMinTimeChange } = setupMocks( { time: 1 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const [ minInput ] = getAllByRole( 'textbox' );

			fireEvent.blur( minInput, { target: { value: '1' } } );

			expect( onMinTimeChange ).not.toHaveBeenCalled();
		} );

		it( 'calls onMaxTimeChange with the new value when max input blurs with a different value', () => {
			const { onMaxTimeChange } = setupMocks( { maxTime: 32 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const [ , maxInput ] = getAllByRole( 'textbox' );

			fireEvent.blur( maxInput, { target: { value: '10' } } );

			expect( onMaxTimeChange ).toHaveBeenCalledTimes( 1 );
			expect( onMaxTimeChange ).toHaveBeenCalledWith( 10 );
		} );

		it( 'does not call onMaxTimeChange when max input blurs with the same value', () => {
			const { onMaxTimeChange } = setupMocks( { maxTime: 32 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const [ , maxInput ] = getAllByRole( 'textbox' );

			fireEvent.blur( maxInput, { target: { value: '32' } } );

			expect( onMaxTimeChange ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'handleIncrement (stepper buttons)', () => {
		it( 'calls onMinTimeChange when the min increment button is pressed', async () => {
			const { onMinTimeChange } = setupMocks( { time: 1 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const incrementButtons = getAllByRole( 'button', {
				name: 'Increment',
			} );

			fireEvent.mouseDown( incrementButtons[ 0 ] );

			await waitFor( () => {
				expect( onMinTimeChange ).toHaveBeenCalledTimes( 1 );
			} );
			expect( onMinTimeChange ).toHaveBeenCalledWith( 2 );
		} );

		it( 'calls onMaxTimeChange when the max increment button is pressed', async () => {
			const { onMaxTimeChange } = setupMocks( { maxTime: 32 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const incrementButtons = getAllByRole( 'button', {
				name: 'Increment',
			} );

			fireEvent.mouseDown( incrementButtons[ 1 ] );

			await waitFor( () => {
				expect( onMaxTimeChange ).toHaveBeenCalledTimes( 1 );
			} );
			expect( onMaxTimeChange ).toHaveBeenCalledWith( 33 );
		} );

		it( 'calls onMinTimeChange when the min decrement button is pressed', async () => {
			const { onMinTimeChange } = setupMocks( { time: 3 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const decrementButtons = getAllByRole( 'button', {
				name: 'Decrement',
			} );

			fireEvent.mouseDown( decrementButtons[ 0 ] );

			await waitFor( () => {
				expect( onMinTimeChange ).toHaveBeenCalledTimes( 1 );
			} );
			expect( onMinTimeChange ).toHaveBeenCalledWith( 2 );
		} );

		it( 'calls onMaxTimeChange when the max decrement button is pressed', async () => {
			const { onMaxTimeChange } = setupMocks( { maxTime: 32 } );
			const { getAllByRole } = render( <CountriesTimeInput /> );
			const decrementButtons = getAllByRole( 'button', {
				name: 'Decrement',
			} );

			fireEvent.mouseDown( decrementButtons[ 1 ] );

			await waitFor( () => {
				expect( onMaxTimeChange ).toHaveBeenCalledTimes( 1 );
			} );
			expect( onMaxTimeChange ).toHaveBeenCalledWith( 31 );
		} );
	} );
} );
