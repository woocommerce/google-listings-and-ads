/**
 * External dependencies
 */
import { format } from '@wordpress/date';
import { CardBody, Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import AppButton from '~/components/app-button';
import './notification.scss';

const PROVIDER_ICONS = {
	google: googleLogoURL,
};

const Notification = ( {
	provider = 'google',
	title,
	description,
	date,
	ctas = [],
} ) => {
	return (
		<CardBody className="gla-notification">
			<Flex align="flex-start" gap={ 4 }>
				<FlexItem>
					<div className="gla-notification__icon">
						<img src={ PROVIDER_ICONS[ provider ] } alt="" />
					</div>
				</FlexItem>
				<FlexBlock>
					<h4 className="gla-notification__title">{ title }</h4>
					<p className="gla-notification__description">
						{ description }
					</p>
					<Flex
						justify="flex-start"
						className="gla-notification__footer"
						gap={ 0 }
					>
						<FlexItem>
							<span className="gla-notification__date">
								{ format( 'F j', date ) }
							</span>
						</FlexItem>
						<FlexItem>
							<Flex gap={ 0 }>
								{ ctas.map( ( { id, label, onClick } ) => (
									<FlexItem
										key={ id }
										className="gla-notification__action"
									>
										<AppButton
											className="gla-notification__cta"
											onClick={ onClick }
											variant="link"
										>
											{ label }
										</AppButton>
									</FlexItem>
								) ) }
							</Flex>
						</FlexItem>
					</Flex>
				</FlexBlock>
			</Flex>
		</CardBody>
	);
};

export default Notification;
