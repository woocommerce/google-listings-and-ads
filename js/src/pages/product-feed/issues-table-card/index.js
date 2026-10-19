/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Card, CardHeader } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import AppTableCardDiv from '~/components/app-table-card-div';
import HelpPopover from '~/components/help-popover';
import AppDocumentationLink from '~/components/app-documentation-link';
import IssuesTable from './issues-table';
import ReviewRequest from '../review-request';
import { ACCOUNT_ISSUES_ID, ACCOUNT_STATUSES_WITH_ISSUES } from '../constants';
import { ISSUE_TYPE_ACCOUNT } from '~/constants';
import useMCIssuesTypeFilter from '~/hooks/useMCIssuesTypeFilter';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import Text from '~/components/app-text';
import './index.scss';

const actions = (
	<HelpPopover id="issues-to-resolve">
		{ createInterpolateElement(
			__(
				'Products and stores must meet <link>Google Merchant Center’s requirements</link> in order to get approved. WooCommerce and Google automatically check your product feed to help you resolve any issues. ',
				'google-listings-and-ads'
			),
			{
				link: (
					<AppDocumentationLink
						context="product-feed"
						linkId="issues-to-resolve"
						href="https://support.google.com/merchants/answer/6363310"
					/>
				),
			}
		) }
	</HelpPopover>
);

/**
 * @fires gla_table_go_to_page with `context: 'issues-to-resolve'`
 * @fires gla_table_page_click with `context: 'issues-to-resolve'`
 * @fires gla_documentation_link_click with `{ context: 'product-feed', link_id: 'issues-to-resolve', href: 'https://support.google.com/merchants/answer/6363310' }`
 */

/**
 * The card rendering the Merchant Center account issues in the Product feed, with
 * the account review request. It doesn't render if there are no account issues
 * and the account review status is neither Disapproved nor Warning.
 * Product issues are listed per product in the Product Feed table instead.
 *
 * @see IssuesTable
 * @return {JSX.Element|null} A Card with a Header and a table with account issues
 */
const IssuesTableCard = () => {
	const total = useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT )?.data?.total;
	const account = useAppSelectDispatch( 'getMCReviewRequest' );
	const hasAccountReviewIssues = ACCOUNT_STATUSES_WITH_ISSUES.includes(
		account.data?.status
	);

	// Render only when there are account issues, or the account review status
	// has issues to fix and a review to request.
	if ( ! total && ! hasAccountReviewIssues ) {
		return null;
	}

	return (
		<AppTableCardDiv
			className="gla-issues-table-card"
			id={ ACCOUNT_ISSUES_ID }
			// Focusable so the overview's link can move focus here.
			tabIndex={ -1 }
		>
			<Card
				className={ classnames( 'woocommerce-table', {
					'has-actions': !! actions,
				} ) }
			>
				<CardHeader>
					{ /* We use this Text component to make it similar to TableCard component. */ }
					<Text variant="title-small" as="h2">
						{ __( 'Account issues', 'google-listings-and-ads' ) }
					</Text>
					{ /* This is also similar to TableCard component implementation. */ }
					<div className="woocommerce-table__actions">
						{ actions }
					</div>
				</CardHeader>
				<ReviewRequest account={ account } />
				<IssuesTable />
			</Card>
		</AppTableCardDiv>
	);
};

export default IssuesTableCard;
