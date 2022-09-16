/**
 * Internal dependencies
 */
import Title from './title';
import Body from './body';
import HelperText from './helper-text';
import Subtitle from './subtitle';
import './index.scss';

const Subsection = ( props ) => {
	const { className = '', ...rest } = props;

	return <div className={ `wcdl-subsection ${ className }` } { ...rest } />;
};

Subsection.Title = Title;
Subsection.Subtitle = Subtitle;
Subsection.Body = Body;
Subsection.HelperText = HelperText;

export default Subsection;
