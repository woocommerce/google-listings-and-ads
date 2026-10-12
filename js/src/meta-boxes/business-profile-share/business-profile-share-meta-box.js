/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BusinessProfileShareControl from '~/business-profile-share/components/business-profile-share-control';
import buildSummaryFromPost from '~/business-profile-share/utils/buildSummaryFromPost';
import { NEVER_SAVED_POST_STATUS } from '~/business-profile-share/constants';

/**
 * @typedef {Object} BusinessProfileSharePost
 * @property {string} status The post's saved status.
 * @property {string} title The post's saved title.
 * @property {boolean} hasManualExcerpt Whether the merchant wrote an excerpt.
 * @property {string} excerpt The post's saved excerpt, or the one WordPress derives from its content.
 * @property {boolean} isShareEnabled Whether sharing is turned on.
 * @property {string} summary The text sent to Google.
 * @property {boolean} isShared Whether the post has already been shared to Google.
 * @property {string} searchUrl The post's link on Google, once Google provides one.
 */

/**
 * Renders the Classic Editor's controls for sharing a post to Google Business Profile, posting the
 * share choice and the text with the post's form through hidden fields. "Generate from post" uses
 * the post as last saved, which the page reflects after every save.
 *
 * @param {Object} props Component props.
 * @param {{ title: string, address: string }} props.location The connected location.
 * @param {BusinessProfileSharePost} props.post The post as last saved.
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

	const getGeneratedSummary = () => {
		return buildSummaryFromPost( {
			title: post.title,
			hasManualExcerpt: post.hasManualExcerpt,
			renderedExcerpt: post.excerpt,
		} );
	};

	return (
		<>
			<BusinessProfileShareControl
				locationName={ location.title || location.address }
				isShareEnabled={ isShareEnabled }
				onShareEnabledChange={ setShareEnabled }
				summary={ summary }
				onSummaryChange={ setSummary }
				getGeneratedSummary={ getGeneratedSummary }
				searchUrl={ post.searchUrl }
				isShared={ post.isShared }
				disabled={ post.status === NEVER_SAVED_POST_STATUS }
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
