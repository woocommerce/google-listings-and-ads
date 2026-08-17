/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useSearchConsoleConnectRedirect from '~/hooks/useSearchConsoleConnectRedirect';
import SearchConsoleErrorAccountCard from './error-account-card';

/**
 * Clicking on the button to (re)connect the Search Console account — covers reconnecting after
 * expiry and retrying after a failed attempt.
 *
 * @event gla_search_console_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders a (re)connect action card shared by the reconnect, connection-failed, and generic
 * incomplete-resume states — undesigned states that fall back to a plain error treatment; each
 * differs only in its copy and button label.
 *
 * @fires gla_search_console_connect_button_click
 *
 * @param {Object} props Component props.
 * @param {string|JSX.Element} props.description Card description for this state.
 * @param {string} props.buttonLabel Action button label for this state.
 * @param {boolean} [props.isError] Whether to render the description inside an error notice.
 * @return {JSX.Element} The account card.
 */
export default function SearchConsoleConnectActionCard( {
	description,
	buttonLabel,
	isError,
} ) {
	const { onClick: handleClick, loading } = useSearchConsoleConnectRedirect();

	return (
		<SearchConsoleErrorAccountCard
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
