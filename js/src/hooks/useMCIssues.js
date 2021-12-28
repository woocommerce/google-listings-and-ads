/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFIlter';

const useMCIssues = ( issueType ) => {
	const issueTypes = {
		[ ISSUE_TYPE_ACCOUNT ]: useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT ),
		[ ISSUE_TYPE_PRODUCT ]: useMCIssuesTypeFilter( ISSUE_TYPE_PRODUCT ),
	};

	return issueType
		? issueTypes[ issueType ]
		: { ...issueTypes, total: getTotal( issueTypes ) };
};

const getTotal = ( issueTypes ) => {
	const total = Object.values( issueTypes ).reduce(
		( accumulator, current ) => accumulator + current.data?.total,
		0
	);

	return Number.isInteger( total ) ? total : undefined;
};

export default useMCIssues;
