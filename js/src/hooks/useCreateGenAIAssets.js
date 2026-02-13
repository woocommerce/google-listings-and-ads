/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { GEN_AI_ASSET_TYPES } from '~/constants';
import { API_NAMESPACE, REQUEST_ACTIONS } from '~/data/constants';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * Custom hook to generate Gen AI assets for a given URL and asset requests.
 *
 * @return {Array} - An array containing the generateAssets function and a boolean indicating if assets are currently being generated.
 */
const useCreateGenAIAssets = () => {
	const [ isGeneratingAssets, setIsGeneratingAssets ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { receiveGenAITextAssets, receiveGenAIMediaAssets } =
		useAppDispatch();

	/**
	 * Helper function to process Gen AI API responses, handling both success and error cases.
	 *
	 * @param {Object} result - The result object from Promise.allSettled.
	 * @return {Object|null} - The parsed JSON data from the response, or null if there was an error.
	 */
	const processGenAIResponse = useCallback(
		async ( result ) => {
			// Handle rejected promises (Network errors or apiFetch-thrown errors)
			if ( result.status === 'rejected' ) {
				const errorResponse = result.reason;

				// Silently handle 400 errors (URL not eligible for suggestions)
				if ( errorResponse && errorResponse.status === 400 ) {
					return null;
				}

				createNotice(
					'error',
					errorResponse?.statusText ||
						__(
							'Unable to load AI-generated assets suggestions.',
							'google-listings-and-ads'
						)
				);

				return null;
			}

			const response = result.value;
			try {
				const responseClone = response.clone();
				return await responseClone.json();
			} catch ( e ) {
				createNotice(
					'error',
					__(
						'An error occurred while processing AI-generated assets suggestions.',
						'google-listings-and-ads'
					)
				);
				return null;
			}
		},
		[ createNotice ]
	);

	/**
	 * Generates Gen AI assets based on the provided URL and asset requests.
	 *
	 * @param {string} url - The final URL for which to generate assets.
	 * @param {Array} requests - An array of asset generation requests, each containing a type and an optional assetKey. type can be 'text' or 'media'. assetKey can be 'headline' for text or 'marketing_image' for media, or it can be undefined to fetch all types.
	 * @return {Promise<Object|undefined>} - A promise that resolves to the generated assets data, or undefined if no requests are processed.
	 */
	const generateAssets = useCallback(
		async ( url, requests = [] ) => {
			if ( ! url || requests.length === 0 ) {
				return;
			}

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

				for ( let index = 0; index < results.length; index++ ) {
					const { type, assetKey } = requests[ index ];
					const data = await processGenAIResponse( results[ index ] );

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

				return generatedAssets;
			} catch ( error ) {
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
			receiveGenAITextAssets,
			receiveGenAIMediaAssets,
			createNotice,
		]
	);

	return [ generateAssets, isGeneratingAssets ];
};

export default useCreateGenAIAssets;
