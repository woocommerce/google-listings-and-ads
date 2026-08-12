/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
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
import { info, moreVertical, warning } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import {
	SEARCH_CONSOLE_ACCOUNT_STATUS,
	SEARCH_CONSOLE_ACCOUNT_STEP,
} from '~/constants';
import { geReportsUrl } from '~/utils/urls';
import AppButton from '~/components/app-button';
import Badge from '~/components/badge';
import { appearanceDict } from '~/components/account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import useExistingSearchConsoleProperties from '~/hooks/useExistingSearchConsoleProperties';
import useVerifySearchConsoleProperty from '~/hooks/useVerifySearchConsoleProperty';
import useSearchConsoleConnectRedirect from '~/hooks/useSearchConsoleConnectRedirect';
import SearchConsoleSelectControl, {
	CREATE_NEW_PROPERTY_VALUE,
} from './search-console-select-control';
import {
	errorDescription,
	SearchConsoleErrorRow,
	SearchConsoleNoticeRow,
} from './search-console-row-shells';
import './search-console-account-row.scss';

const { CONNECTED } = SEARCH_CONSOLE_ACCOUNT_STATUS;
const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

// Google's own help article on verifying Search Console site ownership, linked from the
// verification step's "Learn more" action.
const VERIFICATION_LEARN_MORE_URL =
	'https://support.google.com/webmasters/answer/9008080';

/**
 * Clicking on the button to select (or create) a Search Console property.
 *
 * @event gla_search_console_property_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Clicking on the button to verify the Search Console property, either during the normal
 * verification step or after re-verification is needed following the "action needed" state.
 *
 * @event gla_search_console_verify_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Clicking on the button to (re)connect the Search Console account — covers reconnecting after
 * expiry and retrying after a failed attempt.
 *
 * @event gla_search_console_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the connected state: the icon/title/description, the connected property link, a
 * "Connected" badge, and an actions menu offering "View Organic Search report".
 *
 * The Reports page has no dedicated "Organic search" sub-view yet, so this links to the general
 * Reports page for now — swap in a deep link once that sub-view exists.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The connected row.
 */
function ConnectedRow( { account } ) {
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

/**
 * Renders the "connecting"/setting-up state: shown while the backend is still silently
 * resolving a single-match or no-match property.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The row.
 */
function ConnectingRow( { account } ) {
	return (
		<SearchConsoleNoticeRow
			account={ account }
			status="info"
			icon={ info }
			badgeLabel={ __( 'In progress', 'google-listings-and-ads' ) }
			title={ __(
				'Setting up Google Search Console',
				'google-listings-and-ads'
			) }
			body={ __(
				'We are connecting your account.',
				'google-listings-and-ads'
			) }
			action={
				<AppButton isSecondary href={ geReportsUrl() }>
					{ __( 'View reports', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
}

/**
 * Renders the property-selection step: while the backend is still resolving a single-match or
 * no-match property, the "connecting" row is shown; once multiple candidate properties are
 * reported, a selector (with a "Create new" option) lets the merchant choose which one to
 * connect.
 *
 * @fires gla_search_console_property_select_button_click
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The property-selection row.
 */
function PropertySelectionRow( { account } ) {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const { data: properties, hasFinishedResolution } =
		useExistingSearchConsoleProperties();
	const [ value, setValue ] = useState();

	const [ fetchSelectProperty, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/property`,
		method: 'POST',
	} );

	const handleSelectClick = async () => {
		try {
			const isCreatingNew = value === CREATE_NEW_PROPERTY_VALUE;

			await fetchSelectProperty( {
				data: isCreatingNew ? { create_new: true } : { url: value },
			} );
			invalidateResolution( 'getSearchConsoleAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to select your Search Console property. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	// Single-match or no-match: the backend has already resolved the property silently —
	// no prompt is shown.
	if ( ! hasFinishedResolution || ( properties ?? [] ).length <= 1 ) {
		return <ConnectingRow account={ account } />;
	}

	// Multi-match: show the selector, with non-covering properties greyed out and
	// explained, plus the "Create new" option.
	return (
		<SearchConsoleNoticeRow
			account={ account }
			status="info"
			icon={ info }
			badgeLabel={ __( 'In progress', 'google-listings-and-ads' ) }
			title={ __(
				'We found multiple Google Search Console properties',
				'google-listings-and-ads'
			) }
			body={ __( 'Pick one to connect.', 'google-listings-and-ads' ) }
			detail={
				<SearchConsoleSelectControl
					value={ value }
					onChange={ setValue }
				/>
			}
			action={
				<AppButton
					isSecondary
					disabled={ ! value }
					loading={ loading }
					eventName="gla_search_console_property_select_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleSelectClick }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
}

/**
 * Renders the verification step: a single "Verify site" click for the normal case, or a link
 * into Google's "request access" flow when the merchant can't self-verify.
 *
 * @fires gla_search_console_verify_button_click
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The verification row.
 */
function VerificationRow( { account } ) {
	const { searchConsoleAccount } = useSearchConsoleAccount();
	const { onClick: handleVerifyClick, loading } =
		useVerifySearchConsoleProperty();

	const canSelfVerify = searchConsoleAccount?.can_self_verify !== false;

	if ( ! canSelfVerify ) {
		return (
			<SearchConsoleNoticeRow
				account={ account }
				status="warning"
				icon={ warning }
				badgeLabel={ __( 'Action needed', 'google-listings-and-ads' ) }
				title={ __(
					"We couldn't verify your site",
					'google-listings-and-ads'
				) }
				body={ __(
					'Request access from your Search Console property owner to continue.',
					'google-listings-and-ads'
				) }
				action={
					<ExternalLink
						href={ searchConsoleAccount?.request_access_url }
					>
						{ __( 'Request access', 'google-listings-and-ads' ) }
					</ExternalLink>
				}
			/>
		);
	}

	return (
		<SearchConsoleNoticeRow
			account={ account }
			status="warning"
			icon={ warning }
			badgeLabel={ __( 'Action needed', 'google-listings-and-ads' ) }
			title={ __(
				'Verify your site with Google',
				'google-listings-and-ads'
			) }
			body={ __(
				'A one-time verification is needed before Search Console can collect search data for your site. We add the verification tag for you.',
				'google-listings-and-ads'
			) }
			action={
				<AppButton
					isSecondary
					loading={ loading }
					eventName="gla_search_console_verify_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleVerifyClick }
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>
			}
			secondaryAction={
				<ExternalLink href={ VERIFICATION_LEARN_MORE_URL }>
					{ __( 'Learn more', 'google-listings-and-ads' ) }
				</ExternalLink>
			}
		/>
	);
}

/**
 * Renders the "Action needed" state, shown when Search Console verification is found to have
 * been lost outside of the connect flow.
 *
 * @fires gla_search_console_verify_button_click
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The action-needed row.
 */
function ActionNeededRow( { account } ) {
	const { onClick: handleClick, loading } = useVerifySearchConsoleProperty();

	return (
		<SearchConsoleNoticeRow
			account={ account }
			status="warning"
			icon={ warning }
			badgeLabel={ __( 'Action needed', 'google-listings-and-ads' ) }
			title={ __(
				'Your Search Console property is no longer verified',
				'google-listings-and-ads'
			) }
			body={ __(
				'Verify it again to keep tracking organic performance.',
				'google-listings-and-ads'
			) }
			action={
				<AppButton
					isSecondary
					loading={ loading }
					eventName="gla_search_console_verify_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
}

/**
 * Renders a (re)connect action row shared by the reconnect, connection-failed, and generic
 * incomplete-resume states — undesigned states that fall back to a plain error treatment; each
 * differs only in its copy and button label.
 *
 * @fires gla_search_console_connect_button_click
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {string} props.description Row description for this state.
 * @param {string} props.buttonLabel Action button label for this state.
 * @param {string} props.errorMessage Notice shown if requesting the connect URL fails.
 * @param {boolean} [props.isError] Whether to render the description inside an error notice.
 * @return {JSX.Element} The row.
 */
function ReconnectRow( {
	account,
	description,
	buttonLabel,
	errorMessage,
	isError,
} ) {
	const { onClick: handleClick, loading } =
		useSearchConsoleConnectRedirect( errorMessage );

	return (
		<SearchConsoleErrorRow
			account={ account }
			description={ description }
			isError={ isError }
			action={
				<AppButton
					isSecondary
					isDestructive={ isError }
					loading={ loading }
					eventName="gla_search_console_connect_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
				>
					{ buttonLabel }
				</AppButton>
			}
		/>
	);
}

/**
 * Renders the specialized row for every non-default Search Console state: the connected steady
 * state, and every incomplete connect-flow sub-state — property selection, verification,
 * action-needed (verification lost), reconnect (connection expired), connection-failed (initial
 * attempt failed), and a generic resume fallback for an abandoned flow that isn't covered by a
 * more specific step — never a silent success.
 *
 * The connecting/property-selection/verification/action-needed states follow the landed Figma
 * design (status badge + colored notice with icon, title, and body). Reconnect, connection-failed,
 * and the generic fallback have no design yet, so they keep the plain error-notice treatment.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever state the backend currently reports.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The Search Console account row.
 */
export default function SearchConsoleAccountRow( { account } ) {
	const { searchConsoleAccount } = useSearchConsoleAccount();
	const status = searchConsoleAccount?.status;
	const step = searchConsoleAccount?.step;

	if ( status === CONNECTED ) {
		return <ConnectedRow account={ account } />;
	}

	if ( step === PROPERTY_SELECTION ) {
		return <PropertySelectionRow account={ account } />;
	}

	if ( step === VERIFICATION ) {
		return <VerificationRow account={ account } />;
	}

	if ( step === ACTION_NEEDED ) {
		return <ActionNeededRow account={ account } />;
	}

	if ( step === RECONNECT ) {
		return (
			<ReconnectRow
				account={ account }
				isError
				description={ errorDescription(
					__(
						'<alert>Connection expired:</alert> Your Search Console connection needs to be re-authorized.',
						'google-listings-and-ads'
					)
				) }
				buttonLabel={ __( 'Reconnect', 'google-listings-and-ads' ) }
				errorMessage={ __(
					'Unable to reconnect your Search Console account. Please try again later.',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	if ( step === CONNECTION_FAILED ) {
		return (
			<ReconnectRow
				account={ account }
				isError
				description={ errorDescription(
					__(
						"<alert>Connection failed:</alert> We couldn't connect your Search Console account. Please try again.",
						'google-listings-and-ads'
					)
				) }
				buttonLabel={ __( 'Retry', 'google-listings-and-ads' ) }
				errorMessage={ __(
					'Unable to connect your Search Console account. Please try again later.',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	// Generic fallback for an abandoned flow that isn't covered by a more specific step.
	return (
		<ReconnectRow
			account={ account }
			description={ __(
				"Your Search Console connection isn't complete yet.",
				'google-listings-and-ads'
			) }
			buttonLabel={ __( 'Resume setup', 'google-listings-and-ads' ) }
			errorMessage={ __(
				'Unable to resume your Search Console connection. Please try again later.',
				'google-listings-and-ads'
			) }
		/>
	);
}
