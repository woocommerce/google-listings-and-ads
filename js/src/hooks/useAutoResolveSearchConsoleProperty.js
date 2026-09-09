/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useShouldResolveSearchConsoleProperty from './useShouldResolveSearchConsoleProperty';
import useResolveSearchConsoleProperty from './useResolveSearchConsoleProperty';

/**
 * @typedef {Object} AutoResolveSearchConsolePropertyData
 * @property {boolean} hasDetermined Whether the checks to determine auto-resolution are finished.
 * @property {boolean} isResolving Whether an auto-resolve request is currently in flight.
 * @property {boolean} justResolved Whether this hook instance just auto-resolved a property. This
 *   is the frontend-owned replacement for the backend's one-time `just_resolved` flag: since the
 *   frontend now performs the resolution itself (rather than being told about it after the fact),
 *   it already knows locally when this happened, for as long as this card stays mounted.
 */

/**
 * Automatically resolves a Google Search Console property on the merchant's behalf — mirrors
 * {@see useAutoCreateAdsAccount}'s shape: {@see useShouldResolveSearchConsoleProperty} makes the
 * decision, this hook orchestrates firing it exactly once per mount via a lock ref, matching that
 * hook's tradeoff of not resetting on failure — a merchant hitting a transient error needs to
 * remount (e.g. reload) to retry, rather than the effect silently retrying behind their back.
 *
 * @return {AutoResolveSearchConsolePropertyData} Object containing auto-resolve data.
 */
const useAutoResolveSearchConsoleProperty = () => {
	const lockedRef = useRef( false );
	const { hasDetermined, shouldCreate, shouldAutoSelect, autoSelectSiteUrl } =
		useShouldResolveSearchConsoleProperty();
	const [ isResolving, setIsResolving ] = useState( false );
	const [ justResolved, setJustResolved ] = useState( false );
	const [ resolveProperty ] = useResolveSearchConsoleProperty();

	useEffect( () => {
		if (
			// Wait for all determinations to be ready.
			! hasDetermined ||
			// Avoid repeated calls.
			lockedRef.current ||
			// Nothing to auto-resolve — either already resolved, or a genuine multi-match
			// the merchant needs to choose between.
			( ! shouldCreate && ! shouldAutoSelect )
		) {
			return;
		}

		lockedRef.current = true;
		setIsResolving( true );

		const handleResolveCallback = async () => {
			try {
				await resolveProperty(
					shouldAutoSelect ? autoSelectSiteUrl : undefined
				);
				setJustResolved( true );
			} catch ( error ) {
				// Swallowed here — `useResolveSearchConsoleProperty` already surfaced a
				// notice and refreshed the store, so the merchant sees an up-to-date
				// property list (and the picker, if it's now a genuine multi-match).
			} finally {
				setIsResolving( false );
			}
		};

		handleResolveCallback();
	}, [
		hasDetermined,
		shouldCreate,
		shouldAutoSelect,
		autoSelectSiteUrl,
		resolveProperty,
	] );

	return {
		hasDetermined,
		isResolving,
		justResolved,
	};
};

export default useAutoResolveSearchConsoleProperty;
