/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { FOCUS_ACCOUNT_CARD_PARAM } from '~/constants';
import useScrollIntoView from '~/hooks/useScrollIntoView';

/**
 * Wraps an account card so that it scrolls into view when the `focus-account-card` query arg
 * matches its `id`, then removes that query arg from the URL. Render it only once the wrapped
 * card's data has resolved, so the card is in place when it scrolls.
 *
 * @param {Object} props Component props.
 * @param {string} props.id The value of the `focus-account-card` query arg that focuses this card, e.g. `search-console`.
 * @param {JSX.Element} props.children The account card to wrap.
 * @return {JSX.Element} The wrapped account card.
 */
const FocusableAccountCard = ( { id, children } ) => {
	const { containerRef, scrollIntoView } = useScrollIntoView();
	const isFocused = getQuery()?.[ FOCUS_ACCOUNT_CARD_PARAM ] === id;

	useEffect( () => {
		if ( ! isFocused ) {
			return;
		}

		scrollIntoView();
		getHistory().replace(
			getNewPath( { [ FOCUS_ACCOUNT_CARD_PARAM ]: undefined } )
		);
	}, [ isFocused, scrollIntoView ] );

	return <div ref={ containerRef }>{ children }</div>;
};

export default FocusableAccountCard;
