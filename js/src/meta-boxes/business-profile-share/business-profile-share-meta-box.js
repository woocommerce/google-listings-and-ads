/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS } from '~/constants';
import useGoogleBusinessProfileAccount from '~/hooks/useGoogleBusinessProfileAccount';
import BusinessProfileShareControl from '~/components/business-profile-share-control';
import { BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT } from '~/components/business-profile-share-control/constants';
import buildSummaryFromLiveClassicContent from './buildSummaryFromLiveClassicContent';

const { CONNECTED } = GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS;

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {boolean} isShared Whether the post has already been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 */

/**
 * Renders the Classic Editor's controls for sharing a post to Google Business Profile, posting the
 * share choice and the text with the post's form through hidden fields. The hidden fields always
 * render, since the post's own form needs them regardless of the connected location's resolution
 * state; only the visible controls wait on it. "Generate from post" builds from the editor's
 * content right now, including unsaved edits -- see {@see buildSummaryFromLiveClassicContent}.
 *
 * @param {Object} props Component props.
 * @param {BusinessProfileSharePost} props.post The post's saved share state.
 * @param {string} props.shareFieldName Name of the form field holding the share choice.
 * @param {string} props.summaryFieldName Name of the form field holding the text.
 * @return {JSX.Element} The controls.
 */
export default function BusinessProfileShareMetaBox( {
	post,
	shareFieldName,
	summaryFieldName,
} ) {
	const [ isShareEnabled, setShareEnabled ] = useState( post.isShareEnabled );
	const [ summary, setSummary ] = useState( post.summary );

	const { account, hasFinishedResolution } =
		useGoogleBusinessProfileAccount();
	const isConnected = hasFinishedResolution && account?.status === CONNECTED;

	return (
		<>
			{ isConnected && (
				<BusinessProfileShareControl
					context={ BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT }
					locationName={ account.title || account.address }
					isShareEnabled={ isShareEnabled }
					onShareEnabledChange={ setShareEnabled }
					summary={ summary }
					onSummaryChange={ setSummary }
					getGeneratedSummary={ buildSummaryFromLiveClassicContent }
					searchUrl={ post.searchUrl }
					isShared={ post.isShared }
				/>
			) }
			<input
				type="hidden"
				name={ shareFieldName }
				value={ isShareEnabled ? '1' : '' }
			/>
			<input type="hidden" name={ summaryFieldName } value={ summary } />
		</>
	);
}
