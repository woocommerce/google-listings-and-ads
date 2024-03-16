/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Status from '.~/product-feed/product-statistics/status-box/status';
import ErrorIcon from '.~/components/error-icon';
import { Button } from '@wordpress/components';

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
function SyncMCStatus( { refreshStats, error } ) {
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
						'There was an error loading the Overview Stats. Click to retry.'
					) }
				</Button>
			}
			description={ null }
		/>
	);
}

export default SyncMCStatus;
