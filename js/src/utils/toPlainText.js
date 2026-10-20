/**
 * Converts an HTML fragment to plain text. The fragment is explicitly converted to a string
 * first, so this also accepts a `RichTextData` instance -- the type a rich-text block attribute
 * (for example a paragraph or heading's `content`) holds since WordPress 6.9 -- rather than
 * relying on `DOMParser` to coerce it implicitly.
 *
 * @param {string|{toString: Function}} html The HTML fragment.
 * @return {string} The fragment's text content, trimmed.
 */
export default function toPlainText( html ) {
	const fragment = new window.DOMParser().parseFromString(
		String( html ),
		'text/html'
	);

	return ( fragment.body.textContent || '' ).trim();
}
