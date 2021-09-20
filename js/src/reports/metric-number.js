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
import AppTooltip from '.~/components/app-tooltip';
import TrackableLink from '.~/components/trackable-link';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import { MISSING_FREE_LISTINGS_DATA } from '.~/data/utils';

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
 * @param {import('.~/data/utils').PerformanceMetrics} props.data Data as get from API.
 *
 * @return {SummaryNumber} Filled SummaryNumber.
 */
const MetricNumber = ( {
	href,
	selected,
	onLinkClickCallback,
	metric,
	data: { value, prevValue, delta, missingFreeListingsData },
} ) => {
	const storeCurrencyConfig = useStoreCurrency();
	const valueProps = useMemo( () => {
		return {
			value: metric.formatFn( storeCurrencyConfig, value ),
			prevValue: metric.formatFn( storeCurrencyConfig, prevValue ),
		};
	}, [ metric, storeCurrencyConfig, value, prevValue ] );

	let markedLabel = metric.label;
	const infos = [];
	const ariaInfos = [];

	// Until ~Q4 2021, metrics for all programs, may lack data for free listings.
	// And Free Listings API may not respond with data.
	if ( missingFreeListingsData !== MISSING_FREE_LISTINGS_DATA.NONE ) {
		const text = __(
			'This data is currently available for paid campaigns only.',
			'google-listings-and-ads'
		);
		infos.push( text );
		ariaInfos.push( text );
	}
	if ( missingFreeListingsData === MISSING_FREE_LISTINGS_DATA.FOR_REQUEST ) {
		const text = __(
			'Please try again later, or go to <googleMerchantCenterLink /> to track your performance for Google Free Listings.',
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
						type="external"
						target="_blank"
						href={ googleMCReportingDashboardURL }
						// Stop propagation to avoid triggering the <SummaryNumber> `href` prop
						// that redirects the browser to incorrect pages.
						onClick={ ( e ) => e.stopPropagation() }
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
						className="gla-reports__metric-infoicon"
						role="img"
						aria-label={ ariaInfos.join( ' ' ) }
						size={ 16 }
					/>
				</AppTooltip>
			</div>
		);
	}
	return (
		<SummaryNumber
			label={ markedLabel }
			href={ href }
			selected={ selected }
			delta={ delta }
			onLinkClickCallback={ onLinkClickCallback }
			{ ...valueProps }
		/>
	);
};

export default MetricNumber;
