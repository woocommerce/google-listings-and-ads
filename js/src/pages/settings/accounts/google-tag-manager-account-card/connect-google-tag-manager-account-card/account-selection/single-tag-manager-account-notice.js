/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink, Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';
import NoticeDetail from '../../notice-detail';
import CreateNewAccountLink from './create-new-account-link';

/**
 * Renders the notice shown when exactly one Google Tag Manager account was found: its name and a
 * link to the account ID, plus a create-new-account link.
 *
 * @param {Object} props Component props.
 * @param {Object} props.account The single account found. Shape: `{ id, name }`.
 * @return {JSX.Element} The notice.
 */
export default function SingleTagManagerAccountNotice( { account } ) {
	return (
		<Flex direction="column">
			<NoticeDetail
				status="info"
				body={
					<>
						<p>
							{ __(
								'We found your existing Google Tag Manager account.',
								'google-listings-and-ads'
							) }
						</p>
						<p>
							{ createInterpolateElement(
								sprintf(
									/* translators: %1$s: account name, %2$s: account ID link */
									__(
										'%1$s %2$s',
										'google-listings-and-ads'
									),
									account.name,
									`<link>${ account.id }</link>`
								),
								{
									link: (
										<ExternalLink
											href={ getGoogleTagManagerAccountUrl(
												account.id
											) }
										/>
									),
								}
							) }
						</p>
					</>
				}
			/>
			<FlexItem>
				<CreateNewAccountLink />
			</FlexItem>
		</Flex>
	);
}
