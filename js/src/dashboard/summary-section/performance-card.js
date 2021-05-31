/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryList, SummaryListPlaceholder } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import SummaryCard from './summary-card';

/**
 * @typedef {import('@woocommerce/components').SummaryNumber} SummaryNumber
 */

const googleMCURL = 'https://merchants.google.com/mc/reporting/dashboard';

/**
 * Returns a Card with performance matrics according to the given data.
 *
 * @param {Object} props React props
 * @param {string} props.title Card titile.
 * @param {boolean} props.loaded Was the data loaded?
 * @param {Object | null} props.data Data to be forwarded to `children` once available.
 * @param {(availableData: Object) => Array<SummaryNumber>} props.children Data to be forwarded to `children` once available.
 * @param {number} [props.numberOfItems=2] Number of expected SummaryNumbers.
 * @return {SummaryCard} SummaryCard with Metrics data, preloader or error message.
 */
const PerformanceCard = ( {
	title,
	loaded,
	data,
	children,
	numberOfItems = 2,
} ) => {
	let content;
	if ( ! loaded ) {
		content = <SummaryListPlaceholder numberOfItems={ numberOfItems } />;
	} else if ( ! data ) {
		content = (
			<div className="gla-summary-card__body">
				<p>
					{ __(
						"We're having trouble loading this data. Try again later, or track your performance in Google Merchant Center.",
						'google-listings-and-ads'
					) }
				</p>
				<AppButton
					eventName="gla_google_mc_link_click"
					eventProps={ { context: 'dashboard', href: googleMCURL } }
					href={ googleMCURL }
					target="_blank"
					isSmall
					isSecondary
				>
					{ __(
						'Open Google Merchant Center',
						'google-listings-and-ads'
					) }
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

	return <SummaryCard title={ title }>{ content }</SummaryCard>;
};

export default PerformanceCard;
