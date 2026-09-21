/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import EditImageModal from './index';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import { useAppDispatch } from '~/data';
import { GEN_AI_ASSET_TYPES } from '~/constants';

jest.mock( '~/hooks/useCreateGenAIAssets' );
jest.mock( '~/data' );

describe( 'EditImageModal', () => {
	const finalUrl = 'https://example.com';
	const assetKey = 'marketing_image';
	const sourceImageUrl = 'https://example.com/source.png';
	const newImageUrl = 'https://example.com/new.png';

	let generateAssets;
	let abortGenerateAssets;
	let replaceGenAIMediaAsset;
	let onReplaceImage;
	let onRequestClose;
	const getDisplayImageUrl = jest.fn( ( url ) => `proxied:${ url }` );

	beforeEach( () => {
		jest.clearAllMocks();
		generateAssets = jest.fn();
		abortGenerateAssets = jest.fn();
		replaceGenAIMediaAsset = jest.fn();
		onReplaceImage = jest.fn();
		onRequestClose = jest.fn();

		useCreateGenAIAssets.mockReturnValue( {
			generateAssets,
			isGeneratingAssets: false,
			abortGenerateAssets,
		} );
		useAppDispatch.mockReturnValue( { replaceGenAIMediaAsset } );
	} );

	const renderModal = () =>
		render(
			<EditImageModal
				finalUrl={ finalUrl }
				assetKey={ assetKey }
				sourceImageUrl={ sourceImageUrl }
				getDisplayImageUrl={ getDisplayImageUrl }
				onReplaceImage={ onReplaceImage }
				onRequestClose={ onRequestClose }
			/>
		);

	const typePrompt = ( value ) =>
		fireEvent.change( screen.getByLabelText( 'Prompt' ), {
			target: { value },
		} );

	it( 'renders the source thumbnail using the proxied display URL', () => {
		renderModal();

		expect( screen.getByRole( 'presentation' ) ).toHaveAttribute(
			'src',
			`proxied:${ sourceImageUrl }`
		);
	} );

	it( 'disables Generate when the prompt is empty', () => {
		renderModal();

		expect(
			screen.getByRole( 'button', { name: 'Generate' } )
		).toBeDisabled();
	} );

	it( 'disables Generate and shows the over-limit count when the prompt exceeds 1500 characters', () => {
		renderModal();

		typePrompt( 'a'.repeat( 1501 ) );

		expect(
			screen.getByText( '1501/1500 characters' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Generate' } )
		).toBeDisabled();
	} );

	it( 'enables Generate once a non-empty prompt within the limit is entered', () => {
		renderModal();

		typePrompt( 'Add a red hat' );

		expect(
			screen.getByRole( 'button', { name: 'Generate' } )
		).toBeEnabled();
	} );

	it( 'shows a loading state while generating: disables Generate but keeps Cancel enabled', () => {
		useCreateGenAIAssets.mockReturnValue( {
			generateAssets,
			isGeneratingAssets: true,
			abortGenerateAssets,
		} );

		renderModal();

		expect(
			screen.getByRole( 'button', { name: 'Generate' } )
		).toBeDisabled();
		expect(
			screen.getByRole( 'button', { name: 'Cancel' } )
		).toBeEnabled();
	} );

	it( 'Cancel aborts generation and closes without replacing anything', async () => {
		const user = userEvent.setup();
		renderModal();

		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( abortGenerateAssets ).toHaveBeenCalledTimes( 1 );
		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
		expect( replaceGenAIMediaAsset ).not.toHaveBeenCalled();
		expect( onReplaceImage ).not.toHaveBeenCalled();
		expect( generateAssets ).not.toHaveBeenCalled();
	} );

	it( 'Cancel aborts an in-flight generation and closes, without replacing anything', async () => {
		useCreateGenAIAssets.mockReturnValue( {
			generateAssets,
			isGeneratingAssets: true,
			abortGenerateAssets,
		} );
		const user = userEvent.setup();
		renderModal();

		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( abortGenerateAssets ).toHaveBeenCalledTimes( 1 );
		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
		expect( replaceGenAIMediaAsset ).not.toHaveBeenCalled();
		expect( onReplaceImage ).not.toHaveBeenCalled();
	} );

	it( 'On Generate, calls generateAssets in recontext mode with the prompt and source image', async () => {
		const user = userEvent.setup();
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: { [ assetKey ]: [ newImageUrl ] },
			erroredTypes: [],
		} );

		renderModal();
		typePrompt( 'Add a red hat' );
		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );

		expect( generateAssets ).toHaveBeenCalledWith( finalUrl, [
			{
				type: GEN_AI_ASSET_TYPES.MEDIA,
				assetKey,
				prompt: 'Add a red hat',
				sourceImageUrl,
			},
		] );
	} );

	it( 'On success, replaces the asset in the store and notifies the caller, then closes', async () => {
		const user = userEvent.setup();
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: { [ assetKey ]: [ newImageUrl ] },
			erroredTypes: [],
		} );

		renderModal();
		typePrompt( 'Add a red hat' );
		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );

		expect( replaceGenAIMediaAsset ).toHaveBeenCalledWith(
			finalUrl,
			assetKey,
			sourceImageUrl,
			newImageUrl
		);
		expect( onReplaceImage ).toHaveBeenCalledWith(
			sourceImageUrl,
			newImageUrl
		);
		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'On error, does not replace anything and leaves the modal open', async () => {
		const user = userEvent.setup();
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: {},
			erroredTypes: [ GEN_AI_ASSET_TYPES.MEDIA ],
		} );

		renderModal();
		typePrompt( 'Add a red hat' );
		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );

		expect( replaceGenAIMediaAsset ).not.toHaveBeenCalled();
		expect( onReplaceImage ).not.toHaveBeenCalled();
		expect( onRequestClose ).not.toHaveBeenCalled();
	} );

	it( 'When generateAssets resolves to nothing (e.g. aborted), does not replace or close', async () => {
		const user = userEvent.setup();
		generateAssets.mockResolvedValue( undefined );

		renderModal();
		typePrompt( 'Add a red hat' );
		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );

		expect( replaceGenAIMediaAsset ).not.toHaveBeenCalled();
		expect( onReplaceImage ).not.toHaveBeenCalled();
		expect( onRequestClose ).not.toHaveBeenCalled();
	} );
} );
