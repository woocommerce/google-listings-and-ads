/**
 * Internal dependencies
 */
import { SUMMARY_MAX_LENGTH } from '../constants';

/**
 * Counts the characters in a text the way Google Business Profile does: one per Unicode
 * character, so an emoji counts once.
 *
 * @param {string} text The text to count.
 * @return {number} The number of characters.
 */
export function countSummaryCharacters( text ) {
	return Array.from( text ).length;
}

/**
 * Cuts a text at the Google Business Profile character limit.
 *
 * @param {string} text The text to cut.
 * @return {string} The text, at most `SUMMARY_MAX_LENGTH` characters long.
 */
export function truncateSummary( text ) {
	return Array.from( text ).slice( 0, SUMMARY_MAX_LENGTH ).join( '' );
}

/**
 * Converts an HTML fragment to plain text.
 *
 * @param {string} html The HTML fragment.
 * @return {string} The fragment's text content, trimmed.
 */
export function toPlainText( html ) {
	const fragment = new window.DOMParser().parseFromString(
		html,
		'text/html'
	);

	return ( fragment.body.textContent || '' ).trim();
}

/**
 * Builds the Google Business Profile text from a saved post: its excerpt when the merchant wrote
 * one, otherwise its title followed by the excerpt WordPress derives from the content. The
 * result is plain text, cut at the character limit.
 *
 * @param {Object} post The saved post.
 * @param {string} post.title The post's title.
 * @param {boolean} post.hasManualExcerpt Whether the merchant wrote an excerpt.
 * @param {string} post.renderedExcerpt The post's excerpt as rendered HTML, either the merchant's or the derived one.
 * @return {string} The text to fill the field with.
 */
export default function buildSummaryFromPost( {
	title,
	hasManualExcerpt,
	renderedExcerpt,
} ) {
	const excerpt = toPlainText( renderedExcerpt );

	if ( hasManualExcerpt ) {
		return truncateSummary( excerpt );
	}

	const parts = [ title.trim(), excerpt ].filter( Boolean );

	return truncateSummary( parts.join( '\n' ) );
}
