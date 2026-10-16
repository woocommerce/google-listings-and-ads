/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useCreateGenAIAssets from './useCreateGenAIAssets';
import { useAppDispatch } from '~/data';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';

jest.mock( '@wordpress/api-fetch' );
jest.mock( '~/data' );
jest.mock( '~/hooks/useDispatchCoreNotices' );

/**
 * Builds a fake apiFetch( { parse: false } ) success Response.
 *
 * @param {Object} body Parsed JSON body to resolve `.json()` with.
 * @return {Object} A minimal Response-like object.
 */
const mockResponse = ( body ) => ( {
	status: 200,
	clone: jest.fn().mockReturnThis(),
	json: jest.fn().mockResolvedValue( body ),
} );

/**
 * Builds a fake apiFetch( { parse: false } ) rejection — the raw Response
 * object apiFetch throws when `parse: false` and the request isn't ok.
 *
 * @param {number} status HTTP status code.
 * @param {Object} [body] Parsed JSON error body, if any.
 * @return {Object} A minimal Response-like object.
 */
const mockErrorResponse = ( status, body ) => ( {
	status,
	json: jest.fn().mockResolvedValue( body ?? {} ),
} );

describe( 'useCreateGenAIAssets', () => {
	const createNotice = jest.fn();
	const receiveGenAITextAssets = jest.fn();
	const receiveGenAIMediaAssets = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		useAppDispatch.mockReturnValue( {
			receiveGenAITextAssets,
			receiveGenAIMediaAssets,
		} );
		receiveGenAITextAssets.mockResolvedValue( { data: { headline: [] } } );
		receiveGenAIMediaAssets.mockResolvedValue( {
			data: { marketing_image: [] },
		} );
	} );

	const generate = ( requests ) => {
		const { result } = renderHook( () => useCreateGenAIAssets() );
		return act( () =>
			result.current.generateAssets( 'https://example.com', requests )
		);
	};

	it( 'returns generated assets and shows no notice on success', async () => {
		apiFetch.mockResolvedValueOnce(
			mockResponse( { items: [ { text: 'headline 1' } ] } )
		);

		const returnValue = await generate( [
			{ type: GEN_AI_ASSET_TYPES.TEXT, assetKey: 'headline' },
		] );

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: `${ API_NAMESPACE }/ads/assets/generate-text`,
			} )
		);
		expect( createNotice ).not.toHaveBeenCalled();
		expect( returnValue.erroredTypes ).toEqual( [] );
	} );

	it( 'shows the shared message for a known API error code, regardless of asset type', async () => {
		apiFetch.mockRejectedValueOnce(
			mockErrorResponse( 400, {
				errors: { FINAL_URL_UNSUPPORTED_LANGUAGE: 'unsupported' },
			} )
		);

		const returnValue = await generate( [
			{ type: GEN_AI_ASSET_TYPES.TEXT, assetKey: 'headline' },
		] );

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			"The language on your ad's landing page isn't supported for AI-generated assets."
		);
		expect( returnValue.erroredTypes ).toEqual( [
			GEN_AI_ASSET_TYPES.TEXT,
		] );
	} );

	it( 'silently ignores a 400 with no actionable error code, and does not mark the type as errored', async () => {
		apiFetch.mockRejectedValueOnce( mockErrorResponse( 400, {} ) );

		const returnValue = await generate( [
			{ type: GEN_AI_ASSET_TYPES.TEXT, assetKey: 'headline' },
		] );

		expect( createNotice ).not.toHaveBeenCalled();
		// erroredTypes must stay empty here: texts-editor.js uses it to decide
		// whether to show its own "no texts generated" notice, and this failure
		// mode (URL not eligible) never gets an error notice from this hook.
		expect( returnValue.erroredTypes ).toEqual( [] );
	} );

	it( 'shows the generic per-type message for a non-400 HTTP error', async () => {
		apiFetch.mockRejectedValueOnce( mockErrorResponse( 500 ) );

		const returnValue = await generate( [
			{ type: GEN_AI_ASSET_TYPES.MEDIA, assetKey: 'marketing_image' },
		] );

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			"Google AI isn't able to generate media assets for this page."
		);
		expect( returnValue.erroredTypes ).toEqual( [
			GEN_AI_ASSET_TYPES.MEDIA,
		] );
	} );

	it( 'shows the generic per-type message for a rejection with no HTTP status (e.g. a network error)', async () => {
		apiFetch.mockRejectedValueOnce( new Error( 'network failure' ) );

		const returnValue = await generate( [
			{ type: GEN_AI_ASSET_TYPES.TEXT, assetKey: 'headline' },
		] );

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			"Google AI isn't able to generate text assets for this page."
		);
		expect( returnValue.erroredTypes ).toEqual( [
			GEN_AI_ASSET_TYPES.TEXT,
		] );
	} );

	it( 'dedupes identical generic errors for the same asset type into a single notice', async () => {
		apiFetch
			.mockRejectedValueOnce( mockErrorResponse( 500 ) )
			.mockRejectedValueOnce( mockErrorResponse( 500 ) );

		await generate( [
			{ type: GEN_AI_ASSET_TYPES.TEXT, assetKey: 'headline' },
			{ type: GEN_AI_ASSET_TYPES.TEXT, assetKey: 'description' },
		] );

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'falls back to a generic message rather than showing an empty notice for an unmapped type', async () => {
		apiFetch.mockRejectedValueOnce( mockErrorResponse( 500 ) );

		await generate( [ { type: 'unmapped-type', assetKey: 'headline' } ] );

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			"Google AI isn't able to generate assets for this page."
		);
	} );
} );
