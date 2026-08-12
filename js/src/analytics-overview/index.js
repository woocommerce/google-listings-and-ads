/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

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
	<Suspense fallback={ <Spinner /> }>
		<AnalyticsOverviewPromo { ...props } />
	</Suspense>
);

/*
 * Registered unconditionally at module-evaluation time so it runs before the lazily-loaded
 * `customizable-dashboard` chunk applies this filter when a merchant visits
 * Analytics → Overview.
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
			hiddenBlocks: [],
		},
		...sections,
	]
);

export default SectionComponent;
