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

	return issueType ? issueTypes[ issueType ] : issueTypes;
};

export default useMCIssues;
