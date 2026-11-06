/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
	createReduxStore,
	createRegistry,
	RegistryProvider,
} from '@wordpress/data';

/**
 * Internal dependencies
 */
import BusinessProfileSharePanel from './business-profile-share-panel';
import useBusinessProfileSharePost from './hooks/useBusinessProfileSharePost';
import { glaBusinessProfileData } from '~/constants';
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
		isShared: false,
		searchUrl: '',
		setShareEnabled: jest.fn(),
		setSummary: jest.fn(),
		...overrides,
	} );
}

/**
 * Renders the panel inside a registry whose editor stores hold the post's current (possibly
 * unsaved) title and blocks.
 *
 * @param {Object} [options]
 * @param {string} [options.title] The post's current title.
 * @param {Object[]} [options.blocks] The post's current blocks.
 * @return {{ getBlocks: Function }} The mocked `getBlocks`, so a test can assert on when it was called.
 */
function renderPanel( { title = '', blocks = [] } = {} ) {
	const getBlocks = jest.fn().mockReturnValue( blocks );
	const registry = createRegistry();
	const registerSelectors = ( storeName, selectors ) => {
		registry.register(
			createReduxStore( storeName, {
				reducer: () => {
					return {};
				},
				selectors,
			} )
		);
	};

	registerSelectors( 'core/editor', {
		getEditedPostAttribute: ( state, attribute ) => {
			return attribute === 'title' ? title : undefined;
		},
	} );
	registerSelectors( 'core/block-editor', {
		getBlocks: () => {
			return getBlocks();
		},
	} );

	render(
		<RegistryProvider value={ registry }>
			<BusinessProfileSharePanel />
		</RegistryProvider>
	);

	return { getBlocks };
}

describe( 'BusinessProfileSharePanel', () => {
	afterEach( () => {
		glaBusinessProfileData.businessProfileLocation = null;
	} );

	it( 'renders nothing when no location is connected', () => {
		mockPost();

		const { container } = render( <BusinessProfileSharePanel /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows the Google Business Profile panel with the toggle off by default', () => {
		glaBusinessProfileData.businessProfileLocation = {
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
		glaBusinessProfileData.businessProfileLocation = {
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

	it( 'enables the controls even before the post has ever been saved', () => {
		glaBusinessProfileData.businessProfileLocation = {
			title: 'Trailmark Athletics',
			address: '',
		};
		mockPost( { isShareEnabled: true } );

		render( <BusinessProfileSharePanel /> );

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

	it( 'records toggle clicks as coming from the block editor panel', async () => {
		const user = userEvent.setup();
		glaBusinessProfileData.businessProfileLocation = {
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

	it( '"Generate from post" fills the text from the post right now, including unsaved edits', async () => {
		const user = userEvent.setup();
		const setSummary = jest.fn().mockName( 'setSummary' );
		glaBusinessProfileData.businessProfileLocation = {
			title: 'Trailmark Athletics',
			address: '',
		};
		mockPost( { isShareEnabled: true, setSummary } );

		renderPanel( {
			title: 'New summer collection just landed',
			blocks: [
				{
					name: 'core/paragraph',
					attributes: {
						content: 'Our summer range is now in store.',
					},
					innerBlocks: [],
				},
			],
		} );

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect( setSummary ).toHaveBeenCalledWith(
			'New summer collection just landed\n\nOur summer range is now in store.'
		);
	} );

	it( 'does not read the blocks until "Generate from post" is clicked, so an edit elsewhere in the post does not re-render the panel', () => {
		glaBusinessProfileData.businessProfileLocation = {
			title: 'Trailmark Athletics',
			address: '',
		};
		mockPost( { isShareEnabled: true } );

		const { getBlocks } = renderPanel();

		expect( getBlocks ).not.toHaveBeenCalled();
	} );
} );
