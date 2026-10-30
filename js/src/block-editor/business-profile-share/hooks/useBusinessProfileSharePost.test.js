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
 */
function mockEditor( { savedPost = {}, meta = {} } = {} ) {
	const stores = {
		'core/editor': {
			getCurrentPostType: () => {
				return 'post';
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

	it( 'reads whether the saved post is shared, and its link on Google', () => {
		mockEditor( {
			savedPost: {
				status: 'publish',
				gla_gbp_share_status: {
					is_shared: true,
					search_url: 'https://local.google.com/place?id=1&use=posts',
				},
			},
		} );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			isShared: true,
			searchUrl: 'https://local.google.com/place?id=1&use=posts',
		} );
	} );

	it( 'reports a post that is not shared, with no link', () => {
		mockEditor( { savedPost: { status: 'draft' } } );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			isShared: false,
			searchUrl: '',
		} );
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
