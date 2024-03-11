/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';
import Section from '.~/wcdl/section';
import DisconnectAccount from './disconnect-account';
import LoadingLabel from '.~/components/loading-label';

/**
 * Renders a Google Ads account card UI with connected account information.
 *
 * @param {Object} props React props.
 * @param {{ id: number }} props.googleAdsAccount A data payload object containing the user's Google Ads account ID.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 * @param {boolean} [props.loading=false] Indicate whether there is some activity going.
 * @param {JSX.Element} [props.children] Helper content below the Google account email.
 * @param {Object} props.restProps Props to be forwarded to AccountCard.
 */
export default function ConnectedGoogleAdsAccountCard( {
	googleAdsAccount,
	hideAccountSwitch = false,
	loading = false,
	children,
	...restProps
} ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			description={
				<ExternalLink href="https://ads.google.com/aw/overview">
					{ toAccountText( googleAdsAccount.id ) }
				</ExternalLink>
			}
			indicator={
				loading ? (
					<LoadingLabel
						text={ __( 'Connecting…', 'google-listings-and-ads' ) }
					/>
				) : (
					<ConnectedIconLabel />
				)
			}
			{ ...restProps }
		>
			{ children }
			{ ! hideAccountSwitch && (
				<Section.Card.Footer>
					<DisconnectAccount />
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
}
