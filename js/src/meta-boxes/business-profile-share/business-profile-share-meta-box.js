/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BusinessProfileShareControl from '~/business-profile-share/components/business-profile-share-control';
import buildSummaryFromLiveClassicContent from '~/business-profile-share/utils/buildSummaryFromLiveClassicContent';
import { BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT } from '~/business-profile-share/constants';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {boolean} isShared Whether the post has already been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 */

/**
 * Renders the Classic Editor's controls for sharing a post to Google Business Profile, posting the
 * share choice and the text with the post's form through hidden fields. Available from the very
 * first edit, before the post has ever been saved. "Generate from post" builds from the editor's
 * content right now, including unsaved edits -- see {@see buildSummaryFromLiveClassicContent}.
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

	return (
		<>
			<BusinessProfileShareControl
				context={ BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT }
				locationName={ location.title || location.address }
				isShareEnabled={ isShareEnabled }
				onShareEnabledChange={ setShareEnabled }
				summary={ summary }
				onSummaryChange={ setSummary }
				getGeneratedSummary={ buildSummaryFromLiveClassicContent }
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
