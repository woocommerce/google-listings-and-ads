/**
 * External dependencies
 */
import { ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import getConnectedJetpackInfo from '~/utils/getConnectedJetpackInfo';
import { getGoogleAdsOverviewUrl, getYouTubeChannelUrl } from '~/utils/urls';
import toAccountText from '~/utils/toAccountText';
import { recordGlaEvent } from '~/utils/tracks';
import { APPEARANCE } from '~/components/account-card';
import { GOOGLE_ADS_ACCOUNT_STATUS, YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import { YOUTUBE_ACCOUNT } from '../disconnect-modal';
import IncompleteYouTubeAccountRow from './incomplete-youtube-account-row';
// import MerchantCenterConnectButton from './merchant-center-connect-button';
import YouTubeConnectButton from './youtube-connect-button';

/**
 * Account section keys used to group the connected account rows.
 *
 * @enum {string}
 */
export const ACCOUNT_SECTION = {
	REQUIRED: 'required',
	GROW: 'grow',
};

const { CONNECTED: ADS_CONNECTED, INCOMPLETE: ADS_INCOMPLETE } =
	GOOGLE_ADS_ACCOUNT_STATUS;
const GOOGLE_MERCHANT_CENTER_OVERVIEW_URL =
	'https://merchants.google.com/mc/overview?a=';
export const YOUTUBE_MERCHANT_TERMS_URL =
	'https://www.youtube.com/t/merchant_terms';

/**
 * Records a click on the YouTube Merchant Terms link.
 *
 * @fires gla_documentation_link_click with `{ context: 'settings-connect-youtube-account-card', link_id: 'youtube-merchant-terms' }` and the URL.
 */
function handleYouTubeMerchantTermsClick() {
	recordGlaEvent( 'gla_documentation_link_click', {
		context: 'settings-connect-youtube-account-card',
		link_id: 'youtube-merchant-terms',
		href: YOUTUBE_MERCHANT_TERMS_URL,
	} );
}

/**
 * @typedef {Object} ConnectedAccountItem
 * @property {string} id Stable account identifier.
 * @property {string} section One of {@link ACCOUNT_SECTION}.
 * @property {string} appearance Account card appearance (maps to logo).
 * @property {string} title Account name.
 * @property {string} description Short account description.
 * @property {boolean} connected Whether the account is currently connected.
 * @property {string} [detail] Human-readable account detail (email, id, channel).
 * @property {string} [detailUrl] External URL used to turn the detail into a link.
 * @property {import('react').ReactNode} [helper] Optional content rendered below the description.
 * @property {import('react').ComponentType} [ConnectComponent] Connect action rendered when disconnected.
 * @property {import('react').ComponentType} [RowComponent] Specialized row renderer.
 * @property {boolean} canDisconnect Whether an individual disconnect action is offered today.
 * @property {string} [disconnectTarget] Disconnect-modal target used when `canDisconnect` is true.
 * @property {boolean} [isVisible] Whether the account row should be shown in this UI.
 */

/**
 * Builds the account items rendered as rows in the Settings > Accounts subtab,
 * together with the overall loading state. All accounts are returned, each with
 * a `connected` flag so the row can render a status badge or a connect action.
 *
 * @return {{ accounts: ConnectedAccountItem[], isLoading: boolean }} Accounts and loading state.
 */
export default function useConnectedAccounts() {
	const { jetpack, hasFinishedResolution: hasResolvedJetpack } =
		useJetpackAccount();
	const { google, hasFinishedResolution: hasResolvedGoogle } =
		useGoogleAccount();
	const {
		googleMCAccount,
		hasFinishedResolution: hasResolvedMC,
		hasGoogleMCConnection,
	} = useGoogleMCAccount();
	const { googleAdsAccount, hasFinishedResolution: hasResolvedAds } =
		useGoogleAdsAccount();
	const { youTubeAccount, hasFinishedResolution: hasResolvedYouTube } =
		useYouTubeAccount();
	const serviceBasedMerchant = useServiceBasedMerchant();

	const isLoading = ! (
		hasResolvedJetpack &&
		hasResolvedGoogle &&
		hasResolvedMC &&
		hasResolvedAds &&
		hasResolvedYouTube
	);

	const hasAdsAccount = [ ADS_CONNECTED, ADS_INCOMPLETE ].includes(
		googleAdsAccount?.status
	);
	const youTubeStatus = youTubeAccount?.status;
	const isYouTubeConnected = [
		YOUTUBE_ACCOUNT_STATUS.CONNECTED,
		YOUTUBE_ACCOUNT_STATUS.INCOMPLETE,
	].includes( youTubeStatus );

	const youtubeMerchantTermsLink = ! isYouTubeConnected ? (
		<ExternalLink
			onClick={ handleYouTubeMerchantTermsClick }
			href={ YOUTUBE_MERCHANT_TERMS_URL }
		>
			{ __( 'YouTube Merchant Terms', 'google-listings-and-ads' ) }
		</ExternalLink>
	) : undefined;

	const accounts = [
		{
			id: 'wpcom',
			section: ACCOUNT_SECTION.REQUIRED,
			appearance: APPEARANCE.WPCOM,
			title: __( 'WordPress.com', 'google-listings-and-ads' ),
			description: __(
				'The account that connects your store to Google for WooCommerce.',
				'google-listings-and-ads'
			),
			connected: jetpack?.active === 'yes',
			detail: jetpack ? getConnectedJetpackInfo( jetpack ) : '',
			canDisconnect: false,
		},
		{
			id: 'google',
			section: ACCOUNT_SECTION.REQUIRED,
			appearance: APPEARANCE.GOOGLE,
			title: __( 'Google', 'google-listings-and-ads' ),
			description: __(
				'The account you use to log in to Google products.',
				'google-listings-and-ads'
			),
			connected: Boolean( google?.email ),
			detail: google?.email || '',
			canDisconnect: false,
		},
		{
			id: 'merchant-center',
			section: ACCOUNT_SECTION.REQUIRED,
			appearance: APPEARANCE.GOOGLE_MERCHANT_CENTER,
			title: __( 'Merchant Center', 'google-listings-and-ads' ),
			description: __(
				'Where your product catalog is synced to appear on Google.',
				'google-listings-and-ads'
			),
			connected: hasGoogleMCConnection,
			detail: hasGoogleMCConnection ? (
				<ExternalLink
					href={ `${ GOOGLE_MERCHANT_CENTER_OVERVIEW_URL }${ googleMCAccount.id }` }
				>
					{ googleMCAccount.id }
				</ExternalLink>
			) : undefined,
			canDisconnect: false,
			// Offer an in-page connect action when Merchant Center is not
			// connected and the store is no longer classified as service-based
			// (i.e. it now has physical products that need syncing to Google).
			// ConnectComponent: serviceBasedMerchant
			// 	? undefined
			// 	: MerchantCenterConnectButton,
		},
		{
			id: 'google-ads',
			section: ACCOUNT_SECTION.REQUIRED,
			appearance: APPEARANCE.GOOGLE_ADS,
			title: __( 'Google Ads', 'google-listings-and-ads' ),
			description: __(
				'Where your ad campaigns and conversion tracking are managed.',
				'google-listings-and-ads'
			),
			connected: hasAdsAccount,
			detail: googleAdsAccount?.id ? (
				<ExternalLink href={ getGoogleAdsOverviewUrl() }>
					{ toAccountText( googleAdsAccount.id ) }
				</ExternalLink>
			) : undefined,

			detailUrl: googleAdsAccount?.id ? getGoogleAdsOverviewUrl() : '',
			// Individual disconnect is intentionally not offered for the Ads
			// account: the extension does not function properly without it.
			// Use "Disconnect from all accounts" to remove it.
			canDisconnect: false,
		},
		{
			id: 'youtube',
			section: ACCOUNT_SECTION.GROW,
			appearance: APPEARANCE.YOUTUBE,
			title: __( 'YouTube', 'google-listings-and-ads' ),
			description: __(
				'List your products on YouTube and track sales from your videos.',
				'google-listings-and-ads'
			),
			connected: isYouTubeConnected,
			detail: (
				<>
					{ youTubeAccount?.channel?.id ? (
						<ExternalLink
							href={ getYouTubeChannelUrl(
								youTubeAccount.channel
							) }
						>
							{ youTubeAccount?.channel?.label }
						</ExternalLink>
					) : undefined }

					{ youtubeMerchantTermsLink }
				</>
			),
			// YouTube can be individually disconnected while connected.
			canDisconnect: isYouTubeConnected,
			disconnectTarget: YOUTUBE_ACCOUNT,
			// RowComponent:
			// 	youTubeStatus === YOUTUBE_ACCOUNT_STATUS.INCOMPLETE
			// 		? IncompleteYouTubeAccountRow
			// 		: undefined,
			// // Show the YouTube setup action only after Merchant Center is
			// // connected.
			// ConnectComponent: hasGoogleMCConnection
			// 	? YouTubeConnectButton
			// 	: undefined,
			isVisible: hasGoogleMCConnection,
		},
	];

	return { accounts, isLoading };
}
