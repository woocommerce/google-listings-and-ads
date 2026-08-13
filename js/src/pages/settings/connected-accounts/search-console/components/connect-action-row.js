/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useSearchConsoleConnectRedirect from '../hooks/useSearchConsoleConnectRedirect';
import SearchConsoleErrorRow from './search-console-error-row';

/**
 * Clicking on the button to (re)connect the Search Console account — covers reconnecting after
 * expiry and retrying after a failed attempt.
 *
 * @event gla_search_console_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders a (re)connect action row shared by the reconnect, connection-failed, and generic
 * incomplete-resume states — undesigned states that fall back to a plain error treatment; each
 * differs only in its copy and button label.
 *
 * @fires gla_search_console_connect_button_click
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {string} props.description Row description for this state.
 * @param {string} props.buttonLabel Action button label for this state.
 * @param {boolean} [props.isError] Whether to render the description inside an error notice.
 * @return {JSX.Element} The row.
 */
export default function ConnectActionRow( {
	account,
	description,
	buttonLabel,
	isError,
} ) {
	const { onClick: handleClick, loading } = useSearchConsoleConnectRedirect();

	return (
		<SearchConsoleErrorRow
			account={ account }
			description={ description }
			isError={ isError }
			action={
				<AppButton
					eventName="gla_search_console_connect_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
					isDestructive={ isError }
					loading={ loading }
					isSecondary
				>
					{ buttonLabel }
				</AppButton>
			}
		/>
	);
}
