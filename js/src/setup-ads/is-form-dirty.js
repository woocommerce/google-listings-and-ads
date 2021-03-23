/**
 * External dependencies
 */
import isEqual from 'lodash/isEqual';

/**
 * Internal dependencies
 */
import initialValues from './initial-values';

const isFormDirty = ( formProps ) => {
	const { values } = formProps;

	return ! isEqual( values, initialValues );
};

export default isFormDirty;
