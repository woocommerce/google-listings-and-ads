/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTabNav from '.~/components/app-tab-nav';
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';
import getActiveIssueType from '.~/product-feed/issues-table-card/getActiveIssueType';

const IssuesTypeNavigation = ( { totals } ) => {
	const getTotal = ( issueType ) =>
		totals[ issueType ] ? `(${ totals[ issueType ] })` : '';

	const tabs = [
		{
			key: ISSUE_TYPE_ACCOUNT,
			title: `${ __(
				'Account Issues',
				'google-listings-and-ads'
			) } ${ getTotal( ISSUE_TYPE_ACCOUNT ) }`,
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
			) } ${ getTotal( ISSUE_TYPE_PRODUCT ) }`,
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
