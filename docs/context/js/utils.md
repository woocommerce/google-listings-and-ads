# Utilities

GLA's utility modules live in `js/src/utils/`. Import individual functions — never barrel-import the whole folder.

## URL Utilities (`~/utils/urls.js`)

All GLA page paths and URL builders:

```js
import {
    pagePaths,
    subpaths,
    getEditCampaignUrl,
    getCreateCampaignUrl,
    getDashboardUrl,
    getProductFeedUrl,
    getSettingsUrl,
    getOnboardingUrl,
    getReconnectAccountUrl,
} from '~/utils/urls';

// Never hardcode '/google/dashboard' — always use pagePaths or the getter functions
const url = getDashboardUrl( { query: 'value' } );
const editUrl = getEditCampaignUrl( campaignId, 'budget-step' );
```

## Tracks Utilities (`~/utils/tracks.js`)

See [event-tracking.md](event-tracking.md) for full details.

```js
import { recordGlaEvent, queueRecordGlaEvent, recordStepperChangeEvent } from '~/utils/tracks';
```

## Error Handling (`~/utils/handleError.js`)

Catches async errors and shows a `core/notices` snackbar:

```js
import handleError from '~/utils/handleError';

try {
    await saveData();
} catch ( error ) {
    handleError( error, 'saving campaign data' );
    // Shows: "There was an error saving campaign data."
}
```

Do not call `createNotice` manually for caught API errors — use `handleError`.

## Local Storage (`~/utils/localStorage.js`)

Use for ephemeral client-side state that should not go in the store:

```js
import { LOCAL_STORAGE_KEYS, getLocalStorageItem, setLocalStorageItem } from '~/utils/localStorage';

// Keys are constants — never use bare strings
setLocalStorageItem( LOCAL_STORAGE_KEYS.ACTIONED_CAMPAIGN_IDS, [ 1, 2, 3 ] );
const ids = getLocalStorageItem( LOCAL_STORAGE_KEYS.ACTIONED_CAMPAIGN_IDS );
```

## Date Utilities (`~/utils/date.js`)

Format dates using the site's WordPress date settings from `glaData`:

```js
import { getDateString, getTimeString } from '~/utils/date';

// Uses glaData.dateFormat and glaData.timeFormat automatically
const formatted = getDateString( new Date() );
```

## General Utilities

```js
// Key generation for selectors
import { generateKeyFromObject } from '~/utils/generateKeyFromObject';
const key = generateKeyFromObject( { country: 'US', budget: 10 } );

// Error message from rejected promises
import { createErrorMessageForRejectedPromises } from '~/utils/createErrorMessageForRejectedPromises';
const message = createErrorMessageForRejectedPromises( [ error1, error2 ] );

// API response key transformation (used in adapters)
import { convertKeysFromSnakeCaseToCamelCase } from '~/utils/convertKeysFromSnakeCaseToCamelCase';
const camel = convertKeysFromSnakeCaseToCamelCase( { merchant_id: 1 } );
// → { merchantId: 1 }
```

## Lodash

Import individual methods — never the entire package:

```js
// Correct
import debounce from 'lodash/debounce';
import isEqual from 'lodash/isEqual';

// Wrong
import _ from 'lodash';
import { debounce } from 'lodash';
```
