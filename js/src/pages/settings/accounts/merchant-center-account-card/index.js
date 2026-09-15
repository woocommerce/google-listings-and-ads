/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCardTextDetail from '../account-card-text-detail';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectButton from './connect-button';
import ConnectedBadge from '../connected-badge';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import ServiceBasedContent from './service-based-content';
import './index.scss';

const GOOGLE_MERCHANT_CENTER_OVERVIEW_URL =
	'https://merchants.google.com/mc/overview?a=';

/**
 * Renders the Google Merchant Center account card, which displays the account ID and a link to the Merchant Center overview page if connected.
 *
 * @return {JSX.Element} The Google Merchant Center account card.
 */
const MerchantCenterAccountCard = () => {
	const { googleMCAccount, hasGoogleMCConnection } = useGoogleMCAccount();
	const serviceBasedMerchant = useServiceBasedMerchant();

	const getIndicator = () => {
		if ( hasGoogleMCConnection ) {
			return <ConnectedBadge />;
		}

		if ( ! serviceBasedMerchant ) {
			return <ConnectButton />;
		}

		return null;
	};

	const getDetail = () => {
		if ( hasGoogleMCConnection ) {
			return (
				<AccountCardTextDetail>
					<ExternalLink
						href={ `${ GOOGLE_MERCHANT_CENTER_OVERVIEW_URL }${ googleMCAccount.id }` }
					>
						{ googleMCAccount.id }
					</ExternalLink>
				</AccountCardTextDetail>
			);
		}

		if ( serviceBasedMerchant ) {
			return <ServiceBasedContent />;
		}

		return null;
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ __(
				'Where your product catalog is synced to appear on Google.',
				'google-listings-and-ads'
			) }
			detail={ getDetail() }
			indicator={ getIndicator() }
			alignIndicator="top"
			alignIcon="top"
			expandedDetail={ serviceBasedMerchant && ! hasGoogleMCConnection }
		/>
	);
};

export default MerchantCenterAccountCard;
