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
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';
import { ISSUE_TYPE_PRODUCT, ISSUE_TYPE_ACCOUNT } from '.~/constants';
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

const IssuesTableCard = () => {
	const { totals } = useMCIssuesTotals();

	// We don't want to render if no issues are found
	if ( ! ( totals[ ISSUE_TYPE_ACCOUNT ] || totals[ ISSUE_TYPE_PRODUCT ] ) )
		return null;

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
				<IssuesTable />
			</Card>
		</AppTableCardDiv>
	);
};

export default IssuesTableCard;
