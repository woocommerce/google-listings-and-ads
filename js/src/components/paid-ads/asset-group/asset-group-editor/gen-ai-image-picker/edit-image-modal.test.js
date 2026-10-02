/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import EditImageModal from './edit-image-modal';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import { useAppDispatch } from '~/data';
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useCreateGenAIAssets' );
jest.mock( '~/data' );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

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

const FALLBACK_ERROR =
	'Something went wrong while editing the image. Please try again.';

// Scope to the visible notice: `Notice` also announces its text in a hidden a11y live region.
const NOTICE_SELECTOR = { selector: '.components-notice__content' };

describe( 'EditImageModal', () => {
	const finalUrl = 'https://example.com';
	const assetKey = 'marketing_image';
	const sourceImageUrl = 'https://example.com/source.png';
	const newImageUrl = 'https://example.com/new.png';
	const displayImageUrl = `proxied:${ sourceImageUrl }`;

	let generateAssets;
	let abortGenerateAssets;
	let replaceGenAIMediaAsset;
	let onReplaceImage;
	let onRequestClose;

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
		expect(
			screen.queryByText( FALLBACK_ERROR, NOTICE_SELECTOR )
		).not.toBeInTheDocument();
	} );

	it( 'When no image comes back without a reported error, shows an inline error', async () => {
		const user = userEvent.setup();
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: { [ assetKey ]: [] },
			erroredTypes: [],
		} );

		renderModal();
		typePrompt( 'Add a red hat' );
		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );

		expect(
			screen.getByText( FALLBACK_ERROR, NOTICE_SELECTOR )
		).toBeInTheDocument();
		expect( replaceGenAIMediaAsset ).not.toHaveBeenCalled();
		expect( onRequestClose ).not.toHaveBeenCalled();
	} );

	it( 'clears the inline error when generating again', async () => {
		const user = userEvent.setup();
		generateAssets
			.mockResolvedValueOnce( {
				[ GEN_AI_ASSET_TYPES.MEDIA ]: { [ assetKey ]: [] },
				erroredTypes: [],
			} )
			.mockResolvedValueOnce( undefined );

		renderModal();
		typePrompt( 'Add a red hat' );
		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );
		expect(
			screen.getByText( FALLBACK_ERROR, NOTICE_SELECTOR )
		).toBeInTheDocument();

		await user.click( screen.getByRole( 'button', { name: 'Generate' } ) );
		expect(
			screen.queryByText( FALLBACK_ERROR, NOTICE_SELECTOR )
		).not.toBeInTheDocument();
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
		expect(
			screen.queryByText( FALLBACK_ERROR, NOTICE_SELECTOR )
		).not.toBeInTheDocument();
		expect( recordGlaEvent ).not.toHaveBeenCalledWith(
			'gla_gen_ai_edit_image_modal_generation_completed',
			expect.anything()
		);
	} );

	describe( 'tracking', () => {
		const prompt = 'Add a red hat';
		const eventProps = {
			asset_key: assetKey,
			prompt_length: prompt.length,
		};

		const getEventNames = () =>
			recordGlaEvent.mock.calls.map( ( [ name ] ) => name );

		it( 'records the shown event on mount', () => {
			renderModal();

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_gen_ai_edit_image_modal_shown',
				{ asset_key: assetKey }
			);
		} );

		it( 'records the close event with the trimmed prompt length on Cancel', async () => {
			const user = userEvent.setup();
			renderModal();

			typePrompt( `  ${ prompt }  ` );
			await user.click(
				screen.getByRole( 'button', { name: 'Cancel' } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_gen_ai_edit_image_modal_close',
				eventProps
			);
		} );

		it( 'records the generate click and the completed event on success', async () => {
			const user = userEvent.setup();
			generateAssets.mockResolvedValue( {
				[ GEN_AI_ASSET_TYPES.MEDIA ]: { [ assetKey ]: [ newImageUrl ] },
				erroredTypes: [],
			} );

			renderModal();
			typePrompt( prompt );
			await user.click(
				screen.getByRole( 'button', { name: 'Generate' } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_gen_ai_edit_image_modal_generate_button_click',
				eventProps
			);
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_gen_ai_edit_image_modal_generation_completed',
				eventProps
			);
			expect( getEventNames() ).not.toContain(
				'gla_gen_ai_edit_image_modal_generation_failed'
			);
		} );

		it.each( [
			[ 'error', [ GEN_AI_ASSET_TYPES.MEDIA ] ],
			[ 'empty', [] ],
		] )(
			'records the failed event with reason "%s" when no image is returned',
			async ( reason, erroredTypes ) => {
				const user = userEvent.setup();
				generateAssets.mockResolvedValue( {
					[ GEN_AI_ASSET_TYPES.MEDIA ]: {},
					erroredTypes,
				} );

				renderModal();
				typePrompt( prompt );
				await user.click(
					screen.getByRole( 'button', { name: 'Generate' } )
				);

				expect( recordGlaEvent ).toHaveBeenCalledWith(
					'gla_gen_ai_edit_image_modal_generation_failed',
					{ ...eventProps, reason }
				);
				expect( getEventNames() ).not.toContain(
					'gla_gen_ai_edit_image_modal_generation_completed'
				);
			}
		);

		it( 'records the failed event with reason "unexpected" when the request resolves to nothing without a cancel', async () => {
			const user = userEvent.setup();
			generateAssets.mockResolvedValue( undefined );

			renderModal();
			typePrompt( prompt );
			await user.click(
				screen.getByRole( 'button', { name: 'Generate' } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_gen_ai_edit_image_modal_generation_failed',
				{ ...eventProps, reason: 'unexpected' }
			);
		} );

		it( 'records no outcome event when the request is cancelled', async () => {
			const user = userEvent.setup();
			let resolveGeneration;
			generateAssets.mockReturnValue(
				new Promise( ( resolve ) => {
					resolveGeneration = resolve;
				} )
			);

			renderModal();
			typePrompt( prompt );
			await user.click(
				screen.getByRole( 'button', { name: 'Generate' } )
			);
			await user.click(
				screen.getByRole( 'button', { name: 'Cancel' } )
			);
			resolveGeneration( undefined );
			await waitFor( () => expect( onRequestClose ).toHaveBeenCalled() );

			expect( getEventNames() ).not.toContain(
				'gla_gen_ai_edit_image_modal_generation_completed'
			);
			expect( getEventNames() ).not.toContain(
				'gla_gen_ai_edit_image_modal_generation_failed'
			);
		} );

		it( 'never includes the prompt text in any event', async () => {
			const user = userEvent.setup();
			generateAssets.mockResolvedValue( {
				[ GEN_AI_ASSET_TYPES.MEDIA ]: { [ assetKey ]: [ newImageUrl ] },
				erroredTypes: [],
			} );

			renderModal();
			typePrompt( prompt );
			await user.click(
				screen.getByRole( 'button', { name: 'Generate' } )
			);

			expect( JSON.stringify( recordGlaEvent.mock.calls ) ).not.toContain(
				prompt
			);
		} );
	} );
} );
