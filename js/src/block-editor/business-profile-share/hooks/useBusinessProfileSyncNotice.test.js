/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useBusinessProfileSyncNotice from './useBusinessProfileSyncNotice';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

jest.mock( '~/hooks/useDispatchCoreNotices', () => jest.fn() );

const UNREACHABLE_OUTCOME = {
	outcome: 'request-failure',
	message: '',
	image_omitted: false,
	display_status: 'error',
	display_message:
		"The post couldn't be published to Google Business Profile. Google couldn't be reached. Try again later.",
};

/**
 * Mocks the editor's save state.
 *
 * @param {Object} state
 * @param {boolean} [state.isSaving] Whether a save is in progress.
 * @param {boolean} [state.isAutosaving] Whether the save in progress is an autosave.
 * @param {boolean} [state.didSaveSucceed] Whether the last save succeeded.
 * @param {Object|null} [state.outcome] The saved post's sync outcome field.
 */
function mockEditor( {
	isSaving = false,
	isAutosaving = false,
	didSaveSucceed = true,
	outcome = null,
} = {} ) {
	const editor = {
		isSavingPost: () => {
			return isSaving;
		},
		isAutosavingPost: () => {
			return isAutosaving;
		},
		didPostSaveRequestSucceed: () => {
			return didSaveSucceed;
		},
		getCurrentPost: () => {
			return { gla_gbp_sync_outcome: outcome };
		},
	};

	useSelect.mockImplementation( ( mapSelect ) => {
		return mapSelect( () => {
			return editor;
		} );
	} );
}

describe( 'useBusinessProfileSyncNotice', () => {
	let createNotice;
	let removeNotice;

	beforeEach( () => {
		createNotice = jest.fn();
		removeNotice = jest.fn();
		useDispatchCoreNotices.mockReturnValue( {
			createNotice,
			removeNotice,
		} );
	} );

	/**
	 * Renders the hook while a save is in progress, then finishes the save with the given state.
	 *
	 * @param {Object} savingState The state while saving.
	 * @param {Object} savedState The state once the save finished.
	 */
	function saveWith( savingState, savedState ) {
		mockEditor( { isSaving: true, ...savingState } );
		const { rerender } = renderHook( () => useBusinessProfileSyncNotice() );

		mockEditor( { isSaving: false, ...savedState } );
		rerender();
	}

	it( 'shows the outcome as a snackbar that stays until closed after a save', () => {
		saveWith( {}, { outcome: UNREACHABLE_OUTCOME } );

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			UNREACHABLE_OUTCOME.display_message,
			{
				id: 'gla-business-profile-sync-notice',
				type: 'snackbar',
				explicitDismiss: true,
			}
		);
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows a warning with its own status', () => {
		saveWith(
			{},
			{
				outcome: {
					display_status: 'warning',
					display_message: 'Profile unverified',
				},
			}
		);

		expect( createNotice ).toHaveBeenCalledWith(
			'warning',
			'Profile unverified',
			expect.any( Object )
		);
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows nothing when the editor loads, without a save', () => {
		mockEditor( { outcome: UNREACHABLE_OUTCOME } );

		renderHook( () => useBusinessProfileSyncNotice() );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'clears a previous notice when the post has never been sent', () => {
		saveWith( {}, { outcome: null } );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).toHaveBeenCalledTimes( 1 );
		expect( removeNotice ).toHaveBeenCalledWith(
			'gla-business-profile-sync-notice'
		);
	} );

	it( 'clears a previous notice for a plain success, which has no display message', () => {
		saveWith(
			{},
			{
				outcome: {
					outcome: 'success',
					display_status: null,
					display_message: null,
				},
			}
		);

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).toHaveBeenCalledTimes( 1 );
		expect( removeNotice ).toHaveBeenCalledWith(
			'gla-business-profile-sync-notice'
		);
	} );

	it( 'shows nothing after an autosave', () => {
		saveWith( { isAutosaving: true }, { outcome: UNREACHABLE_OUTCOME } );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows nothing when the save failed, and leaves any existing notice as is', () => {
		saveWith( {}, { didSaveSucceed: false, outcome: UNREACHABLE_OUTCOME } );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).not.toHaveBeenCalled();
	} );
} );
