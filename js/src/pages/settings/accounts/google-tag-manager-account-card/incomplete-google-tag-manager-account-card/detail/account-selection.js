/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { ExternalLink, Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import GoogleTagManagerAccountSelectControl from '../google-tag-manager-account-select-control';
import NoticeDetail from '../notice-detail';
import CreateNewAccountLink from '../create-new-account-link';

/**
 * Renders the account-selection status's detail: the single candidate account shown as plain
 * text, or a selector when more than one exists. The "Connect" submit action for whichever is
 * currently picked lives in the sibling `Indicator`, not here — see the incomplete card's own
 * top-level component.
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked account ID.
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account changes.
 * @return {JSX.Element} The detail.
 */
export default function AccountSelection( { accountId, onAccountChange } ) {
	const { account } = useGoogleTagManagerAccount();
	const [ singleAccount ] = account.accounts;
	const hasMultipleAccounts = account.accounts.length > 1;

	// With only one candidate there's nothing to pick — auto-select it so "Connect" enables
	// without showing a selector that only ever has one option.
	useEffect( () => {
		if ( ! hasMultipleAccounts ) {
			onAccountChange( singleAccount.accountId );
		}
	}, [ hasMultipleAccounts, singleAccount, onAccountChange ] );

	if ( ! hasMultipleAccounts ) {
		return (
			<Flex direction="column" gap={ 3 } expanded={ false }>
				<NoticeDetail
					status="info"
					body={ [
						__(
							'We found your existing Google Tag Manager account.',
							'google-listings-and-ads'
						),
						<>
							{ singleAccount.name }{ ' ' }
							<ExternalLink href={ singleAccount.tagManagerUrl }>
								{ singleAccount.accountId }
							</ExternalLink>
						</>,
					] }
				/>
				<FlexItem>
					<CreateNewAccountLink />
				</FlexItem>
			</Flex>
		);
	}

	return (
		<Flex direction="column" gap={ 3 } expanded={ false }>
			<NoticeDetail
				status="info"
				body={ [
					__(
						'We found multiple Google Tag Manager accounts.',
						'google-listings-and-ads'
					),
					__(
						'Pick one to connect, or create a new one.',
						'google-listings-and-ads'
					),
				] }
			/>
			<FlexBlock>
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
