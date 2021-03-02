/**
 * Internal dependencies
 */
import Subsection from '.~/wcdl/subsection';
import ContentButtonLayout from '../content-button-layout';
import './index.scss';

const TitleButtonLayout = ( props ) => {
	const { title, button } = props;

	return (
		<ContentButtonLayout className="gla-title-button-layout">
			<Subsection.Title className="title">{ title }</Subsection.Title>
			{ button }
		</ContentButtonLayout>
	);
};

export default TitleButtonLayout;
