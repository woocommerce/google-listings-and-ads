/**
 * External dependencies
 */
import { CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const Body = ( props ) => {
	const { children } = props;

	return <CardBody className="wcdl-section-card-body">{ children }</CardBody>;
};

export default Body;
