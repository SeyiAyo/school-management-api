# School Onboarding and Admin Registration Implementation Plan

This document defines the step-by-step plan to change registration to onboard a school administrator first, enforce email verification, and complete a 3-step school profile setup. It also lists all code changes, endpoints, middleware, and deployment steps.

## Objectives
- Register a school owner/principal as the initial administrator only.
- Enforce email verification before system access using OTP codes.
- Complete a guided 3-step onboarding to create the School profile.
- Provide Swagger documentation for all endpoints.

## ✅ **IMPLEMENTATION STATUS: COMPLETED**

### **Email Verification System**
- ✅ **OTP-Based Verification**: 6-digit codes with 5-minute expiry
- ✅ **Temporary Token System**: Secure verification tokens for OTP endpoints
- ✅ **Gmail SMTP Integration**: Local development email delivery
- ✅ **Synchronous Email Sending**: No queue dependency required

### **Authentication Flow**
1. **Registration** → Issues temporary verification token + sends OTP email
2. **OTP Verification** → Uses temporary token, marks email verified, revokes token
3. **Login** → Full access token after email verification

---

## 1) Data Model Changes

- Create `School` model and migration
  - Table: `schools`
    - `id` (PK)
    - `owner_user_id` (FK `users.id`, unique)
    - `name` (string)
    - `type` (string; e.g., Primary, Secondary, Both, etc.)
    - `email` (nullable)
    - `phone` (nullable)
    - `address` (nullable)
    - `website` (nullable)
    - `logo_path` (nullable)
    - `description` (text, nullable)
    - `academic_levels` (json, nullable)
    - `calendar_structure` (string, nullable)
    - `status` (string enum: `pending|active|suspended`, default `pending`)
    - timestamps
  - Model: `app/Models/School.php`
    - `$fillable` for fields above
    - Relationships: `owner(): belongsTo(User::class, 'owner_user_id')`

---

## 2) Update User for Email Verification

- In `app/Models/User.php`:
  - Implement `Illuminate\Contracts\Auth\MustVerifyEmail`
  - Keep `Notifiable` (already present)
  - Ensure `email_verified_at` cast exists (already present)

- Configure mail in `.env` (SMTP) for verification emails.

---

## 3) AuthController::register() Changes

File: `app/Http/Controllers/API/AuthController.php`

- Replace current register() to create an Admin user only with fields:
  - `first_name`, `last_name`, `email`, `password`, `password_confirmation`, `phone`, `position`
- Combine first/last name into `name` when storing in `users`.
- Set `role = 'admin'`.
- Send email verification: `user->sendEmailVerificationNotification()`.
- Return response without full access until verified:
  - `{ requires_email_verification: true }`
  - Option A (recommended): Do not issue token until verified.
  - Option B: issue restricted token with ability `pending:onboarding` (if needed).

Swagger updates for `POST /api/register` to reflect new request/response.

---

## 4) OnboardingController and Endpoints

File: `app/Http/Controllers/API/OnboardingController.php`

- Middleware: `auth:sanctum` + require verified email (`verified` or explicit check).
- Authorization: Only `role=admin` can access these endpoints and only for their own `School`.

Endpoints:
1. `POST /api/onboarding/school-profile/step-1`
   - Purpose: Create or update school with core identity
   - Payload: `name` (required), `type` (required), `description` (optional), `logo` (optional file or URL)
   - Action: Upsert School for `owner_user_id = auth()->id()`

2. `POST /api/onboarding/school-profile/step-2`
   - Purpose: Contact and presence info
   - Payload: `email`, `phone`, `address`, `website`, `accept_terms` (required: boolean)
   - Action: Update School; store `accept_terms` as a flag (if needed) or in audit log.

3. `POST /api/onboarding/school-profile/complete`
   - Purpose: Finalize onboarding
   - Action: Set `status = active` and return bootstrap data for dashboard

Each method uses DB transactions and the base Controller helpers `success()` / `error()`.

Swagger: Document all three endpoints with request bodies, validation, responses, and `security={{"bearerAuth":{}}}`.

---

## 5) Routes and Middleware

- `routes/web.php` (email verification callback):
  - `GET /email/verify/{id}/{hash}` using Laravel’s verification routes (signed).

- `routes/api.php`:
  - `POST /api/email/verification-notification` (auth:sanctum) to resend verification email
  - Onboarding routes (auth:sanctum + verified):
    - `POST /api/onboarding/school-profile/step-1`
    - `POST /api/onboarding/school-profile/step-2`
    - `POST /api/onboarding/school-profile/complete`

- Middleware:
  - Use `verified` middleware on onboarding routes OR check `auth()->user()->hasVerifiedEmail()` in controller.

---

## 6) Validation Rules (suggested)

- Register:
  - `first_name,last_name`: required|string|max:100
  - `email`: required|email|unique:users,email
  - `password`: required|string|min:8|confirmed
  - `phone`: nullable|string|max:20
  - `position`: required|string|max:100

- Step 1:
  - `name`: required|string|max:255
  - `type`: required|string|in:Primary,Secondary,Both Primary and Secondary,Nursery
  - `description`: nullable|string|max:1000
  - `logo`: nullable|image|mimes:png,jpg,jpeg|max:2048 (if file upload)

- Step 2:
  - `email`: nullable|email
  - `phone`: nullable|string|max:20
  - `address`: nullable|string|max:255
  - `website`: nullable|url
  - `accept_terms`: required|boolean|accepted

---

## 7) Swagger/OpenAPI Documentation

- Update `@OA\Post("/api/register")` schema and examples.
- Add `@OA\Post` docs for each onboarding endpoint in `OnboardingController`.
- Ensure global `bearerAuth` security scheme is defined (already configured via l5-swagger).
- Regenerate docs: `php artisan l5-swagger:generate`.

---

## 8) Email Verification Flow (API) - **UPDATED FOR OTP SYSTEM**

### **New OTP-Based Flow**
1. **Registration** → Returns `verification_token` + sends OTP email
2. **OTP Verification** → `POST /api/email/verify-otp` with temporary token
3. **Resend OTP** → `POST /api/email/verification-notification` with temporary token

### **API Endpoints**
- `POST /api/email/verify-otp` (requires temporary token)
  - Headers: `Authorization: Bearer {verification_token}`
  - Body: `{"otp_code": "123456"}`
  
- `POST /api/email/verification-notification` (requires temporary token)
  - Headers: `Authorization: Bearer {verification_token}`
  - Body: `{}` (empty)

### **Frontend Integration**
- After registration, store `verification_token` from response
- Show OTP input screen with 6-digit code field
- Use temporary token for verification requests
- After successful verification, redirect to login (token is revoked)
- Provide resend button using temporary token

---

## 9) Deployment Checklist

- Migrate DB on live: `php artisan migrate --force`
- Ensure `.env` mail settings are correct (SMTP)
- Ensure `APP_URL` is correct for signed verification links
- Generate Swagger on live: `php artisan l5-swagger:generate`
- Test flow end-to-end:
  1) Register admin
  2) Receive email and verify
  3) Step-1 school profile
  4) Step-2 contacts and terms
  5) Complete onboarding and access dashboard

---

## 10) Future Considerations

- Multi-tenant subdomain provisioning (link `school.subdomain` + wildcard DNS)
- Assign `school_id` to users and scope queries by tenant
- Ability-based tokens: `role:admin`, `onboarding:*` granular permissions
- Audit logs for terms acceptance and profile changes

---

## References in Codebase
- `app/Http/Controllers/API/AuthController.php` (to be updated)
- `app/Models/User.php` (MustVerifyEmail)
- `routes/web.php`, `routes/api.php` (verification + onboarding)
- `app/Models/School.php` (new)
- `docs/onboarding-implementation.md` (this file)
