/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Status from '.~/product-feed/product-statistics/status-box/status';
import ErrorIcon from '.~/components/error-icon';

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
				<Button
					aria-label={ error }
					onClick={ refreshStats }
					className="overview-stats-error-button"
				>
					{ __(
						'There was an error loading the Overview Stats. Click to retry.',
						'google-listings-and-ads'
					) }
				</Button>
			}
			description={ error }
		/>
	);
}

export default SyncProductStatistics;
