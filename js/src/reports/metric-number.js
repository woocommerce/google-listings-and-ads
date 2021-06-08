/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { SummaryNumber } from '@woocommerce/components';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import './metric-number.scss';
import AppTooltip from '.~/components/app-tooltip';
import useCurrencyFormat from '.~/hooks/useCurrencyFormat';
import useCurrencyFactory from '.~/hooks/useCurrencyFactory';
import { MISSING_FREE_LISTINGS_DATA } from '.~/data/utils';

const numberFormatSetting = { precision: 0 };

/**
 * SummeryNumber annotated about missing data.
 * To be used in ProgramsReport.
 *
 * Renders SummaryNumber with label that contains an info icon with a tootip
 * informing about missing data for some metrics.
 *
 * @param {Object} props
 * @param {string} props.label Metric label.
 * @param {string} [props.href] An internal link to the report focused on this metric.
 * @param {boolean} [props.selected] Whether show a highlight style on this metric.
 * @param {Function} [props.onLinkClickCallback] A function to be called after a SummaryNumber, rendered as a link, is clicked.
 * @param {boolean} [props.isCurrency=false] Display `data.value` and `data.prevValue` as price format if true.
 *                                           Otherwise, display as number format.
 * @param {import('.~/data/utils').PerformanceMetrics} props.data Data as get from API.
 *
 * @return {SummaryNumber} Filled SummaryNumber.
 */
const MetricNumber = ( {
	label,
	href,
	selected,
	onLinkClickCallback,
	isCurrency = false,
	data: { value, prevValue, delta, missingFreeListingsData },
} ) => {
	const formatNumber = useCurrencyFormat( numberFormatSetting );
	const { formatAmount } = useCurrencyFactory();
	const valueProps = useMemo( () => {
		const formatFn = isCurrency ? formatAmount : formatNumber;

		return {
			value:
				value === undefined
					? __( 'Unavailable', 'google-listings-and-ads' )
					: formatFn( value ),
			prevValue: formatFn( prevValue ),
		};
	}, [ isCurrency, value, prevValue, formatNumber, formatAmount ] );

	let markedLabel = label;
	const infos = [];

	// Until ~Q4 2021, metrics for all programs, may lack data for free listings.
	// And Free Listings API may not respond with data.
	if ( missingFreeListingsData !== MISSING_FREE_LISTINGS_DATA.NONE ) {
		infos.push(
			__(
				'This data is currently available for paid campaigns only.',
				'google-listings-and-ads'
			)
		);
	}
	if ( missingFreeListingsData === MISSING_FREE_LISTINGS_DATA.FOR_REQUEST ) {
		infos.push(
			__(
				'Please try again later, or go to merchants.google.com to track your performance for Google Free Listings.',
				'google-listings-and-ads'
			)
		);
	}

	if ( infos.length > 0 ) {
		const infoElements = infos.map( ( info, index ) => (
			<div className="gla-reports__metric-info" key={ index }>
				{ info }
			</div>
		) );
		markedLabel = (
			<div className="gla-reports__metric-label">
				{ label }
				<AppTooltip text={ infoElements }>
					<GridiconInfoOutline
						className="gla-reports__metric-infoicon"
						role="img"
						aria-label={ infos.join( ' ' ) }
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
