/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import GoogleTagManagerAccountSelectControl from './google-tag-manager-account-select-control';
import NoticeDetail from './notice-detail';
import CreateNewAccountLink from './create-new-account-link';

/**
 * Renders the notice shown when multiple Google Tag Manager accounts were found: a picker to
 * choose which to connect, plus a create-new-account link.
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked account ID.
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account changes.
 * @return {JSX.Element} The notice.
 */
export default function MultipleTagManagerAccountsNotice( {
	accountId,
	onAccountChange,
} ) {
	return (
		<Flex direction="column">
			<FlexBlock>
				<NoticeDetail
					status="info"
					body={
						<>
							<p>
								{ __(
									'We found multiple Google Tag Manager accounts.',
									'google-listings-and-ads'
								) }
							</p>
							<p>
								{ __(
									'Pick one to connect, or create a new one.',
									'google-listings-and-ads'
								) }
							</p>
						</>
					}
				/>
				<GoogleTagManagerAccountSelectControl
					value={ accountId }
					onChange={ onAccountChange }
				/>
			</FlexBlock>
			<FlexItem>
				<CreateNewAccountLink />
			</FlexItem>
		</Flex>
	);
}
