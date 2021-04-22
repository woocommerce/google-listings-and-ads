/**
 * External dependencies
 */
import { format as formatDate } from '@wordpress/date';
import { __ } from '@wordpress/i18n';
import { SummaryList, SummaryNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import useMCProductStatistics from '.~/hooks/useMCProductStatistics';
import ProductStatusHelpPopover from './product-status-help-popover';
import './index.scss';

const ProductStatistics = () => {
	const { hasFinishedResolution, data } = useMCProductStatistics();

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	/**
	 * Check for error.
	 */
	if ( hasFinishedResolution && ! data ) {
		return null;
	}

	const { timestamp, statistics } = data;
	const lastUpdatedDateTime = new Date( timestamp * 1000 );

	return (
		<div className="gla-product-statistics">
			<div className="gla-product-statistics__last-updated">
				{ __( 'Last updated: ', 'google-listings-and-ads' ) }
				{ formatDate( 'Y-m-d H:i:s', lastUpdatedDateTime ) }
				<ProductStatusHelpPopover />
			</div>
			<div className="gla-product-statistics__summaries">
				<SummaryList>
					{ () => [
						<SummaryNumber
							key="active"
							label={ __(
								'Active / Partially Active',
								'google-listings-and-ads'
							) }
							value={ statistics.active }
						/>,
						<SummaryNumber
							key="expiring"
							label={ __(
								'Expiring',
								'google-listings-and-ads'
							) }
							value={ statistics.expiring }
						/>,
						<SummaryNumber
							key="pending"
							label={ __( 'Pending', 'google-listings-and-ads' ) }
							value={ statistics.pending }
						/>,
						<SummaryNumber
							key="disapproved"
							label={ __(
								'Disapproved',
								'google-listings-and-ads'
							) }
							value={ statistics.disapproved }
						/>,
						<SummaryNumber
							key="not_synced"
							label={ __(
								'Not Synced',
								'google-listings-and-ads'
							) }
							value={ statistics.not_synced }
						/>,
					] }
				</SummaryList>
			</div>
		</div>
	);
};

export default ProductStatistics;
