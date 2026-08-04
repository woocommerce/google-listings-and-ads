/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useCallback, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { API_NAMESPACE, REQUEST_ACTIONS } from '~/data/constants';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

const GEN_AI_ERROR_MESSAGES = {
	FINAL_URL_UNSUPPORTED_LANGUAGE: __(
		"The language on your ad's landing page isn't supported for AI-generated assets.",
		'google-listings-and-ads'
	),
};

const GEN_AI_DEFAULT_ERROR_MESSAGES = {
	[ GEN_AI_ASSET_TYPES.TEXT ]: __(
		"Google AI isn't able to generate text assets for this page.",
		'google-listings-and-ads'
	),
	[ GEN_AI_ASSET_TYPES.MEDIA ]: __(
		"Google AI isn't able to generate media assets for this page.",
		'google-listings-and-ads'
	),
};

/**
 * Custom hook to generate Gen AI assets for a given URL and asset requests.
 *
 * @return {Object} An object containing the generateAssets function, isGeneratingAssets boolean, and abortGenerateAssets function.
 */
const useCreateGenAIAssets = () => {
	const [ isGeneratingAssets, setIsGeneratingAssets ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const abortControllerRef = useRef( null );
	const { receiveGenAITextAssets, receiveGenAIMediaAssets } =
		useAppDispatch();

	/**
	 * Aborts any ongoing Gen AI asset generation requests.
	 */
	const abortGenerateAssets = useCallback( () => {
		if ( abortControllerRef.current ) {
			abortControllerRef.current.abort();
		}
	}, [] );

	/**
	 * Processes a single settled promise result from the Gen AI API.
	 * Throws the raw error response for rejected results; returns parsed JSON for fulfilled results.
	 *
	 * @param {Object} result The result object from Promise.allSettled.
	 * @return {Promise<Object>} The parsed JSON data from the response.
	 * @throws {Object} The raw error response if the request was rejected, or a parse error if JSON parsing fails.
	 */
	const processGenAIResponse = useCallback( async ( result ) => {
		if ( result.status === 'rejected' ) {
			throw result.reason;
		}

		const response = result.value;
		try {
			return await response.clone().json();
		} catch ( parseError ) {
			throw parseError;
		}
	}, [] );

	/**
	 * Displays error notices for Gen AI API errors, deduplicating by error code.
	 * Known API error codes (e.g. FINAL_URL_UNSUPPORTED_LANGUAGE) are deduped by code alone — same
	 * message regardless of asset type. Generic errors are deduped per asset type so text and image
	 * failures each show their own specific message.
	 *
	 * @param {Array<{error: Object, type: string}>} errors - Errors with their asset type collected from the generation loop.
	 * @return {Promise<void>}
	 */
	const displayGenAIErrors = useCallback(
		async ( errors ) => {
			const seen = new Set();

			for ( const { error, type } of errors ) {
				let errorCode;

				if ( error?.status === 400 ) {
					try {
						const { errors: apiErrors } = await error.json();
						errorCode = Object.keys( apiErrors || {} )[ 0 ] ?? null;
					} catch ( jsonParseError ) {
						errorCode = null;
					}

					if ( ! errorCode ) {
						continue; // silent — URL not eligible, no actionable code
					}
				} else if ( error?.status ) {
					errorCode = `HTTP_${ error.status }`;
				} else {
					errorCode = 'PARSE_FAILED';
				}

				// Known API codes use errorCode as the dedup key (type-agnostic message).
				// Generic codes include type so each asset type can show its own message.
				const dedupeKey = GEN_AI_ERROR_MESSAGES[ errorCode ]
					? errorCode
					: `${ errorCode }:${ type }`;

				if ( ! seen.has( dedupeKey ) ) {
					seen.add( dedupeKey );
					createNotice(
						'error',
						GEN_AI_ERROR_MESSAGES[ errorCode ] ??
							GEN_AI_DEFAULT_ERROR_MESSAGES[ type ]
					);
				}
			}
		},
		[ createNotice ]
	);

	/**
	 * Generates Gen AI assets based on the provided URL and asset requests.
	 *
	 * @param {string} url - The final URL for which to generate assets.
	 * @param {Array} requests - An array of asset generation requests, each containing a type and an optional assetKey. type can be 'text' or 'media'. assetKey can be 'headline' for text or 'marketing_image' for media, or it can be undefined to fetch all types.
	 * @return {Promise<Object|undefined>} - A promise that resolves to the generated assets data along with an `erroredTypes` array listing which requested types failed (an error notice has already been shown for these), or undefined if no requests are processed.
	 */
	const generateAssets = useCallback(
		async ( url, requests = [] ) => {
			if ( ! url || requests.length === 0 ) {
				return;
			}

			abortControllerRef.current = new AbortController();
			const { signal } = abortControllerRef.current;

			setIsGeneratingAssets( true );

			// Initialize as empty arrays to avoid overwriting multiple requests of same type
			const generatedAssets = {
				[ GEN_AI_ASSET_TYPES.TEXT ]: [],
				[ GEN_AI_ASSET_TYPES.MEDIA ]: [],
			};

			try {
				const promises = requests.map( ( request ) => {
					const isText = request.type === GEN_AI_ASSET_TYPES.TEXT;
					const path = isText
						? `${ API_NAMESPACE }/ads/assets/generate-text`
						: `${ API_NAMESPACE }/ads/assets/generate-images`;

					return apiFetch( {
						path,
						signal,
						method: REQUEST_ACTIONS.POST,
						parse: false,
						data: {
							final_url: url,
							...( request.assetKey
								? { types: [ request.assetKey ] }
								: {} ),
						},
					} );
				} );

				const results = await Promise.allSettled( promises );

				if ( signal.aborted ) {
					return;
				}

				const errors = [];

				for ( let index = 0; index < results.length; index++ ) {
					const { type, assetKey } = requests[ index ];
					let data;

					try {
						data = await processGenAIResponse( results[ index ] );
					} catch ( error ) {
						errors.push( { error, type } );
						continue;
					}

					if ( ! data || ! data.items ) {
						continue;
					}

					if ( type === GEN_AI_ASSET_TYPES.TEXT ) {
						const { data: textData } = await receiveGenAITextAssets(
							url,
							data,
							assetKey
						);

						generatedAssets[ GEN_AI_ASSET_TYPES.TEXT ] = {
							...generatedAssets[ GEN_AI_ASSET_TYPES.TEXT ],
							...textData,
						};
					} else if ( type === GEN_AI_ASSET_TYPES.MEDIA ) {
						const { data: mediaData } =
							await receiveGenAIMediaAssets(
								url,
								data,
								assetKey
							);

						generatedAssets[ GEN_AI_ASSET_TYPES.MEDIA ] = {
							...generatedAssets[ GEN_AI_ASSET_TYPES.MEDIA ],
							...mediaData,
						};
					}
				}

				await displayGenAIErrors( errors );

				return {
					...generatedAssets,
					erroredTypes: errors.map( ( error ) => error.type ),
				};
			} catch ( error ) {
				if ( signal.aborted ) {
					return;
				}

				// Catch unexpected runtime errors
				createNotice(
					'error',
					__(
						'An unexpected error occurred.',
						'google-listings-and-ads'
					)
				);
			} finally {
				setIsGeneratingAssets( false );
			}
		},
		[
			processGenAIResponse,
			displayGenAIErrors,
			receiveGenAITextAssets,
			receiveGenAIMediaAssets,
			createNotice,
		]
	);

	return { generateAssets, isGeneratingAssets, abortGenerateAssets };
};

export default useCreateGenAIAssets;
