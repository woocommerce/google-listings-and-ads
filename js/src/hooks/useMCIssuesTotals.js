/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';

/**
 * Returns the issues totals for both Issue types (product & account)
 *
 * @return {{account: number, product: number, total: number}} The total number of issues for account and product types
 * and the total of both issues combined.
 * @see useMCIssuesTypeFilter
 */
const useMCIssuesTotals = () => {
	const issueTypesTotals = {
		[ ISSUE_TYPE_ACCOUNT ]: useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT )
			?.data?.total,
		[ ISSUE_TYPE_PRODUCT ]: useMCIssuesTypeFilter( ISSUE_TYPE_PRODUCT )
			?.data?.total,
	};

	return {
		...issueTypesTotals,
		total: getTotal( issueTypesTotals ),
	};
};

const getTotal = ( issueTypes ) => {
	const total = Object.values( issueTypes ).reduce(
		( accumulator, current ) => accumulator + current,
		0
	);

	return Number.isInteger( total ) ? total : undefined;
};

export default useMCIssuesTotals;
