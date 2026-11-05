/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink, Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedBadge from './connected-badge';
import AccountCardTextDetail from './account-card-text-detail';
import GoogleAdsTagRow from './google-ads-tag-row';
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
	const conversionTag = googleAdsAccount?.conversion_tag;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			description={ __(
				'Where your ad campaigns and conversion tracking are managed.',
				'google-listings-and-ads'
			) }
			detail={
				hasAdsAccount ? (
					<Flex direction="column" gap={ 4 }>
						<AccountCardTextDetail>
							<ExternalLink href={ getGoogleAdsOverviewUrl() }>
								{ toAccountText( googleAdsAccount.id ) }
							</ExternalLink>
						</AccountCardTextDetail>
						{ conversionTag?.id && (
							<GoogleAdsTagRow conversionTag={ conversionTag } />
						) }
					</Flex>
				) : null
			}
			indicator={ hasAdsAccount ? <ConnectedBadge /> : null }
			alignIndicator="top"
			alignIcon="top"
			expandedDetail
		/>
	);
};

export default GoogleAdsAccountCard;
