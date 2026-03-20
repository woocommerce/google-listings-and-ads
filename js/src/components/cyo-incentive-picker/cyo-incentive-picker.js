/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCYOIncentives from '~/hooks/useCYOIncentives';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import CYOIAvailableOffers from './cyoi-available-offers';
import styles from './cyo-incentive-picker.module.scss';
import './index.scss';

const CyoIncentivePicker = () => {
	const { data: incentives, hasFinishedResolution } = useCYOIncentives();

	if ( ! hasFinishedResolution || ! incentives || incentives.length === 0 ) {
		return null;
	}

	return (
		<div className="gla-cyoi-section">
			<Section
				verticalGap={ 4 }
				title={ __( 'Ads credit offer', 'google-listings-and-ads' ) }
				description={
					<p>
						{ __(
							'New advertisers are eligible for free Google Ads credits.',
							'google-listings-and-ads'
						) }
					</p>
				}
			>
				<Section.Card>
					<Section.Card.Body className="gla-cyoi-section__card-body">
						<div>
							<Subsection.Title>
								{ __(
									'Choose a sign-up offer to jumpstart your first campaign',
									'google-listings-and-ads'
								) }
							</Subsection.Title>
							<Subsection.Subtitle>
								{ __(
									'Select an offer that fits your monthly budget. New advertisers will receive an ad credit after meeting the minimum spend requirement for the selected offer.',
									'google-listings-and-ads'
								) }
							</Subsection.Subtitle>
						</div>
						<div className={ styles.container }>
							<CYOIAvailableOffers incentives={ incentives } />
						</div>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</div>
	);
};

export default CyoIncentivePicker;
