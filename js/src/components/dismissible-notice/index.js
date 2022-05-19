/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import localStorage from '.~/utils/localStorage';

/**
 * Shows dismissible Notice {@link Notice}
 * If localStorageKey is provided it will store the dismiss state in the localStorage
 *
 *
 * @param {Object} props React props.
 * @param {JSX.Element} props.children Children to render.
 * @param {string|null} props.localStorageKey Local Storage Key where is keep the dismiss state.
 * @param {Function} props.onRemove Callback when clicking on remove notice
 * @param {Object} props.restProps Props to be forwarded to Notice Component. Like className.
 * @return {JSX.Element} {@link Notice} element with the info message and the link to the documentation.
 */
const DismissibleNotice = ( {
	children,
	localStorageKey = null,
	onRemove = noop,
	...rest
} ) => {
	const defaultDismissedValue = localStorageKey
		? !! localStorage.get( localStorageKey )
		: false;

	const [ isDismissed, setIsDismissed ] = useState( defaultDismissedValue );

	const handleRemove = () => {
		if ( localStorageKey ) {
			localStorage.set( localStorageKey, true );
		}

		onRemove();

		setIsDismissed( true );
	};

	if ( isDismissed ) {
		return null;
	}

	return (
		<Notice onRemove={ handleRemove } { ...rest }>
			{ children }
		</Notice>
	);
};

export default DismissibleNotice;
