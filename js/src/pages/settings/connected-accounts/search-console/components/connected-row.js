/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	DropdownMenu,
	ExternalLink,
	Flex,
	FlexBlock,
	FlexItem,
	MenuGroup,
	MenuItem,
	__experimentalItem as Item,
} from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { geReportsUrl } from '~/utils/urls';
import Badge from '~/components/badge';
import { appearanceDict } from '~/components/account-card';

/**
 * Renders the connected state: the icon/title/description, the connected property link, a
 * "Connected" badge, and an actions menu offering "View Organic Search report".
 *
 * The Reports page has no dedicated "Organic search" sub-view yet, so this links to the general
 * Reports page for now — swap in a deep link once that sub-view exists.
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The connected row.
 */
export default function ConnectedRow( { account } ) {
	const icon = appearanceDict[ account.appearance ]?.icon;
	const accountActionsLabel = __(
		'Account actions for Google Search Console',
		'google-listings-and-ads'
	);

	return (
		<Item className="gla-search-console-account-row">
			<Flex align="flex-start" gap={ 4 } wrap>
				<FlexItem>{ icon }</FlexItem>
				<FlexBlock>
					<div className="gla-search-console-account-row__title">
						{ account.title }
					</div>
					<div className="gla-search-console-account-row__description">
						{ account.description }
					</div>
					{ account.detail && (
						<div className="gla-search-console-account-row__detail">
							{ account.detailUrl ? (
								<ExternalLink href={ account.detailUrl }>
									{ account.detail }
								</ExternalLink>
							) : (
								account.detail
							) }
						</div>
					) }
				</FlexBlock>
				<FlexItem className="gla-search-console-account-row__status-action">
					<Flex align="center" gap={ 3 } justify="flex-end">
						<Badge intent="success">
							{ __( 'Connected', 'google-listings-and-ads' ) }
						</Badge>
						<DropdownMenu
							icon={ moreVertical }
							label={ accountActionsLabel }
							popoverProps={ { placement: 'bottom-end' } }
						>
							{ () => (
								<MenuGroup>
									<MenuItem href={ geReportsUrl() }>
										{ __(
											'View Organic Search report',
											'google-listings-and-ads'
										) }
									</MenuItem>
								</MenuGroup>
							) }
						</DropdownMenu>
					</Flex>
				</FlexItem>
			</Flex>
		</Item>
	);
}
