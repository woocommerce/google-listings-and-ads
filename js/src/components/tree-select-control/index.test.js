/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
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
		const onChange = jest.fn().mockName( 'onChange' );
		const { queryByLabelText, queryByRole, rerender } = render(
			<TreeSelectControl options={ options } onChange={ onChange } />
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		const allCheckbox = queryByLabelText( 'All' );

		expect( allCheckbox ).toBeTruthy();

		fireEvent.click( allCheckbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES', 'FR', 'IT', 'AS' ] );

		rerender(
			<TreeSelectControl
				value={ [ 'ES', 'FR', 'IT', 'AS' ] }
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
} );
