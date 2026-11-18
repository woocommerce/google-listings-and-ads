/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useBlockEditorSyncStatus from './useBlockEditorSyncStatus';
import useBusinessProfileSyncStatus from '~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );
jest.mock(
	'~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus',
	() => jest.fn().mockName( 'useBusinessProfileSyncStatus' )
);

/**
 * Mocks the editor store with the given saved post.
 *
 * @param {Object} savedPost The post as last saved.
 */
function mockEditor( savedPost ) {
	const stores = {
		'core/editor': {
			getCurrentPostId: () => {
				return 240;
			},
			getCurrentPost: () => {
				return savedPost;
			},
		},
	};

	useSelect.mockImplementation( ( callback ) => {
		return callback( ( storeName ) => {
			return stores[ storeName ];
		} );
	} );
}

describe( 'useBlockEditorSyncStatus', () => {
	it( "keeps the saved post's send status current, telling saves apart by their modified time", () => {
		const shareStatus = {
			is_shared: true,
			search_url: 'https://local.google.com/place?id=1&use=posts',
		};
		const syncOutcome = { outcome: 'queued', display_message: null };
		const status = {
			syncOutcome,
			isShared: true,
			searchUrl: shareStatus.search_url,
		};
		useBusinessProfileSyncStatus.mockReturnValue( status );
		mockEditor( {
			modified_gmt: '2026-11-11T16:45:05',
			gla_gbp_share_status: shareStatus,
			gla_gbp_sync_outcome: syncOutcome,
		} );

		const { result } = renderHook( () => useBlockEditorSyncStatus() );

		expect( useBusinessProfileSyncStatus ).toHaveBeenCalledWith( {
			postId: 240,
			syncOutcome,
			shareStatus,
			savedAt: '2026-11-11T16:45:05',
		} );
		expect( result.current ).toBe( status );
	} );

	it( 'passes a never-sent post with no share status or sync outcome', () => {
		mockEditor( {} );

		renderHook( () => useBlockEditorSyncStatus() );

		expect( useBusinessProfileSyncStatus ).toHaveBeenLastCalledWith( {
			postId: 240,
			syncOutcome: null,
			shareStatus: null,
			savedAt: '',
		} );
	} );
} );
