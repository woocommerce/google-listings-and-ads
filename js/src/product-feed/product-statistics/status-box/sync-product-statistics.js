/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Status from '.~/product-feed/product-statistics/status-box/status';
import ErrorIcon from '.~/components/error-icon';
import AppButton from '.~/components/app-button';

/**
 * @typedef {import('.~/data/actions').ProductStatistics } ProductStatistics
 */

/**
 * Renders status information for the Product Sync
 *
 * @param {Object} props The component props
 * @param {Function} props.refreshStats
 * @param {string} props.error
 * @return {JSX.Element} The status for the Product Sync
 */
function SyncProductStatistics( { refreshStats, error } ) {
	return (
		<Status
			title={ __( 'Overview Stats:', 'google-listings-and-ads' ) }
			icon={ <ErrorIcon size={ 24 } /> }
			label={
				<AppButton
					aria-label={ error }
					onClick={ refreshStats }
					className="overview-stats-error-button"
					eventName="gla_retry_loading_product_stats"
				>
					{ __(
						'There was an error loading the Overview Stats. Click to retry.',
						'google-listings-and-ads'
					) }
				</AppButton>
			}
			description={ error }
		/>
	);
}

export default SyncProductStatistics;
