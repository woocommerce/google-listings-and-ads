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
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { GEN_AI_ASSET_TYPES } from '~/constants';

jest.mock( '~/hooks/useCreateGenAIAssets' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/data' );

// ProgressBar ships in the @wordpress/components build output but isn't on the module's
// type entry point, so it resolves to undefined under Jest. Stub it for the loading state.
jest.mock( '@wordpress/components', () => {
	const actual = jest.requireActual( '@wordpress/components' );
	const { createElement } = jest.requireActual( '@wordpress/element' );
	return {
		...actual,
		ProgressBar: ( props ) =>
			createElement( 'div', { role: 'progressbar', ...props } ),
	};
} );

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
	let createNotice;
	const displayImageUrl = `proxied:${ sourceImageUrl }`;

	beforeEach( () => {
		jest.clearAllMocks();
		generateAssets = jest.fn();
		abortGenerateAssets = jest.fn();
		replaceGenAIMediaAsset = jest.fn();
		onReplaceImage = jest.fn();
		onRequestClose = jest.fn();
		createNotice = jest.fn();
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

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
				displayImageUrl={ displayImageUrl }
				onReplaceImage={ onReplaceImage }
				onRequestClose={ onRequestClose }
			/>
		);

	const typePrompt = ( value ) =>
		fireEvent.change( screen.getByLabelText( 'Prompt' ), {
			target: { value },
		} );

	it( 'renders the source thumbnail using the given display URL', () => {
		renderModal();

		expect( screen.getByRole( 'presentation' ) ).toHaveAttribute(
			'src',
			displayImageUrl
		);
	} );

	it( 'disables Generate when the prompt is empty', () => {
		renderModal();

		expect(
			screen.getByRole( 'button', { name: 'Generate' } )
		).toBeDisabled();
	} );

	it( 'clamps the prompt to 1500 characters and keeps Generate enabled', () => {
		renderModal();

		typePrompt( 'a'.repeat( 1501 ) );

		expect(
			screen.getByText( '1500/1500 characters' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Generate' } )
		).toBeEnabled();
	} );

	it( 'enables Generate once a non-empty prompt within the limit is entered', () => {
		renderModal();

		typePrompt( 'Add a red hat' );

		expect(
			screen.getByRole( 'button', { name: 'Generate' } )
		).toBeEnabled();
	} );

	it( 'shows the progress state instead of the form and buttons while generating', () => {
		useCreateGenAIAssets.mockReturnValue( {
			generateAssets,
			isGeneratingAssets: true,
			abortGenerateAssets,
		} );

		renderModal();

		expect( screen.getByText( 'Generating asset' ) ).toBeInTheDocument();
		expect( screen.getByRole( 'progressbar' ) ).toBeInTheDocument();
		expect( screen.queryByLabelText( 'Prompt' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Generate' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Cancel' } )
		).not.toBeInTheDocument();
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

	it( 'Closing the modal aborts an in-flight generation, without replacing anything', async () => {
		useCreateGenAIAssets.mockReturnValue( {
			generateAssets,
			isGeneratingAssets: true,
			abortGenerateAssets,
		} );
		const user = userEvent.setup();
		renderModal();

		await user.click( screen.getByRole( 'button', { name: 'Close' } ) );

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
		expect( createNotice ).not.toHaveBeenCalled();
	} );

	it( 'When no image comes back without a reported error, shows a fallback error notice', async () => {
		const user = userEvent.setup();
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: { [ assetKey ]: [] },
			erroredTypes: [],
		} );

		renderModal();
		typePrompt( 'Add a red hat' );
		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Something went wrong while editing the image. Please try again.'
		);
		expect( replaceGenAIMediaAsset ).not.toHaveBeenCalled();
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
		expect( createNotice ).not.toHaveBeenCalled();
	} );
} );
