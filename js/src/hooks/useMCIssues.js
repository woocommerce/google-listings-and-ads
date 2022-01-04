/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';

/**
 * Gets the issues data for both Issue types (product & account)
 *
 * @return {Object} The `getMCIssues` dispatchers for account and product types
 * of issues in the format `{account, product}`
 * @see useMCIssuesTypeFilter
 */
const useMCIssues = () => {
	return {
		[ ISSUE_TYPE_ACCOUNT ]: useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT ),
		[ ISSUE_TYPE_PRODUCT ]: useMCIssuesTypeFilter( ISSUE_TYPE_PRODUCT ),
	};
};

export default useMCIssues;
