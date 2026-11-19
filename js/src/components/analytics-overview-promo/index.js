/**
 * Internal dependencies
 */
import usePreference from '~/hooks/usePreference';
import { ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY } from './constants';
import PromoCard from './promo-card';

/**
 * Promo shown on the Analytics → Overview dashboard, mounted by the
 * `woocommerce_dashboard_default_sections` filter registered in
 * `~/filters/analytics-overview-section`.
 *
 * @param {Object} props Props core passes down (path, query, title, controls, etc.).
 * @param {Object} [props.query] The URL query params carrying the selected range.
 * @return {JSX.Element|null} The promo Card, or `null` once dismissed.
 */
const AnalyticsOverviewPromo = ( { query = {} } ) => {
	const isDismissed = usePreference( ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY );

	if ( isDismissed ) {
		return null;
	}

	return <PromoCard query={ query } />;
};

export default AnalyticsOverviewPromo;
