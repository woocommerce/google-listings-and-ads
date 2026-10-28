/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { ExternalLink, Flex, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import { buildSummary } from './utils/summaryText';
import SummaryField from './summary-field';
import GenerateFromPostButton from './generate-from-post-button';
import './index.scss';

/**
 * Turning the "Share on my Business Profile" toggle on or off.
 *
 * @event gla_google_business_profile_share_toggle_click
 * @property {string} context Indicates which editor the toggle was clicked in. Possible values: 'business-profile-share-panel' (block editor), 'business-profile-share-meta-box' (Classic Editor).
 * @property {string} status Whether sharing was turned on or off. Possible values: 'on', 'off'.
 * @property {boolean} is_shared Whether the post has already been shared to Google Business Profile.
 */

/**
 * Clicking the "View on Google Business Profile" link to a post shared to Google Business Profile.
 *
 * @event gla_google_business_profile_post_link_click
 * @property {string} context Indicates which editor the link was clicked in. Possible values: 'business-profile-share-panel' (block editor), 'business-profile-share-meta-box' (Classic Editor).
 */

/**
 * Renders the controls for sharing a post to Google Business Profile, independent of the editor
 * hosting them: the "Share on my Business Profile" toggle, a link to the post on Google once it has
 * one, and — while sharing is on — the field holding the text sent to Google with its "Generate
 * from post" action. On a post already shared, the toggle explains that changes made here aren't
 * sent to Google.
 *
 * @param {Object} props Component props.
 * @param {string} props.context The editor hosting the controls, sent with tracking events. Possible values: 'business-profile-share-panel', 'business-profile-share-meta-box'.
 * @param {string} props.locationName The connected location's name, shown in the toggle's help text.
 * @param {boolean} props.isShareEnabled Whether sharing is turned on.
 * @param {( isShareEnabled: boolean ) => void} props.onShareEnabledChange Called when the toggle changes.
 * @param {string} props.summary The text sent to Google.
 * @param {( summary: string ) => void} props.onSummaryChange Called with the new text.
 * @param {() => {title: string, body: string}} props.getLivePostContent Returns the post's title and body right now, including unsaved edits, as plain text.
 * @param {string} [props.searchUrl] The post's link on Google, once Google provides one.
 * @param {boolean} [props.isShared] Whether the post has already been shared to Google.
 * @return {JSX.Element} The controls.
 *
 * @fires gla_google_business_profile_share_toggle_click with `{ context, status, is_shared }`.
 * @fires gla_google_business_profile_post_link_click with `{ context }`.
 */
export default function BusinessProfileShareControl( {
	context,
	locationName,
	isShareEnabled,
	onShareEnabledChange,
	summary,
	onSummaryChange,
	getLivePostContent,
	searchUrl,
	isShared = false,
} ) {
	const handleShareEnabledChange = ( nextIsShareEnabled ) => {
		recordGlaEvent( 'gla_google_business_profile_share_toggle_click', {
			context,
			status: nextIsShareEnabled ? 'on' : 'off',
			is_shared: isShared,
		} );
		onShareEnabledChange( nextIsShareEnabled );
	};

	const handleGenerate = () => {
		const { title, body } = getLivePostContent();
		const generatedSummary = buildSummary( title, body );
		onSummaryChange( generatedSummary );
	};

	const handleViewOnGoogleClick = () => {
		recordGlaEvent( 'gla_google_business_profile_post_link_click', {
			context,
		} );
	};

	const help = isShared
		? sprintf(
				/* translators: %s: the connected Google Business Profile location's name. */
				__(
					'Shared to %s. Changes made here aren’t sent to Google. To change the post on Google, edit it in your Business Profile.',
					'google-listings-and-ads'
				),
				locationName
			)
		: sprintf(
				/* translators: %s: the connected Google Business Profile location's name. */
				__(
					'Share to %s as an update when you publish.',
					'google-listings-and-ads'
				),
				locationName
			);

	return (
		<Flex
			className="gla-business-profile-share-control"
			direction="column"
			gap={ 4 }
		>
			<ToggleControl
				label={ __(
					'Share on my Business Profile',
					'google-listings-and-ads'
				) }
				help={ help }
				checked={ isShareEnabled }
				onChange={ handleShareEnabledChange }
				__nextHasNoMarginBottom
			/>
			{ searchUrl && (
				<ExternalLink
					href={ searchUrl }
					onClick={ handleViewOnGoogleClick }
				>
					{ __(
						'View on Google Business Profile',
						'google-listings-and-ads'
					) }
				</ExternalLink>
			) }
			{ isShareEnabled && (
				<Flex
					className="gla-business-profile-share-control__summary"
					direction="column"
					align="flex-start"
					gap={ 4 }
				>
					<SummaryField
						value={ summary }
						onChange={ onSummaryChange }
					/>
					<GenerateFromPostButton
						summary={ summary }
						onGenerate={ handleGenerate }
					/>
				</Flex>
			) }
		</Flex>
	);
}
