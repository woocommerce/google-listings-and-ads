/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	ExternalLink,
	Flex,
	FlexBlock,
	FlexItem,
	Notice,
	__experimentalItem as Item,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import { SEARCH_CONSOLE_ACCOUNT_STEP } from '~/constants';
import AppButton from '~/components/app-button';
import LoadingLabel from '~/components/loading-label';
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
import './search-console-account-row.scss';

const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

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
 * expiry, retrying after a failed attempt, and resuming an abandoned flow.
 *
 * @event gla_search_console_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the row shell shared by every incomplete Search Console sub-state: the icon/title
 * on the left, an optional error notice and detail content below the description, and the
 * step's action on the right.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {string} props.description Description overriding the account's static one for this step.
 * @param {boolean} [props.isError] Whether to render the description inside an error notice.
 * @param {import('react').ReactNode} [props.detail] Extra content rendered below the description (e.g. the property selector).
 * @param {import('react').ReactNode} props.action The step's action control, rendered on the right.
 * @return {JSX.Element} The row.
 */
function SearchConsoleRowShell( {
	account,
	description,
	isError,
	detail,
	action,
} ) {
	const icon = appearanceDict[ account.appearance ]?.icon;

	return (
		<Item className="gla-search-console-account-row">
			<Flex align="flex-start" gap={ 4 } wrap>
				<FlexItem>{ icon }</FlexItem>
				<FlexBlock>
					<div className="gla-search-console-account-row__title">
						{ account.title }
					</div>
					{ isError ? (
						<Notice
							status="error"
							isDismissible={ false }
							className="gla-search-console-account-row__notice"
						>
							{ description }
						</Notice>
					) : (
						<div className="gla-search-console-account-row__description">
							{ description }
						</div>
					) }
					{ detail && (
						<div className="gla-search-console-account-row__detail">
							{ detail }
						</div>
					) }
				</FlexBlock>
				<FlexItem className="gla-search-console-account-row__status-action">
					{ action }
				</FlexItem>
			</Flex>
		</Item>
	);
}

/**
 * Renders the property-selection step: while the backend is still resolving a single-match or
 * no-match property, a loading label is shown; once multiple candidate properties are reported,
 * a selector (with a "Create new" option) lets the merchant choose which one to connect.
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
		return (
			<SearchConsoleRowShell
				account={ account }
				description={ __(
					'Setting up your Search Console property…',
					'google-listings-and-ads'
				) }
				action={
					<LoadingLabel
						text={ __( 'Setting up…', 'google-listings-and-ads' ) }
					/>
				}
			/>
		);
	}

	// Multi-match: show the selector, with non-covering properties greyed out and
	// explained, plus the "Create new" option.
	return (
		<SearchConsoleRowShell
			account={ account }
			description={ __(
				'We found more than one Search Console property for your store. Choose the one to connect.',
				'google-listings-and-ads'
			) }
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
 * into Google's "request access" flow when the merchant can't self-verify. Always informational,
 * never styled as an error — tag placement happens automatically and this is just a confirmation.
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
			<SearchConsoleRowShell
				account={ account }
				description={ __(
					"We couldn't automatically verify your Search Console property. Request access from your Search Console property owner to continue.",
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
		<SearchConsoleRowShell
			account={ account }
			description={ __(
				"We've automatically placed a verification tag on your site. Verify your property to finish connecting.",
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
		<SearchConsoleRowShell
			account={ account }
			isError
			description={ __(
				'Your Search Console property is no longer verified. Verify it again to keep tracking organic performance.',
				'google-listings-and-ads'
			) }
			action={
				<AppButton
					isSecondary
					isDestructive
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
 * incomplete-resume states — each differs only in its copy and button label.
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
		<SearchConsoleRowShell
			account={ account }
			isError={ isError }
			description={ description }
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
 * Renders the specialized row for every incomplete Search Console connect-flow sub-state:
 * property selection, verification, action-needed (verification lost), reconnect (connection
 * expired), connection-failed (initial attempt failed), and a generic resume fallback for an
 * abandoned flow that isn't covered by a more specific step — never a silent success.
 *
 * Regardless of entry point (fresh page load, resuming from Accounts, or returning from an
 * OAuth redirect), this always resumes into whichever sub-state the backend currently reports.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The Search Console account row.
 */
export default function SearchConsoleAccountRow( { account } ) {
	const { searchConsoleAccount } = useSearchConsoleAccount();
	const step = searchConsoleAccount?.step;

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
				description={ __(
					'Your Search Console connection needs to be re-authorized.',
					'google-listings-and-ads'
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
				description={ __(
					"We couldn't connect your Search Console account. Please try again.",
					'google-listings-and-ads'
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
