/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedBadge from './connected-badge';
import AccountCardTextDetail from './account-card-text-detail';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '~/constants';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import { getGoogleAdsOverviewUrl } from '~/utils/urls';
import toAccountText from '~/utils/toAccountText';

/**
 * Renders the Google Ads account card.
 *
 * @return {JSX.Element} The Google Ads account card.
 */
const GoogleAdsAccountCard = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const hasAdsAccount = [
		GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED,
		GOOGLE_ADS_ACCOUNT_STATUS.INCOMPLETE,
	].includes( googleAdsAccount?.status );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			description={ __(
				'Where your ad campaigns and conversion tracking are managed.',
				'google-listings-and-ads'
			) }
			detail={
				<AccountCardTextDetail>
					<ExternalLink href={ getGoogleAdsOverviewUrl() }>
						{ toAccountText( googleAdsAccount.id ) }
					</ExternalLink>
				</AccountCardTextDetail>
			}
			indicator={ hasAdsAccount ? <ConnectedBadge /> : null }
			alignIndicator="top"
			alignIcon="top"
		/>
	);
};

export default GoogleAdsAccountCard;
