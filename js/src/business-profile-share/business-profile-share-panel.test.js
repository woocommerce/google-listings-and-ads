/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import BusinessProfileSharePanel from './business-profile-share-panel';
import useBusinessProfileSharePost from './hooks/useBusinessProfileSharePost';
import { glaData } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock(
	'@wordpress/editor',
	() => ( {
		PluginDocumentSettingPanel: ( { title, children } ) => {
			return (
				<section aria-label={ title }>
					<h2>{ title }</h2>
					{ children }
				</section>
			);
		},
	} ),
	{ virtual: true }
);

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );
jest.mock( './hooks/useBusinessProfileSyncNotice', () => jest.fn() );
jest.mock( './hooks/useBusinessProfileSharePost', () =>
	jest.fn().mockName( 'useBusinessProfileSharePost' )
);

/**
 * Mocks the post's share state.
 *
 * @param {Object} [overrides] Values to override.
 */
function mockPost( overrides = {} ) {
	useBusinessProfileSharePost.mockReturnValue( {
		isShareEnabled: false,
		summary: '',
		isNeverSaved: false,
		isShared: false,
		searchUrl: '',
		getGeneratedSummary: jest.fn().mockReturnValue( '' ),
		setShareEnabled: jest.fn(),
		setSummary: jest.fn(),
		...overrides,
	} );
}

describe( 'BusinessProfileSharePanel', () => {
	afterEach( () => {
		glaData.businessProfileLocation = null;
	} );

	it( 'renders nothing when no location is connected', () => {
		mockPost();

		const { container } = render( <BusinessProfileSharePanel /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows the Google Business Profile panel with the toggle off by default', () => {
		glaData.businessProfileLocation = {
			title: 'Trailmark Athletics',
			address: '123 Market St, San Francisco, 94103',
		};
		mockPost();

		render( <BusinessProfileSharePanel /> );

		expect(
			screen.getByRole( 'region', { name: 'Google Business Profile' } )
		).toBeInTheDocument();
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
	} );

	it( "falls back to the location's address when it has no name", () => {
		glaData.businessProfileLocation = {
			title: '',
			address: '123 Market St, San Francisco, 94103',
		};
		mockPost();

		render( <BusinessProfileSharePanel /> );

		expect(
			screen.getByText(
				'Share to 123 Market St, San Francisco, 94103 as an update when you publish.'
			)
		).toBeInTheDocument();
	} );

	it( 'disables the controls on a post that has never been saved', () => {
		glaData.businessProfileLocation = {
			title: 'Trailmark Athletics',
			address: '',
		};
		mockPost( { isShareEnabled: true, isNeverSaved: true } );

		render( <BusinessProfileSharePanel /> );

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

	it( 'enables the controls once the post has been saved', () => {
		glaData.businessProfileLocation = {
			title: 'Trailmark Athletics',
			address: '',
		};
		mockPost( { isShareEnabled: true, isNeverSaved: false } );

		render( <BusinessProfileSharePanel /> );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		).toBeEnabled();
		expect(
			screen.getByRole( 'textbox', { name: /^Summary/ } )
		).toBeEnabled();
	} );

	it( 'records toggle clicks as coming from the block editor panel', async () => {
		const user = userEvent.setup();
		glaData.businessProfileLocation = {
			title: 'Trailmark Athletics',
			address: '',
		};
		mockPost();

		render( <BusinessProfileSharePanel /> );

		await user.click(
			screen.getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_share_toggle_click',
			expect.objectContaining( {
				context: 'business-profile-share-panel',
			} )
		);
	} );
} );
