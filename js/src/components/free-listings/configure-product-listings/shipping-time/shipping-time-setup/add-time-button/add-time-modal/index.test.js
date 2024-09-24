/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AddTimeModal from './index';
import useMCCountryTreeOptions from '.~/components/supported-country-select/useMCCountryTreeOptions';

jest.mock( '.~/components/supported-country-select/useMCCountryTreeOptions' );

describe( 'Add time Modal', () => {
	it( 'The min and max shipping time inputs should be displayed, and both values should be submitted', async () => {
		const onSubmit = jest.fn();

		useMCCountryTreeOptions.mockImplementation( () => {
			return [
				{
					value: 'EU',
					label: 'Europe',
					children: [
						{
							value: 'ES',
							label: 'Spain',
							parent: 'EU',
						},
					],
				},
			];
		} );

		const { getByRole, getAllByRole } = render(
			<AddTimeModal
				countries={ [ 'ES' ] }
				onRequestClose={ jest.fn() }
				onSubmit={ onSubmit }
			/>
		);

		const inputs = getAllByRole( 'textbox' );
		// it should render 2 inputs ( the min and max shipping time inputs )
		expect( inputs ).toHaveLength( 2 );

		const [ minInput, maxInput ] = inputs;

		expect( minInput ).toHaveValue( '' );
		expect( maxInput ).toHaveValue( '' );

		fireEvent.blur( minInput, { target: { value: '2' } } );
		expect( minInput ).toHaveValue( '2' );

		fireEvent.blur( maxInput, { target: { value: '11' } } );
		expect( maxInput ).toHaveValue( '11' );

		const submitButton = getByRole( 'button', {
			name: 'Add shipping time',
		} );

		expect( submitButton ).toBeEnabled();

		fireEvent.click( submitButton );

		await waitFor( () => {
			expect( onSubmit ).toHaveBeenCalledTimes( 1 );
		} );

		expect( onSubmit ).toHaveBeenCalledWith( {
			countries: [ 'ES' ],
			time: 2,
			maxTime: 11,
		} );
	} );
} );
