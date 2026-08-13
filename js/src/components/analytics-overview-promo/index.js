/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Analytics overview promo section for the Analytics → Overview page, mounted by the
 * `woocommerce_dashboard_default_sections` filter registered in
 * `~/filters/analytics-overview-section`.
 *
 * @return {JSX.Element} Analytics overview promo component.
 */
const AnalyticsOverviewPromo = () => (
	<div className="gla-analytics-overview-promo">
		{ __( 'placeholder', 'google-listings-and-ads' ) }
	</div>
);

export default AnalyticsOverviewPromo;
