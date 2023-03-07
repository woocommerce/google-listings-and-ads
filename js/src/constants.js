export const glaData = window.glaData;

export const FREE_LISTINGS_PROGRAM_ID = 0;

// Products report related
export const REPORT_SOURCE_PARAM = 'report-source';
export const REPORT_SOURCE_PAID = 'paid';
export const REPORT_SOURCE_FREE = 'free';
export const REPORT_SOURCE_DEFAULT = REPORT_SOURCE_PAID;

// Programs report related
export const REPORT_PROGRAM_PARAM = 'programs';

export const MINUTE_IN_SECONDS = 60;
export const HOUR_IN_SECONDS = 60 * MINUTE_IN_SECONDS;
export const DAY_IN_SECONDS = 24 * HOUR_IN_SECONDS;
export const MONTH_IN_SECONDS = 30 * DAY_IN_SECONDS;

export const GUIDE_NAMES = {
	SUBMISSION_SUCCESS: 'submission-success',
	CAMPAIGN_CREATION_SUCCESS: 'campaign-creation-success',
};

export const LOCAL_STORAGE_KEYS = {
	CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN:
		'gla-can-onboarding-setup-ces-prompt-open',
};

export const API_RESPONSE_CODES = {
	WPCOM_DISCONNECTED: 'JETPACK_DISCONNECTED',
	GOOGLE_DISCONNECTED: 'GOOGLE_DISCONNECTED',
};

export const SHIPPING_RATE_METHOD = {
	FLAT_RATE: 'flat_rate',
};

// Stepper key related
export const CAMPAIGN_STEP = {
	CAMPAIGN: 'campaign',
	ASSET_GROUP: 'asset-group',
};

// MC Issues Related
export const ISSUE_TYPE_PRODUCT = 'product';
export const ISSUE_TYPE_ACCOUNT = 'account';
export const REQUEST_REVIEW = 'request-review';
export const ISSUE_TABLE_PER_PAGE = 5;

// Account status related
export const GOOGLE_ADS_ACCOUNT_STATUS = {
	CONNECTED: 'connected',
	DISCONNECTED: 'disconnected',
	INCOMPLETE: 'incomplete',
};

export const GOOGLE_ADS_BILLING_STATUS = {
	UNKNOWN: 'unknown',
	PENDING: 'pending',
	APPROVED: 'approved',
	CANCELLED: 'cancelled',
};

// Attribute Mapping
export const CATEGORY_CONDITION_SELECT_TYPES = {
	ALL: 'ALL',
	EXCEPT: 'EXCEPT',
	ONLY: 'ONLY',
};

export const CATEGORIES_TO_SHOW_IN_TOOLTIP = 5;

// Assets of Performance Max Campaign related
export const ASSET_KEY = {
	BUSINESS_NAME: 'business_name',
	MARKETING_IMAGE: 'marketing_image',
	SQUARE_MARKETING_IMAGE: 'square_marketing_image',
	PORTRAIT_MARKETING_IMAGE: 'portrait_marketing_image',
	LOGO: 'logo',
	HEADLINE: 'headline',
	LONG_HEADLINE: 'long_headline',
	DESCRIPTION: 'description',
	CALL_TO_ACTION_SELECTION: 'call_to_action_selection',
};

export const ASSET_GROUP_KEY = {
	FINAL_URL: 'final_url',
	DISPLAY_URL_PATH: 'display_url_path',
};

export const ASSET_FORM_KEY = {
	...ASSET_KEY,
	...ASSET_GROUP_KEY,
};
