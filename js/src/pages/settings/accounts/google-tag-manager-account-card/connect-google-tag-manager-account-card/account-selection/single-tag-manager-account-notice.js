/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import NoticeDetail from '../../notice-detail';
import AccountNameWithLink from '../../account-name-with-link';
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
							<AccountNameWithLink account={ account } />
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
