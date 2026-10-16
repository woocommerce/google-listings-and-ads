/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { SYNC_NOTICE_FIELD } from '../constants';

/**
 * ID of the snackbar, so a later save replaces it instead of adding another.
 */
const NOTICE_ID = 'gla-business-profile-sync-notice';

/**
 * Shows how a share to Google Business Profile went, as a snackbar that stays until the merchant
 * closes it, after a save that attempted the share. Loading or reloading the editor shows nothing.
 */
export default function useBusinessProfileSyncNotice() {
	const { createNotice } = useDispatchCoreNotices();
	const wasSaving = useRef( false );
	const wasAutosaving = useRef( false );

	const { isSaving, isAutosaving, didSaveSucceed, notice } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' );

			return {
				isSaving: editor.isSavingPost(),
				isAutosaving: editor.isAutosavingPost(),
				didSaveSucceed: editor.didPostSaveRequestSucceed(),
				notice: editor.getCurrentPost()?.[ SYNC_NOTICE_FIELD ] ?? null,
			};
		},
		[]
	);

	useEffect( () => {
		if ( isSaving ) {
			wasSaving.current = true;
			wasAutosaving.current = isAutosaving;
			return;
		}

		if ( ! wasSaving.current ) {
			return;
		}

		wasSaving.current = false;

		if ( wasAutosaving.current || ! didSaveSucceed || ! notice ) {
			return;
		}

		createNotice( notice.status, notice.message, {
			id: NOTICE_ID,
			type: 'snackbar',
			explicitDismiss: true,
		} );
	}, [ isSaving, isAutosaving, didSaveSucceed, notice, createNotice ] );
}
