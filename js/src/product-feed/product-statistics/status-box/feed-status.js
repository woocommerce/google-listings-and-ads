/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';
import Status from '.~/product-feed/product-statistics/status-box/status';
import SuccessIcon from '.~/components/success-icon';

function getUnsolvedStatusText( totalUnsolvedIssues ) {
	if ( ! Number.isInteger( totalUnsolvedIssues ) ) {
		return '';
	}

	if ( totalUnsolvedIssues === 0 ) {
		return __( 'No issues to resolve 🎉', 'google-listings-and-ads' );
	}

	return sprintf(
		// translators: %d: number of unsolved Merchant Center issues, with minimum value of 1.
		_n(
			'%d issue to resolve',
			'%d issues to resolve',
			totalUnsolvedIssues,
			'google-listings-and-ads'
		),
		totalUnsolvedIssues
	);
}

function FeedStatus() {
	const { total } = useMCIssuesTotals();
	const description = getUnsolvedStatusText( total );

	return (
		<Status
			title={ __( 'Feed setup:', 'google-listings-and-ads' ) }
			icon={ <SuccessIcon size={ 24 } /> }
			label={
				<span className="gla-status-success">
					{ __(
						'Standard free listings setup completed',
						'google-listings-and-ads'
					) }
				</span>
			}
			description={ description }
		/>
	);
}

export default FeedStatus;
