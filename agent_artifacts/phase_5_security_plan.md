# Phase 5: Security & Authentication Implementation Plan

Based on the screenshot of your **Queen SMTP** dashboard, we will use Email OTP for enhanced security. Here is the step-by-step plan to implement Phase 5 using your SMTP service:

## Step 1: Configure Queen SMTP
1. Update the `.env` file to replace the default mailer settings with your **Queen SMTP** credentials (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`).
2. Test the connection to ensure emails can be sent out reliably.

## Step 2: Database Updates
1. Create a migration to add necessary security columns to the `users` table:
   - `otp_code` (string, nullable)
   - `otp_expires_at` (timestamp, nullable)
   - `requires_password_change` (boolean, default: false) - *for the first-login requirement*.

## Step 3: Implement Email OTP for Sign In & Sign Up
1. **Sign Up Flow**:
   - When a user registers, their account is created but they are not fully logged in yet.
   - Generate a 6-digit OTP, store it in the database, and email it to them using Queen SMTP.
   - Redirect to an OTP Verification page.
   - Upon entering the correct OTP, the account is verified and fully logged in.
2. **Sign In Flow (Two-Factor Authentication)**:
   - User enters email and password.
   - If credentials are correct, generate and email a new OTP via Queen SMTP.
   - Place the user in a "pending OTP verification" session state.
   - Redirect to the OTP Verification page.
   - Upon success, complete the login process.

## Step 4: First-Login Password Change Requirement
1. During account creation (or for existing users, if you prefer to force a reset), set `requires_password_change = true`.
2. Create a global middleware `CheckPasswordChange` that intercepts authenticated requests.
3. If a logged-in user has `requires_password_change == true`, force-redirect them to a dedicated "Update Password" screen.
4. Once they update their password, toggle the flag to `false` and grant full access.

## Step 5: Establish Strict User Permissions & Harden API
1. Implement Laravel Gates/Policies to explicitly define what an `owner`, `admin`, and `customer` can do.
2. Ensure all routes (especially API and admin routes) are protected by strict role-based middleware (`role:owner`, etc.).
3. Add rate limiting to all authentication-related endpoints (like OTP verification) to prevent brute-force guessing.

---

### Action Required
Please review this plan. If it looks good, click **Proceed** (or tell me to continue) and we will start with **Step 1 and Step 2**. You will need to provide the Queen SMTP credentials so I can configure the `.env` file, or I can guide you on where to paste them!
