/**
 * Internal dependencies
 */
import buildSummaryFromLiveClassicContent from './buildSummaryFromLiveClassicContent';

/**
 * Sets up the page's title field and main content editor the way the Classic Editor screen does.
 *
 * @param {Object} options
 * @param {string} [options.title] The title field's value.
 * @param {string} [options.tinymceText] TinyMCE's plain-text content, while the Visual tab is active. Omit to simulate the Text tab, or TinyMCE not being loaded.
 * @param {string} [options.textareaValue] The raw `#content` textarea's value.
 */
function setLiveClassicEditorContent( {
	title = '',
	tinymceText,
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

	if ( undefined !== tinymceText ) {
		window.tinymce = {
			get: ( id ) => {
				return id === 'content'
					? {
							isHidden: () => false,
							getContent: () => tinymceText,
					  }
					: undefined;
			},
		};
	}
}

describe( 'buildSummaryFromLiveClassicContent', () => {
	afterEach( () => {
		document.getElementById( 'title' )?.remove();
		document.getElementById( 'content' )?.remove();
		delete window.tinymce;
	} );

	it( 'joins the title and the live Visual editor text with a blank line', () => {
		setLiveClassicEditorContent( {
			title: 'New summer collection just landed',
			tinymceText: 'Our summer range is now in store.',
		} );

		expect( buildSummaryFromLiveClassicContent() ).toBe(
			'New summer collection just landed\n\nOur summer range is now in store.'
		);
	} );

	it( 'falls back to the raw textarea when the Text tab is active', () => {
		setLiveClassicEditorContent( {
			title: 'New summer collection just landed',
			tinymceText: '',
			textareaValue: '<p>Our summer range is now in store.</p>',
		} );
		window.tinymce.get = () => ( {
			isHidden: () => true,
			getContent: () => '',
		} );

		expect( buildSummaryFromLiveClassicContent() ).toBe(
			'New summer collection just landed\n\nOur summer range is now in store.'
		);
	} );

	it( 'falls back to the raw textarea when TinyMCE has not loaded', () => {
		setLiveClassicEditorContent( {
			title: 'New summer collection just landed',
			textareaValue: '<p>Our summer range is now in store.</p>',
		} );

		expect( buildSummaryFromLiveClassicContent() ).toBe(
			'New summer collection just landed\n\nOur summer range is now in store.'
		);
	} );

	it( 'strips HTML from the raw textarea fallback', () => {
		setLiveClassicEditorContent( {
			title: '',
			textareaValue: '<strong>Bold</strong> and <em>italic</em> text.',
		} );

		expect( buildSummaryFromLiveClassicContent() ).toBe(
			'Bold and italic text.'
		);
	} );

	it( 'strips a shortcode from the raw textarea fallback, keeping its text', () => {
		setLiveClassicEditorContent( {
			title: '',
			textareaValue:
				'[caption id="attachment_12" align="alignnone" width="300"]<img src="shoes.jpg" alt="" /> My caption[/caption]',
		} );

		expect( buildSummaryFromLiveClassicContent() ).toBe( 'My caption' );
	} );

	it( 'returns just the title when the editor has no text', () => {
		setLiveClassicEditorContent( { title: 'Gallery' } );

		expect( buildSummaryFromLiveClassicContent() ).toBe( 'Gallery' );
	} );

	it( 'returns an empty string when there is no title field and no content field', () => {
		expect( buildSummaryFromLiveClassicContent() ).toBe( '' );
	} );
} );
