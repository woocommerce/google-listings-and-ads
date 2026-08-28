/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useMemo,
	createInterpolateElement,
	renderToString,
} from '@wordpress/element';
import { SummaryNumber } from '@woocommerce/components';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import './metric-number.scss';
import AppTooltip from '~/components/app-tooltip';
import TrackableLink from '~/components/trackable-link';
import { MISSING_FREE_LISTINGS_DATA } from '~/data/utils';

const googleMCReportingDashboardURL =
	'https://merchants.google.com/mc/reporting/dashboard';

/**
 * SummeryNumber annotated about missing data.
 * To be used in ProgramsReport.
 *
 * Renders SummaryNumber with label that contains an info icon with a tootip
 * informing about missing data for some metrics.
 *
 * @param {Object} props
 * @param {import('./index').Metric} props.metric Metrics label and formatting characteristics.
 * @param {string} [props.href] An internal link to the report focused on this metric.
 * @param {boolean} [props.selected] Whether show a highlight style on this metric.
 * @param {Function} [props.onLinkClickCallback] A function to be called after a SummaryNumber, rendered as a link, is clicked.
 * @param {import('~/data/utils').PerformanceMetrics} props.data Data as get from API.
 *
 * @return {SummaryNumber} Filled SummaryNumber.
 *
 * @fires gla_google_mc_link_click with `{ context: 'reports' }`
 */
const MetricNumber = ( {
	href,
	selected,
	onLinkClickCallback,
	metric,
	data: { value, prevValue, delta, missingFreeListingsData },
	...restProps
} ) => {
	const valueProps = useMemo( () => {
		return {
			value: metric.formatFn( value ),
			prevValue: metric.formatFn( prevValue ),
		};
	}, [ metric, value, prevValue ] );

	let markedLabel = metric.label;
	const infos = [];
	const ariaInfos = [];

	// Until ~Q4 2021, metrics for all programs, may lack data for product feed.
	// And Product Feed API may not respond with data.
	if ( missingFreeListingsData !== MISSING_FREE_LISTINGS_DATA.NONE ) {
		const text = __(
			'This data is currently available for Google Ads campaigns only.',
			'google-listings-and-ads'
		);
		infos.push( text );
		ariaInfos.push( text );
	}
	if ( missingFreeListingsData === MISSING_FREE_LISTINGS_DATA.FOR_REQUEST ) {
		const text = __(
			'Please try again later, or go to <googleMerchantCenterLink /> to track your performance for Google Product Feed.',
			'google-listings-and-ads'
		);

		infos.push(
			createInterpolateElement( text, {
				googleMerchantCenterLink: (
					<TrackableLink
						eventName="gla_google_mc_link_click"
						eventProps={ {
							context: 'reports',
							href: googleMCReportingDashboardURL,
						} }
						href={ googleMCReportingDashboardURL }
						onClick={ ( e ) => e.stopPropagation() }
						target="_blank"
						// Stop propagation to avoid triggering the <SummaryNumber> `href` prop
						// that redirects the browser to incorrect pages.
						type="external"
					>
						{ __(
							'Google Merchant Center',
							'google-listings-and-ads'
						) }
					</TrackableLink>
				),
			} )
		);

		// `aria-label` prop only accepts a pure text.
		const textElement = createInterpolateElement( text, {
			googleMerchantCenterLink: (
				<>
					{ sprintf(
						// translators: %s: link to Google Merchant Center.
						__(
							'Google Merchant Center (%s)',
							'google-listings-and-ads'
						),
						googleMCReportingDashboardURL
					) }
				</>
			),
		} );
		ariaInfos.push( renderToString( textElement ) );
	}

	if ( infos.length > 0 ) {
		const infoElements = infos.map( ( info, index ) => (
			<div className="gla-reports__metric-info" key={ index }>
				{ info }
			</div>
		) );
		markedLabel = (
			<div className="gla-reports__metric-label">
				{ metric.label }
				<AppTooltip text={ infoElements }>
					<GridiconInfoOutline
						aria-label={ ariaInfos.join( ' ' ) }
						className="gla-reports__metric-infoicon"
						role="img"
						size={ 16 }
					/>
				</AppTooltip>
			</div>
		);
	}
	return (
		<SummaryNumber
			delta={ delta }
			href={ href }
			label={ markedLabel }
			onLinkClickCallback={ onLinkClickCallback }
			prevValue={ valueProps.prevValue }
			selected={ selected }
			value={ valueProps.value }
			{ ...restProps }
		/>
	);
};

export default MetricNumber;
