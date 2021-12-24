/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import useMCIssues from '.~/hooks/useMCIssues';

const useMCIssuesTotals = () => {
	const { data: account, hasFinishedResolution: hfrAccount } = useMCIssues(
		ISSUE_TYPE_ACCOUNT
	);
	const { data: product, hasFinishedResolution: hfrProduct } = useMCIssues(
		ISSUE_TYPE_PRODUCT
	);

	const total = account?.total + product?.total;

	return {
		hasFinishedResolution: hfrAccount && hfrProduct,
		totals: {
			[ ISSUE_TYPE_ACCOUNT ]: account?.total,
			[ ISSUE_TYPE_PRODUCT ]: product?.total,
			total: ! isNaN( total ) ? total : undefined,
		},
	};
};

export default useMCIssuesTotals;
