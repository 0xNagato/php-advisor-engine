### 1. Restaurant Owner User Stories

1. **As a restaurant owner, I want to log into the app, so I can manage my restaurant's profile and reservation
   availability.**

    - Acceptance Criteria:
        - Access to a secure login page.
        - Ability to create and edit restaurant profile (name, location, contact info).
        - Facility to set and update available reservations (time slots, number of guests).
2. **As a restaurant owner, I want to create available reservations in the app, so that they can be booked by
   concierges.**

    - Acceptance Criteria:
        - Option to add new reservations with specific times (6pm to 10pm in 30-minute increments) and guest limits (up
          to 8).
        - View and edit upcoming reservations.
        - View history of past reservations.

### 2. Concierge User Stories

1. **As a concierge, I want to log into the app, so I can view and book available reservations.**

    - Acceptance Criteria:
        - Access to a secure login page.
        - View a list of available reservations from various restaurants.
        - Filter and search functionality based on time, date, restaurant, and guest number.
2. **As a concierge, I want to book reservations for guests, so that I can earn a commission.**

    - Acceptance Criteria:
        - Ability to book a reservation and pay the fee ($200 for 2 guests, +$50 per additional guest).
        - Confirmation of reservation booking and payment.
        - View commission earnings per booking.
3. **As a concierge, I want to track my earnings and booked reservations, so I can manage my activities.**

    - Acceptance Criteria:
        - Dashboard to view total earnings, number of reservations booked, and upcoming payments.
        - Breakdown of earnings (personal commission, app's share, restaurant's share, charity donation).
        - History of all transactions and bookings.

### 3. System-Generated Processes

1. **As the app system, I want to send a text message to the restaurant upon a reservation booking, so that they can
   update their reservation system.**

    - Acceptance Criteria:
        - Automated text message to restaurant with reservation details upon booking confirmation.
        - Record of messages sent for auditing purposes.
2. **As the app system, I want to distribute the fees collected from bookings, so that each party receives their share.**
    - Acceptance Criteria:
        - Automated calculation and distribution of fees (concierge commission, app fee, restaurant share, charity
          donation).
        - Secure transaction process.
        - Confirmation receipts to relevant parties.
        -

### 4. Additional Features/User Stories

1. **As a concierge, I want to cancel or modify a booked reservation, so I can accommodate changes requested by guests.
   **

    - Acceptance Criteria:
        - Option to modify or cancel reservations within a specified time frame.
        - Automated updates to restaurant and recalculated fees if applicable.
2. **As a restaurant owner, I want to receive notifications for reservation changes, so I can keep my reservation system
   up-to-date.**

    - Acceptance Criteria:
        - Instant notifications for any modifications or cancellations.
        - Updated reservation details in the app.
