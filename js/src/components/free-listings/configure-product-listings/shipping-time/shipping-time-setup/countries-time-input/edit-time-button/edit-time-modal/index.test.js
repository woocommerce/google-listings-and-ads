/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import EditTimeModal from './index';
import useMCCountryTreeOptions from '.~/components/supported-country-select/useMCCountryTreeOptions';

jest.mock( '.~/components/supported-country-select/useMCCountryTreeOptions' );

describe( 'Edit time Modal', () => {
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
			<EditTimeModal
				audienceCountries={ [ 'ES' ] }
				time={ { countries: [ 'ES' ], time: 1, maxTime: 9 } }
				onRequestClose={ jest.fn() }
				onSubmit={ onSubmit }
				onDelete={ jest.fn() }
			/>
		);

		const inputs = getAllByRole( 'textbox' );
		// it should render 2 inputs ( the min and max shipping time inputs )
		expect( inputs ).toHaveLength( 2 );

		const [ minInput, maxInput ] = inputs;

		expect( minInput ).toHaveValue( '1' );
		expect( maxInput ).toHaveValue( '9' );

		fireEvent.blur( minInput, { target: { value: '2' } } );
		expect( minInput ).toHaveValue( '2' );

		fireEvent.blur( maxInput, { target: { value: '11' } } );
		expect( maxInput ).toHaveValue( '11' );

		const submitButton = getByRole( 'button', {
			name: 'Update shipping time',
		} );

		expect( submitButton ).toBeEnabled();

		fireEvent.click( submitButton );

		await waitFor( () => {
			expect( onSubmit ).toHaveBeenCalledTimes( 1 );
		} );

		expect( onSubmit ).toHaveBeenCalledWith(
			{ countries: [ 'ES' ], time: 2, maxTime: 11 },
			[]
		);
	} );
} );
