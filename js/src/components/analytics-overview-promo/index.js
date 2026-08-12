/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Placeholder for the Analytics → Overview dashboard section, mounted by the
 * `woocommerce_dashboard_default_sections` filter registered in
 * `~/analytics-overview`.
 *
 * @return {JSX.Element} Analytics promo component.
 */
const AnalyticsOverviewPromo = () => (
	<div className="gla-analytics-overview-promo">
		{ __( 'placeholder', 'google-listings-and-ads' ) }
	</div>
);

export default AnalyticsOverviewPromo;
