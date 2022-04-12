/**
 * External dependencies
 */
import { SummaryList, SummaryListPlaceholder } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';

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
 * @param {{body: string, buttonLabel: string, eventName: string, link: string}} props.noDataMessage Content of the message to be shown if there is no data loaded.
 * @return {JSX.Element} Metrics data, preloader or error message.
 */
const PerformanceCard = ( { loaded, data, children, noDataMessage } ) => {
	let content;
	if ( ! loaded ) {
		content = <SummaryListPlaceholder numberOfItems={ 2 } />;
	} else if ( ! data ) {
		content = (
			<div className="gla-summary-card__body">
				<p>{ noDataMessage.body }</p>
				<AppButton
					eventName={ noDataMessage.eventName }
					eventProps={ {
						context: 'dashboard',
						href: noDataMessage.link,
					} }
					href={ noDataMessage.link }
					target="_blank"
					isSmall
					isSecondary
				>
					{ noDataMessage.buttonLabel }
				</AppButton>
			</div>
		);
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
