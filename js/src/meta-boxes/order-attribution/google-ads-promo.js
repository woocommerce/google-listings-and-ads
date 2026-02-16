/**
 * Internal dependencies
 */
import './google-ads-promo.scss';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';

/**
 * Google Ads Promo component.
 *
 * @return {JSX.Element|null} The Google Ads Promo component or null.
 */
const GoogleAdsPromo = () => {
	const { data: campaigns, loading } = useAdsCampaigns();

	if ( loading || ! Array.isArray( campaigns ) ) {
		return null;
	}

	return <div className="gla-google-ads-promo"></div>;
};

export default GoogleAdsPromo;
