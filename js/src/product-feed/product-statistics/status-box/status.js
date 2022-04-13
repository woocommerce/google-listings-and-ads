/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';

const Status = ( { icon, title, status, description } ) => {
	return (
		<Flex justify="normal" gap={ 1 }>
			<FlexItem>{ title }</FlexItem>
			<FlexItem>
				{ icon }
				{ status }
			</FlexItem>
			<FlexItem>{ description }</FlexItem>
		</Flex>
	);
};

export default Status;
