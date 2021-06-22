/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const ContentButtonLayout = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div
			className={ classnames( 'gla-content-button-layout', className ) }
			{ ...rest }
		/>
	);
};

export default ContentButtonLayout;
