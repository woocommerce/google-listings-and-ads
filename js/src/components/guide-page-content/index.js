/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

export default function GuidePageContent( { title, children } ) {
	return (
		<div className="gla-guide__page-content">
			<h2 className="gla-guide__page-content__header">{ title }</h2>
			<div className="gla-guide__page-content__body">{ children }</div>
		</div>
	);
}
