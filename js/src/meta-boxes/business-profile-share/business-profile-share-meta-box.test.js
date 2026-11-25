/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { doAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import BusinessProfileShareMetaBox from './business-profile-share-meta-box';
import { FIRST_CHECK_DELAY } from '~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus';
import useBusinessProfilePostCheck from '~/components/business-profile-share-control/hooks/useBusinessProfilePostCheck';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );
jest.mock(
	'~/components/business-profile-share-control/hooks/useBusinessProfilePostCheck',
	() => jest.fn().mockName( 'useBusinessProfilePostCheck' )
);

const location = {
	title: 'Trailmark Athletics',
	address: '456 Riverside Ave, San Francisco, 94107',
};

const savedPost = {
	postId: 123,
	isShareEnabled: false,
	summary: '',
	shareStatus: { is_shared: false, search_url: '' },
	syncOutcome: null,
};

/**
 * Runs a Heartbeat request the way `heartbeat.js` does, and returns the data the request sent.
 *
 * @param {Object} [response] The Heartbeat response.
 * @return {Object} The data sent with the request.
 */
function beat( response = {} ) {
	const data = {};

	act( () => {
		doAction( 'heartbeat.send', data );
		doAction( 'heartbeat.tick', response );
	} );

	return data;
}

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
		useBusinessProfilePostCheck.mockReturnValue( {
			isChecking: false,
			checkedPost: null,
			checkFailure: null,
		} );
		window.wp = {
			heartbeat: { connectNow: jest.fn().mockName( 'connectNow' ) },
		};
	} );

	afterEach( () => {
		document.getElementById( 'title' )?.remove();
		document.getElementById( 'content' )?.remove();
		delete window.tinymce;
		delete window.wp;
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

	it( 'points to Business Profile to change the post and links to it on a shared post', () => {
		renderMetaBox( {
			shareStatus: {
				is_shared: true,
				search_url: 'https://local.google.com/place?id=1&use=posts',
			},
		} );

		expect(
			screen.getByText(
				'Shared to Trailmark Athletics. To change the post, edit it in your Business Profile.'
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

	it( 'asks Heartbeat about a queued send and says the post is on its way', () => {
		jest.useFakeTimers();
		renderMetaBox( { syncOutcome: { outcome: 'queued' } } );

		act( () => {
			jest.advanceTimersByTime( FIRST_CHECK_DELAY );
		} );
		jest.useRealTimers();

		expect( window.wp.heartbeat.connectNow ).toHaveBeenCalledTimes( 1 );
		expect( beat() ).toEqual( { gla_gbp_sync_status: 123 } );
		expect(
			screen.getByText( 'Sending to Google Business Profile…' )
		).toBeInTheDocument();
	} );

	it( "shows a failed send's message once Heartbeat reports the send settled", () => {
		renderMetaBox( { syncOutcome: { outcome: 'queued' } } );

		beat( {
			gla_gbp_sync_status: {
				syncOutcome: {
					outcome: 'request-failure',
					display_message: 'Google couldn’t be reached.',
					display_status: 'error',
				},
				shareStatus: { is_shared: false, search_url: '' },
			},
		} );

		expect(
			screen.getByText( 'Google couldn’t be reached.', {
				selector: '.components-notice__content',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'Sending to Google Business Profile…' )
		).not.toBeInTheDocument();
	} );

	it( "shows a saved failure's message on load without asking Heartbeat", () => {
		renderMetaBox( {
			syncOutcome: {
				outcome: 'content-rejection',
				display_message: 'This update was rejected by Google.',
				display_status: 'error',
			},
		} );

		expect( beat() ).toEqual( {} );
		expect(
			screen.getByText( 'This update was rejected by Google.', {
				selector: '.components-notice__content',
			} )
		).toBeInTheDocument();
	} );

	it( 'checks a shared post on Google, showing a loading state meanwhile', () => {
		useBusinessProfilePostCheck.mockReturnValue( {
			isChecking: true,
			checkedPost: null,
			checkFailure: null,
		} );
		const shareStatus = {
			is_shared: true,
			search_url: 'https://local.google.com/place?id=1&use=posts',
		};

		renderMetaBox( { isShareEnabled: true, shareStatus } );

		expect( useBusinessProfilePostCheck ).toHaveBeenCalledWith( {
			postId: 123,
			shareStatus,
			syncOutcome: null,
		} );
		expect(
			screen.getByText( 'Checking Google Business Profile…' )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'checkbox' ) ).not.toBeInTheDocument();
	} );

	it( 'shows a post deleted on Google as unlinked, with sharing off, and posts that with the form', () => {
		useBusinessProfilePostCheck.mockReturnValue( {
			isChecking: false,
			checkedPost: {
				checked: true,
				check_failure: null,
				is_share_enabled: false,
				share_status: { is_shared: false, search_url: '' },
				sync_outcome: {
					outcome: 'not-found',
					message: '',
					image_omitted: false,
					display_message:
						'The post no longer exists on Google Business Profile.',
					display_status: 'error',
				},
			},
			checkFailure: null,
		} );

		const form = renderMetaBox( {
			isShareEnabled: true,
			summary: 'Autumn collection',
			shareStatus: {
				is_shared: true,
				search_url: 'https://local.google.com/place?id=1&use=posts',
			},
		} );

		expect(
			screen.getByText(
				'The post no longer exists on Google Business Profile.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).not.toBeChecked();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_share' )
		).toBe( '' );
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_summary' )
		).toBe( 'Autumn collection' );
	} );

	it( 'keeps the saved state and warns when the post could not be checked', () => {
		useBusinessProfilePostCheck.mockReturnValue( {
			isChecking: false,
			checkedPost: null,
			checkFailure: 'error',
		} );

		renderMetaBox( {
			isShareEnabled: true,
			shareStatus: {
				is_shared: true,
				search_url: 'https://local.google.com/place?id=1&use=posts',
			},
		} );

		expect(
			screen.getByText(
				'We couldn’t check whether this post still exists on Google Business Profile.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).toBeChecked();
		expect(
			screen.getByRole( 'link', {
				name: 'View on Google Business Profile (opens in a new tab)',
			} )
		).toBeInTheDocument();
	} );
} );
