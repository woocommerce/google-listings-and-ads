/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';

/**
 * Gets the issues data for both Issue types (product & account)
 *
 * @return {Object} The `getMCIssues` dispatchers for account and product types and the total of issues combined
 * in the format `{account, product, total}`
 * @see useMCIssuesTypeFilter
 */
const useMCIssues = () => {
	const issueTypes = {
		[ ISSUE_TYPE_ACCOUNT ]: useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT ),
		[ ISSUE_TYPE_PRODUCT ]: useMCIssuesTypeFilter( ISSUE_TYPE_PRODUCT ),
	};

	return {
		...issueTypes,
		total: getTotal( issueTypes ),
	};
};

const getTotal = ( issueTypes ) => {
	const total = Object.values( issueTypes ).reduce(
		( accumulator, current ) => accumulator + current.data?.total,
		0
	);

	return Number.isInteger( total ) ? total : undefined;
};

export default useMCIssues;
