/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import {
	Card,
	CardHeader,
	__experimentalText as Text,
} from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import AppTableCardDiv from '.~/components/app-table-card-div';
import HelpPopover from '.~/components/help-popover';
import AppDocumentationLink from '.~/components/app-documentation-link';
import IssuesTable from '.~/product-feed/issues-table-card/issues-table';
import IssuesTypeNavigation from '.~/product-feed/issues-table-card/issues-type-navigation';
import ReviewRequest from '.~/product-feed/review-request';
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
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
 * Triggered when edit links are clicked from Issues to resolve table.
 *
 * @event gla_edit_product_issue_click
 * @property {string} code Issue code returned from Google
 * @property {string} issue Issue description returned from Google
 */

/**
 * @fires gla_edit_product_issue_click
 * @fires gla_table_go_to_page with `context: 'issues-to-resolve'`
 * @fires gla_table_page_click with `context: 'issues-to-resolve'`
 */

/**
 * The card rendering the Merchant Center Issues in the Product feed. It doesn't render if there is no issues.
 * It uses useMCIssuesTotals for getting the total of issues.
 *
 * @see useMCIssuesTotals
 * @see IssuesTypeNavigation
 * @see IssuesTable
 * @return {JSX.Element|null} A Card with a Header, a Navigation and a table with issues
 */
const IssuesTableCard = () => {
	const { total } = useMCIssuesTotals();
	const account = useAppSelectDispatch( 'getMCReviewRequest' );

	// We don't want to render if no issues are found
	if ( ! total ) return null;

	return (
		<AppTableCardDiv className="gla-issues-table-card">
			<Card
				className={ classnames( 'woocommerce-table', {
					'has-actions': !! actions,
				} ) }
			>
				<CardHeader>
					{ /* We use this Text component to make it similar to TableCard component. */ }
					<Text variant="title.small" as="h2">
						{ __( 'Issues to resolve', 'google-listings-and-ads' ) }
					</Text>
					{ /* This is also similar to TableCard component implementation. */ }
					<div className="woocommerce-table__actions">
						{ actions }
					</div>
				</CardHeader>
				<IssuesTypeNavigation />
				<ReviewRequest account={ account } />
				<IssuesTable />
			</Card>
		</AppTableCardDiv>
	);
};

export default IssuesTableCard;
