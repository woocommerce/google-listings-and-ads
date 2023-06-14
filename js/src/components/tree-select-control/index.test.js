/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
/**
 * Internal dependencies
 */
import TreeSelectControl from '.~/components/tree-select-control/index';

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
		value: 'AS',
		label: 'Asia',
	},
	{
		value: 'same-value',
		label: 'Parent - same value',
		children: [ { value: 'same-value', label: 'Child - same value' } ],
	},
];

describe( 'TreeSelectControl Component', () => {
	it( 'Expands and collapse the Tree', () => {
		const { queryByRole } = render(
			<TreeSelectControl options={ options } value={ [] } />
		);

		const control = queryByRole( 'combobox' );
		expect( queryByRole( 'tree' ) ).toBeFalsy();
		fireEvent.click( control );
		expect( queryByRole( 'tree' ) ).toBeTruthy();
	} );

	it( 'Calls onChange property with the selected values', () => {
		const onChange = jest.fn().mockName( 'onChange' );

		const { queryByLabelText, queryByRole, rerender } = render(
			<TreeSelectControl
				options={ options }
				value={ [] }
				onChange={ onChange }
			/>
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		let checkbox = queryByLabelText( 'Europe' );
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES', 'FR', 'IT' ] );

		checkbox = queryByLabelText( 'Asia' );
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'AS' ] );

		rerender(
			<TreeSelectControl
				options={ options }
				value={ [ 'ES' ] }
				onChange={ onChange }
			/>
		);

		checkbox = queryByLabelText( 'Asia' );
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES', 'AS' ] );
	} );

	it( 'Renders the label', () => {
		const { queryByLabelText } = render(
			<TreeSelectControl options={ options } label="Select" />
		);

		expect( queryByLabelText( 'Select' ) ).toBeTruthy();
	} );

	it( 'Renders the All Options', () => {
		const allValues = [ 'ES', 'FR', 'IT', 'AS', 'same-value' ];
		const onChange = jest.fn().mockName( 'onChange' );
		const { queryByLabelText, queryByRole, rerender } = render(
			<TreeSelectControl options={ options } onChange={ onChange } />
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		const allCheckbox = queryByLabelText( 'All' );

		expect( allCheckbox ).toBeTruthy();

		fireEvent.click( allCheckbox );
		expect( onChange ).toHaveBeenCalledWith( allValues );

		rerender(
			<TreeSelectControl
				value={ allValues }
				options={ options }
				onChange={ onChange }
			/>
		);
		fireEvent.click( allCheckbox );
		expect( onChange ).toHaveBeenCalledWith( [] );
	} );

	it( 'Renders the All Options custom Label', () => {
		const { queryByLabelText, queryByRole } = render(
			<TreeSelectControl
				options={ options }
				selectAllLabel="All countries"
			/>
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		const allCheckbox = queryByLabelText( 'All countries' );

		expect( allCheckbox ).toBeTruthy();
	} );

	it( 'Should only allow children to be rendered as selected tags', () => {
		render(
			<TreeSelectControl
				value={ [ 'EU', 'AS', 'FR' ] }
				options={ options }
			/>
		);

		const buttons = screen.getAllByRole( 'button', { name: /remove/i } );

		expect( buttons.length ).toBe( 2 );
		expect( screen.queryByText( 'Europe' ) ).toBeFalsy();
	} );

	it( 'When a parent and a child have the same value and be selected, the rendered tag should be the child', () => {
		render(
			<TreeSelectControl value={ [ 'same-value' ] } options={ options } />
		);

		const buttons = screen.getAllByRole( 'button', { name: /remove/i } );

		expect( buttons.length ).toBe( 1 );
		expect( screen.queryByText( 'Child - same value' ) ).toBeTruthy();
	} );

	it( 'Filters Options on Search', () => {
		const { queryByLabelText, queryByRole } = render(
			<TreeSelectControl options={ options } />
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		expect( queryByLabelText( 'Europe' ) ).toBeTruthy();
		expect( queryByLabelText( 'Asia' ) ).toBeTruthy();

		fireEvent.change( control, { target: { value: 'Asi' } } );

		expect( queryByLabelText( 'Europe' ) ).toBeFalsy(); // none of its children match Asi
		expect( queryByLabelText( 'Asia' ) ).toBeTruthy(); // match Asi

		fireEvent.change( control, { target: { value: 'As' } } ); // doesnt trigger if length < 3

		expect( queryByLabelText( 'Europe' ) ).toBeTruthy();
		expect( queryByLabelText( 'Asia' ) ).toBeTruthy();
		expect( queryByLabelText( 'Spain' ) ).toBeFalsy(); // not expanded

		fireEvent.change( control, { target: { value: 'pain' } } );

		expect( queryByLabelText( 'Europe' ) ).toBeTruthy(); // contains Spain
		expect( queryByLabelText( 'Spain' ) ).toBeTruthy(); // match pain
		expect( queryByLabelText( 'France' ) ).toBeFalsy(); // doesn't match pain
		expect( queryByLabelText( 'Asia' ) ).toBeFalsy(); // doesn't match pain
	} );
} );
