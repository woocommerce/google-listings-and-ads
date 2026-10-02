/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { lazy, Suspense } from '@wordpress/element';

/**
 * Dynamically imports the Analytics Overview Promo component.
 */
const AnalyticsOverviewPromo = lazy( () =>
	import(
		/* webpackChunkName: "analytics-overview-promo" */ '~/components/analytics-overview-promo'
	)
);

/**
 * Lazy-loads and renders the Analytics Overview Promo component, wrapped in a Suspense component.
 *
 * @param {Object} props Props core passes down to the Analytics Overview Promo component (path, query, title, controls, etc.).
 * @return {JSX.Element} The Analytics Overview Promo component, wrapped in a Suspense component.
 */
const SectionComponent = ( props ) => (
	<Suspense fallback={ null }>
		<AnalyticsOverviewPromo { ...props } />
	</Suspense>
);

/**
 * Adds the Analytics Overview Promo section to the WooCommerce Analytics Overview page.
 */
addFilter(
	'woocommerce_dashboard_default_sections',
	'woocommerce/google-listings-and-ads/add-analytics-overview-section',
	( sections ) => [
		{
			key: 'google-listings-and-ads-analytics-overview-promo',
			component: SectionComponent,
			title: __( 'Analytics Overview Promo', 'google-listings-and-ads' ),
			icon: 'megaphone',
			isVisible: true,
		},
		...sections,
	]
);

export default SectionComponent;
