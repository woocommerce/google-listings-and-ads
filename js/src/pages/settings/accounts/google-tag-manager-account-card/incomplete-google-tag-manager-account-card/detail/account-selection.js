/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { ExternalLink, Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import GoogleTagManagerAccountSelectControl from '../google-tag-manager-account-select-control';
import NoticeDetail from '../notice-detail';
import CreateNewAccountLink from '../create-new-account-link';

/**
 * Renders the account-selection step's detail: the single candidate account shown as plain
 * text, or a selector when more than one exists. Sourced from `getExistingGoogleTagManagerAccounts`
 * (a resolver independent of the connection's own), so this waits on its own resolution rather
 * than the connection's. The "Connect" submit action for whichever is currently picked lives in
 * the sibling `Indicator`, not here — see the incomplete card's own top-level component.
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked account ID.
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account changes.
 * @return {JSX.Element|null} The detail, or `null` until the accounts list has resolved.
 */
export default function AccountSelection( { accountId, onAccountChange } ) {
	const { existingAccounts, hasFinishedResolution } =
		useExistingGoogleTagManagerAccounts();
	const hasMultipleAccounts = ( existingAccounts?.length ?? 0 ) > 1;
	const [ singleAccount ] = existingAccounts ?? [];

	// With only one candidate there's nothing to pick — auto-select it so "Connect" enables
	// without showing a selector that only ever has one option.
	useEffect( () => {
		if ( ! singleAccount || hasMultipleAccounts ) {
			return;
		}

		onAccountChange( singleAccount.id );
	}, [ hasMultipleAccounts, singleAccount, onAccountChange ] );

	if ( ! hasFinishedResolution ) {
		return null;
	}

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
						createInterpolateElement(
							sprintf(
								/* translators: %1$s: account name, %2$s: account ID link */
								__( '%1$s %2$s', 'google-listings-and-ads' ),
								singleAccount.name,
								'<link>' + singleAccount.id + '</link>'
							),
							{
								link: (
									<ExternalLink
										href={ singleAccount.tagManagerUrl }
									/>
								),
							}
						),
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
