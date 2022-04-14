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

/**
 * Returns the translatable text for total number of issues, solved and unsolved
 *
 * @param {number|undefined} totalUnsolvedIssues The total number of unsolved issues
 * @return {string} Translation for the number of issues
 */
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

/**
 * Renders the status for the Product Feed Setup
 *
 * @return {JSX.Element} The component with the Feed status
 */
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
