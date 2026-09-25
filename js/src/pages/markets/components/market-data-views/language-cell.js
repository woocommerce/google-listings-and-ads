/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useAvailableStoreLanguages from '~/hooks/useAvailableStoreLanguages';

/**
 * @typedef {Object} LanguageCellRow
 * @property {string[]} [language] BCP 47 language codes assigned to the market.
 */

/**
 * Renders the human-readable language list for a market row.
 *
 * Labels are resolved in priority order: MC-supported language API label →
 * `Intl.DisplayNames` (localised to the browser locale) → raw BCP 47 code.
 * Multiple languages are joined with ", "; an empty list renders "-".
 *
 * Returns `null` for non-multilingual stores or while the language list is
 * still loading.
 *
 * @param {Object}          props
 * @param {LanguageCellRow} props.market Market data row.
 * @return {string|null} Formatted language label(s), or null.
 */
const LanguageCell = ( { market } ) => {
	const { languages, hasFinishedResolution: hasResolvedLanguages } =
		useAvailableStoreLanguages();

	const languagesByCode = useMemo(
		() =>
			Object.fromEntries(
				( languages || [] ).map( ( language ) => [
					language.code,
					language.label,
				] )
			),
		[ languages ]
	);

	// Intl.DisplayNames is unavailable in Safari < 14; guard before instantiating.
	const displayNames = useMemo(
		() =>
			typeof Intl !== 'undefined' && Intl.DisplayNames
				? new Intl.DisplayNames( [ navigator.language ], {
						type: 'language',
				  } )
				: null,
		[]
	);

	if ( ! glaData.isMultiLingualStore || ! hasResolvedLanguages ) {
		return null;
	}

	// Resolution order: API label → Intl.DisplayNames → raw code.
	const formatLanguageCodes = ( codes ) =>
		codes
			?.map(
				( code ) =>
					languagesByCode[ code ] ?? displayNames?.of( code ) ?? code
			)
			.join( ', ' ) || '-';

	return formatLanguageCodes( market.language );
};

export default LanguageCell;
