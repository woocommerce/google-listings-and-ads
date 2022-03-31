/**
 * Internal dependencies
 */
import toAccountText from '.~/utils/toAccountText';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import ConnectedIconLabel from '.~/components/connected-icon-label';

/**
 * Renders a Google Ads account card UI with connected account information.
 *
 * @param {Object} props React props.
 * @param {{ id: number }} props.googleAdsAccount A data payload object contains user's Google Ads account ID.
 * @param {JSX.Element} [props.children] Helper content below the Google account email.
 * @param {Object} props.restProps Props to be forwarded to AccountCard.
 */
export default function ConnectedGoogleAdsAccountCard( {
	googleAdsAccount,
	...restProps
} ) {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			description={ toAccountText( googleAdsAccount.id ) }
			indicator={ <ConnectedIconLabel /> }
			{ ...restProps }
		/>
	);
}
