/**
 * External dependencies
 */
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Control from '.~/components/tree-select-control/control';

describe( 'TreeSelectControl - Control Component', () => {
	const onTagsChange = jest.fn().mockName( 'onTagsChange' );

	it( 'Renders the tags and calls onTagsChange when they change', () => {
		const { queryByText, queryByLabelText, rerender } = render(
			<Control
				tags={ [ { id: 'es', label: 'Spain' } ] }
				onTagsChange={ onTagsChange }
			/>
		);

		expect( queryByText( 'Spain (1 of 1)' ) ).toBeTruthy();
		userEvent.click( queryByLabelText( 'Remove Spain' ) );
		expect( onTagsChange ).toHaveBeenCalledTimes( 1 );
		expect( onTagsChange ).toHaveBeenCalledWith( [] );

		rerender(
			<Control
				tags={ [ { id: 'es', label: 'Spain' } ] }
				disabled={ true }
				onTagsChange={ onTagsChange }
			/>
		);

		expect( screen.queryByText( 'Spain (1 of 1)' ) ).toBeTruthy();
		userEvent.click( screen.queryByLabelText( 'Remove Spain' ) );
		expect( onTagsChange ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Renders the input', () => {
		const { queryByRole } = render( <Control /> );

		const input = queryByRole( 'combobox' );
		expect( input ).toBeTruthy();
		expect( input.hasAttribute( 'disabled' ) ).toBeFalsy();
		userEvent.type( input, 'test' );
		expect( input.value ).toBe( 'test' );
	} );

	it( 'Allows disabled input', () => {
		const { queryByRole } = render( <Control disabled={ true } /> );

		const input = queryByRole( 'combobox' );
		expect( input ).toBeTruthy();
		expect( input.hasAttribute( 'disabled' ) ).toBeTruthy();
		userEvent.type( input, 'test' );
		expect( input.value ).toBe( '' );
	} );

	it( 'Calls onFocus callback when it is focused', () => {
		const onFocus = jest.fn().mockName( 'onFocus' );
		const { queryByRole } = render( <Control onFocus={ onFocus } /> );
		userEvent.click( queryByRole( 'combobox' ) );
		expect( onFocus ).toHaveBeenCalled();
	} );

	it( 'Renders placeholder when there are no tags and is not expanded', () => {
		const { rerender } = render( <Control placeholder="Select" /> );
		let input = screen.queryByRole( 'combobox' );
		let placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBe( 'Select' );

		rerender(
			<Control
				placeholder="Select"
				tags={ [ { id: 'es', label: 'Spain' } ] }
			/>
		);

		input = screen.queryByRole( 'combobox' );
		placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBeFalsy();

		rerender( <Control placeholder="Select" isExpanded={ true } /> );
		input = screen.queryByRole( 'combobox' );
		placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBeFalsy();
	} );
} );
