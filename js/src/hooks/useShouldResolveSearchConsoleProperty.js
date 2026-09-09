/**
 * Internal dependencies
 */
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from './useGoogleSearchConsoleAccount';
import useGoogleSearchConsoleProperties from './useGoogleSearchConsoleProperties';

const { INCOMPLETE } = GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

/**
 * @typedef {Object} ShouldResolveSearchConsolePropertyData
 * @property {boolean} hasDetermined Whether the checks to decide on auto-resolution are finished.
 * @property {boolean} shouldCreate Whether a new property should be auto-created, because the
 *   merchant has no candidate properties at all.
 * @property {boolean} shouldAutoSelect Whether the one candidate property should be auto-selected.
 * @property {string} [autoSelectSiteUrl] The candidate's `siteUrl`, when `shouldAutoSelect` is `true`.
 */

/**
 * Determines whether the frontend should automatically resolve a Google Search Console property
 * on the merchant's behalf, mirroring {@see useShouldCreateAdsAccount}'s shape.
 *
 * Reads the same candidate list a merchant would see in the property-selection UI
 * ({@see useGoogleSearchConsoleProperties}) and applies the same rule the backend used to apply
 * silently: zero candidates auto-creates one, exactly one candidate auto-selects it, and a
 * genuine multi-match is left alone for the merchant to choose between.
 *
 * @return {ShouldResolveSearchConsolePropertyData} The auto-resolve decision.
 */
const useShouldResolveSearchConsoleProperty = () => {
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleSearchConsoleAccount();
	const isIncomplete = account?.status === INCOMPLETE;

	const { properties, hasFinishedResolution: hasResolvedProperties } =
		useGoogleSearchConsoleProperties( { skip: ! isIncomplete } );

	if ( ! hasResolvedAccount || ( isIncomplete && ! hasResolvedProperties ) ) {
		return {
			hasDetermined: false,
			shouldCreate: false,
			shouldAutoSelect: false,
			autoSelectSiteUrl: undefined,
		};
	}

	if ( ! isIncomplete ) {
		return {
			hasDetermined: true,
			shouldCreate: false,
			shouldAutoSelect: false,
			autoSelectSiteUrl: undefined,
		};
	}

	const matchCount = properties?.length ?? 0;

	return {
		hasDetermined: true,
		shouldCreate: matchCount === 0,
		shouldAutoSelect: matchCount === 1,
		autoSelectSiteUrl:
			matchCount === 1 ? properties[ 0 ].siteUrl : undefined,
	};
};

export default useShouldResolveSearchConsoleProperty;
