/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Subsection from '.~/wcdl/subsection';
import './index.scss';

const Title = ( props ) => {
	const { className, ...rest } = props;

	return (
		<Subsection.Title
			className={ classnames( 'wcdl-section-card-title', className ) }
			{ ...rest }
		/>
	);
};

export default Title;
