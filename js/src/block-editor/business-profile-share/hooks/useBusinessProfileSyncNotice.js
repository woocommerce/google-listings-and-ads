/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { SYNC_OUTCOME_FIELD } from '../constants';

/**
 * ID of the snackbar, so a later save replaces it instead of adding another.
 */
const NOTICE_ID = 'gla-business-profile-sync-notice';

/**
 * Shows a non-success outcome of the post's last attempt to reach Google Business Profile, as a
 * snackbar that stays until the merchant closes it, after a save that attempted it. A plain
 * success has no message to show. Loading or reloading the editor shows nothing.
 */
export default function useBusinessProfileSyncNotice() {
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const wasSaving = useRef( false );
	const wasAutosaving = useRef( false );

	const { isSaving, isAutosaving, didSaveSucceed, outcome } = useSelect(
		( select ) => {
			const {
				isSavingPost,
				isAutosavingPost,
				didPostSaveRequestSucceed,
				getCurrentPost,
			} = select( 'core/editor' );

			return {
				isSaving: isSavingPost(),
				isAutosaving: isAutosavingPost(),
				didSaveSucceed: didPostSaveRequestSucceed(),
				outcome: getCurrentPost()?.[ SYNC_OUTCOME_FIELD ] ?? null,
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

		if ( wasAutosaving.current || ! didSaveSucceed ) {
			return;
		}

		if ( ! outcome?.display_message ) {
			// Clear an earlier save's snackbar (explicitDismiss keeps it open): this save either
			// reached Google successfully or attempted nothing, so that snackbar no longer applies.
			removeNotice( NOTICE_ID );
			return;
		}

		createNotice( outcome.display_status, outcome.display_message, {
			id: NOTICE_ID,
			type: 'snackbar',
			explicitDismiss: true,
		} );
	}, [
		isSaving,
		isAutosaving,
		didSaveSucceed,
		outcome,
		createNotice,
		removeNotice,
	] );
}
