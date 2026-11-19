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
 * Mocks the editor store with the given edited meta.
 *
 * @param {Object} options
 * @param {Object} [options.meta] The post's edited meta.
 */
function mockEditor( { meta = {} } = {} ) {
	const stores = {
		'core/editor': {
			getEditedPostAttribute: ( attribute ) => {
				if ( attribute === 'meta' ) {
					return meta;
				}

				return undefined;
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
			meta: { _wc_gla_gbp_share: true, _wc_gla_gbp_summary: 'My text' },
		} );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			isShareEnabled: true,
			summary: 'My text',
		} );
	} );

	it( 'defaults to sharing off and an empty text', () => {
		mockEditor( { meta: {} } );

		const { result } = renderHook( () => useBusinessProfileSharePost() );

		expect( result.current ).toMatchObject( {
			isShareEnabled: false,
			summary: '',
		} );
	} );

	it( 'edits the share choice and the text as post meta', () => {
		mockEditor();

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
