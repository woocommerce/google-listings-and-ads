/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';

const Status = ( { icon, title, label, description, className } ) => {
	return (
		<Flex className={ className } justify="normal" gap={ 1 }>
			<FlexItem>{ title }</FlexItem>
			<FlexItem className="gla-status-icon">{ icon }</FlexItem>
			<FlexItem>
				<span className="gla-status-label">{ label }</span>
				<span className="gla-status-description">{ description }</span>
			</FlexItem>
		</Flex>
	);
};

export default Status;
