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
			value: formatFn( value ),
			prevValue: formatFn( prevValue ),
		};
	}, [ isCurrency, value, prevValue, formatNumber, formatAmount ] );

	let markedLabel = label;

	// Until ~Q4 2021, metrics for all programs, may lack data for free listings.
	if ( missingFreeListingsData ) {
		const infoText = __(
			'This data is available for paid campaigns only.',
			'google-listings-and-ads'
		);
		markedLabel = (
			<div className="gla-reports__metric-label">
				{ label }
				<AppTooltip text={ infoText }>
					<GridiconInfoOutline
						className="gla-reports__metric-infoicon"
						role="img"
						aria-label={ infoText }
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
