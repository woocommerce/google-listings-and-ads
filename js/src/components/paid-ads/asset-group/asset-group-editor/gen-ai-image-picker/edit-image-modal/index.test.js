/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import EditImageModal from './index';
import useCreateGenAIAssets from '~/hooks/useCreateGenAIAssets';
import { useAppDispatch } from '~/data';
import { recordGlaEvent } from '~/utils/tracks';
import { GEN_AI_ASSET_TYPES } from '~/constants';

jest.mock( '~/hooks/useCreateGenAIAssets' );
jest.mock( '~/data' );

jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

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
	const displayImageUrl = `proxied:${ sourceImageUrl }`;

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
