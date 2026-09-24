/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GenerateWithPromptModal from './index';
import { recordGlaEvent } from '~/utils/tracks';
import { GEN_AI_ASSET_TYPES } from '~/constants';

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

describe( 'GenerateWithPromptModal', () => {
	const finalUrl = 'https://example.com';
	const assetKey = 'marketing_image';
	const MAX = 1500;

	let generateAssets;
	let abortGenerateAssets;
	let onRequestClose;

	const renderModal = ( { isGeneratingAssets = false } = {} ) =>
		render(
			<GenerateWithPromptModal
				finalUrl={ finalUrl }
				assetKey={ assetKey }
				generateAssets={ generateAssets }
				isGeneratingAssets={ isGeneratingAssets }
				abortGenerateAssets={ abortGenerateAssets }
				onRequestClose={ onRequestClose }
			/>
		);

	const getTextarea = () => screen.getByRole( 'textbox' );
	const getGenerateButton = () =>
		screen.getByRole( 'button', { name: 'Generate' } );

	const typeValue = ( value ) =>
		fireEvent.change( getTextarea(), { target: { value } } );

	beforeEach( () => {
		generateAssets = jest.fn().mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: {},
			erroredTypes: [],
		} );
		abortGenerateAssets = jest.fn();
		onRequestClose = jest.fn();
		recordGlaEvent.mockClear();
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

	it( 'swaps the form for the generating state while a request is in flight', () => {
		renderModal( { isGeneratingAssets: true } );

		expect(
			screen.getByRole( 'heading', { name: 'Generating asset' } )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'textbox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Generate' } )
		).not.toBeInTheDocument();
	} );

	it( 'generates in freeform mode and closes on success', async () => {
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
	} );

	it( 'keeps the modal open without an inline error when the hook already shows a notice', async () => {
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: {},
			erroredTypes: [ GEN_AI_ASSET_TYPES.MEDIA ],
		} );

		renderModal();

		typeValue( 'a photorealistic sneaker' );
		fireEvent.click( getGenerateButton() );

		await waitFor( () => expect( generateAssets ).toHaveBeenCalled() );

		expect(
			screen.queryByText(
				'Something went wrong while generating the image. Please try again.'
			)
		).not.toBeInTheDocument();
		expect( onRequestClose ).not.toHaveBeenCalled();
	} );

	it( 'shows an inline error and keeps the modal open when no image is generated without a notice', async () => {
		renderModal();

		typeValue( 'a photorealistic sneaker' );
		fireEvent.click( getGenerateButton() );

		// Notice renders the message twice: visibly and in its screen-reader announcement.
		expect(
			(
				await screen.findAllByText(
					'Something went wrong while generating the image. Please try again.'
				)
			).length
		).toBeGreaterThan( 0 );
		expect( onRequestClose ).not.toHaveBeenCalled();
	} );

	it( 'aborts the in-flight request and closes when the modal is dismissed', () => {
		renderModal( { isGeneratingAssets: true } );

		fireEvent.click( screen.getByRole( 'button', { name: 'Close' } ) );

		expect( abortGenerateAssets ).toHaveBeenCalled();
		expect( onRequestClose ).toHaveBeenCalled();
	} );

	it( 'records the shown event on mount', () => {
		renderModal();

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_generate_with_prompt_modal_shown',
			{ asset_key: assetKey }
		);
	} );

	it( 'records the close event when the modal is dismissed', () => {
		renderModal();

		fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_generate_with_prompt_modal_close',
			{ asset_key: assetKey }
		);
	} );

	it( 'records the generate click and the completed event with the number of images generated', async () => {
		generateAssets.mockResolvedValue( {
			[ GEN_AI_ASSET_TYPES.MEDIA ]: {
				[ assetKey ]: [ 'https://image/new' ],
			},
			erroredTypes: [],
		} );

		renderModal();

		typeValue( 'a photorealistic sneaker' );
		fireEvent.click( getGenerateButton() );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_gen_ai_generate_with_prompt_modal_generate_button_click',
			{ asset_key: assetKey }
		);

		await waitFor( () =>
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_gen_ai_generate_with_prompt_modal_generation_completed',
				{ asset_key: assetKey, generated: 1 }
			)
		);
	} );
} );
