/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * A container div that is meant to be used in `TableCard`'s `actions` props.
 *
 * This will make the children aligned to the left. Without using this container, TableCard will make the `actions` aligned to the right.
 *
 * Also adds margin-left to the container div, so that there is some spacing between the children and the TableCard's `title`.
 *
 * @param {Object} props Props to be passed to the container div.
 */
const AppTableCardActionsContainer = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div
			className={ classnames(
				'app-table-card-actions-container',
				className
			) }
			{ ...rest }
		/>
	);
};

export default AppTableCardActionsContainer;
