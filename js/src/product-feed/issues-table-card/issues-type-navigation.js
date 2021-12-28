/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import AppTabNav from '.~/components/app-tab-nav';
import getActiveIssueType from '.~/product-feed/issues-table-card/getActiveIssueType';
import useMCIssues from '.~/hooks/useMCIssues';

const IssuesTypeNavigation = () => {
	const issueTypes = useMCIssues();

	const issuesTotal = ( issueType ) => {
		const total = issueTypes[ issueType ]?.data?.total;
		return total >= 0 ? `(${ total })` : '';
	};

	const tabs = [
		{
			key: ISSUE_TYPE_ACCOUNT,
			title: `${ __(
				'Account Issues',
				'google-listings-and-ads'
			) } ${ issuesTotal( ISSUE_TYPE_ACCOUNT ) }`,
			href: getNewPath(
				{ issueType: ISSUE_TYPE_ACCOUNT },
				'/google/product-feed',
				{}
			),
		},
		{
			key: ISSUE_TYPE_PRODUCT,
			title: `${ __(
				'Product Issues',
				'google-listings-and-ads'
			) } ${ issuesTotal( ISSUE_TYPE_PRODUCT ) }`,
			href: getNewPath(
				{ issueType: ISSUE_TYPE_PRODUCT },
				'/google/product-feed',
				{}
			),
		},
	];

	return <AppTabNav tabs={ tabs } selectedKey={ getActiveIssueType() } />;
};

export default IssuesTypeNavigation;
