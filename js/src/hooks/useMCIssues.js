/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';

/**
 * Gets the issues data filtering by Issue type (product|account)
 * If no filter is provided then, it returns both and also the sum of the total.
 *
 * @param {string|undefined} [issueType=undefined|'account'|'product']
 * @return {Object} The `getMCIssues` dispatcher filtered, or both and the total number
 * of issues in the format `{account, product, total}` if no filter is provided.
 * @see useMCIssuesTypeFilter
 */
const useMCIssues = ( issueType ) => {
	const issueTypes = {
		[ ISSUE_TYPE_ACCOUNT ]: useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT ),
		[ ISSUE_TYPE_PRODUCT ]: useMCIssuesTypeFilter( ISSUE_TYPE_PRODUCT ),
	};

	return issueType ? issueTypes[ issueType ] : issueTypes;
};

export default useMCIssues;
