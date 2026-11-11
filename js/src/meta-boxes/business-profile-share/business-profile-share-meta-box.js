/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BusinessProfileShareControl from '~/components/business-profile-share-control';
import useBusinessProfileSyncStatus from '~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus';
import { BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT } from './constants';
import getLiveClassicPostContent from './getLiveClassicPostContent';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {boolean} isShared Whether the post has already been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 * @property {string} restPath The post's REST route, e.g. `/wp/v2/posts/123`.
 * @property {import('~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus').SyncOutcome|null} syncOutcome The post's last sync outcome.
 */

/**
 * Renders the Classic Editor's controls for sharing a post to Google Business Profile, posting the
 * share choice and the text with the post's form through hidden fields. "Generate from post" reads
 * the editor's content right now, including unsaved edits -- see {@see getLiveClassicPostContent}.
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
	const { syncOutcome, isShared, searchUrl, isChecking } =
		useBusinessProfileSyncStatus( {
			restPath: post.restPath,
			syncOutcome: post.syncOutcome,
			shareStatus: {
				is_shared: post.isShared,
				search_url: post.searchUrl,
			},
		} );

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
				isCheckingSync={ isChecking }
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
