/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import TreeSelectControl from '.~/components/tree-select-control/index';

describe( 'TreeSelectControl Component', () => {
	it( 'Renders', () => {
		const { queryByRole } = render(
			<TreeSelectControl
				options={ [
					{
						id: 'EU',
						name: 'Europe',
						children: [
							{ id: 'ES', name: 'Spain' },
							{ id: 'FR', name: 'France' },
							{ id: 'IT', name: 'Italy' },
						],
					},
				] }
			/>
		);
		expect( queryByRole( 'combobox' ) ).toBeTruthy();
	} );

	it( "Doesn't render without options", () => {
		const { queryByRole } = render( <TreeSelectControl /> );
		expect( queryByRole( 'combobox' ) ).toBeFalsy();
	} );

	it( 'Renders the provided options', () => {
		const options = [
			{
				id: 'EU',
				name: 'Europe',
				children: [
					{ id: 'ES', name: 'Spain' },
					{ id: 'FR', name: 'France' },
					{ id: 'IT', name: 'Italy' },
				],
			},
		];

		const { queryByLabelText } = render(
			<TreeSelectControl options={ options } />
		);

		options.forEach( ( { children } ) => {
			children.forEach( ( item ) => {
				expect( queryByLabelText( item.name ) ).toBeTruthy();
			} );
		} );
	} );

	it( 'Renders with the selected values', () => {
		const options = [
			{
				id: 'EU',
				name: 'Europe',
				children: [
					{ id: 'ES', name: 'Spain' },
					{ id: 'FR', name: 'France' },
					{ id: 'IT', name: 'Italy' },
				],
			},
		];

		const selectedValues = [ 'ES' ];

		const { queryByLabelText } = render(
			<TreeSelectControl options={ options } value={ selectedValues } />
		);

		options.forEach( ( { children } ) => {
			children.forEach( ( item ) => {
				const checkbox = queryByLabelText( item.name );
				expect( checkbox.checked ).toEqual(
					selectedValues.includes( item.id )
				);
			} );
		} );
	} );

	it( 'Can expand and collapse', async () => {
		const options = [
			{
				id: 'EU',
				name: 'Europe',
				children: [
					{ id: 'ES', name: 'Spain' },
					{ id: 'FR', name: 'France' },
					{ id: 'IT', name: 'Italy' },
				],
			},
		];

		const { queryByRole } = render(
			<TreeSelectControl options={ options } placeholder="Select" />
		);

		const control = queryByRole( 'combobox' );
		expect( queryByRole( 'listbox' ) ).toBeFalsy();
		fireEvent.click( control );
		expect( queryByRole( 'listbox' ) ).toBeTruthy();
	} );

	it( 'Calls onChange property with the selected values', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		const options = [
			{
				id: 'EU',
				name: 'Europe',
				children: [
					{ id: 'ES', name: 'Spain' },
					{ id: 'FR', name: 'France' },
					{ id: 'IT', name: 'Italy' },
				],
			},
			{
				id: 'AS',
				name: 'Asia',
				children: [
					{ id: 'JP', name: 'Japan' },
					{ id: 'CH', name: 'China' },
				],
			},
		];

		const { rerender, queryByLabelText } = render(
			<TreeSelectControl
				options={ options }
				value={ [] }
				onChange={ onChange }
			/>
		);

		let checkbox = queryByLabelText( 'Spain' );

		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES' ] );

		rerender(
			<TreeSelectControl
				options={ options }
				value={ [ 'ES' ] }
				onChange={ onChange }
			/>
		);

		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledTimes( 2 );
		expect( onChange ).toHaveBeenCalledWith( [] );

		rerender(
			<TreeSelectControl
				options={ options }
				value={ [ 'JP' ] }
				onChange={ onChange }
			/>
		);

		checkbox = queryByLabelText( 'Asia' );
		expect( checkbox.checked ).toBeFalsy();
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledTimes( 3 );
		expect( onChange ).toHaveBeenCalledWith( [ 'JP', 'CH' ] );

		rerender(
			<TreeSelectControl
				options={ options }
				value={ [ 'JP', 'CH' ] }
				onChange={ onChange }
			/>
		);

		expect( checkbox.checked ).toBeTruthy();
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledTimes( 4 );
		expect( onChange ).toHaveBeenCalledWith( [] );
	} );

	it( 'Renders the label', () => {
		const options = [
			{
				id: 'EU',
				name: 'Europe',
				children: [
					{ id: 'ES', name: 'Spain' },
					{ id: 'FR', name: 'France' },
					{ id: 'IT', name: 'Italy' },
				],
			},
		];

		const { queryByLabelText } = render(
			<TreeSelectControl options={ options } label="Select" />
		);

		expect( queryByLabelText( 'Select' ) ).toBeTruthy();
	} );

	it( 'Show tags', () => {} );
	it( 'Disabled state show tags but does not allow to change', () => {} );
} );
