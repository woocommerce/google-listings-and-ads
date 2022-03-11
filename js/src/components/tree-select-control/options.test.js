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

	it( 'Partially selects groups', () => {
		const { queryByText } = render(
			<Options options={ options } value={ [ 'ES' ] } />
		);

		const partiallyCheckedOption = queryByText( 'Europe' );
		const unCheckedOption = queryByText( 'North America' );

		expect( partiallyCheckedOption ).toBeTruthy();
		expect( unCheckedOption ).toBeTruthy();

		const partiallyCheckedOptionWrapper = partiallyCheckedOption.closest(
			'.woocommerce-tree-select-control__option'
		);
		const unCheckedOptionWrapper = unCheckedOption.closest(
			'.woocommerce-tree-select-control__option'
		);

		expect( partiallyCheckedOptionWrapper ).toBeTruthy();
		expect( unCheckedOptionWrapper ).toBeTruthy();

		expect(
			partiallyCheckedOptionWrapper.classList.contains(
				'is-partially-checked'
			)
		).toBeTruthy();

		expect(
			unCheckedOptionWrapper.classList.contains( 'is-partially-checked' )
		).toBeFalsy();
	} );
} );
