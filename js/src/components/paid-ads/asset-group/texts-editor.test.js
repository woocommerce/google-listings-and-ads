/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import TextsEditor from './texts-editor';

describe( 'TextsEditor', () => {
	let onChange;

	beforeEach( () => {
		onChange = jest.fn().mockName( 'onChange' );
	} );

	it( 'Should render the children', () => {
		render( <TextsEditor>Children</TextsEditor> );

		expect( screen.getByText( 'Children' ) ).toBeInTheDocument();
	} );

	it( 'Should render addButtonText to the add button', () => {
		render( <TextsEditor addButtonText="Add headline" /> );
		const addButton = screen.getByRole( 'button', { name: 'Add text' } );

		expect( addButton ).toHaveTextContent( 'Add headline' );
	} );

	it( 'Should set placeholder to inputs', () => {
		render(
			<TextsEditor minNumberOfTexts={ 2 } placeholder="Enter headline" />
		);
		const inputs = screen.getAllByRole( 'textbox' );

		expect( inputs[ 0 ] ).toHaveAttribute(
			'placeholder',
			'Enter headline'
		);
		expect( inputs[ 1 ] ).toHaveAttribute(
			'placeholder',
			'Enter headline'
		);
	} );

	it( 'Should use `initialTexts` as the initial texts', () => {
		const initialTexts = [ 'Text 1', 'Text 2' ];
		render( <TextsEditor initialTexts={ initialTexts } /> );
		const inputs = screen.getAllByRole( 'textbox' );

		expect( inputs ).toHaveLength( 2 );
		expect( inputs[ 0 ] ).toHaveValue( initialTexts[ 0 ] );
		expect( inputs[ 1 ] ).toHaveValue( initialTexts[ 1 ] );
	} );

	it( 'When `minNumberOfTexts` is specified, it should prefill empty strings as initial texts to supplement the shortage parts of `initialTexts` or `texts`', () => {
		const initialTexts = [ 'Text 1' ];
		const { rerender } = render(
			<TextsEditor
				initialTexts={ initialTexts }
				minNumberOfTexts={ 2 }
				onChange={ onChange }
			/>
		);
		let inputs = screen.getAllByRole( 'textbox' );

		expect( inputs ).toHaveLength( 2 );
		expect( inputs[ 0 ] ).toHaveValue( initialTexts[ 0 ] );
		expect( inputs[ 1 ] ).toHaveValue( '' );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange ).toHaveBeenCalledWith( [ ...initialTexts, '' ] );

		rerender(
			<TextsEditor
				initialTexts={ initialTexts }
				minNumberOfTexts={ 3 }
				onChange={ onChange }
			/>
		);
		inputs = screen.getAllByRole( 'textbox' );

		expect( inputs ).toHaveLength( 3 );
		expect( inputs[ 0 ] ).toHaveValue( initialTexts[ 0 ] );
		expect( inputs[ 1 ] ).toHaveValue( '' );
		expect( inputs[ 2 ] ).toHaveValue( '' );
		expect( onChange ).toHaveBeenCalledTimes( 2 );
		expect( onChange ).toHaveBeenCalledWith( [ ...initialTexts, '', '' ] );
	} );

	it( 'Inputs with sequence numbers larger than `minNumberOfTexts` should be accompanied by a delete button', () => {
		const initialTexts = [ 'Text 1', 'Text 2', 'Text 3', 'Text 4' ];
		render(
			<TextsEditor initialTexts={ initialTexts } minNumberOfTexts={ 2 } />
		);
		const inputs = screen.getAllByRole( 'textbox' );
		const deleteButtons = screen.getAllByRole( 'button', {
			name: 'Remove text',
		} );

		expect( inputs ).toHaveLength( 4 );
		expect( deleteButtons ).toHaveLength( 2 );
		expect(
			inputs[ 2 ]
				.closest( '.gla-texts-editor__text-item' )
				.querySelector( 'button' )
		).toBe( deleteButtons[ 0 ] );
		expect(
			inputs[ 3 ]
				.closest( '.gla-texts-editor__text-item' )
				.querySelector( 'button' )
		).toBe( deleteButtons[ 1 ] );
	} );

	it( 'When the number of texts reaches `maxNumberOfTexts`, it should disable the add button and vice versa', async () => {
		const user = userEvent.setup();
		const initialTexts = [ 'Text 1', 'Text 2' ];
		render(
			<TextsEditor initialTexts={ initialTexts } maxNumberOfTexts={ 3 } />
		);
		const addButton = screen.getByRole( 'button', { name: 'Add text' } );

		expect( addButton ).toBeEnabled();

		await user.click( addButton );

		expect( addButton ).toBeDisabled();

		await user.click(
			screen.getAllByRole( 'button', { name: 'Remove text' } ).at( -1 )
		);

		expect( addButton ).toBeEnabled();
	} );

	it( 'When the `minNumberOfTexts` and `maxNumberOfTexts` are the same and greater than 1, it should hide the add button`', () => {
		const { rerender } = render( <TextsEditor /> );
		const addButton = screen.getByRole( 'button', { name: 'Add text' } );

		expect( addButton ).toBeVisible();

		rerender( <TextsEditor minNumberOfTexts={ 1 } /> );

		expect( addButton ).toBeVisible();

		rerender( <TextsEditor maxNumberOfTexts={ 1 } /> );

		expect( addButton ).toBeVisible();

		rerender(
			<TextsEditor minNumberOfTexts={ 1 } maxNumberOfTexts={ 5 } />
		);

		expect( addButton ).toBeVisible();

		rerender(
			<TextsEditor minNumberOfTexts={ 1 } maxNumberOfTexts={ 1 } />
		);

		expect( addButton ).not.toBeVisible();

		rerender(
			<TextsEditor minNumberOfTexts={ 5 } maxNumberOfTexts={ 5 } />
		);

		expect( addButton ).not.toBeVisible();
	} );

	it( 'When the length of `initialTexts` or `texts` is greater than `maxNumberOfTexts`, it should truncate the excess', () => {
		const initialTexts = [ 'Text 1', 'Text 2', 'Text 3' ];
		const { rerender } = render(
			<TextsEditor
				initialTexts={ initialTexts }
				maxNumberOfTexts={ 2 }
				onChange={ onChange }
			/>
		);

		expect( screen.getAllByRole( 'textbox' ) ).toHaveLength( 2 );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange ).toHaveBeenCalledWith( initialTexts.slice( 0, 2 ) );

		rerender(
			<TextsEditor
				texts={ initialTexts }
				maxNumberOfTexts={ 1 }
				onChange={ onChange }
			/>
		);

		expect( screen.getAllByRole( 'textbox' ) ).toHaveLength( 1 );
		expect( onChange ).toHaveBeenCalledTimes( 2 );
		expect( onChange ).toHaveBeenCalledWith( initialTexts.slice( 0, 1 ) );
	} );

	it( 'When `maxCharacterCounts` is an array, it should be applied to each input fields in order of index', () => {
		render(
			<TextsEditor
				minNumberOfTexts={ 2 }
				maxCharacterCounts={ [ 10, 20 ] }
			/>
		);
		const labels = screen.getAllByText( /0\/\d+ characters/ );

		expect( labels ).toHaveLength( 2 );
		expect( labels[ 0 ] ).toHaveTextContent( '0/10 characters' );
		expect( labels[ 1 ] ).toHaveTextContent( '0/20 characters' );
	} );

	it( 'When `maxCharacterCounts` is a number, it should be applied to each input fields', () => {
		render(
			<TextsEditor minNumberOfTexts={ 2 } maxCharacterCounts={ 10 } />
		);
		const labels = screen.getAllByText( /0\/\d+ characters/ );

		expect( labels ).toHaveLength( 2 );
		expect( labels[ 0 ] ).toHaveTextContent( '0/10 characters' );
		expect( labels[ 1 ] ).toHaveTextContent( '0/10 characters' );
	} );

	it( 'When typing on the input field, the count of characters should be updated accordingly', async () => {
		const user = userEvent.setup();

		render(
			<TextsEditor minNumberOfTexts={ 1 } maxCharacterCounts={ 10 } />
		);
		const input = screen.getByRole( 'textbox' );
		const label = screen.getByText( /\d+\/10 characters/ );

		await user.type( input, 'Hello' );

		expect( label ).toHaveTextContent( '5/10 characters' );

		await user.type( input, ' World!' );

		expect( label ).toHaveTextContent( '12/10 characters' );
	} );

	it( 'When the texts are changed, should trigger `onChange` callback function with the updated texts', async () => {
		const user = userEvent.setup();

		render( <TextsEditor minNumberOfTexts={ 1 } onChange={ onChange } /> );

		await user.type( screen.getByRole( 'textbox' ), 'Hello' );

		expect( onChange ).toHaveBeenCalledWith( [ 'Hello' ] );

		await user.click( screen.getByRole( 'button', { name: 'Add text' } ) );

		expect( onChange ).toHaveBeenCalledWith( [ 'Hello', '' ] );

		await user.type( screen.getAllByRole( 'textbox' )[ 1 ], 'World' );

		expect( onChange ).toHaveBeenCalledWith( [ 'Hello', 'World' ] );

		await user.click(
			screen.getByRole( 'button', { name: 'Remove text' } )
		);

		expect( onChange ).toHaveBeenCalledWith( [ 'Hello' ] );
	} );

	it( 'Should trim the texts for the `onChange` callback function', async () => {
		const user = userEvent.setup();

		render( <TextsEditor minNumberOfTexts={ 1 } onChange={ onChange } /> );

		await user.type( screen.getByRole( 'textbox' ), ' Hello ' );

		expect( onChange ).toHaveBeenCalledWith( [ 'Hello' ] );
	} );
} );
