/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GenerateWithPromptModal from './index';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import { GEN_AI_ASSET_TYPES } from '~/constants';

jest.mock( '~/hooks/useCreateGenAIAssets', () =>
	jest.fn().mockName( 'useCreateGenAIAssets' )
);

describe( 'GenerateWithPromptModal', () => {
	const finalUrl = 'https://example.com';
	const assetKey = 'marketing_image';
	const MAX = 1500;

	let generateAssets;
	let abortGenerateAssets;
	let onAddImages;
	let onRequestClose;

	const renderModal = ( { isGeneratingAssets = false } = {} ) => {
		useCreateGenAIAssets.mockReturnValue( {
			generateAssets,
			isGeneratingAssets,
			abortGenerateAssets,
		} );

		return render(
			<GenerateWithPromptModal
				finalUrl={ finalUrl }
				assetKey={ assetKey }
				onAddImages={ onAddImages }
				onRequestClose={ onRequestClose }
			/>
		);
	};

	const getTextarea = () => screen.getByRole( 'textbox' );
	const getGenerateButton = () =>
		screen.getByRole( 'button', { name: 'Generate' } );
	const getCancelButton = () =>
		screen.getByRole( 'button', { name: 'Cancel' } );

	const typeValue = ( value ) =>
		fireEvent.change( getTextarea(), { target: { value } } );

	beforeEach( () => {
		generateAssets = jest.fn().mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: {},
			erroredTypes: [],
		} );
		abortGenerateAssets = jest.fn();
		onAddImages = jest.fn();
		onRequestClose = jest.fn();
	} );

	it( 'renders the guiding copy and the character counter starting at zero', () => {
		renderModal();

		expect(
			screen.getByRole( 'heading', { name: 'Generate a new image' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				"Describe the direction and style you'd like to generate."
			)
		).toBeInTheDocument();
		expect( screen.getByText( '0/1500 characters' ) ).toBeInTheDocument();
	} );

	it( 'updates the counter with the raw character length', () => {
		renderModal();

		typeValue( 'hello' );

		expect( screen.getByText( '5/1500 characters' ) ).toBeInTheDocument();
	} );

	it( 'disables Generate when the field is empty or whitespace-only', () => {
		renderModal();

		expect( getGenerateButton() ).toBeDisabled();

		typeValue( '   ' );

		expect( getGenerateButton() ).toBeDisabled();
	} );

	it( 'clamps the input to the 1500-char limit and keeps Generate enabled', () => {
		renderModal();

		typeValue( 'a'.repeat( MAX ) );
		expect(
			screen.getByText( '1500/1500 characters' )
		).toBeInTheDocument();
		expect( getGenerateButton() ).toBeEnabled();

		typeValue( 'a'.repeat( MAX + 1 ) );
		expect(
			screen.getByText( '1500/1500 characters' )
		).toBeInTheDocument();
		expect( getTextarea() ).toHaveValue( 'a'.repeat( MAX ) );
		expect( getGenerateButton() ).toBeEnabled();
	} );

	it( 'shows a loading state on Generate while a request is in flight', () => {
		renderModal( { isGeneratingAssets: true } );

		typeValue( 'a photorealistic sneaker' );

		expect( getGenerateButton() ).toBeDisabled();
	} );

	it( 'generates in freeform mode, appends the new image, and closes on success', async () => {
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: {
				[ assetKey ]: [ 'https://image/new' ],
			},
			erroredTypes: [],
		} );

		renderModal();

		typeValue( 'a photorealistic sneaker' );
		fireEvent.click( getGenerateButton() );

		await waitFor( () => expect( onRequestClose ).toHaveBeenCalled() );

		expect( generateAssets ).toHaveBeenCalledWith( finalUrl, [
			{
				type: GEN_AI_ASSET_TYPES.MEDIA,
				assetKey,
				prompt: 'a photorealistic sneaker',
			},
		] );
		expect( onAddImages ).toHaveBeenCalledWith( [ 'https://image/new' ] );
	} );

	it( 'surfaces an error state and keeps the modal open on failure', async () => {
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: {},
			erroredTypes: [ GEN_AI_ASSET_TYPES.MEDIA ],
		} );

		renderModal();

		typeValue( 'a photorealistic sneaker' );
		fireEvent.click( getGenerateButton() );

		await waitFor( () =>
			expect(
				screen.getAllByText(
					'Something went wrong while generating the image. Please try again.'
				).length
			).toBeGreaterThan( 0 )
		);

		expect( onAddImages ).not.toHaveBeenCalled();
		expect( onRequestClose ).not.toHaveBeenCalled();
	} );

	it( 'aborts the in-flight request and closes when Cancel is clicked', () => {
		renderModal( { isGeneratingAssets: true } );

		fireEvent.click( getCancelButton() );

		expect( abortGenerateAssets ).toHaveBeenCalled();
		expect( onRequestClose ).toHaveBeenCalled();
	} );
} );
