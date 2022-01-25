/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';

/**
 * Gets the active Issue Type Filter based on the `issueType` query property.
 *
 * @return {string|null} The active Issue Type Filter based on the url query. If `issueType` is falsy because is not in the query
 * it returns the issue type account if it has any issue, or it returns issue type product otherwise.
 */
const useActiveIssueType = () => {
	const issueTotals = useMCIssuesTotals();

	const defaultIssueType = issueTotals[ ISSUE_TYPE_ACCOUNT ]
		? ISSUE_TYPE_ACCOUNT
		: ISSUE_TYPE_PRODUCT;

	if ( ! issueTotals?.total ) {
		return null;
	}

	return getQuery()?.issueType || defaultIssueType;
};

export default useActiveIssueType;
