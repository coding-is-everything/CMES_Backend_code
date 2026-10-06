# License Management System — Backend API (for the Next.js frontend)

Base URL: `{API_URL}/api/v1` (e.g. `http://localhost:8000/api/v1`)

All requests and responses are JSON. Send these headers on every call:

```
Accept: application/json
Content-Type: application/json
```

Authenticated calls add `Authorization: Bearer <access_token>`.

---

## 1. Conventions

### Response envelope

```jsonc
// success
{ "success": true, "message": "…", "data": { … } }

// failure (handled errors: 401 / 403 / 404 / 500)
{ "success": false, "message": "…", "data": null }

// validation failure (422) — standard Laravel shape
{
  "message": "The given data was invalid.",
  "errors": { "email": ["Invalid email or password."] }
}
```

Note: **422 errors do not use the `success` envelope.** Read field messages from `errors.<field>[0]`.

### Status codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 401 | Missing / invalid / expired access token |
| 403 | Authenticated but lacks permission |
| 404 | Resource not found |
| 422 | Validation or business-rule failure (wrong password, locked account, bad refresh token…) |
| 429 | Rate limited — back off, honour the `Retry-After` header |
| 500 | Server error (generic message) |

### TypeScript types

```ts
export interface ApiSuccess<T> { success: true; message: string; data: T }
export interface ApiFailure { success: false; message: string; data: null }
export interface ValidationError { message: string; errors: Record<string, string[]> }

export interface AdminRole { id: number; role_code: string; role_name: string }

export interface AdminAuthUser {
  id: number;
  admin_code: string;
  full_name: string;
  email: string;
  mobile_number: string | null;
  status: 'ACTIVE' | 'INACTIVE' | 'LOCKED';
  last_login_at: string | null; // ISO 8601
  roles: AdminRole[];
}

export interface AdminUser extends AdminAuthUser {
  created_at: string | null;
  updated_at: string | null;
}

export interface AdminTokens {
  admin: AdminAuthUser;
  access_token: string;
  refresh_token: string;
  token_type: 'Bearer';
  access_token_expires_at: string | null;  // ISO 8601
  refresh_token_expires_at: string | null; // ISO 8601
}
```

---

## 2. Admin authentication

### 2.1 `POST /admin/auth/login`

Rate limits: 10 requests/min per email+IP (route), plus 5 failed attempts per email → temporary lock (60 s window).

**Body**

| Field | Type | Rules |
|-------|------|-------|
| `email` | string | required, valid email, max 150 (trimmed + lower-cased server-side) |
| `password` | string | required, 8–255 chars |
| `device_name` | string \| null | optional, max 100. Default `admin-web`. Logging in again with the same device name replaces that device's previous token pair |

**200**

```json
{
  "success": true,
  "message": "Admin login successful.",
  "data": {
    "admin": {
      "id": 1, "admin_code": "ADM0001", "full_name": "Super Admin",
      "email": "super@example.com", "mobile_number": "9999999999",
      "status": "ACTIVE", "last_login_at": "2026-10-06T10:00:00.000000Z",
      "roles": [{ "id": 1, "role_code": "SUPER_ADMIN", "role_name": "Super Admin" }]
    },
    "access_token": "12|abc…",
    "refresh_token": "13|def…",
    "token_type": "Bearer",
    "access_token_expires_at": "2026-10-06T11:00:00.000000Z",
    "refresh_token_expires_at": "2026-11-05T10:00:00.000000Z"
  }
}
```

**422 messages** (all under `errors.email[0]` unless noted)

| Message | Cause |
|---------|-------|
| `Invalid email or password.` | wrong credentials, unknown or deleted admin |
| `Your administrator account is locked.` | status `LOCKED` |
| `Your administrator account is inactive.` | status not `ACTIVE` |
| `Too many login attempts. Please try again in N seconds.` | 5 failures reached |
| `Email address is required.` / `Please enter a valid email address.` | `errors.email` |
| `Password is required.` / `Password must be at least 8 characters.` | `errors.password` |

**429** — route throttle exceeded.

### 2.2 `POST /admin/auth/refresh`

Rate limit: 20 requests/min per IP.

Exchanges a refresh token for a **new access token and a new refresh token**. Refresh tokens are **single-use (rotation)**: the one you send is revoked, as is the previous access token of that session. Always store the new pair.

**Body**

| Field | Type | Rules |
|-------|------|-------|
| `refresh_token` | string | required, max 500 (trimmed) |
| `device_name` | string \| null | optional, max 100. Defaults to the device used at login |

**200** — same `data` shape as login (`AdminTokens`), message `Admin access token refreshed successfully.`

**422 messages** (under `errors.refresh_token[0]`)

| Message | Cause |
|---------|-------|
| `Refresh token is required.` | missing/blank |
| `Invalid refresh token format.` | not a string |
| `Invalid or expired refresh token.` | unknown, or already used |
| `Invalid refresh token.` | an access token was sent, or the admin no longer exists |
| `Refresh token has expired.` | past `refresh_token_expires_at` |
| `Administrator account is not active.` | admin locked/inactive/deleted since login |
| `Invalid refresh token session.` | malformed legacy token |

On any 422 here, **clear stored tokens and send the user to the login page.**

TTLs are server-configured (defaults: access 60 min, refresh 43 200 min = 30 days). Use the `*_expires_at` fields rather than hard-coding.

### 2.3 `POST /admin/auth/logout`

Requires `Authorization: Bearer <access_token>`. No request body.

Ends the **current device session only**: the access token you send and the matching refresh token (same session) are both revoked. The admin's other devices stay logged in, as do other admins.

**200**

```json
{ "success": true, "message": "Admin logout successful.", "data": null }
```

**Errors**

| Code | Message | Cause |
|------|---------|-------|
| 401 | `Unauthenticated.` | missing, invalid, expired or already-revoked access token (Laravel's default body `{ "message": "Unauthenticated." }` — no `success` envelope) |
| 401 | `Unauthenticated admin session.` | token belongs to a non-admin user |
| 405 | — | any method other than `POST` |
| 500 | `Unable to logout admin session.` | server error |

Frontend: after a 200 **or** a 401, discard both stored tokens and redirect to login — a 401 means the session is already gone. The refresh token can no longer be used after logout (it returns 422 `Invalid or expired refresh token.`).

> The old `/api/auth/logout` path (outside `/v1/admin`) has been removed.

### 2.4 `POST /admin/auth/logout-all`

Endpoint ID `ADM-AUTH-004` · Next.js screen `/security/sessions` · Permission slug `auth.logout_all`.

Requires `Authorization: Bearer <access_token>`. No request body.

Revokes **every** session of the authenticated admin — all access and refresh tokens on all devices, including the one making the call. Other admins are not affected.

**200**

```json
{
  "success": true,
  "message": "All admin sessions revoked successfully.",
  "data": { "revoked_tokens": 6 }
}
```

`revoked_tokens` is the number of tokens deleted (two per active device: one access, one refresh).

**Errors**

| Code | Message | Cause |
|------|---------|-------|
| 401 | `Unauthenticated.` | missing, invalid, expired or already-revoked access token (Laravel default body, no `success` envelope) |
| 401 | `Unauthenticated admin session.` | token belongs to a non-admin user |
| 405 | — | any method other than `POST` |
| 500 | `Unable to revoke admin sessions.` | server error |

Frontend: on 200, clear stored tokens and redirect to login. Any other device will get a 401 on its next call and a 422 if it tries to refresh.

> The `auth.logout_all` slug is informational for now: access is granted to any authenticated admin and no permission check is enforced.

---

## 3. Admin users

### 3.1 `GET /admin/admin-users`

Requires `Authorization: Bearer <access_token>` and the `admin_users.view` permission (via any of the admin's roles).

**Query parameters** (all optional)

| Param | Type | Rules |
|-------|------|-------|
| `page` | int | ≥ 1 |
| `per_page` | int | 1–100 |
| `search` | string | max 150 |
| `status` | `ACTIVE` \| `INACTIVE` \| `LOCKED` | |
| `role_id` | int | must exist |
| `sort_by` | `id`, `admin_code`, `full_name`, `email`, `status`, `last_login_at`, `created_at`, `updated_at` | |
| `sort_order` | `asc` \| `desc` | |

**200**

```json
{
  "success": true,
  "message": "Admin users retrieved successfully.",
  "data": [ /* AdminUser[] */ ],
  "meta":  { "current_page": 1, "per_page": 15, "total": 42, "last_page": 3, "from": 1, "to": 15 },
  "links": { "first": "…?page=1", "last": "…?page=3", "prev": null, "next": "…?page=2" }
}
```

**Errors:** 401 `Authentication token is required.` / `Invalid or expired authentication token.`; 403 `You do not have permission to perform this action.`; 422 for invalid query params.

> ⚠️ **Known backend issue (as of 2026-10-06):** this route is guarded by the `admin.auth` middleware, which looks tokens up in the `admin_sessions` table. Admin login now issues Sanctum tokens, and nothing writes `admin_sessions`, so the access token from §2.1 will currently get **401** here. The backend needs to either switch `admin.auth` to Sanctum (checking the `admin:access` ability) or be reconciled with the login flow before the frontend can call this endpoint.

---

## 4. Customer account

Requires a customer bearer token (validated against `customer_sessions`). **No endpoint in this API issues customer tokens yet**, so these calls cannot be exercised end-to-end from the frontend until a customer login endpoint exists.

```ts
export interface CustomerAccount {
  id: number;
  customer_code: string;
  full_name: string;
  mobile: { country_code: string; number: string; verified: boolean };
  email: { address: string | null; verified: boolean };
  alternate_mobile: { country_code: string | null; number: string | null };
  address: {
    line_1: string | null; line_2: string | null; city: string | null;
    district: string | null; state: string | null; postal_code: string | null;
  };
  profile_photo: string | null;
  status: string;
  created_at: string | null;
  updated_at: string | null;
}
```

### 4.1 `GET /account`

**200** — `data: CustomerAccount`, message `Account details retrieved successfully.`
**Errors:** 401 (`Authentication token is required.`, `Invalid authentication token.`, `Invalid or expired authentication token.`), 404 `Customer account not found or inactive.`

### 4.2 `PATCH /account`

Partial update — send only the fields to change. Strings are trimmed.

| Field | Rules |
|-------|-------|
| `full_name` | 2–150 chars (cannot be blank if sent) |
| `alternate_mobile_country_code` | nullable, max 10 |
| `alternate_mobile_number` | nullable, digits only, 6–20 |
| `address_line_1`, `address_line_2` | nullable, max 255 |
| `city`, `district`, `state` | nullable, max 100 |
| `postal_code` | nullable, max 20 |

**200** — updated `CustomerAccount`, message `Account updated successfully.`
**Errors:** 401, 404, 422 (field messages e.g. `Alternate mobile number must contain only digits.`).

---

## 5. Other endpoints

| Endpoint | Notes |
|----------|-------|
| `POST /auth/login` | Legacy default-`users` login (`email`, `password`) returning `data.token`. Not part of the admin or customer flows — don't build on it. Returns 401 `Invalid email or password.` on failure |
| `GET /api/user` (no `/v1`) | Returns the Sanctum-authenticated user as `data`. Useful as a token sanity check |
| `GET /up` | Health check |

---

## 6. Next.js integration guide

### 6.1 Token storage

Prefer keeping tokens **server-side**: call the Laravel API from Next.js route handlers / server actions and store the pair in `httpOnly`, `Secure`, `SameSite=Lax` cookies. Avoid `localStorage` for the refresh token (XSS-exposed). The API sets no CORS config, so browser-direct calls from another origin will fail until CORS is configured — a server-side proxy sidesteps this.

### 6.2 Minimal client

```ts
// lib/api.ts
const API = process.env.NEXT_PUBLIC_API_URL + '/api/v1';

export class ApiError extends Error {
  constructor(public status: number, message: string, public errors?: Record<string, string[]>) {
    super(message);
  }
}

export async function api<T>(path: string, init: RequestInit & { token?: string } = {}): Promise<T> {
  const { token, ...rest } = init;
  const res = await fetch(`${API}${path}`, {
    ...rest,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...rest.headers,
    },
  });
  const body = await res.json().catch(() => null);
  if (!res.ok) {
    const firstError = body?.errors && Object.values<string[]>(body.errors)[0]?.[0];
    throw new ApiError(res.status, firstError ?? body?.message ?? 'Request failed', body?.errors);
  }
  return body as T;
}
```

### 6.3 Login + silent refresh

```ts
export const adminLogin = (email: string, password: string, device_name = 'admin-web') =>
  api<ApiSuccess<AdminTokens>>('/admin/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password, device_name }),
  });

export const adminRefresh = (refresh_token: string) =>
  api<ApiSuccess<AdminTokens>>('/admin/auth/refresh', {
    method: 'POST',
    body: JSON.stringify({ refresh_token }),
  });

export const adminLogout = (token: string) =>
  api<ApiSuccess<null>>('/admin/auth/logout', { method: 'POST', token });

export const adminLogoutAll = (token: string) =>
  api<ApiSuccess<{ revoked_tokens: number }>>('/admin/auth/logout-all', { method: 'POST', token });
```

Refresh strategy:

1. Before a call, if `access_token_expires_at` is within ~60 s, refresh first.
2. On a 401, refresh **once** and retry the original request.
3. **Serialize refreshes** — share one in-flight promise. Refresh tokens are single-use, so two concurrent refreshes will make the second fail with `Invalid or expired refresh token.` and log the user out.
4. Persist the *new* `refresh_token` from every refresh response.
5. If refresh returns 422 (or 429 persists), clear tokens and redirect to login.

### 6.4 Handling errors in forms

Map `ApiError.errors` onto form fields (`errors.email[0]`, `errors.password[0]`, `errors.refresh_token[0]`). For login, credential/lock/throttle messages arrive under `email`.

### 6.5 Permissions

Roles are returned on the admin object (`roles[].role_code`). Use them only to show/hide UI — the server enforces permissions (403) regardless.
