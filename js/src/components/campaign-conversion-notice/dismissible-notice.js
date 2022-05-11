/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';

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
 * @param {string} props.className Class to use in the Notice component.
 * @param {string|null} props.localStorageKey Local Storage Key where is keep the dismiss state.
 * @return {JSX.Element} {@link Notice} element with the info message and the link to the documentation.
 */
const DismissibleNotice = ( {
	children,
	className,
	localStorageKey = null,
} ) => {
	const defaultDismissedValue = localStorageKey
		? !! localStorage.get( localStorageKey )
		: false;

	const [ isDismissed, setIsDismissed ] = useState( defaultDismissedValue );

	const onRemove = () => {
		if ( localStorageKey ) {
			localStorage.set( localStorageKey, true );
		}

		setIsDismissed( true );
	};

	if ( isDismissed ) {
		return null;
	}

	return (
		<Notice className={ className } onRemove={ onRemove }>
			{ children }
		</Notice>
	);
};

export default DismissibleNotice;
