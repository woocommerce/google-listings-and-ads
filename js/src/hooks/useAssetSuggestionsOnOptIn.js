/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { ADVERTISING_SOURCE, ASSET_FORM_KEY } from '~/constants';

/**
 * Starts Google AI asset suggestions from the store's homepage the first time the merchant opts in
 * to adding text, images and videos to a campaign that advertises their products.
 *
 * It requests suggestions once. It does nothing again after an untick and a tick, or while a request
 * is still running.
 *
 * It expects a `CampaignAssetsForm` to exist in its parents.
 */
export default function useAssetSuggestionsOnOptIn() {
	const hasRequestedSuggestionsRef = useRef( false );
	const { values, adapter } = useAdaptiveFormContext();
	const { includeCreativeAssets, advertisingSource } = values;
	const { fetchAssets, resetAssetGroup, isFetchingAssets } = adapter;
	const finalUrl = adapter.baseAssetGroup[ ASSET_FORM_KEY.FINAL_URL ];

	useEffect( () => {
		if (
			! includeCreativeAssets ||
			advertisingSource !== ADVERTISING_SOURCE.PRODUCT ||
			finalUrl ||
			isFetchingAssets ||
			hasRequestedSuggestionsRef.current
		) {
			return;
		}

		hasRequestedSuggestionsRef.current = true;

		async function loadSuggestions() {
			// `id` is a required parameter, but it is ignored when loading homepage assets.
			const suggestedAssets = await fetchAssets( 0, 'homepage' );
			resetAssetGroup( suggestedAssets );
		}

		loadSuggestions();
	}, [
		includeCreativeAssets,
		advertisingSource,
		finalUrl,
		isFetchingAssets,
		fetchAssets,
		resetAssetGroup,
	] );
}
