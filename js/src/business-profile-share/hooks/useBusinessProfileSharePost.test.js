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
 * Mocks the editor stores with the given saved post, edited meta and rendered excerpt.
 *
 * @param {Object} options
 * @param {Object} [options.savedPost] The post as last saved.
 * @param {Object} [options.meta] The post's edited meta.
 * @param {string} [options.renderedExcerpt] The saved post's rendered excerpt.
 */
function mockEditor( {
	savedPost = {},
	meta = {},
	renderedExcerpt = '',
} = {} ) {
	const stores = {
		'core/editor': {
			getCurrentPostType: () => {
				return 'post';
			},
			getCurrentPostId: () => {
				return 240;
			},
			getEditedPostAttribute: ( attribute ) => {
				return attribute === 'meta' ? meta : undefined;
			},
			getCurrentPost: () => {
				return savedPost;
			},
		},
		core: {
			getEntityRecord: () => {
				return { excerpt: { rendered: renderedExcerpt } };
			},
		},
	};

	useSelect.mockImplementation( ( callback ) => {
		return callback( ( storeName ) => {
			return stores[ storeName ];
		} );
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
			isNeverSaved: false,
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

	it( 'reports a post that has never been saved', () => {
		mockEditor( { savedPost: { status: 'auto-draft' } } );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current.isNeverSaved ).toBe( true );
	} );

	it.each( [ 'draft', 'pending', 'private', 'future', 'publish' ] )(
		'treats a %s post as saved',
		( status ) => {
			mockEditor( { savedPost: { status } } );

			const { result } = renderHook( () =>
				useBusinessProfileSharePost()
			);

			expect( result.current.isNeverSaved ).toBe( false );
		}
	);

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

	it( 'generates the text from the saved post, not from unsaved edits', () => {
		mockEditor( {
			savedPost: {
				status: 'draft',
				title: 'New summer collection just landed',
				excerpt: '',
			},
			renderedExcerpt: '<p>Our summer range is now in store.</p>',
		} );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current.getGeneratedSummary() ).toBe(
			'New summer collection just landed\nOur summer range is now in store.'
		);
	} );

	it( 'generates the text from the excerpt alone when the merchant wrote one', () => {
		mockEditor( {
			savedPost: {
				status: 'draft',
				title: 'New summer collection just landed',
				excerpt: 'Come in this weekend.',
			},
			renderedExcerpt: '<p>Come in this weekend.</p>',
		} );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current.getGeneratedSummary() ).toBe(
			'Come in this weekend.'
		);
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
