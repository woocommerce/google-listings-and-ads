/**
 * External dependencies
 */
import { screen, render, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Control from '.~/components/tree-select-control/control';

describe( 'TreeSelectControl - Control Component', () => {
	const onTagsChange = jest.fn().mockName( 'onTagsChange' );
	const ref = {
		current: {
			focus: jest.fn(),
		},
	};

	it( 'Renders the tags and calls onTagsChange when they change', async () => {
		const user = userEvent.setup();
		const { queryByText, queryByLabelText, rerender } = render(
			<Control
				ref={ ref }
				tags={ [ { id: 'es', label: 'Spain' } ] }
				onTagsChange={ onTagsChange }
			/>
		);

		expect( queryByText( 'Spain (1 of 1)' ) ).toBeTruthy();
		await user.click( queryByLabelText( 'Remove Spain' ) );
		expect( onTagsChange ).toHaveBeenCalledTimes( 1 );
		expect( onTagsChange ).toHaveBeenCalledWith( [] );

		rerender(
			<Control
				ref={ ref }
				tags={ [ { id: 'es', label: 'Spain' } ] }
				disabled={ true }
				onTagsChange={ onTagsChange }
			/>
		);

		expect( screen.queryByText( 'Spain (1 of 1)' ) ).toBeTruthy();
		await user.click( screen.queryByLabelText( 'Remove Spain' ) );
		expect( onTagsChange ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Calls onInputChange when typing', async () => {
		const user = userEvent.setup();
		const onInputChange = jest
			.fn()
			.mockName( 'onInputChange' )
			.mockImplementation( ( e ) => e.target.value );
		const { queryByRole } = render(
			<Control ref={ ref } onInputChange={ onInputChange } />
		);

		const input = queryByRole( 'combobox' );
		expect( input ).toBeTruthy();
		expect( input.hasAttribute( 'disabled' ) ).toBeFalsy();
		await user.type( input, 'a' );
		expect( onInputChange ).toHaveBeenCalledTimes( 1 );
		expect( onInputChange ).toHaveNthReturnedWith( 1, 'a' );
		fireEvent.change( input, { target: { value: 'test' } } );
		expect( onInputChange ).toHaveBeenCalledTimes( 2 );
		expect( onInputChange ).toHaveNthReturnedWith( 2, 'test' );
	} );

	it( 'Allows disabled input', async () => {
		const user = userEvent.setup();
		const onInputChange = jest.fn().mockName( 'onInputChange' );
		const { queryByRole } = render(
			<Control disabled={ true } onInputChange={ onInputChange } />
		);

		const input = queryByRole( 'combobox' );
		expect( input ).toBeTruthy();
		expect( input.hasAttribute( 'disabled' ) ).toBeTruthy();
		await user.type( input, 'a' );
		expect( onInputChange ).not.toHaveBeenCalled();
	} );

	it( 'Calls onFocus callback when it is focused', async () => {
		const user = userEvent.setup();
		const onFocus = jest.fn().mockName( 'onFocus' );
		const { queryByRole } = render(
			<Control ref={ ref } onFocus={ onFocus } />
		);
		await user.click( queryByRole( 'combobox' ) );
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
