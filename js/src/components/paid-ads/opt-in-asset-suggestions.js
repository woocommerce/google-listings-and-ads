/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAssetSuggestionsOnOptIn from '~/hooks/useAssetSuggestionsOnOptIn';
import GenAIProgressCard from './gen-ai-progress/gen-ai-progress-card';

/**
 * Starts the Google AI suggestions when the merchant opts in to adding assets, and shows the
 * progress card while they are being generated.
 *
 * It expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @return {JSX.Element|null} The progress card, or null when no suggestions are being fetched.
 */
export default function OptInAssetSuggestions() {
	const { adapter } = useAdaptiveFormContext();

	useAssetSuggestionsOnOptIn();

	if ( ! adapter.isFetchingAssets ) {
		return null;
	}

	return <GenAIProgressCard />;
}
