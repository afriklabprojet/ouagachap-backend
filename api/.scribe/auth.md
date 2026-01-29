# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {VOTRE_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtenez votre token en envoyant un OTP via `/api/v1/auth/send-otp` puis en le vérifiant via `/api/v1/auth/verify-otp`.
