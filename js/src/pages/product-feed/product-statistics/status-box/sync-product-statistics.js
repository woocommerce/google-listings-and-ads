/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Status from './status';
import ErrorIcon from '~/components/error-icon';
import AppButton from '~/components/app-button';

/**
 * @typedef {import('~/data/actions').ProductStatistics } ProductStatistics
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
			description={ error }
			icon={ <ErrorIcon size={ 24 } /> }
			label={
				<AppButton
					aria-label={ error }
					className="overview-stats-error-button"
					eventName="gla_retry_loading_product_stats"
					onClick={ refreshStats }
				>
					{ __(
						'There was an error loading the Overview Stats. Click to retry.',
						'google-listings-and-ads'
					) }
				</AppButton>
			}
			title={ __( 'Overview Stats:', 'google-listings-and-ads' ) }
		/>
	);
}

export default SyncProductStatistics;
