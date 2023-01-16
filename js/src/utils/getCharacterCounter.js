const counterMap = new Map();

/**
 * @function
 * @name CharacterCounter
 * @param {string} string The string to be counted characters.
 */

/**
 * Returns a function that uses the same character counting rule as the Google Ads admin pages.
 *
 * @return {CharacterCounter} Character counter function using the same character counting rule as the Google Ads admin pages
 */
export function getCharacterCounterOfGoogleAds() {
	const oneCountRules = [
		// Unicode blocks:
		// - Basic Latin
		// - Latin-1 Supplement
		// - Latin Extended-A
		// - Latin Extended-B
		// - IPA Extensions
		// - Spacing Modifier Letters
		// - Combining Diacritical Marks
		// - Greek and Coptic
		// - Cyrillic (Part of. The full range is \u0400 - \u04FF)
		/[\u0000-\u04F9]/,

		// Unicode blocks:
		// - Latin Extended Additional
		// - Greek Extended
		// - General Punctuation
		// - Superscripts and Subscripts
		// - Currency Symbols
		/[\u1E00-\u20BF]/,

		// Unicode block: Halfwidth and Fullwidth Forms (Part of halfwidth ranges. The full range is \uFF00 - \uFFEF)
		// This check includes the halfwidth character ranges except for the "Halfwidth symbol variants".
		// - Halfwidth CJK punctuation
		// - Halfwidth Katakana variants
		// - Halfwidth Hangul variants
		// Ref: https://unicode.org/charts/PDF/UFF00.pdf
		/[\uFF61-\uFFDC]/,

		// Unicode block: Thai
		/[\u0E00-\u0E7F]/,

		// Unicode block: Letterlike Symbols (Part of. The full range is \u2100 - \u214F)
		/[\u2100-\u213A]/,

		// Unicode block: Arabic
		/[\u0600-\u06FF]/,

		// Unicode block: Arabic Supplement
		/[\u0750-\u077F]/,

		// Unicode block: Arabic Presentation Forms-A
		/[\uFB50-\uFDFF]/,

		// Unicode block: Arabic Presentation Forms-B
		/[\uFE70-\uFEFF]/,

		// Unicode block: Hebrew (Part of. The full range is \u0590 - \u05FF)
		/[\u05D0-\u05EA]/,

		// Unicode block: Hebrew Punctuation: Maqaf, Geresh, and Gershayim
		/\u05BE|\u05F3|\u05F4/,
	];

	// Some of the vowel diacritics, virama and accent marks in Devanagari letters
	// are considered 0 char count as they don't affect the visual width of a char.
	// - Various signs
	// - Dependent vowel signs
	// - Virama
	// - Vedic tone marks
	// - Accent marks
	// - Dependent vowel sign
	// - Dependent vowel signs for Kashmiri
	// - Additional vowels for Sanskrit
	//
	// Ref: https://unicode.org/charts/nameslist/n_0900.html
	//
	// The readability is better than applying the formatting here.
	/* eslint-disable prettier/prettier */
	// prettier-ignore
	const zeroWidthSet = new Set( [
		'\u0900', '\u0901', '\u0902', '\u093A', '\u093C',
		'\u0941', '\u0942', '\u0943', '\u0944', '\u0945',
		'\u0946', '\u0947', '\u0948', '\u094D', '\u0951',
		'\u0952', '\u0953', '\u0954', '\u0955', '\u0956',
		'\u0957', '\u0962', '\u0963',
	] );
	/* eslint-enable prettier/prettier */

	function countChar( char ) {
		if ( oneCountRules.some( ( rule ) => rule.test( char ) ) ) {
			return 1;
		}

		// Unicode blocks:
		// - Devanagari
		// - Bengali
		// - Gurmukhi
		// - Gujarati
		// - Oriya
		// - Tamil
		// - Telugu
		// - Kannada
		// - Malayalam
		if ( /[\u0900-\u0D7F]/.test( char ) ) {
			return zeroWidthSet.has( char ) ? 0 : 1;
		}

		return 2;
	}

	return function ( string ) {
		return string
			.split( '' )
			.reduce( ( sum, char ) => sum + countChar( char ), 0 );
	};
}

counterMap.set( 'google-ads', getCharacterCounterOfGoogleAds() );

/**
 * Gets the specified kind of character counter function.
 *
 * @param {'google-ads'} kind Kind of character counter.
 *
 * @return {CharacterCounter} The specified kind of character counter function.
 * @throws Will throw an error if the given `kind` is unknown.
 */
export default function getCharacterCounter( kind ) {
	if ( counterMap.has( kind ) ) {
		return counterMap.get( kind );
	}

	throw new Error(
		`The given \`kind\` of character counter is an unknown kind: ${ kind }`
	);
}
