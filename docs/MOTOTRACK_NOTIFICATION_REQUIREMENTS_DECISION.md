# MotoTrack — Notification Requirements Decision Paper

Status: **analysis and recommendation only. No new channel was implemented.** Decision belongs to the adviser/owner.

## 1. What the source documents actually require

| Source | Exact passage | Channel stated? |
|---|---|---|
| Ch1 §Scope → Customer → Pre-Order | "the shop notifies the customer once the item has arrived, and the customer must pick up the product within 24 hours after the notification." | **No** |
| Ch1 §Objectives #1 | "provides the owners with updates on inventory levels and sends automatic alerts." | **No** (owner alerts) |
| Ch1 §Purpose | "real-time stock monitoring that notifies the owners when a product is running low" | **No** |
| Ch3 §When | "Stock monitoring alerts run continuously so the owners are notified right away whenever a product falls below its set stock level." | **No** |
| Context diagram (Fig 5.0) | "Pre-Order Confirmation and Pick-up Notice" (system → Customer); "Low-Stock Alert" (system → Owner); "Reservation Confirmation" | **No** (a data flow, not a medium) |
| Customer flowchart (Fig 3.0) | "Shop notifies customer that the part has arrived" | **No** |
| Owner flowchart (Fig 4.0) | "Source part (pre-order) and notify customer" | **No** |
| Test PDF PRE-005 | "The customer is notified and the dashboard shows the 24-hour pick-up." | **Implies in-app** ("dashboard shows") |
| Test PDF RES-002 | "The customer is notified and the 24-hour pick-up starts." | No |
| Test PDF TCLA-009 / Fig 3.0 / Fig 4.0 | "MFA code from the email" / "Send one-time MFA code to registered email" | Email is stated **only** for authentication, not for order notices |

**Finding (confirmed from text):** no manuscript passage, figure or test case requires email or SMS for reservation/pre-order notices. The channel is **unspecified**, and the only test-case wording that hints at a medium (PRE-005) points to the **dashboard**. Search of Ch1–3 for "email", "SMS", "text message", "in-app" found only the OTP/verification usage in the figures. The requirement is therefore **ambiguous, not violated**.

## 2. What the system does today (code inspection + automated + controlled-browser evidence)

- Pre-order readiness, reservation/pre-order cancellation and expiry: **database (in-app) notifications** written synchronously by `NotificationDispatcher` (`notifyNow(['database'])`); broadcast is best-effort only when a real broadcast driver is configured.
- Low-stock alerts: in-app notification for owners **plus** SMS via `SmsService` (gateway Movider/Twilio/Mock) dispatched through a **queued** job (`CheckLowStockAlertJob`).
- OTP/verification: email (`OtpMail`, synchronous).
- Business rule preserved: `ready_notified_at` / `pickup_expires_at` are set **only when ≥1 durable notification row was recorded**; no countdown while sourcing.

## 3. Limitation of in-app-only readiness notices

1. A customer who does not open the dashboard within 24 h never sees the notice, then the pre-order expires — the customer is penalised for something the system did not make visible.
2. A guest-looking workflow ("notified once the item arrived") normally implies out-of-app contact for walk-in-oriented shops.
3. The 24-hour deadline is anchored to a record the customer may not have read. Today this is defensible (the notice is durable and auditable) but not customer-friendly.
4. Dashboard-only notices cannot be proven *seen*; `read_at` is the only signal.

## 4. Options

| | A — In-app only (current) | B — In-app + email | C — In-app + SMS |
|---|---|---|---|
| Matches manuscript | Yes (channel unspecified) | Yes | Yes |
| New work | None (document it) | Mail template + send on ready/cancel/expire; failure handling | SMS template + gateway use for customers, consent/opt-in |
| Provider / cost | None | Existing SMTP (already used for OTP) — marginal cost ≈ 0 within provider limits | Per-message fee, sender-ID registration, Philippine telco rules; existing `SmsService` is wired for *owner* alerts only |
| Reliability | Highest (DB write) | Depends on SMTP; delivery success is asynchronous and not provable | Depends on gateway; delivery receipts vary |
| Customer reach | Only if they open the app | Good if email is monitored | Best for this shop's walk-in customers |
| Risk to existing rules | None | If email is made a precondition, a mail outage would block the deadline | Same, plus cost |
| Extra UAT | None | Email receipt tests, failure simulation | SMS receipt tests, cost control |
| Defense exposure | "Why only in-app?" | Low | Medium (cost/provider) |

### 4.1 Failure handling if B (or C) is chosen
- **In-app record remains the system of record.** The deadline starts only after the durable in-app row exists (unchanged rule).
- External delivery is **best-effort and non-blocking**: attempt at the time of notification, log success/failure (e.g., `notification_deliveries` — *proposal only*), never roll back the in-app record or the deadline.
- If external delivery fails: show the owner a "Not delivered by email/SMS — contact customer" flag so the owner can call; offer **Resend** (already exists for readiness) and allow the owner to extend or restart the deadline manually (owner action, audited).
- Do **not** make external delivery success a precondition for starting the deadline unless the adviser decides that the shop wants that stricter rule; if so, the deadline would start at the first *successful* channel and the owner would be told when none succeeded. This increases risk of "stuck" pre-orders without a worker/provider and is **not** recommended as the minimum.
- Email requires a working queue worker or a synchronous send; today no worker is started by deployment. A synchronous best-effort send (like `OtpMail`) avoids that dependency but adds latency.

## 5. Recommendation (minimum aligned solution)

1. **Adopt Option A as the verified baseline** and make the manuscript explicit: notifications are delivered **in-app through the customer dashboard**; the 24-hour pickup period starts when the notification is recorded; the owner can **resend** and can contact the customer by phone (customer phone is collected at sign-up).
2. **Add a documented, low-cost safeguard rather than a new channel:** have the owner-side pre-order list show "Notified (unread)" vs "Read", so the owner can phone unread customers. (Proposal only; requires approval.)
3. If the adviser requires out-of-app notice, choose **Option B (email, best-effort, non-blocking)** before C: it reuses the SMTP already needed for OTP, has no per-message cost, and keeps the deadline rule intact. SMS (C) only if the owner accepts per-message cost.
4. Whatever is chosen, record it as an approved requirement and add matching test cases (e.g., PRE-005 expected result: "notified on the dashboard").

**Confirmed vs needs approval:** the *text analysis* (channel unspecified; PRE-005 implies dashboard) is **confirmed**. Choosing B or C, any schema/Mailable/SMS work, and any change to the deadline rule **require adviser approval**.

## 6. Evidence and caveats
- In-app persistence: automated (`CancelUiAndNotificationDeliveryTest`) and controlled-browser; **not yet verified on production** (production `CACHE_STORE`/`QUEUE_CONNECTION` unknown; stale `jobs` rows possible).
- Email/SMS deliverability was **not** tested to real recipients; no SMTP/gateway credentials were inspected.

## 7. Addendum (2026-10-16) — provisional baseline
In-app notifications remain the **provisional baseline**; no email or SMS channel will be added until the owner/adviser decide that an external channel is required. **Documented limitation:** a customer must sign in to MotoTrack to see an in-app readiness notice; the 24-hour pickup period starts when the notice is recorded, whether or not it has been opened. Mitigations that need no new channel: owner "Resend" and phone contact. See the Pre-Deployment Decision Matrix, §4.
