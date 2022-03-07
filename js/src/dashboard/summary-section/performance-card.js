/**
 * External dependencies
 */
import { SummaryList, SummaryListPlaceholder } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import SummaryCard from './summary-card';
import PerformanceCardNoData from '.~/dashboard/summary-section/performance-card-no-data';

/**
 * @typedef {import('@woocommerce/components').SummaryNumber} SummaryNumber
 */

/**
 * Returns a Card with performance matrics according to the given data.
 *
 * @param {Object} props React props
 * @param {string} props.title Card titile.
 * @param {boolean} props.loaded Was the data loaded?
 * @param {Object | null} props.data Data to be forwarded to `children` once available.
 * @param {(availableData: Object) => Array<SummaryNumber>} props.children Data to be forwarded to `children` once available.
 * @param {number} [props.numberOfItems=2] Number of expected SummaryNumbers.
 * @param {string} props.campaignType The Campaign type (free|paid) used to define text an links when no data
 * @return {SummaryCard} SummaryCard with Metrics data, preloader or error message.
 */
const PerformanceCard = ( {
	title,
	loaded,
	data,
	children,
	numberOfItems = 2,
	campaignType,
} ) => {
	let content;
	if ( ! loaded ) {
		content = <SummaryListPlaceholder numberOfItems={ numberOfItems } />;
	} else if ( ! data ) {
		content = <PerformanceCardNoData campaignType={ campaignType } />;
	} else {
		content = (
			<SummaryList>
				{ () => {
					return children( data );
				} }
			</SummaryList>
		);
	}

	return <SummaryCard title={ title }>{ content }</SummaryCard>;
};

export default PerformanceCard;
