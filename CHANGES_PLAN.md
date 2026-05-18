# Changes plan — Option A

Query keeps `campaign.start_date_time`. REST response key becomes `start_date`, value is date-only via `substr()`.

---

## `src/API/Google/AdsCampaign.php`

**Line 755** — rename key, extract date portion only:

```diff
-			'start_date_time'    => $campaign->hasStartDateTime() ? $campaign->getStartDateTime() : null,
+			// getStartDateTime() returns a full datetime string (e.g. 2025-01-15 00:00:00) from the Google Ads API v23 start_date_time field.
+			'start_date'         => $campaign->hasStartDateTime() ? substr( $campaign->getStartDateTime(), 0, 10 ) : null,
```

---

## `src/API/Site/Controllers/Ads/CampaignController.php`

**Lines 442–448** — rename schema key, update description:

```diff
-			'start_date_time'                       => [
+			'start_date'                            => [
 				'type'        => [ 'string', 'null' ],
-				'description' => __( 'Campaign start date and time in YYYY-MM-dd HH:mm:ss format.', 'google-listings-and-ads' ),
+				'description' => __( 'Campaign start date in YYYY-MM-DD format.', 'google-listings-and-ads' ),
 				'context'     => [ 'view' ],
 				'readonly'    => true,
 				'nullable'    => true,
 			],
```

---

## `src/Coupon/WCCouponAdapter.php`

**Line 437** — fix double semicolon introduced by existing edit:

```diff
-			$product_ids = array_merge( $product_ids, $object_ids );;
+			$product_ids = array_merge( $product_ids, $object_ids );
```

---

## `tests/Tools/HelperTrait/GoogleAdsClientTrait.php`

**Line 427** — read from `start_date` key instead of `start_date_time`:

```diff
-		$start_date_time = $data['start_date_time'] ?? null;
+		$start_date_time = $data['start_date'] ?? null;
```

---

## `tests/Unit/API/Google/AdsCampaignTest.php`

**Line 134:**
```diff
-				'start_date_time'                       => '2025-01-15 00:00:00',
+				'start_date'                            => '2025-01-15',
```

**Line 145:**
```diff
-				'start_date_time'                       => '2025-01-20 00:00:00',
+				'start_date'                            => '2025-01-20',
```

**Line 164:**
```diff
-				'start_date_time'                       => '2025-01-15 00:00:00',
+				'start_date'                            => '2025-01-15',
```

**Line 175:**
```diff
-				'start_date_time'                       => '2025-01-20 00:00:00',
+				'start_date'                            => '2025-01-20',
```

**Line 216:**
```diff
-				'start_date_time'                       => '2025-01-15 00:00:00',
+				'start_date'                            => '2025-01-15',
```

**Line 227:**
```diff
-				'start_date_time'                       => '2025-01-20 00:00:00',
+				'start_date'                            => '2025-01-20',
```

**Line 312:**
```diff
-			'start_date_time'                       => '2025-01-15 00:00:00',
+			'start_date'                            => '2025-01-15',
```

**Line 319:**
```diff
-	public function test_get_campaign_returns_null_start_date_time_when_unavailable() {
+	public function test_get_campaign_returns_null_start_date_when_unavailable() {
```

**Line 325:**
```diff
-		$campaign_data_without_start_date_time = [
+		$campaign_data_without_start_date = [
```

**Line 336:**
```diff
-		$expected                    = $campaign_data_without_start_date_time;
+		$expected               = $campaign_data_without_start_date;
```

**Line 337:**
```diff
-		$expected['start_date_time'] = null;
+		$expected['start_date'] = null;
```

**Line 339:**
```diff
-		$this->generate_ads_campaign_query_mock( [ $campaign_data_without_start_date_time ], [ $campaign_criterion_data ] );
+		$this->generate_ads_campaign_query_mock( [ $campaign_data_without_start_date ], [ $campaign_criterion_data ] );
```

---

## `tests/Unit/API/Site/Controllers/Ads/CampaignControllerTest.php`

**Line 76:**
```diff
-				'start_date_time'                       => '2025-01-15 00:00:00',
+				'start_date'                            => '2025-01-15',
```

**Line 87:**
```diff
-				'start_date_time'                       => '2025-01-20 00:00:00',
+				'start_date'                            => '2025-01-20',
```

**Line 101:**
```diff
-			$this->assertArrayHasKey( 'start_date_time', $campaign );
+			$this->assertArrayHasKey( 'start_date', $campaign );
```

**Line 102:**
```diff
-			if ( $campaign['start_date_time'] !== null ) {
+			if ( $campaign['start_date'] !== null ) {
```

**Line 103:**
```diff
-				$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $campaign['start_date_time'] );
+				$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $campaign['start_date'] );
```

**Line 119:**
```diff
-				'start_date_time'                       => '2025-01-15 00:00:00',
+				'start_date'                            => '2025-01-15',
```

**Line 130:**
```diff
-				'start_date_time'                       => '2025-01-20 00:00:00',
+				'start_date'                            => '2025-01-20',
```

**Line 144:**
```diff
-				'start_date_time'                       => '2025-01-15 00:00:00',
+				'start_date'                            => '2025-01-15',
```

**Line 155:**
```diff
-				'start_date_time'                       => '2025-01-20 00:00:00',
+				'start_date'                            => '2025-01-20',
```

**Line 199:**
```diff
-			'start_date_time'                       => null,
+			'start_date'                            => null,
```

**Line 205:**
```diff
-			->willReturn( array_diff_key( $expected, [ 'start_date_time' => null ] ) );
+			->willReturn( array_diff_key( $expected, [ 'start_date' => null ] ) );
```

**Line 242:**
```diff
-			'start_date_time'                       => null,
+			'start_date'                            => null,
```

**Line 248:**
```diff
-			->willReturn( array_diff_key( $expected, [ 'start_date_time' => null ] ) );
+			->willReturn( array_diff_key( $expected, [ 'start_date' => null ] ) );
```

**Line 282:**
```diff
-			'start_date_time'                       => null,
+			'start_date'                            => null,
```

**Line 296:**
```diff
-				return array_diff_key( $expected, [ 'start_date_time' => null ] );
+				return array_diff_key( $expected, [ 'start_date' => null ] );
```

**Line 402:**
```diff
-			'start_date_time'                       => null,
+			'start_date'                            => null,
```

**Line 408:**
```diff
-			->willReturn( array_diff_key( $expected, [ 'start_date_time' => null ] ) );
+			->willReturn( array_diff_key( $expected, [ 'start_date' => null ] ) );
```

**Line 443:**
```diff
-			'start_date_time'                       => '2025-01-15 00:00:00',
+			'start_date'                            => '2025-01-15',
```

**Line 456:**
```diff
-		$this->assertArrayHasKey( 'start_date_time', $data );
+		$this->assertArrayHasKey( 'start_date', $data );
```

**Line 457:**
```diff
-		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['start_date_time'] );
+		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $data['start_date'] );
```

**Line 460:**
```diff
-	public function test_get_campaign_returns_null_start_date_time_when_unavailable() {
+	public function test_get_campaign_returns_null_start_date_when_unavailable() {
```

**Line 470:**
```diff
-			'start_date_time'                       => null,
+			'start_date'                            => null,
```

**Line 482:**
```diff
-		$this->assertArrayHasKey( 'start_date_time', $data );
+		$this->assertArrayHasKey( 'start_date', $data );
```

**Line 483:**
```diff
-		$this->assertNull( $data['start_date_time'] );
+		$this->assertNull( $data['start_date'] );
```
