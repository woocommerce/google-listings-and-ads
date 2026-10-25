/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import BusinessProfileShareControl from '.';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

const UPDATE_WARNING =
	'Updating this post on Google sends it back for review. If Google rejects the update, the post is hidden from Search and Maps.';

/**
 * Renders the control with sensible defaults, overridden by the given props.
 *
 * @param {Object} props Props to override.
 * @return {Object} The callbacks passed in.
 */
function renderControl( props = {} ) {
	const callbacks = {
		onShareEnabledChange: jest.fn().mockName( 'onShareEnabledChange' ),
		onSummaryChange: jest.fn().mockName( 'onSummaryChange' ),
		getLivePostContent: jest
			.fn()
			.mockName( 'getLivePostContent' )
			.mockReturnValue( { title: '', body: 'Generated text' } ),
	};

	render(
		<BusinessProfileShareControl
			context="business-profile-share-panel"
			locationName="Trailmark Athletics"
			isShareEnabled={ false }
			summary=""
			{ ...callbacks }
			{ ...props }
		/>
	);

	return callbacks;
}

describe( 'BusinessProfileShareControl', () => {
	beforeEach( () => {
		recordGlaEvent.mockClear();
	} );

	it( 'shows the toggle off, naming the connected location, with no field', () => {
		renderControl();

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
		expect( screen.queryByRole( 'textbox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Generate from post' } )
		).not.toBeInTheDocument();
	} );

	it( 'reports the toggle being turned on', async () => {
		const user = userEvent.setup();
		const { onShareEnabledChange } = renderControl();

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		);

		expect( onShareEnabledChange ).toHaveBeenCalledWith( true );
	} );

	it( 'records turning sharing on before reporting it', async () => {
		const user = userEvent.setup();
		const { onShareEnabledChange } = renderControl();

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_share_toggle_click',
			{
				context: 'business-profile-share-panel',
				status: 'on',
				is_shared: false,
			}
		);
		expect( recordGlaEvent.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			onShareEnabledChange.mock.invocationCallOrder[ 0 ]
		);
	} );

	it( 'records turning sharing off on a post already shared', async () => {
		const user = userEvent.setup();
		const { onShareEnabledChange } = renderControl( {
			context: 'business-profile-share-meta-box',
			isShareEnabled: true,
			isShared: true,
		} );

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_share_toggle_click',
			{
				context: 'business-profile-share-meta-box',
				status: 'off',
				is_shared: true,
			}
		);
		expect( onShareEnabledChange ).toHaveBeenCalledWith( false );
	} );

	it( 'shows the field and "Generate from post" while sharing is on', () => {
		renderControl( { isShareEnabled: true, summary: 'My text' } );

		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toHaveValue( 'My text' );
		expect(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		).toBeInTheDocument();
	} );

	it( 'fills the field from the live post content with "Generate from post"', async () => {
		const user = userEvent.setup();
		const { onSummaryChange } = renderControl( { isShareEnabled: true } );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect( onSummaryChange ).toHaveBeenCalledWith( 'Generated text' );
	} );

	it( 'joins the live title and body with a blank line', async () => {
		const user = userEvent.setup();
		const { onSummaryChange } = renderControl( {
			isShareEnabled: true,
			getLivePostContent: jest.fn().mockReturnValue( {
				title: 'New summer collection just landed',
				body: 'Our summer range is now in store.',
			} ),
		} );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect( onSummaryChange ).toHaveBeenCalledWith(
			'New summer collection just landed\n\nOur summer range is now in store.'
		);
	} );

	it( 'warns that updating sends the post back for review once it has been shared', () => {
		renderControl( { isShared: true } );

		expect( screen.getByText( UPDATE_WARNING ) ).toBeInTheDocument();
		expect(
			screen.queryByText(
				'Share to Trailmark Athletics as an update when you publish.'
			)
		).not.toBeInTheDocument();
	} );

	it( 'links to the post on Google once Google provides a link', () => {
		renderControl( {
			isShared: true,
			searchUrl: 'https://local.google.com/place?id=1&use=posts',
		} );

		expect(
			screen.getByRole( 'link', {
				name: 'View on Google Business Profile (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://local.google.com/place?id=1&use=posts'
		);
	} );

	it( 'shows no link until Google provides one', () => {
		renderControl( { isShared: true, searchUrl: '' } );

		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );

	it( 'records clicking the link to the post on Google', async () => {
		const user = userEvent.setup();
		renderControl( {
			context: 'business-profile-share-meta-box',
			isShared: true,
			searchUrl: 'https://local.google.com/place?id=1&use=posts',
		} );

		await user.click(
			screen.getByRole( 'link', {
				name: 'View on Google Business Profile (opens in a new tab)',
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_post_link_click',
			{ context: 'business-profile-share-meta-box' }
		);
	} );

	it( 'disables the toggle, field and button together', () => {
		renderControl( { isShareEnabled: true, disabled: true } );

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
} );
