/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useRegistry } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { glaBusinessProfileData } from '~/constants';
import useBusinessProfileSharePost from './hooks/useBusinessProfileSharePost';
import getBodyTextFromBlocks from './utils/getBodyTextFromBlocks';
import BusinessProfileShareControl from '~/components/business-profile-share-control';
import useBusinessProfileSyncStatus from '~/components/business-profile-share-control/hooks/useBusinessProfileSyncStatus';
import { BUSINESS_PROFILE_SHARE_PANEL_CONTEXT } from './constants';

/**
 * Renders the "Google Business Profile" panel in the block editor's post settings, holding the
 * controls for sharing the post to the connected location. Nothing renders when no location is
 * connected.
 *
 * @return {JSX.Element|null} The panel, or `null` when no location is connected.
 */
export default function BusinessProfileSharePanel() {
	const {
		isShareEnabled,
		summary,
		shareStatus,
		syncOutcome: savedSyncOutcome,
		restPath,
		savedAt,
		setShareEnabled,
		setSummary,
	} = useBusinessProfileSharePost();
	const { syncOutcome, isShared, searchUrl, isChecking } =
		useBusinessProfileSyncStatus( {
			restPath,
			syncOutcome: savedSyncOutcome,
			shareStatus,
			savedAt,
		} );
	const registry = useRegistry();

	const location = glaBusinessProfileData?.businessProfileLocation;

	if ( ! location ) {
		return null;
	}

	// Reads the title and blocks through the registry, rather than `useSelect`, so the panel
	// doesn't re-render on every edit anywhere in the post -- their current value is only ever
	// needed at the moment the merchant clicks "Generate from post".
	const getLivePostContent = () => {
		const title = registry
			.select( 'core/editor' )
			.getEditedPostAttribute( 'title' );
		const blocks = registry.select( 'core/block-editor' ).getBlocks();

		return { title: title ?? '', body: getBodyTextFromBlocks( blocks ) };
	};

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
				getLivePostContent={ getLivePostContent }
				searchUrl={ searchUrl }
				isShared={ isShared }
				syncOutcome={ syncOutcome }
				isCheckingSync={ isChecking }
			/>
		</PluginDocumentSettingPanel>
	);
}
