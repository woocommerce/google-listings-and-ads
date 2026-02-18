/**
 * Internal dependencies
 */
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import './google-ads-promo.scss';

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
