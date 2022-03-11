/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Options from '.~/components/tree-select-control/options';

const options = [
	{
		value: 'EU',
		label: 'Europe',
		children: [
			{ value: 'ES', label: 'Spain' },
			{ value: 'FR', label: 'France' },
			{ value: 'IT', label: 'Italy' },
		],
	},
	{
		value: 'NA',
		label: 'North America',
		children: [
			{ value: 'US', label: 'United States' },
			{ value: 'CA', label: 'Canada' },
		],
	},
	{
		value: 'AS',
		label: 'Asia',
	},
];

describe( 'TreeSelectControl - Options Component', () => {
	it( 'Expands and collapses groups', () => {
		const { queryByText } = render( <Options options={ options } /> );

		options.forEach( ( option ) => {
			const optionItem = queryByText( option.label );
			expect( optionItem ).toBeTruthy();
			options.children?.forEach( ( child ) => {
				const childItem = queryByText( child.label );
				expect( childItem ).toBeFalsy();
			} );

			fireEvent.click( optionItem );
			options.children?.forEach( ( child ) => {
				const childItem = queryByText( child.label );
				expect( childItem ).toBeTruthy();
			} );
		} );
	} );
} );
