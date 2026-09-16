/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import SearchableSelectControl from '../searchable-select-control';
import './index.scss';

/**
 * Wrapper around SearchableSelectControl to apply consistent styling across the app.
 */
const AppSearchableSelectControl = ( props ) => {
	const { className, ...rest } = props;

	return (
		<SearchableSelectControl
			className={ classNames(
				'gla-app-searchable-select-control',
				className
			) }
			{ ...rest }
		/>
	);
};

export default AppSearchableSelectControl;
