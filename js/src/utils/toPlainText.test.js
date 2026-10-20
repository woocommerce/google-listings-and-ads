/**
 * External dependencies
 */
import { RichTextData } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import toPlainText from './toPlainText';

describe( 'toPlainText', () => {
	it( 'strips HTML tags, keeping the text', () => {
		expect(
			toPlainText( '<strong>Bold</strong> and <em>italic</em> text.' )
		).toBe( 'Bold and italic text.' );
	} );

	it( 'decodes HTML entities', () => {
		expect( toPlainText( 'Shoes &amp; boots' ) ).toBe( 'Shoes & boots' );
	} );

	it( 'trims surrounding whitespace', () => {
		expect( toPlainText( '<p>\n\tHello\n</p>\n' ) ).toBe( 'Hello' );
	} );

	it( 'returns an empty string for empty HTML', () => {
		expect( toPlainText( '' ) ).toBe( '' );
	} );

	it( 'converts a RichTextData value explicitly, rather than relying on implicit coercion', () => {
		const value = RichTextData.fromHTMLString(
			'<strong>Bold</strong> text.'
		);

		expect( toPlainText( value ) ).toBe( 'Bold text.' );
	} );
} );
