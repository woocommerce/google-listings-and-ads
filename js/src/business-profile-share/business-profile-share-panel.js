/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useBusinessProfileSharePost from './hooks/useBusinessProfileSharePost';
import useBusinessProfileSyncNotice from './hooks/useBusinessProfileSyncNotice';
import BusinessProfileShareControl from './components/business-profile-share-control';
import { BUSINESS_PROFILE_SHARE_PANEL_CONTEXT } from './constants';

/**
 * Renders the "Google Business Profile" panel in the block editor's post settings, holding the
 * controls for sharing the post to the connected location. Nothing renders when no location is
 * connected. Available from the very first edit, before the post has ever been saved.
 *
 * @return {JSX.Element|null} The panel, or `null` when no location is connected.
 */
export default function BusinessProfileSharePanel() {
	const {
		isShareEnabled,
		summary,
		isShared,
		searchUrl,
		getGeneratedSummary,
		setShareEnabled,
		setSummary,
	} = useBusinessProfileSharePost();
	useBusinessProfileSyncNotice();

	const location = glaData?.businessProfileLocation;

	if ( ! location ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="google-listings-and-ads-business-profile"
			title={ __( 'Google Business Profile', 'google-listings-and-ads' ) }
		>
			<BusinessProfileShareControl
				context={ BUSINESS_PROFILE_SHARE_PANEL_CONTEXT }
				locationName={ location.title || location.address }
				isShareEnabled={ isShareEnabled }
				onShareEnabledChange={ setShareEnabled }
				summary={ summary }
				onSummaryChange={ setSummary }
				getGeneratedSummary={ getGeneratedSummary }
				searchUrl={ searchUrl }
				isShared={ isShared }
			/>
		</PluginDocumentSettingPanel>
	);
}
