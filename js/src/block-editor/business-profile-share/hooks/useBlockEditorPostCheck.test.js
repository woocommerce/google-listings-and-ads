/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import {
	createReduxStore,
	createRegistry,
	createRegistrySelector,
	RegistryProvider,
} from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import useBlockEditorPostCheck from './useBlockEditorPostCheck';
import useBusinessProfilePostCheck from '~/components/business-profile-share-control/hooks/useBusinessProfilePostCheck';

// The `core` store's resolvers would fetch the post; the test gives it the post directly.
jest.mock( '@wordpress/api-fetch', () =>
	jest.fn( () => new Promise( () => {} ) )
);
jest.mock(
	'~/components/business-profile-share-control/hooks/useBusinessProfilePostCheck',
	() => jest.fn().mockName( 'useBusinessProfilePostCheck' )
);

const POST_ID = 240;

const shared = {
	is_shared: true,
	search_url: 'https://local.google.com/place?id=1&use=posts',
};

const notShared = { is_shared: false, search_url: '' };

const notFound = {
	outcome: 'not-found',
	message: '',
	image_omitted: false,
	display_message: 'The post no longer exists on Google Business Profile.',
	display_status: 'error',
};

const savedPost = {
	id: POST_ID,
	type: 'post',
	status: 'publish',
	meta: {
		_wc_gla_gbp_share: true,
		_wc_gla_gbp_summary: 'Autumn collection',
	},
	gla_gbp_share_status: shared,
	gla_gbp_sync_outcome: null,
};

/**
 * Renders the hook in a registry with the real `core` store holding the saved post, and an editor
 * store reading the post from it, the way the block editor does.
 *
 * @return {Object} The registry and the hook's result.
 */
function renderWithEditor() {
	const registry = createRegistry();
	registry.register( coreStore );
	registry.register(
		createReduxStore( 'core/editor', {
			reducer: () => {
				return {};
			},
			selectors: {
				getCurrentPostId: () => {
					return POST_ID;
				},
				getCurrentPostType: () => {
					return 'post';
				},
				// Like the editor's own, it reads the saved record from the `core` store.
				getCurrentPost: createRegistrySelector( ( select ) => () => {
					return select( coreStore ).getEntityRecord(
						'postType',
						'post',
						POST_ID
					);
				} ),
			},
		} )
	);

	const core = registry.dispatch( coreStore );
	core.addEntities( [
		{
			kind: 'postType',
			name: 'post',
			baseURL: '/wp/v2/posts',
			mergedEdits: { meta: true },
		},
	] );
	core.receiveEntityRecords( 'postType', 'post', [ savedPost ] );
	core.finishResolution( 'getEntityRecord', [ 'postType', 'post', POST_ID ] );

	const { result } = renderHook( () => useBlockEditorPostCheck(), {
		wrapper: ( { children } ) => {
			return (
				<RegistryProvider value={ registry }>
					{ children }
				</RegistryProvider>
			);
		},
	} );

	return { registry, result };
}

describe( 'useBlockEditorPostCheck', () => {
	beforeEach( () => {
		useBusinessProfilePostCheck.mockReset();
	} );

	it( 'checks the saved post with its share state', () => {
		useBusinessProfilePostCheck.mockReturnValue( {
			isChecking: true,
			checkedPost: null,
			checkFailure: null,
		} );

		const { result } = renderWithEditor();

		expect( useBusinessProfilePostCheck ).toHaveBeenCalledWith( {
			postId: POST_ID,
			shareStatus: shared,
			syncOutcome: null,
		} );
		expect( result.current ).toEqual( {
			isChecking: true,
			checkFailure: null,
		} );
	} );

	it( 'gives the saved post the unlinked state without marking it as having unsaved changes', () => {
		useBusinessProfilePostCheck.mockReturnValue( {
			isChecking: false,
			checkedPost: {
				checked: true,
				check_failure: null,
				is_share_enabled: false,
				share_status: notShared,
				sync_outcome: notFound,
			},
			checkFailure: null,
		} );

		const { registry } = renderWithEditor();
		const core = registry.select( coreStore );
		const record = core.getEntityRecord( 'postType', 'post', POST_ID );

		expect( record.meta._wc_gla_gbp_share ).toBe( false );
		expect( record.meta._wc_gla_gbp_summary ).toBe( 'Autumn collection' );
		expect( record.gla_gbp_share_status ).toEqual( notShared );
		expect( record.gla_gbp_sync_outcome ).toEqual( notFound );
		expect(
			core.getEditedEntityRecord( 'postType', 'post', POST_ID ).meta
				._wc_gla_gbp_share
		).toBe( false );
		expect(
			core.hasEditsForEntityRecord( 'postType', 'post', POST_ID )
		).toBe( false );
	} );

	it( 'leaves the saved post alone when the check failed', () => {
		useBusinessProfilePostCheck.mockReturnValue( {
			isChecking: false,
			checkedPost: null,
			checkFailure: 'error',
		} );

		const { registry, result } = renderWithEditor();
		const record = registry
			.select( coreStore )
			.getEntityRecord( 'postType', 'post', POST_ID );

		expect( record.meta._wc_gla_gbp_share ).toBe( true );
		expect( record.gla_gbp_share_status ).toEqual( shared );
		expect( result.current.checkFailure ).toBe( 'error' );
	} );
} );
