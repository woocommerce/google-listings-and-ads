/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useReducedMotion } from '@wordpress/compose';
import { useEffect, useRef } from '@wordpress/element';
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useYouTubeSetupCompleteCallback from '~/hooks/useYouTubeSetupCompleteCallback';

const ACCOUNTS_SETTINGS_PATH = '/google/settings';
const ACCOUNTS_SETTINGS_QUERY = { section: 'accounts' };

/**
 * Renders the incomplete YouTube account row with its setup CTA, error notice,
 * and OAuth return auto-complete behavior.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {JSX.Element|null} props.actions Optional account actions menu.
 * @return {JSX.Element} The incomplete YouTube account row.
 */
export default function IncompleteYouTubeAccountRow( { account, actions } ) {
	const isReducedMotion = useReducedMotion();
	const isYouTubeOAuthReturn = getQuery()?.youtube === 'connected';
	const hasCompletedSetupRef = useRef( false );
	const containerRef = useRef();
	const [ handleFinishSetup, { loading, error } ] =
		useYouTubeSetupCompleteCallback();

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
			getHistory().replace(
				getNewPath(
					ACCOUNTS_SETTINGS_QUERY,
					ACCOUNTS_SETTINGS_PATH,
					null
				)
			);
		}

		if ( isYouTubeOAuthReturn && ! hasCompletedSetupRef.current ) {
			completeSetup();
		}

		return () => {
			isActive = false;
		};
	}, [ handleFinishSetup, isReducedMotion, isYouTubeOAuthReturn ] );

	return (
		<div className="gla-connected-accounts__row" ref={ containerRef }>
			<img
				className="gla-connected-accounts__logo"
				src={ account.logo }
				alt=""
				width="40"
				height="40"
			/>
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
		</div>
	);
}
