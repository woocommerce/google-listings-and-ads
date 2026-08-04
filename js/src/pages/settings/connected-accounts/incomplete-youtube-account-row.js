/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, __experimentalItem as Item } from '@wordpress/components';
import { useReducedMotion } from '@wordpress/compose';
import { useEffect, useRef } from '@wordpress/element';
import { getHistory, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { appearanceDict } from '~/components/account-card';
import useYouTubeSetupCompleteCallback from '~/hooks/useYouTubeSetupCompleteCallback';
import { getSettingsUrl } from '~/utils/urls';
import AccountActions from './account-row/account-actions';

const ACCOUNTS_SETTINGS_QUERY = { section: 'accounts' };

/**
 * Clicking on the button to link the YouTube account.
 *
 * @event gla_link_youtube_account_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

/**
 * Renders the incomplete YouTube account row with its setup CTA, error notice,
 * and OAuth return auto-complete behavior.
 *
 * @fires gla_link_youtube_account_button_click When the user clicks on the button to complete YouTube setup.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {(target: string) => void} props.onDisconnect Called with the account's disconnect-modal target when the Disconnect action is chosen.
 * @return {JSX.Element} The incomplete YouTube account row.
 */
export default function IncompleteYouTubeAccountRow( {
	account,
	onDisconnect,
} ) {
	const isReducedMotion = useReducedMotion();
	const isYouTubeOAuthReturn = getQuery()?.youtube === 'connected';
	const hasCompletedSetupRef = useRef( false );
	const containerRef = useRef();
	const [ handleFinishSetup, { loading, error } ] =
		useYouTubeSetupCompleteCallback();
	const actions = (
		<AccountActions account={ account } onDisconnect={ onDisconnect } />
	);
	const icon = appearanceDict[ account.appearance ]?.icon;

	useEffect( () => {
		let isActive = true;

		async function completeSetup() {
			containerRef.current?.scrollIntoView( {
				behavior: isReducedMotion ? 'auto' : 'smooth',
				inline: 'nearest',
				block: 'nearest',
			} );

			hasCompletedSetupRef.current = true;
			await handleFinishSetup();
			if ( ! isActive ) {
				return;
			}
			getHistory().replace( getSettingsUrl( ACCOUNTS_SETTINGS_QUERY ) );
		}

		if ( isYouTubeOAuthReturn && ! hasCompletedSetupRef.current ) {
			completeSetup();
		}

		return () => {
			isActive = false;
		};
	}, [ handleFinishSetup, isReducedMotion, isYouTubeOAuthReturn ] );

	return (
		<Item className="gla-connected-accounts__row" ref={ containerRef }>
			<div className="gla-connected-accounts__logo">{ icon }</div>
			<div className="gla-connected-accounts__subject">
				<div className="gla-connected-accounts__title">
					{ account.title }
				</div>
				<div className="gla-connected-accounts__description">
					{ loading
						? __( 'Please wait…', 'google-listings-and-ads' )
						: __(
								'Your YouTube account is connected, but setup isn’t complete yet.',
								'google-listings-and-ads'
						  ) }
				</div>
				{ error?.message && (
					<div className="gla-connected-accounts__notice">
						<Notice status="error" isDismissible={ false }>
							{ error.message }
						</Notice>
					</div>
				) }
			</div>
			<div className="gla-connected-accounts__indicator gla-connected-accounts__indicator--top">
				<div className="gla-connected-accounts__indicator-actions">
					<AppButton
						eventName="gla_link_youtube_account_button_click"
						eventProps={ { context: 'settings-youtube' } }
						onClick={ handleFinishSetup }
						disabled={ loading }
						loading={ loading }
						isSecondary
					>
						{ __( 'Complete setup', 'google-listings-and-ads' ) }
					</AppButton>
					{ actions }
				</div>
			</div>
		</Item>
	);
}
