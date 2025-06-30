# Booking Endpoint

## Overview

This endpoint provides functionality for creating, updating, and deleting bookings in the system.

## Create Booking

### Request

- **Method:** POST
- **URL:** `/api/bookings`
- **Authentication:** Required

#### Headers

| Header        | Value            | Required | Description                            |
|---------------|------------------|----------|----------------------------------------|
| Authorization | Bearer {token}   | Yes      | Authentication token                   |
| Accept        | application/json | Yes      | Specifies the expected response format |
| Content-Type  | application/json | Yes      | Specifies the request format           |

#### Request Body

| Parameter            | Type                | Required | Description                                        |
|----------------------|---------------------|----------|----------------------------------------------------|
| date                 | string (YYYY-MM-DD) | Yes      | The date for the booking (must not be in the past) |
| schedule_template_id | integer             | Yes      | The ID of the schedule template for the booking    |
| guest_count          | integer             | Yes      | The number of guests for the booking               |

#### Example Request

```bash
curl -X POST \
  https://api.example.com/api/bookings \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "date": "2023-06-15",
    "schedule_template_id": 123,
    "guest_count": 4
  }'
```

### Response

#### Success Response

- **Status Code:** 200 OK

##### Response Body

**Non-Prime Booking Response:**

```json
{
    "data": {
        "bookings_enabled": true,
        "bookings_disabled_message": "Bookings are currently disabled while we are onboarding venues and concierges. We expect to be live by mid-November.",
        "id": 288705,
        "guest_count": "2",
        "dayDisplay": "Sun, Jun 15 at 6:00 pm",
        "status": "pending",
        "venue": "Gekko",
        "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/gekko.png",
        "total": "$0.00",
        "subtotal": "$0.00",
        "tax_rate_term": null,
        "tax_amount": null,
        "bookingUrl": "http://localhost:8000/checkout/ba52e84f-9dd2-41e1-a80f-d928ac2e5a6d?r=sms",
        "qrCode": "data:image/svg+xml;base64,PD94b...",
        "is_prime": 0,
        "booking_at": "2025-06-15T18:00:00.000000Z"
    }
}
```

**Prime Booking Response:**

```json
{
    "data": {
        "bookings_enabled": true,
        "bookings_disabled_message": "Bookings are currently disabled while we are onboarding venues and concierges. We expect to be live by mid-November.",
        "id": 288706,
        "guest_count": "4",
        "dayDisplay": "Sun, Jun 15 at 8:00 pm",
        "status": "pending",
        "venue": "Gekko",
        "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/gekko.png",
        "total": "$150.00",
        "subtotal": "$130.43",
        "tax_rate_term": "NYC Tax",
        "tax_amount": "$19.57",
        "bookingUrl": "http://localhost:8000/checkout/ba52e84f-9dd2-41e1-a80f-d928ac2e5a6d?r=sms",
        "qrCode": "data:image/svg+xml;base64,PD94b...",
        "is_prime": 1,
        "booking_at": "2025-06-15T20:00:00.000000Z",
        "paymentIntentSecret": "pi_1234567890abcdef_secret_1234567890abcdef"
    }
}
```

## Response Fields

| Field                     | Type    | Description                                                                |
|---------------------------|---------|----------------------------------------------------------------------------|
| bookings_enabled          | boolean | Indicates whether bookings are enabled.                                    |
| bookings_disabled_message | string  | Message displayed if bookings are disabled.                                |
| id                        | integer | Unique identifier for the booking.                                         |
| guest_count               | integer | Number of guests for the booking.                                          |
| dayDisplay                | string  | Human-readable representation of the booking day (optional).               |
| status                    | string  | Status of the booking.                                                     |
| venue                     | string  | Name of the venue.                                                         |
| logo                      | string  | URL of the venue's logo.                                                   |
| total                     | string  | Total amount with taxes, formatted with currency.                          |
| subtotal                  | string  | Subtotal amount, formatted with currency.                                  |
| tax_rate_term             | string  | Tax rate term (optional).                                                  |
| tax_amount                | string  | Tax amount, formatted with currency (optional).                            |
| bookingUrl                | string  | URL for the booking.                                                       |
| qrCode                    | string  | QR code for the booking.                                                   |
| is_prime                  | string  | Indicates whether the booking is prime ('true' or '0').                    |
| booking_at                | string  | Date and time of the booking.                                              |
| paymentIntentSecret       | string  | **Prime bookings only** - Stripe payment intent client secret for payment. |

#### Error Responses

##### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

##### 404 Not Found

```json
{
    "message": "Booking failed"
}
```

##### 422 Unprocessable Entity

```json
{
    "message": "Venue is not currently accepting bookings"
}
```

or

```json
{
    "date": [
        "The date field is required.",
        "The date must not be in the past."
    ],
    "schedule_template_id": [
        "The schedule template id field is required."
    ],
    "guest_count": [
        "The guest count field is required."
    ]
}
```

##### 500 Internal Server Error

**Prime bookings only** - Returned when payment intent creation fails:

```json
{
    "message": "Payment processing unavailable. Please try again."
}
```

## Update Booking

### Request

- **Method:** PUT
- **URL:** `/api/bookings/{booking}`
- **Authentication:** Required

#### Headers

| Header        | Value            | Required | Description                            |
|---------------|------------------|----------|----------------------------------------|
| Authorization | Bearer {token}   | Yes      | Authentication token                   |
| Accept        | application/json | Yes      | Specifies the expected response format |
| Content-Type  | application/json | Yes      | Specifies the request format           |

#### URL Parameters

| Parameter | Type    | Required | Description                     |
|-----------|---------|----------|---------------------------------|
| booking   | integer | Yes      | The ID of the booking to update |

#### Request Body

| Parameter  | Type   | Required | Description                                                  |
|------------|--------|----------|--------------------------------------------------------------|
| first_name | string | Yes      | The first name of the guest (max: 255 characters)            |
| last_name  | string | Yes      | The last name of the guest (max: 255 characters)             |
| phone      | string | Yes      | The phone number of the guest (must be a valid phone number) |
| email      | string | No       | The email address of the guest (must be a valid email)       |
| notes      | string | No       | Additional notes for the booking (max: 1000 characters)      |
| bookingUrl | string | Yes      | The URL for the booking payment form                         |

#### Example Request

```bash
curl -X PUT \
  https://api.example.com/api/bookings/456 \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1 (555) 123-4567",
    "email": "john.doe@example.com",
    "notes": "Allergic to nuts",
    "bookingUrl": "https://example.com/booking/456/payment"
  }'
```

### Response

#### Success Response

- **Status Code:** 200 OK

##### Response Body

```json
{
    "message": "SMS Message Sent Successfully"
}
```

#### Error Responses

##### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

##### 404 Not Found

```json
{
    "message": "Booking already confirmed or cancelled"
}
```

##### 422 Unprocessable Entity

```json
{
    "message": "Venue is not currently accepting bookings"
}
```

or

```json
{
    "message": "Customer already has a non-prime booking for this day"
}
```

or

```json
{
    "first_name": [
        "The first name field is required."
    ],
    "last_name": [
        "The last name field is required."
    ],
    "phone": [
        "The phone field must be a valid phone number."
    ],
    "bookingUrl": [
        "The booking url field is required."
    ]
}
```

## Complete Booking

### Request

- **Method:** POST
- **URL:** `/api/bookings/{booking}/complete`
- **Authentication:** Required

#### Headers

| Header        | Value            | Required | Description                            |
|---------------|------------------|----------|----------------------------------------|
| Authorization | Bearer {token}   | Yes      | Authentication token                   |
| Accept        | application/json | Yes      | Specifies the expected response format |
| Content-Type  | application/json | Yes      | Specifies the request format           |

#### URL Parameters

| Parameter | Type    | Required | Description                       |
|-----------|---------|----------|-----------------------------------|
| booking   | integer | Yes      | The ID of the booking to complete |

#### Request Body

| Parameter         | Type   | Required    | Description                                                                                    |
|-------------------|--------|-------------|------------------------------------------------------------------------------------------------|
| first_name        | string | Yes         | The first name of the guest (max: 255 characters)                                              |
| last_name         | string | Yes         | The last name of the guest (max: 255 characters)                                               |
| phone             | string | Yes         | The phone number of the guest (must be a valid phone number)                                   |
| email             | string | No          | The email address of the guest (must be a valid email)                                         |
| notes             | string | No          | Additional notes for the booking (max: 1000 characters)                                        |
| payment_intent_id | string | Conditional | **Required for prime bookings** - Stripe payment intent ID from client-side payment processing |
| r                 | string | No          | Referral code                                                                                  |

#### Example Request

**Prime Booking:**

```bash
curl -X POST \
  https://api.example.com/api/bookings/456/complete \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1 (555) 123-4567",
    "email": "john.doe@example.com",
    "notes": "Celebrating anniversary",
    "payment_intent_id": "pi_1234567890abcdef",
    "r": "friend_referral"
  }'
```

**Non-Prime Booking:**

```bash
curl -X POST \
  https://api.example.com/api/bookings/456/complete \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1 (555) 123-4567",
    "email": "john.doe@example.com",
    "notes": "Table by the window please"
  }'
```

### Response

#### Success Response

- **Status Code:** 200 OK

##### Response Body

**Prime Booking Response:**

```json
{
    "message": "Booking completed successfully",
    "data": {
        "booking": {
            "bookings_enabled": true,
            "bookings_disabled_message": "Bookings are currently disabled while we are onboarding venues and concierges. We expect to be live by mid-November.",
            "id": 288706,
            "guest_count": "4",
            "dayDisplay": "Sun, Jun 15 at 8:00 pm",
            "status": "confirmed",
            "venue": "Gekko",
            "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/gekko.png",
            "total": "$150.00",
            "subtotal": "$130.43",
            "tax_rate_term": "NYC Tax",
            "tax_amount": "$19.57",
            "bookingUrl": "http://localhost:8000/checkout/ba52e84f-9dd2-41e1-a80f-d928ac2e5a6d?r=sms",
            "qrCode": "data:image/svg+xml;base64,PD94b...",
            "is_prime": 1,
            "booking_at": "2025-06-15T20:00:00.000000Z"
        },
        "invoice_download_url": "https://api.example.com/customer/invoice/download/ba52e84f-9dd2-41e1-a80f-d928ac2e5a6d",
        "result": {
            "success": true,
            "booking_confirmed": true,
            "payment_processed": true
        }
    }
}
```

**Non-Prime Booking Response:**

```json
{
    "message": "Booking completed successfully",
    "data": {
        "booking": {
            "bookings_enabled": true,
            "bookings_disabled_message": "Bookings are currently disabled while we are onboarding venues and concierges. We expect to be live by mid-November.",
            "id": 288705,
            "guest_count": "2",
            "dayDisplay": "Sun, Jun 15 at 6:00 pm",
            "status": "confirmed",
            "venue": "Gekko",
            "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/gekko.png",
            "total": "$0.00",
            "subtotal": "$0.00",
            "tax_rate_term": null,
            "tax_amount": null,
            "bookingUrl": "http://localhost:8000/checkout/ba52e84f-9dd2-41e1-a80f-d928ac2e5a6d?r=sms",
            "qrCode": "data:image/svg+xml;base64,PD74b...",
            "is_prime": 0,
            "booking_at": "2025-06-15T18:00:00.000000Z"
        },
        "invoice_status": "processing",
        "invoice_message": "Invoice is being generated and will be available shortly. You can check back or it will be emailed once ready."
    }
}
```

## Response Fields

| Campo                                  | Tipo    | Descripción                                                                                                               |
|----------------------------------------|---------|---------------------------------------------------------------------------------------------------------------------------|
| message                                | string  | Mensaje de éxito indicando que la reserva se completó correctamente.                                                      |
| data.booking.bookings_enabled          | boolean | Indica si las reservas están habilitadas.                                                                                 |
| data.booking.bookings_disabled_message | string  | Mensaje mostrado si las reservas están deshabilitadas.                                                                    |
| data.booking.id                        | integer | Identificador único de la reserva.                                                                                        |
| data.booking.guest_count               | string  | Número de invitados para la reserva.                                                                                      |
| data.booking.dayDisplay                | string  | Representación legible del tiempo de la reserva (por ejemplo, "Sun, Jun 15 at 6:00 pm").                                  |
| data.booking.status                    | string  | Estado de la reserva.                                                                                                     |
| data.booking.venue                     | string  | Nombre del lugar.                                                                                                         |
| data.booking.logo                      | string  | URL del logo del lugar.                                                                                                   |
| data.booking.total                     | string  | Total con impuestos, formateado con moneda.                                                                               |
| data.booking.subtotal                  | string  | Subtotal, formateado con moneda.                                                                                          |
| data.booking.tax_rate_term             | string  | Término de la tasa de impuestos (puede ser `null` en reservas no-prime).                                                  |
| data.booking.tax_amount                | string  | Monto de impuestos, formateado con moneda (puede ser `null` en reservas no-prime).                                        |
| data.booking.bookingUrl                | string  | URL de la reserva.                                                                                                        |
| data.booking.qrCode                    | string  | Código QR de la reserva.                                                                                                  |
| data.booking.is_prime                  | integer | Indica si la reserva es prime (`1` para sí, `0` para no).                                                                 |
| data.booking.booking_at                | string  | Fecha y hora de la reserva.                                                                                               |
| data.invoice_status                    | string  | Estado de la generación de la factura (`processing` cuando el PDF está siendo generado). **Solo para reservas no-prime.** |
| data.invoice_message                   | string  | Mensaje explicando el estado de la factura. **Solo para reservas no-prime.**                                              |
| data.invoice_download_url              | string  | URL directa para descargar la factura en PDF (solo presente si la factura está lista). **Solo para reservas prime.**      |
| data.result.success                    | boolean | Indica si el proceso de finalización fue exitoso. **Solo para reservas prime.**                                           |
| data.result.booking_confirmed          | boolean | Indica si la reserva fue confirmada. **Solo para reservas prime.**                                                        |
| data.result.payment_processed          | boolean | Indica si el pago fue procesado correctamente. **Solo para reservas prime.**                                              |

#### Error Responses

##### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

##### 422 Unprocessable Entity

**Invalid booking status:**

```json
{
    "message": "Booking already confirmed or cancelled"
}
```

**Venue not active:**

```json
{
    "message": "Venue is not currently accepting bookings"
}
```

**Missing payment intent (prime bookings):**

```json
{
    "message": "Payment intent ID is required for prime bookings"
}
```

**Duplicate booking (non-prime):**

```json
{
    "message": "Customer already has a non-prime booking for this day"
}
```

**Validation errors:**

```json
{
    "first_name": [
        "The first name field is required."
    ],
    "last_name": [
        "The last name field is required."
    ],
    "phone": [
        "The phone field is required."
    ]
}
```

##### 500 Internal Server Error

**Prime booking completion failure:**

```json
{
    "message": "Booking completion failed: Payment processing error"
}
```

## Check Invoice Status

### Request

- **Method:** GET
- **URL:** `/api/bookings/{booking}/invoice-status`
- **Authentication:** Required

#### Headers

| Header        | Value            | Required | Description                            |
|---------------|------------------|----------|----------------------------------------|
| Authorization | Bearer {token}   | Yes      | Authentication token                   |
| Accept        | application/json | Yes      | Specifies the expected response format |

#### URL Parameters

| Parameter | Type    | Required | Description                                       |
|-----------|---------|----------|---------------------------------------------------|
| booking   | integer | Yes      | The ID of the booking to check invoice status for |

#### Example Request

```bash
curl -X GET \
  https://api.example.com/api/bookings/456/invoice-status \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Response

#### Success Response

- **Status Code:** 200 OK

##### Response Body

**Invoice Ready:**

```json
{
    "status": "ready",
    "invoice_download_url": "https://api.example.com/customer/invoice/download/ba52e84f-9dd2-41e1-a80f-d928ac2e5a6d",
    "message": "Invoice is ready for download"
}
```

**Invoice Processing:**

```json
{
    "status": "processing",
    "message": "Invoice is being generated and will be available shortly"
}
```

##### Response Fields

| Field                | Type   | Description                                       |
|----------------------|--------|---------------------------------------------------|
| status               | string | Invoice status: "ready" or "processing"           |
| invoice_download_url | string | Direct download URL (only when status is "ready") |
| message              | string | Status description                                |

#### Error Responses

##### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

##### 422 Unprocessable Entity

```json
{
    "message": "Invoice not available for this booking"
}
```

## Email Invoice

### Request

- **Method:** POST
- **URL:** `/api/bookings/{booking}/email-invoice`
- **Authentication:** Required

#### Headers

| Header        | Value            | Required | Description                            |
|---------------|------------------|----------|----------------------------------------|
| Authorization | Bearer {token}   | Yes      | Authentication token                   |
| Accept        | application/json | Yes      | Specifies the expected response format |
| Content-Type  | application/json | Yes      | Specifies the request format           |

#### URL Parameters

| Parameter | Type    | Required | Description                                    |
|-----------|---------|----------|------------------------------------------------|
| booking   | integer | Yes      | The ID of the booking to email the invoice for |

#### Request Body

| Parameter | Type   | Required | Description                                                                                |
|-----------|--------|----------|--------------------------------------------------------------------------------------------|
| email     | string | No       | Email address to send the invoice to (falls back to booking's guest email if not provided) |

#### Example Request

**With custom email:**

```bash
curl -X POST \
  https://api.example.com/api/bookings/456/email-invoice \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "email": "custom@example.com"
  }'
```

**Using booking's guest email:**

```bash
curl -X POST \
  https://api.example.com/api/bookings/456/email-invoice \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json'
```

### Response

#### Success Response

- **Status Code:** 200 OK

##### Response Body

```json
{
    "message": "Invoice sent to customer@example.com",
    "data": {
        "email": "customer@example.com"
    }
}
```

##### Response Fields

| Field      | Type   | Description                           |
|------------|--------|---------------------------------------|
| message    | string | Confirmation message                  |
| data.email | string | Email address the invoice was sent to |

#### Error Responses

##### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

##### 422 Unprocessable Entity

**Booking not confirmed:**

```json
{
    "message": "Invoice can only be emailed for confirmed bookings"
}
```

**Invoice not ready:**

```json
{
    "message": "Invoice is not yet available. Please try again shortly."
}
```

**No email address:**

```json
{
    "message": "No email address provided and no email address available for this booking"
}
```

**Invalid email format:**

```json
{
    "email": [
        "The email field must be a valid email address."
    ]
}
```

##### 500 Internal Server Error

```json
{
    "message": "Failed to send invoice email. Please try again."
}
```

## Delete Booking

### Request

- **Method:** DELETE
- **URL:** `/api/bookings/{booking}`
- **Authentication:** Required

#### Headers

| Header        | Value            | Required | Description                            |
|---------------|------------------|----------|----------------------------------------|
| Authorization | Bearer {token}   | Yes      | Authentication token                   |
| Accept        | application/json | Yes      | Specifies the expected response format |

#### URL Parameters

| Parameter | Type    | Required | Description                     |
|-----------|---------|----------|---------------------------------|
| booking   | integer | Yes      | The ID of the booking to delete |

#### Example Request

```bash
curl -X DELETE \
  https://api.example.com/api/bookings/456 \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Response

#### Success Response

- **Status Code:** 200 OK

##### Response Body

```json
{
    "message": "Booking Abandoned"
}
```

#### Error Responses

##### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

##### 404 Not Found

```json
{
    "message": "No query results for model [App\\Models\\Booking] 456"
}
```

##### 422 Unprocessable Entity

```json
{
    "message": "Booking cannot be abandoned in its current status"
}
```

## Notes

### General Booking Flow

1. **Create booking** using `POST /api/bookings` - Returns booking details and `paymentIntentSecret` for prime bookings
2. **For prime bookings**: Process payment client-side using the `paymentIntentSecret` with Stripe's payment libraries
3. **Complete booking** using `POST /api/bookings/{booking}/complete` - Finalizes the reservation and provides invoice

### Endpoint-Specific Notes

- When creating a booking, the `schedule_template_id` should be obtained from the availability calendar endpoint
- When updating a booking, the booking must be in the "pending" status
- **When completing a booking**, the booking must be in the "pending" or "guest_on_page" status
- When deleting a booking, the booking must be in the "pending" or "guest_on_page" status
- The delete operation doesn't actually delete the booking from the database, but changes its status to "abandoned"
- For non-prime bookings, a customer can only have one booking per day at a venue

### Payment Processing

- **Prime bookings** automatically include a `paymentIntentSecret` in the create response for immediate payment
  processing
- The `paymentIntentSecret` should be used with Stripe's client-side payment libraries to process the payment
- Payment intent creation includes booking metadata for tracking purposes
- **Complete endpoint** requires the processed `payment_intent_id` for prime bookings after successful client-side
  payment
- **Invoice generation** is processed asynchronously via background jobs and uploaded to Digital Ocean storage
- **All confirmed bookings** may return `invoice_status: "processing"` initially if the PDF is still being generated
- Use the **invoice status endpoint** (`GET /api/bookings/{booking}/invoice-status`) to check when the invoice is ready
  for any confirmed booking
- **Invoice download URL** is only returned when the PDF has been successfully generated and uploaded
- Payment intent is handled entirely client-side by your app, server only validates the completed payment
