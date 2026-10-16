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
	status: 'draft',
	title: 'New Shoes Coming Soon',
	hasManualExcerpt: false,
	excerpt: 'Our spring footwear collection lands in store next Friday.',
	isShareEnabled: false,
	summary: '',
	isShared: false,
	searchUrl: '',
};

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

	it( 'fills the field from the saved post and posts the text with the form', async () => {
		const user = userEvent.setup();
		const form = renderMetaBox( { isShareEnabled: true } );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		const generated =
			'New Shoes Coming Soon\nOur spring footwear collection lands in store next Friday.';

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toHaveValue( generated );
		expect(
			getSubmittedValue( form, 'gla_business_profile_share_summary' )
		).toBe( generated );
	} );

	it( 'uses the manual excerpt alone when the merchant wrote one', async () => {
		const user = userEvent.setup();
		renderMetaBox( {
			isShareEnabled: true,
			hasManualExcerpt: true,
			excerpt: 'Reserve your pair online.',
		} );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toHaveValue( 'Reserve your pair online.' );
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

	it( 'disables the controls on a post that has never been saved', () => {
		renderMetaBox( { status: 'auto-draft', isShareEnabled: true } );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).toBeDisabled();
		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toBeDisabled();
		expect(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		).toBeDisabled();
	} );

	it.each( [ 'draft', 'pending', 'private', 'future', 'publish' ] )(
		'enables the controls on a %s post',
		( status ) => {
			renderMetaBox( { status, isShareEnabled: true } );

			expect(
				screen.getByRole( 'checkbox', {
					name: 'Share on my Business Profile',
				} )
			).toBeEnabled();
			expect(
				screen.getByRole( 'textbox', { name: /^Summary/ } )
			).toBeEnabled();
		}
	);

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
