/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BusinessProfileShareControl from '~/components/business-profile-share-control';
import useBusinessProfileSyncStatus from '~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus';
import useBusinessProfilePostCheck from '~/components/business-profile-share-control/hooks/useBusinessProfilePostCheck';
import { BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT } from './constants';
import getLiveClassicPostContent from './getLiveClassicPostContent';

/**
 * @typedef {import('~/components/business-profile-share-control/types').ShareStatus} ShareStatus
 * @typedef {import('~/components/business-profile-share-control/types').SyncOutcome} SyncOutcome
 */

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {number} postId The post's ID.
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {ShareStatus} shareStatus The post's share status.
 * @property {SyncOutcome|null} syncOutcome The post's last sync outcome.
 */

/**
 * Renders the Classic Editor's controls for sharing a post to Google Business Profile, posting the
 * share choice and the text with the post's form through hidden fields. "Generate from post" reads
 * the editor's content right now, including unsaved edits -- see {@see getLiveClassicPostContent}.
 * When the editor opens on a shared post, it first checks that the post still exists on Google,
 * and takes the server's share state once Google answers.
 *
 * @param {Object} props Component props.
 * @param {{ title: string, address: string }} props.location The connected location.
 * @param {BusinessProfileSharePost} props.post The post's saved share state.
 * @param {string} props.shareFieldName Name of the form field holding the share choice.
 * @param {string} props.summaryFieldName Name of the form field holding the text.
 * @return {JSX.Element} The controls.
 */
export default function BusinessProfileShareMetaBox( {
	location,
	post,
	shareFieldName,
	summaryFieldName,
} ) {
	const [ isShareEnabled, setShareEnabled ] = useState( post.isShareEnabled );
	const [ summary, setSummary ] = useState( post.summary );
	const { isChecking, checkedPost, checkFailure } =
		useBusinessProfilePostCheck( {
			postId: post.postId,
			shareStatus: post.shareStatus,
			syncOutcome: post.syncOutcome,
		} );
	const { syncOutcome, isShared, searchUrl } = useBusinessProfileSyncStatus( {
		postId: post.postId,
		syncOutcome: checkedPost ? checkedPost.sync_outcome : post.syncOutcome,
		shareStatus: checkedPost ? checkedPost.share_status : post.shareStatus,
	} );

	useEffect( () => {
		if ( checkedPost ) {
			setShareEnabled( checkedPost.is_share_enabled );
		}
	}, [ checkedPost ] );

	return (
		<>
			<BusinessProfileShareControl
				context={ BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT }
				locationName={ location.title || location.address }
				isShareEnabled={ isShareEnabled }
				onShareEnabledChange={ setShareEnabled }
				summary={ summary }
				onSummaryChange={ setSummary }
				getLivePostContent={ getLiveClassicPostContent }
				searchUrl={ searchUrl }
				isShared={ isShared }
				syncOutcome={ syncOutcome }
				isChecking={ isChecking }
				checkFailure={ checkFailure }
			/>
			<input
				type="hidden"
				name={ shareFieldName }
				value={ isShareEnabled ? '1' : '' }
			/>
			<input type="hidden" name={ summaryFieldName } value={ summary } />
		</>
	);
}
