/**
 * External dependencies
 */
import { SummaryList, SummaryListPlaceholder } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import PerformanceCardNoData from '.~/dashboard/summary-section/performance-card-no-data';

/**
 * @typedef {import('@woocommerce/components').SummaryNumber} SummaryNumber
 */

/**
 * Returns a Card's content with performance matrics according to the given data.
 *
 * @param {Object} props React props
 * @param {boolean} props.loaded Was the data loaded?
 * @param {Object | null} props.data Data to be forwarded to `children` once available.
 * @param {(availableData: Object) => Array<SummaryNumber>} props.children Data to be forwarded to `children` once available.
 * @param {string} props.campaignType The Campaign type (free|paid) used to define text an links when no data
 * @return {JSX.Element} Metrics data, preloader or error message.
 */
const PerformanceCard = ( { loaded, data, children, campaignType } ) => {
	let content;
	if ( ! loaded ) {
		content = <SummaryListPlaceholder numberOfItems={ 2 } />;
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

	return content;
};

export default PerformanceCard;
