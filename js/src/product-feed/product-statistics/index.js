/**
 * External dependencies
 */
import { format as formatDate } from '@wordpress/date';
import { __ } from '@wordpress/i18n';
import {
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import ProductStatusHelpPopover from './product-status-help-popover';
import './index.scss';

const ProductStatistics = () => {
	const { hasFinishedResolution, data } = useAppSelectDispatch(
		'getMCProductStatistics'
	);

	return (
		<div className="gla-product-statistics">
			<div className="gla-product-statistics__last-updated">
				{ ! hasFinishedResolution &&
					__( 'Updating…', 'google-listings-and-ads' ) }
				{ hasFinishedResolution &&
					! data &&
					__(
						'An error occurred while loading product statistics. Please try again later.',
						'google-listings-and-ads'
					) }
				{ hasFinishedResolution && data && (
					<>
						{ __( 'Last updated: ', 'google-listings-and-ads' ) }
						{ formatDate(
							'Y-m-d H:i:s',
							new Date( data.timestamp * 1000 )
						) }
						<ProductStatusHelpPopover />
					</>
				) }
			</div>
			<div className="gla-product-statistics__summaries">
				{ ! hasFinishedResolution && (
					<SummaryListPlaceholder numberOfItems={ 5 } />
				) }
				{ hasFinishedResolution && data && (
					<SummaryList>
						{ () => [
							<SummaryNumber
								key="active"
								label={ __(
									'Active / Partially Active',
									'google-listings-and-ads'
								) }
								value={ data.statistics.active }
							/>,
							<SummaryNumber
								key="expiring"
								label={ __(
									'Expiring',
									'google-listings-and-ads'
								) }
								value={ data.statistics.expiring }
							/>,
							<SummaryNumber
								key="pending"
								label={ __(
									'Pending',
									'google-listings-and-ads'
								) }
								value={ data.statistics.pending }
							/>,
							<SummaryNumber
								key="disapproved"
								label={ __(
									'Disapproved',
									'google-listings-and-ads'
								) }
								value={ data.statistics.disapproved }
							/>,
							<SummaryNumber
								key="not_synced"
								label={ __(
									'Not Synced',
									'google-listings-and-ads'
								) }
								value={ data.statistics.not_synced }
							/>,
						] }
					</SummaryList>
				) }
			</div>
		</div>
	);
};

export default ProductStatistics;
