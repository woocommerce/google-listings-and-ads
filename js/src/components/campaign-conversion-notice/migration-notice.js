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
 * Shows Notice {@link Notice}
 * It is the wrapper for the campaign conversion notices
 *
 *
 * @param {Object} props React props.
 * @param {JSX.Element} props.children Children to render.
 * @param {string} props.className Class to use in the Notice component.
 * @param {Object} props.localStorageKey Local Storage Key where is keep the dismiss state.
 * @return {JSX.Element} {@link Notice} element with the info message and the link to the documentation.
 */
const MigrationCampaignNotice = ( {
	children,
	className,
	localStorageKey,
} ) => {
	const defaultDismissedValue = !! localStorage.get( localStorageKey );

	const [ isDismissed, setIsDismissed ] = useState( defaultDismissedValue );

	const onRemove = () => {
		localStorage.set( localStorageKey, true );
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

export default MigrationCampaignNotice;
