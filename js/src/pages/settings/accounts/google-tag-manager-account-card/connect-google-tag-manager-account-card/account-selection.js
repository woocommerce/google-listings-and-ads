/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { ExternalLink, Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';
import GoogleTagManagerAccountSelectControl from './google-tag-manager-account-select-control';
import NoticeDetail from './notice-detail';
import CreateNewAccountLink from './create-new-account-link';
import './notice-detail.scss';

/**
 * Clicking on the button to re-check for a newly created Google Tag Manager account.
 *
 * @event gla_google_tag_manager_check_connection_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the account-selection detail: the zero-accounts CTA (with its "Check again" refetch),
 * the single candidate account shown as plain text, or a selector when more than one exists.
 *
 * @fires gla_google_tag_manager_check_connection_again_button_click
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked account ID.
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account changes.
 * @return {JSX.Element|null} The detail, or `null` until the accounts list has resolved.
 */
export default function AccountSelection( { accountId, onAccountChange } ) {
	const {
		fetchGoogleTagManagerAccount,
		fetchExistingGoogleTagManagerAccounts,
	} = useAppDispatch();
	const { google } = useGoogleAccount();
	const { existingAccounts, hasFinishedResolution } =
		useExistingGoogleTagManagerAccounts();
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( ! existingAccounts?.length ) {
		// `Accounts` already gates rendering on every account — including Google's — having
		// resolved, so `google` itself is guaranteed set here; only its `email` can still be empty.
		const email = google.email || __( 'Google', 'google-listings-and-ads' );

		const handleCheckAgainClick = async () => {
			setIsRefreshing( true );
			await Promise.all( [
				fetchGoogleTagManagerAccount(),
				fetchExistingGoogleTagManagerAccounts(),
			] );
			setIsRefreshing( false );
		};

		return (
			<NoticeDetail
				status="warning"
				body={
					<p>
						{ sprintf(
							/* translators: %s: the connected Google account's email address, or "Google" if not set. */
							__(
								"We couldn't find a Google Tag Manager account associated with your %s account. If you have already created an account, click the 'Check again' button to fetch your account details.",
								'google-listings-and-ads'
							),
							email
						) }
					</p>
				}
				actions={ [
					<CreateNewAccountLink key="create-account" />,
					<AppButton
						key="check-again"
						onClick={ handleCheckAgainClick }
						eventName="gla_google_tag_manager_check_connection_again_button_click"
						eventProps={ { context: 'settings-tag-manager' } }
						disabled={ isRefreshing }
						loading={ isRefreshing }
						isTertiary
					>
						{ __( 'Check again', 'google-listings-and-ads' ) }
					</AppButton>,
				] }
			/>
		);
	}

	if ( existingAccounts.length === 1 ) {
		const [ singleAccount ] = existingAccounts;

		return (
			<Flex direction="column" gap={ 0 }>
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
										singleAccount.name,
										`<link>${ singleAccount.id }</link>`
									),
									{
										link: (
											<ExternalLink
												href={ getGoogleTagManagerAccountUrl(
													singleAccount.id
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

	return (
		<Flex direction="column" gap={ 3 }>
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
