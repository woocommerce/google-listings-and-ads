/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	CheckboxControl,
	SelectControl,
	Notice,
	TextareaControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { OPTIONS } from './constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import './survey.scss';

/**
 * Renders a survey component for the skip paid ads modal.
 * This component allows users to select reasons for skipping the paid ads setup
 * and provides text input fields for additional comments on certain options.
 */
const Survey = () => {
	const { getInputProps } = useAdaptiveFormContext();

	return (
		<div className="gla-skip-paid-ads-survey-modal__survey">
			<h3 className="gla-skip-paid-ads-survey-modal__survey-title">
				{ __(
					'Why do you want to skip ads?',
					'google-listings-and-ads'
				) }
			</h3>

			<ul>
				{ OPTIONS.map( ( option ) => {
					const inputProps = getInputProps( option.value );

					return (
						<li key={ option.value }>
							{ option.optionType === 'select' && (
								<div className="gla-skip-paid-ads-survey-modal__select">
									<SelectControl
										options={ option.options }
										label={ option.label }
										value={ option.value }
										{ ...inputProps }
									/>

									{ inputProps.value === 'other' && (
										<TextareaControl
											placeholder={
												option.otherInputTextPlaceholder
											}
											name={ `${ option.value }_text` }
											{ ...getInputProps(
												`${ option.value }_text`
											) }
											rows={ 2 }
										/>
									) }
								</div>
							) }

							{ option.optionType === 'checkbox' && (
								<div>
									<CheckboxControl
										label={ option.label }
										value={ option.value }
										{ ...inputProps }
									/>

									{ inputProps.checked && (
										<>
											{ option.hasTextInput && (
												<div className="gla-skip-paid-ads-survey-modal__text-input">
													<TextareaControl
														placeholder={ __(
															'Tell us why (optional)',
															'google-listings-and-ads'
														) }
														name={ `${ option.value }_text` }
														{ ...getInputProps(
															`${ option.value }_text`
														) }
														rows={ 2 }
													/>
												</div>
											) }

											{ option.notice && (
												<Notice
													status="info"
													isDismissible={ false }
													className="gla-skip-paid-ads-survey-modal__notice"
												>
													{ option.notice }
												</Notice>
											) }
										</>
									) }
								</div>
							) }
						</li>
					);
				} ) }
			</ul>
		</div>
	);
};

export default Survey;
