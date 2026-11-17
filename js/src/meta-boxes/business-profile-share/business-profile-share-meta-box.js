/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import BusinessProfileShareControl from '~/components/business-profile-share-control';
import { BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT } from './constants';
import getLiveClassicPostContent from './getLiveClassicPostContent';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {boolean} isShared Whether the post has already been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 * @property {?string} displayMessage A ready-to-display message for a non-success outcome of the post's last attempt to reach Google, or null.
 * @property {?string} displayStatus The message's severity ("error" or "warning"), or null.
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
	const [ showNotice, setShowNotice ] = useState(
		Boolean( post.displayMessage )
	);

	return (
		<>
			{ showNotice && (
				<Notice
					status={ post.displayStatus }
					onRemove={ () => setShowNotice( false ) }
				>
					{ post.displayMessage }
				</Notice>
			) }
			<BusinessProfileShareControl
				context={ BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT }
				locationName={ location.title || location.address }
				isShareEnabled={ isShareEnabled }
				onShareEnabledChange={ setShareEnabled }
				summary={ summary }
				onSummaryChange={ setSummary }
				getLivePostContent={ getLiveClassicPostContent }
				searchUrl={ post.searchUrl }
				isShared={ post.isShared }
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
