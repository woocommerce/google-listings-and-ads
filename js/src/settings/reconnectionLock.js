/**
 * External dependencies
 */
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { getQueryArgs } from '@wordpress/url';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { pagePaths } from '.~/utils/urls';
import isWCNavigationEnabled from '.~/utils/isWCNavigationEnabled';
import './reconnectionLock.scss';

/*
 * When required accounts were disconnected, the available link is
 * the corresponding reconnecting subpage under the Settings page.
 *
 * But there is no reliable way to disable the menu link of WC Navigation,
 * so the given workaround is using a pair of singleton locking and unlocking
 * functions to disable the GLA menu links globally, and lock the current link
 * as the available link when redirecting.
 */

let unlockCallback = null;

/**
 * Disables the GLA menu links of WC Navigation except for the Settings link
 * and uses the current link as the locked link. When clicking on the Settings link,
 * it will redirect to the locked link.
 */
function lockInReconnection() {
	if ( unlockCallback ) {
		return;
	}

	const lockedQuery = getQueryArgs( document.location.search );
	const handleClick = ( e ) => {
		const { target } = e;

		if ( target.tagName !== 'A' ) {
			return;
		}

		const paths = [
			pagePaths.dashboard,
			pagePaths.reports,
			pagePaths.productFeed,
			pagePaths.settings,
		];

		const targetQuery = getQueryArgs( target.getAttribute( 'href' ) );
		if ( paths.includes( targetQuery.path ) ) {
			e.stopPropagation();
			e.preventDefault();

			/**
			 * With WC Navigation, the available entry link on menu is the GLA Settings;
			 * With Classic Navigation, the available entry link is the only one link on menu.
			 *
			 * When clicking on the available entry link, redirects to the locked link
			 * if the current link is not the locked link.
			 */
			const isAvailableEntry = isWCNavigationEnabled()
				? targetQuery.path === pagePaths.settings
				: true;
			const currentQuery = getQueryArgs( document.location.search );
			if ( isAvailableEntry && ! isEqual( lockedQuery, currentQuery ) ) {
				const { path, ...query } = lockedQuery;

				getHistory().push( getNewPath( query, path, null ) );
			}
		}
	};

	document.defaultView.addEventListener( 'click', handleClick, true );
	document.body.classList.add( 'gla-reconnection-lock' );

	unlockCallback = () => {
		document.defaultView.removeEventListener( 'click', handleClick, true );
		document.body.classList.remove( 'gla-reconnection-lock' );
	};
}

/**
 * Enables the GLA menu links of WC Navigation that are locked by
 * the `lockInReconnection` function.
 */
function unlockFromReconnection() {
	if ( unlockCallback ) {
		unlockCallback();
		unlockCallback = null;
	}
}

export { lockInReconnection, unlockFromReconnection };
