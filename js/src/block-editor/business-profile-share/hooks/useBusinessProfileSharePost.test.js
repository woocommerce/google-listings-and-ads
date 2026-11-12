/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useBusinessProfileSharePost from './useBusinessProfileSharePost';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

/**
 * Mocks the editor store with the given saved post and edited meta.
 *
 * @param {Object} options
 * @param {Object} [options.savedPost] The post as last saved.
 * @param {Object} [options.meta] The post's edited meta.
 * @param {string} [options.postType] The post's type; anything but `post` has no loaded type object.
 */
function mockEditor( { savedPost = {}, meta = {}, postType = 'post' } = {} ) {
	const stores = {
		core: {
			getPostType: ( requestedPostType ) => {
				return requestedPostType === 'post'
					? { rest_base: 'posts', rest_namespace: 'wp/v2' }
					: undefined;
			},
		},
		'core/editor': {
			getCurrentPostType: () => {
				return postType;
			},
			getCurrentPostId: () => {
				return 240;
			},
			getEditedPostAttribute: ( attribute ) => {
				if ( attribute === 'meta' ) {
					return meta;
				}

				return undefined;
			},
			getCurrentPost: () => {
				return savedPost;
			},
		},
	};

	const select = ( storeName ) => {
		return stores[ storeName ];
	};

	useSelect.mockImplementation( ( callback ) => {
		return callback( select );
	} );
}

describe( 'useBusinessProfileSharePost', () => {
	let editPost;

	beforeEach( () => {
		editPost = jest.fn().mockName( 'editPost' );
		useDispatch.mockReturnValue( { editPost } );
	} );

	it( 'reads the share choice and the text from the edited meta', () => {
		mockEditor( {
			savedPost: { status: 'draft' },
			meta: { _wc_gla_gbp_share: true, _wc_gla_gbp_summary: 'My text' },
		} );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			isShareEnabled: true,
			summary: 'My text',
		} );
	} );

	it( 'defaults to sharing off and an empty text', () => {
		mockEditor( { savedPost: { status: 'draft' }, meta: {} } );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			isShareEnabled: false,
			summary: '',
		} );
	} );

	it( "reads the saved post's share status and sync outcome, and its REST route", () => {
		const shareStatus = {
			is_shared: true,
			search_url: 'https://local.google.com/place?id=1&use=posts',
		};
		const syncOutcome = { outcome: 'queued', display_message: null };
		mockEditor( {
			savedPost: {
				status: 'publish',
				modified_gmt: '2026-11-11T16:45:05',
				gla_gbp_share_status: shareStatus,
				gla_gbp_sync_outcome: syncOutcome,
			},
		} );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			shareStatus,
			syncOutcome,
			restPath: '/wp/v2/posts/240',
			savedAt: '2026-11-11T16:45:05',
		} );
	} );

	it( 'reports a never-sent post, with no share status or sync outcome', () => {
		mockEditor( { savedPost: { status: 'draft' } } );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			shareStatus: null,
			syncOutcome: null,
		} );
	} );

	it( "has no REST route until the post's type has loaded", () => {
		mockEditor( { savedPost: { status: 'draft' }, postType: 'unloaded' } );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current.restPath ).toBeNull();
	} );

	it( 'edits the share choice and the text as post meta', () => {
		mockEditor( { savedPost: { status: 'draft' } } );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		result.current.setShareEnabled( true );
		result.current.setSummary( 'My text' );

		expect( editPost ).toHaveBeenCalledWith( {
			meta: { _wc_gla_gbp_share: true },
		} );
		expect( editPost ).toHaveBeenCalledWith( {
			meta: { _wc_gla_gbp_summary: 'My text' },
		} );
	} );
} );
