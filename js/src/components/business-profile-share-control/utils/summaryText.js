/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';

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
 * Builds the Google Business Profile text from a title and body text, the same way regardless of
 * which editor they came from. WordPress can store a title with HTML entities (`&amp;` for `&`),
 * so the title is decoded to plain text first.
 *
 * @param {string} title The post's title, as stored or typed.
 * @param {string} content The post's body text.
 * @return {string} The title and content joined by a blank line, cut to the character limit.
 */
export function buildSummary( title, content ) {
	const parts = [ decodeEntities( title ).trim(), content.trim() ].filter(
		Boolean
	);

	return truncateSummary( parts.join( '\n\n' ) );
}
