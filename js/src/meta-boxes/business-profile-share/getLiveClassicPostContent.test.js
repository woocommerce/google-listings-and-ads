/**
 * Internal dependencies
 */
import getLiveClassicPostContent from './getLiveClassicPostContent';

/**
 * Sets up the page's title field and main content editor the way the Classic Editor screen does.
 *
 * @param {Object} options
 * @param {string} [options.title] The title field's value.
 * @param {string} [options.tinymceHtml] The Visual tab's current HTML, while it's active. Omit to simulate the Text tab, or TinyMCE not being loaded.
 * @param {string} [options.textareaValue] The raw `#content` textarea's value.
 */
function setLiveClassicEditorContent( {
	title = '',
	tinymceHtml,
	textareaValue = '',
} = {} ) {
	const titleField = document.createElement( 'input' );
	titleField.id = 'title';
	titleField.value = title;
	document.body.appendChild( titleField );

	const textarea = document.createElement( 'textarea' );
	textarea.id = 'content';
	textarea.value = textareaValue;
	document.body.appendChild( textarea );

	if ( undefined !== tinymceHtml ) {
		window.tinymce = {
			get: ( id ) => {
				return id === 'content'
					? {
							isHidden: () => false,
							getContent: () => tinymceHtml,
						}
					: undefined;
			},
		};
	}
}

describe( 'getLiveClassicPostContent', () => {
	afterEach( () => {
		document.getElementById( 'title' )?.remove();
		document.getElementById( 'content' )?.remove();
		delete window.tinymce;
	} );

	it( 'returns the title and the live Visual editor text separately', () => {
		setLiveClassicEditorContent( {
			title: 'New summer collection just landed',
			tinymceHtml: '<p>Our summer range is now in store.</p>',
		} );

		expect( getLiveClassicPostContent() ).toEqual( {
			title: 'New summer collection just landed',
			body: 'Our summer range is now in store.',
		} );
	} );

	it( 'separates every block with a blank line, including a heading immediately before a preformatted block', () => {
		setLiveClassicEditorContent( {
			title: 'Test After Refactor',
			tinymceHtml:
				'<p>This is a test</p><h2>This is a heading</h2><pre>This is some preformatted text...</pre>',
		} );

		expect( getLiveClassicPostContent() ).toEqual( {
			title: 'Test After Refactor',
			body: 'This is a test\n\nThis is a heading\n\nThis is some preformatted text...',
		} );
	} );

	it( 'reads a list as one entry per item', () => {
		setLiveClassicEditorContent( {
			title: '',
			tinymceHtml: '<ul><li>First item</li><li>Second item</li></ul>',
		} );

		expect( getLiveClassicPostContent().body ).toBe(
			'First item\n\nSecond item'
		);
	} );

	it( 'strips a shortcode the Visual tab serializes back from a view plugin (e.g. a gallery)', () => {
		setLiveClassicEditorContent( {
			title: '',
			tinymceHtml:
				'<p>Check out our new arrivals.</p>[gallery ids="4,5,6"]',
		} );

		expect( getLiveClassicPostContent().body ).toBe(
			'Check out our new arrivals.'
		);
	} );

	it( 'strips a captioned image shortcode the Visual tab serializes back, keeping the caption text', () => {
		setLiveClassicEditorContent( {
			title: '',
			tinymceHtml:
				'[caption id="attachment_12" align="alignnone" width="300"]<img src="shoes.jpg" alt="" /> My caption[/caption]',
		} );

		expect( getLiveClassicPostContent().body ).toBe( 'My caption' );
	} );

	it( 'reads a table one cell at a time, instead of running every cell together', () => {
		setLiveClassicEditorContent( {
			title: '',
			tinymceHtml:
				'<table><tbody><tr><td>Mon</td><td>9–5</td></tr></tbody></table>',
		} );

		expect( getLiveClassicPostContent().body ).toBe( 'Mon\n\n9–5' );
	} );

	it( 'reads the paragraphs nested inside a div separately, instead of running them together', () => {
		setLiveClassicEditorContent( {
			title: '',
			tinymceHtml:
				'<div><p>First paragraph.</p><p>Second paragraph.</p></div>',
		} );

		expect( getLiveClassicPostContent().body ).toBe(
			'First paragraph.\n\nSecond paragraph.'
		);
	} );

	it( 'reads a blockquote by the paragraph nested inside it', () => {
		setLiveClassicEditorContent( {
			title: '',
			tinymceHtml: '<blockquote><p>A happy customer.</p></blockquote>',
		} );

		expect( getLiveClassicPostContent().body ).toBe( 'A happy customer.' );
	} );

	it( 'reads a figure by its caption, skipping the image', () => {
		setLiveClassicEditorContent( {
			title: '',
			tinymceHtml:
				'<figure><img src="shoes.jpg" alt="" /><figcaption>New arrivals.</figcaption></figure>',
		} );

		expect( getLiveClassicPostContent().body ).toBe( 'New arrivals.' );
	} );

	it( 'falls back to the raw textarea when the Text tab is active', () => {
		setLiveClassicEditorContent( {
			title: 'New summer collection just landed',
			tinymceHtml: '',
			textareaValue: '<p>Our summer range is now in store.</p>',
		} );
		window.tinymce.get = () => ( {
			isHidden: () => true,
			getContent: () => '',
		} );

		expect( getLiveClassicPostContent() ).toEqual( {
			title: 'New summer collection just landed',
			body: 'Our summer range is now in store.',
		} );
	} );

	it( 'falls back to the raw textarea when TinyMCE has not loaded', () => {
		setLiveClassicEditorContent( {
			title: 'New summer collection just landed',
			textareaValue: '<p>Our summer range is now in store.</p>',
		} );

		expect( getLiveClassicPostContent() ).toEqual( {
			title: 'New summer collection just landed',
			body: 'Our summer range is now in store.',
		} );
	} );

	it( 'strips HTML from the raw textarea fallback, keeping unwrapped inline text as one paragraph', () => {
		setLiveClassicEditorContent( {
			title: '',
			textareaValue: '<strong>Bold</strong> and <em>italic</em> text.',
		} );

		expect( getLiveClassicPostContent().body ).toBe(
			'Bold and italic text.'
		);
	} );

	it( 'strips a shortcode from the raw textarea fallback, keeping its text', () => {
		setLiveClassicEditorContent( {
			title: '',
			textareaValue:
				'[caption id="attachment_12" align="alignnone" width="300"]<img src="shoes.jpg" alt="" /> My caption[/caption]',
		} );

		expect( getLiveClassicPostContent().body ).toBe( 'My caption' );
	} );

	it( 'returns an empty body when the editor has no text', () => {
		setLiveClassicEditorContent( { title: 'Gallery' } );

		expect( getLiveClassicPostContent() ).toEqual( {
			title: 'Gallery',
			body: '',
		} );
	} );

	it( "returns an empty title and body when there's no title field and no content field", () => {
		expect( getLiveClassicPostContent() ).toEqual( {
			title: '',
			body: '',
		} );
	} );
} );
