/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';
import './index.scss';

const defaultDateRange =
	getSetting( 'wcAdminSettings' )?.woocommerce_default_date_range;

/**
 * Analytics overview promo section for the Analytics → Overview page, mounted by the
 * `woocommerce_dashboard_default_sections` filter registered in
 * `~/filters/analytics-overview-section`.
 *
 * @param {Object} props Props core passes down (path, query, title, controls, etc.).
 * @param {Object} [props.query] The URL query params carrying the selected range.
 * @return {JSX.Element|null} Analytics overview promo component, or `null` while resolving.
 */
const AnalyticsOverviewPromo = ( { query = {} } ) => {
	const { hasFinishedResolution, isDown, metricsCase } =
		useProductRevenueMetricsDown( query, defaultDateRange );

	if ( ! hasFinishedResolution ) {
		return null;
	}

	return (
		<div className="gla-analytics-overview-promo">
			{ isDown
				? sprintf(
						// translators: %s: the matched metrics case, e.g. "revenue" or "products".
						__( 'Metrics down: %s', 'google-listings-and-ads' ),
						metricsCase
				  )
				: __( 'Metrics not down', 'google-listings-and-ads' ) }
		</div>
	);
};

export default AnalyticsOverviewPromo;
