/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssues from '.~/hooks/useMCIssues';

const useMCIssuesCombined = () => {
	const [ account, hfrAccountIssues ] = useMCIssues( ISSUE_TYPE_ACCOUNT );
	const [ product, hfrProductIssues ] = useMCIssues( ISSUE_TYPE_PRODUCT );

	return {
		hasFinishedResolution: hfrProductIssues && hfrAccountIssues,
		data: {
			account,
			product,
			total: account?.total + product?.total,
		},
	};
};

export default useMCIssuesCombined;
