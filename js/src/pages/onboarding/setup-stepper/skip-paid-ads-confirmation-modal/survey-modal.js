/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { megaphone, tag, external, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { OPTIONS } from './constants';
import { recordGlaEvent } from '~/utils/tracks';
import AdaptiveForm from '~/components/adaptive-form';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import Survey from './survey';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import './survey-modal.scss';

/**
 * Send survey responses when the user skips the paid ads setup.
 *
 * @event gla_skip_campaign_creation_survey
 * @property {string} context Name of the context where the survey was triggered (e.g. 'skip-paid-ads-survey-modal').
 * @property {string} your_role The role of the user (e.g. 'owner', 'developer', 'agency', 'marketing_lead', 'other').
 * @property {string} your_role_text Text input for the user's role if 'other' is selected.
 * @property {boolean} i_already_have_ads_on_google Indicates if the user already has ads on Google.
 * @property {boolean} i_dont_have_the_budget_to_create_ads_now Indicates if the user doesn't have the budget to create ads now.
 * @property {boolean} ive_tried_google_ads_before_without_success Indicates if the user has tried Google ads before without success.
 * @property {boolean} i_dont_want_ads_on_google Indicates if the user doesn't want ads on Google.
 * @property {string} i_dont_want_ads_on_google_text Text input for the reason why the user doesn't want ads on Google.
 * @property {boolean} ill_create_ads_later Indicates if the user will create ads later.
 * @property {string} ill_create_ads_later_text Text input for the reason why the user will create ads later.
 * @property {boolean} i_dont_have_time Indicates if the user doesn't have time.
 * @property {boolean} other Indicates if the user has another reason.
 * @property {string} other_text Text input for the user's other reason.
 */

/**
 * Renders a modal dialog that confirms whether the user wants to skip setting up paid ads along with a survey to understand their reasons.
 * It provides information about the benefits of enabling Performance Max and includes a link to learn more.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function to be called when the modal should be closed.
 * @param {Function} props.onSkipCreatePaidAds Function to be called when the user confirms skipping the paid ads setup.
 * @fires gla_documentation_link_click with `{ context: 'skip-paid-ads-survey-modal', link_id: 'paid-ads-with-performance-max-campaigns-learn-more', href: 'https://support.google.com/google-ads/answer/10724817' }`
 * @fires gla_skip_campaign_creation_survey with the survey responses and context 'skip-paid-ads-survey-modal'.
 */
const SurveyModal = ( { onRequestClose, onSkipCreatePaidAds } ) => {
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const formRef = useRef();

	const initialFormValues = OPTIONS.reduce( ( accumulator, option ) => {
		if ( option.hasTextInput ) {
			return {
				...accumulator,
				[ option.value ]: false,
				[ `${ option.value }_text` ]: '', // This is to handle the text input for options that require additional explanation.
			};
		}

		return {
			...accumulator,
			[ option.value ]: false,
		};
	}, {} );

	return (
		<AdaptiveForm initialValues={ initialFormValues } ref={ formRef }>
			{ ( formContext ) => {
				const handleSendAndCompleteSetupClick = async () => {
					const { values, isDirty } = formContext;

					if ( isDirty ) {
						recordGlaEvent( 'gla_skip_campaign_creation_survey', {
							...values,
							context: 'skip-paid-ads-survey-modal',
						} );
					}

					onSkipCreatePaidAds();
				};

				return (
					<AppModal
						buttons={ [
							<AppButton
								key="cancel"
								onClick={ onRequestClose }
								isSecondary
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="complete-setup"
								onClick={ handleSendAndCompleteSetupClick }
								isPrimary
							>
								{ __(
									'Send and complete setup',
									'google-listings-and-ads'
								) }
							</AppButton>,
						] }
						className="gla-skip-paid-ads-survey-modal"
						onRequestClose={ onRequestClose }
						title={ __(
							'Skip setting up ads?',
							'google-listings-and-ads'
						) }
					>
						<Flex
							align="flex-start"
							direction={ [ 'column', 'row' ] }
							gap={ 6 }
							wrap
						>
							<FlexBlock>
								<div className="gla-skip-paid-ads-survey-modal__benefits">
									<h3 className="gla-skip-paid-ads-survey-modal__benefits-title">
										{ __(
											'Enable Performance Max to reach more customers',
											'google-listings-and-ads'
										) }
									</h3>

									<ul className="gla-skip-paid-ads-survey-modal__benefits-list">
										<li>
											<Flex
												align="flex-start"
												gap={ 4 }
												justify="flex-start"
											>
												<FlexItem>
													<Icon
														icon={ tag }
														width={ 24 }
													/>
												</FlexItem>

												<FlexBlock>
													<h4 className="gla-skip-paid-ads-survey-modal__benefits-item-title">
														{ __(
															'Drive more sales',
															'google-listings-and-ads'
														) }
													</h4>
													<p>
														{ __(
															"Let Google's AI find and connect you with converting customers across Search, Maps, YouTube, and more.",
															'google-listings-and-ads'
														) }
													</p>
												</FlexBlock>
											</Flex>
										</li>
										<li>
											<Flex
												align="flex-start"
												gap={ 4 }
												justify="flex-start"
											>
												<FlexItem>
													<Icon
														icon={ megaphone }
														width={ 24 }
													/>
												</FlexItem>
												<FlexBlock>
													<h4 className="gla-skip-paid-ads-survey-modal__benefits-item-title">
														{ __(
															'Create impactful ads automatically',
															'google-listings-and-ads'
														) }
													</h4>
													<p>
														{ hasGoogleMCConnection
															? __(
																	'Your product data is used to generate ads, shown at the right time and place.',
																	'google-listings-and-ads'
															  )
															: __(
																	'Your service details are used to generate ads, shown at the right time and place.',
																	'google-listings-and-ads'
															  ) }
													</p>
													<p>
														<AppDocumentationLink
															context="skip-paid-ads-survey-modal"
															href="https://woocommerce.com/document/google-for-woocommerce/get-started/google-performance-max-campaigns/"
															linkId="paid-ads-with-performance-max-campaigns-learn-more"
														>
															{ __(
																'Learn more about Performance Max',
																'google-listings-and-ads'
															) }{ ' ' }
															<Icon
																icon={
																	external
																}
																size={ 16 }
															/>
														</AppDocumentationLink>
													</p>
												</FlexBlock>
											</Flex>
										</li>
									</ul>
								</div>
							</FlexBlock>
							<FlexBlock>
								<Survey />
							</FlexBlock>
						</Flex>
					</AppModal>
				);
			} }
		</AdaptiveForm>
	);
};

export default SurveyModal;
