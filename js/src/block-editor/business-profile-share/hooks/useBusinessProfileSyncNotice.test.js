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

const UNREACHABLE_NOTICE = {
	status: 'error',
	message:
		"The post couldn't be published to Google Business Profile. Google couldn't be reached. Try again later.",
};

/**
 * Mocks the editor's save state.
 *
 * @param {Object} state
 * @param {boolean} [state.isSaving] Whether a save is in progress.
 * @param {boolean} [state.isAutosaving] Whether the save in progress is an autosave.
 * @param {boolean} [state.didSaveSucceed] Whether the last save succeeded.
 * @param {Object|null} [state.notice] The saved post's notice field.
 */
function mockEditor( {
	isSaving = false,
	isAutosaving = false,
	didSaveSucceed = true,
	notice = null,
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
			return { gla_gbp_sync_notice: notice };
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

	it( 'shows the notice as a snackbar that stays until closed after a save', () => {
		saveWith( {}, { notice: UNREACHABLE_NOTICE } );

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			UNREACHABLE_NOTICE.message,
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
			{ notice: { status: 'warning', message: 'Profile unverified' } }
		);

		expect( createNotice ).toHaveBeenCalledWith(
			'warning',
			'Profile unverified',
			expect.any( Object )
		);
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows nothing when the editor loads, without a save', () => {
		mockEditor( { notice: UNREACHABLE_NOTICE } );

		renderHook( () => useBusinessProfileSyncNotice() );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'clears a previous notice when a save succeeds with nothing new to show', () => {
		saveWith( {}, { notice: null } );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).toHaveBeenCalledTimes( 1 );
		expect( removeNotice ).toHaveBeenCalledWith(
			'gla-business-profile-sync-notice'
		);
	} );

	it( 'shows nothing after an autosave', () => {
		saveWith( { isAutosaving: true }, { notice: UNREACHABLE_NOTICE } );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows nothing when the save failed, and leaves any existing notice as is', () => {
		saveWith( {}, { didSaveSucceed: false, notice: UNREACHABLE_NOTICE } );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( removeNotice ).not.toHaveBeenCalled();
	} );
} );
