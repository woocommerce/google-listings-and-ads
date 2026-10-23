/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import BusinessProfileShareMetaBox from './business-profile-share-meta-box';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

const location = {
	title: 'Trailmark Athletics',
	address: '456 Riverside Ave, San Francisco, 94107',
};

const savedPost = {
	isShareEnabled: false,
	summary: '',
	isShared: false,
	searchUrl: '',
};

/**
 * Sets up the page's title field and main content editor the way the Classic Editor screen does,
 * outside this meta box, so "Generate from post" has something to read live.
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

/**
 * Renders the meta box inside a form, as the Classic Editor does.
 *
 * @param {Object} [overrides] Saved post values to override.
 * @return {HTMLFormElement} The form.
 */
function renderMetaBox( overrides = {} ) {
	const { container } = render(
		<form>
			<BusinessProfileShareMetaBox
				location={ location }
				post={ { ...savedPost, ...overrides } }
				shareFieldName="gla_business_profile_share_share"
				summaryFieldName="gla_business_profile_share_summary"
			/>
		</form>
	);

	return container.querySelector( 'form' );
}

/**
 * Reads a form field's value the way the browser would submit it.
 *
 * @param {HTMLFormElement} form The form.
 * @param {string} name The field name.
 * @return {string|null} The submitted value.
 */
function getSubmittedValue( form, name ) {
	return new window.FormData( form ).get( name );
}

describe( 'BusinessProfileShareMetaBox', () => {
	beforeEach( () => {
		recordGlaEvent.mockClear();
	} );

	afterEach( () => {
		document.getElementById( 'title' )?.remove();
		document.getElementById( 'content' )?.remove();
		delete window.tinymce;
	} );

	it( 'shows the toggle off by default, naming the connected location, with no field', () => {
		const form = renderMetaBox();

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).not.toBeChecked();
		expect(
			screen.getByText(
				'Share to Trailmark Athletics as an update when you publish.'
			)
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'textbox', { name: /^Summary/ } )
		).not.toBeInTheDocument();
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_share' )
		).toBe( '' );
	} );

	it( 'posts the share choice with the form once the toggle is on', async () => {
		const user = userEvent.setup();
		const form = renderMetaBox();

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		);

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toBeInTheDocument();
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_share' )
		).toBe( '1' );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_share_toggle_click',
			expect.objectContaining( {
				context: 'business-profile-share-meta-box',
			} )
		);
	} );

	it( 'shows the saved share choice and text', () => {
		const form = renderMetaBox( {
			isShareEnabled: true,
			summary: 'My text',
		} );

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toHaveValue( 'My text' );
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_summary' )
		).toBe( 'My text' );
	} );

	it( 'fills the field from the live Visual editor content and posts it with the form', async () => {
		const user = userEvent.setup();
		setLiveClassicEditorContent( {
			title: 'New Shoes Coming Soon',
			tinymceText:
				'Our spring footwear collection lands in store next Friday.',
		} );
		const form = renderMetaBox( { isShareEnabled: true } );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		const generated =
			'New Shoes Coming Soon\n\nOur spring footwear collection lands in store next Friday.';

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toHaveValue( generated );
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_summary' )
		).toBe( generated );
	} );

	it( 'falls back to the raw content field on the Text tab', async () => {
		const user = userEvent.setup();
		setLiveClassicEditorContent( {
			title: 'New Shoes Coming Soon',
			textareaValue: '<p>Reserve your pair online.</p>',
		} );
		renderMetaBox( { isShareEnabled: true } );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toHaveValue( 'New Shoes Coming Soon\n\nReserve your pair online.' );
	} );

	it( 'asks before replacing existing text, and keeps it until confirmed', async () => {
		const user = userEvent.setup();
		const form = renderMetaBox( {
			isShareEnabled: true,
			summary: 'My own text',
		} );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Replace your text?' } )
		).toBeInTheDocument();
		// The open dialog hides the rest of the page from assistive technology, so the text is
		// checked through the form field it's posted with.
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_summary' )
		).toBe( 'My own text' );
	} );

	it( 'enables the controls even before the post has ever been saved', () => {
		renderMetaBox( { isShareEnabled: true } );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).toBeEnabled();
		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toBeEnabled();
		expect(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		).toBeEnabled();
	} );

	it( 'warns about updates and links to the post on a shared post', () => {
		renderMetaBox( {
			isShared: true,
			searchUrl: 'https://local.google.com/place?id=1&use=posts',
		} );

		expect(
			screen.getByText(
				'Updating this post on Google sends it back for review. If Google rejects the update, the post is hidden from Search and Maps.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'View on Google Business Profile (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://local.google.com/place?id=1&use=posts'
		);
	} );

	it( "falls back to the location's address when it has no name", () => {
		render(
			<BusinessProfileShareMetaBox
				location={ { title: '', address: location.address } }
				post={ savedPost }
				shareFieldName="share"
				summaryFieldName="summary"
			/>
		);

		expect(
			screen.getByText(
				'Share to 456 Riverside Ave, San Francisco, 94107 as an update when you publish.'
			)
		).toBeInTheDocument();
	} );
} );
