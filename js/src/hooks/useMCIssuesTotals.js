/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssues from '.~/hooks/useMCIssues';

const useMCIssuesTotals = () => {
	const { account, product } = useMCIssues();

	const accountTotal = account?.data?.total;
	const productTotal = product?.data?.total;

	const total = accountTotal + productTotal;

	return {
		totals: {
			[ ISSUE_TYPE_ACCOUNT ]: accountTotal,
			[ ISSUE_TYPE_PRODUCT ]: productTotal,
			total: ! isNaN( total ) ? total : undefined,
		},
	};
};

export default useMCIssuesTotals;
