# System Audit & Implementation Plan

Based on the review of the `system-checking-3_10-07-26.pdf` document and the current state of the MotoTrack project, here is the comprehensive audit and step-by-step implementation plan. 

## Phase 1: Header, Navigation & UI Consistency
1. **Header Layout**: 
   - Ensure the navigation links (Home to Contact) are perfectly centered.
   - Ensure the Dark Mode toggle, Notifications, and Auth buttons (Sign In/Get Started) are pushed to the far right.
   - Make the header `sticky` / fixed at the top for both Admin and Customer dashboards.
2. **Mobile Responsiveness**:
   - Fix the mobile menu layout to ensure the hamburger icon is correctly positioned on the right and fully functional.
3. **Pre-order UI Refresh**:
   - Update the color scheme, UI elements, and fonts on the Pre-order page to match the modern aesthetics of the rest of the site.

## Phase 2: Dashboard Space Optimization
1. **Customer Dashboard**:
   - **Remove** the 4 action cards (Browse Parts, Compatibility, Pre-order, My Cart) since they already exist in the sidebar/navbar.
   - **Move** the "Recent Activity" section next to the "Welcome Back" banner.
   - Ensure the entire dashboard fits on a single screen without requiring scrolling.
2. **Admin Dashboard**:
   - **Overview Tab**: Make the layout fixed to the screen height to eliminate scrolling.
   - **Live Activity Feed**: Restrict the Live Activity sidebar to the **Overview tab only**. Remove it from Inventory, Reports, POS, etc., to maximize horizontal space for data tables.

## Phase 3: Product Catalog & Compatibility
1. **Catalog Filters**:
   - Change the left-hand "Filters" sidebar on the Browse Parts page to a `fixed` or `sticky` position so it remains visible while users scroll through the products.
2. **Compatibility Checker**:
   - Convert the current text inputs (Make, Model, Year) into interconnected **Dropdown Menus**.
   - The "Model" dropdown will dynamically update based on the selected "Make", and "Year" will update based on the selected "Model".

## Phase 4: Pre-orders, Checkout & Reservations (No Payment)
1. **Pure Reservation System**:
   - Completely **remove** all downpayment (50%) and full payment requirements from the Pre-order/Reservation flow. It will become a "Purely Reservation" process.
2. **Checkout Updates**:
   - For full checkout, replace the image placeholder with a "Scan QR Code" graphic.
   - Remove the image placeholder entirely from the Pre-order form.
3. **Information & Policy Text**:
   - Update the "How it Works" and "Payment & Pickup Info" sections:
     1. Submit Pre-order
     2. We Process Your Order
     3. Pick-up and Pay Balance at the shop
   - Add the **24-Hour Policy**: Explicitly state that reservations must be picked up and paid for within 24 hours, or they will be automatically canceled.

## Phase 5: Security & Authentication
1. **Login Rate Limiting**:
   - Implement a strict lockout policy: If a user enters an incorrect password 5 times, they will be locked out for 30 seconds before they can type again.
2. **OTP Integration**:
   - Add an OTP (One-Time Password) input field to both the Sign Up and Sign In processes for enhanced security. *(Note: We will need to confirm if this will use Email OTP or the SMS gateway configured in your `.env`)*.

---

### Next Steps
Please review this plan. If everything aligns with your requirements, click **Proceed** and I will begin implementing **Phase 1 and Phase 2** immediately!
