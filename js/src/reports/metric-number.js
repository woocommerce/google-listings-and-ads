/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber } from '@woocommerce/components';
import { Tooltip } from '@wordpress/components';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import './metric-number.scss';

/**
 * SummeryNumber annotated about missing data.
 * To be used in ProgramsReport.
 *
 * Renders SummaryNumber with label that contains an info icon with a tootip
 * informing about missing data for some metrics.
 *
 * @param {Object} props
 * @param {string} props.label Metric label.
 * @param {Object} props.data Data as get from API.
 * @param {string} props.data.value
 * @param {string} props.data.delta
 * @param {boolean} props.data.missingFreeListingsData Flag indicating whether the data miss entries from Free Listings.
 *
 * @return {SummaryNumber} Filled SummaryNumber.
 */
const MetricNumber = ( {
	label,
	data: { value, delta, missingFreeListingsData },
} ) => {
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
				<Tooltip text={ infoText }>
					<span>
						<GridiconInfoOutline
							className="gla-reports__metric-infoicon"
							role="img"
							aria-label={ infoText }
							size={ 16 }
						/>
					</span>
				</Tooltip>
			</div>
		);
	}
	return (
		<SummaryNumber label={ markedLabel } value={ value } delta={ delta } />
	);
};

export default MetricNumber;
