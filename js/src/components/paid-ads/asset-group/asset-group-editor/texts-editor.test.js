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
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGenAITextAssets from '~/hooks/useGenAITextAssets';
import { GEN_AI_ASSET_TYPES } from '~/constants';

jest.mock( '~/hooks/useCreateGenAIAssets' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/hooks/useGenAITextAssets' );

describe( 'TextsEditor', () => {
	let onChange;

	beforeEach( () => {
		onChange = jest.fn().mockName( 'onChange' );
		useGenAITextAssets.mockReturnValue( { assets: [] } );
		useDispatchCoreNotices.mockReturnValue( {
			createNotice: jest.fn().mockName( 'createNotice' ),
		} );
		useCreateGenAIAssets.mockReturnValue( {
			generateAssets: jest.fn(),
			isGeneratingAssets: false,
		} );
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

	describe( 'Generating texts', () => {
		const renderWithGenerateButton = ( initialTexts = [ '' ] ) =>
			render(
				<TextsEditor
					finalUrl="https://example.com"
					assetKey="headline"
					initialTexts={ initialTexts }
					generateButtonSingularText="Generate text"
					generateButtonPluralText="Generate texts"
				/>
			);

		it( 'fills empty slots with the generated texts on success', async () => {
			const user = userEvent.setup();
			useCreateGenAIAssets.mockReturnValue( {
				generateAssets: jest.fn().mockResolvedValue( {
					[ GEN_AI_ASSET_TYPES.TEXT ]: {
						headline: [ 'Generated headline' ],
					},
					erroredTypes: [],
				} ),
				isGeneratingAssets: false,
			} );

			renderWithGenerateButton();
			await user.click(
				screen.getByRole( 'button', { name: 'Generate text' } )
			);

			expect( screen.getByRole( 'textbox' ) ).toHaveValue(
				'Generated headline'
			);
			expect(
				useDispatchCoreNotices().createNotice
			).not.toHaveBeenCalled();
		} );

		it( 'shows an info notice when nothing was generated and the request did not error', async () => {
			const user = userEvent.setup();
			const createNotice = jest.fn();
			useDispatchCoreNotices.mockReturnValue( { createNotice } );
			useCreateGenAIAssets.mockReturnValue( {
				generateAssets: jest.fn().mockResolvedValue( {
					[ GEN_AI_ASSET_TYPES.TEXT ]: {},
					erroredTypes: [],
				} ),
				isGeneratingAssets: false,
			} );

			renderWithGenerateButton();
			await user.click(
				screen.getByRole( 'button', { name: 'Generate text' } )
			);

			expect( createNotice ).toHaveBeenCalledWith(
				'info',
				'No texts were generated. Please try again.'
			);
		} );

		it( 'does not show the generic info notice when generateAssets already showed a specific error notice', async () => {
			const user = userEvent.setup();
			const createNotice = jest.fn();
			useDispatchCoreNotices.mockReturnValue( { createNotice } );
			useCreateGenAIAssets.mockReturnValue( {
				// generateAssets() itself already called createNotice for this
				// failure — texts-editor must not pile a second notice on top.
				generateAssets: jest.fn().mockResolvedValue( {
					[ GEN_AI_ASSET_TYPES.TEXT ]: {},
					erroredTypes: [ GEN_AI_ASSET_TYPES.TEXT ],
				} ),
				isGeneratingAssets: false,
			} );

			renderWithGenerateButton();
			await user.click(
				screen.getByRole( 'button', { name: 'Generate text' } )
			);

			expect( createNotice ).not.toHaveBeenCalled();
		} );

		it( 'shows an error notice when generateAssets throws unexpectedly', async () => {
			const user = userEvent.setup();
			const createNotice = jest.fn();
			useDispatchCoreNotices.mockReturnValue( { createNotice } );
			useCreateGenAIAssets.mockReturnValue( {
				generateAssets: jest
					.fn()
					.mockRejectedValue( new Error( 'boom' ) ),
				isGeneratingAssets: false,
			} );

			renderWithGenerateButton();
			await user.click(
				screen.getByRole( 'button', { name: 'Generate text' } )
			);

			expect( createNotice ).toHaveBeenCalledWith(
				'error',
				'Something went wrong while generating texts. Please try again.'
			);
		} );
	} );
} );
