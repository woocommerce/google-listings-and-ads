# QA: Campaign start date on Ads campaigns endpoint

**Goal:** Confirm that the ads/campaigns API returns a `start_date` for each campaign (date in YYYY-MM-DD or `null`).

**You need:** A WordPress admin account that can open **WooCommerce → Marketing → Google Listings & Ads** (or wherever your Ads campaigns are shown), and at least one connected Google Ads account with at least one campaign (or no campaigns—see step 7).

---

## Steps

### 1. Log in and go to Ads campaigns

- Log in to WordPress as an administrator.
- Go to **WooCommerce → Marketing → Google Listings & Ads** (or your site’s path to the Google for WooCommerce / Ads area).
- Open the **Campaigns** (or “Paid campaigns”) section so the list of campaigns loads on screen.

### 2. Open Developer Tools in your browser

- **Windows/Linux:** Press **F12** or **Ctrl+Shift+I**.
- **Mac:** Press **Cmd+Option+I**.
- Or: right‑click on the page → **Inspect** (or **Inspect Element**).

### 3. Open the Network tab

- In the Developer Tools window, click the **Network** tab.
- If you see a list of requests, leave it open. If not, refresh the page (F5 or Cmd+R) so the list fills.

### 4. Find the request that loads campaigns

- In the Network list, look at the **Name** or **URL** of each request.
- Find one whose URL contains **`campaigns`** or **`gla`** (e.g. `.../wp-json/wc/gla/ads/campaigns` or similar).
- Click that request once to select it.

### 5. Open the response body

- On the right (or below the list), you should see details for the selected request.
- Click the **Response** or **Preview** sub-tab so you see the **body** of the response (usually JSON text or a tree view).

### 6. Check that each campaign has `start_date`

- In the response, you should see an array of campaign objects. Each object is one campaign.
- For **each** campaign object, check:
  - There is a field named **`start_date`**.
  - Its value is either:
    - A **date** in the form **YYYY-MM-DD** (e.g. `2025-01-15`), or  
    - The word **`null`** (meaning “no start date” for that campaign).

**Pass:** Every campaign in the response has a `start_date` key, and every value is either a YYYY-MM-DD date or `null`.  
**Fail:** Any campaign is missing `start_date`, or a value is not a date string and not `null`.

### 7. If you have no campaigns

- If the account has **no campaigns**, the response may be an empty list `[]`.
- In that case the test is **passed** as long as the endpoint loads without errors (e.g. status 200 in the Network tab for the campaigns request). The requirement is that whenever campaigns are returned, each one has `start_date`.

---

## Quick checklist

- [ ] Opened the campaigns page while logged in as admin.
- [ ] Opened Developer Tools → Network and found the campaigns (or `gla`) request.
- [ ] Opened the Response/Preview for that request.
- [ ] Confirmed every campaign object has a `start_date` field.
- [ ] Confirmed each `start_date` is either YYYY-MM-DD or `null`.

---

## If something doesn’t match

- Note the **exact URL** of the request you inspected.
- Note whether **any** campaign was missing `start_date` or had an unexpected format.
- If possible, copy a small snippet of the response (one campaign object) and share it with the developer.
