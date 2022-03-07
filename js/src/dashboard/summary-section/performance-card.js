/**
 * External dependencies
 */
import { SummaryList, SummaryListPlaceholder } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import SummaryCard from './summary-card';
import CampaignNoData from '.~/dashboard/summary-section/campaign-no-data';

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
 * @param {string} props.type Type of Campaign (paid|free)
 * @return {SummaryCard} SummaryCard with Metrics data, preloader or error message.
 */
const PerformanceCard = ( {
	title,
	loaded,
	data,
	children,
	numberOfItems = 2,
	type,
} ) => {
	let content;
	if ( ! loaded ) {
		content = <SummaryListPlaceholder numberOfItems={ numberOfItems } />;
	} else if ( ! data ) {
		content = <CampaignNoData type={ type } />;
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
