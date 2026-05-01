# TRUSTai
Intellegence referal system

## Multi-tenant SaaS (super_admin / store_admin / ambassador)


## Database-konfigurasjon (påkrevd før seed/login)

1. Lag `inc/config.local.php` lokalt/på server (filen er ignorert av git):

```php
<?php
// Local/production overrides. IKKE committ denne med passord.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'trustai_prod');
define('DB_USER', 'trustai_user');
define('DB_PASS', 'hemmelig_passord');
```

2. `inc/config.php` laster automatisk `inc/config.local.php` hvis den finnes.
3. Hvis DB ikke er konfigurert (tomme/plassholder-verdier), returnerer API-endepunkter `{"ok":false,"error":"db_not_configured"}`.
4. `GET /api/dev/seed-demo-data.php?allow_seed=1` returnerer `db_not_configured` i stedet for HTTP 500 med fatal bootstrap-feil når DB-konfigurasjon mangler.

---

Ny multi-tenant backend ligger under `api/`:
- `api/_auth.php` shared auth/permissions helper.
- `api/stores/*` super-admin store management.
- `api/store-admin/*` store scoped admin endpoints.
- `api/super-admin/dashboard.php` global dashboard data.
- `api/ambassador/dashboard.php` ambassador scoped data.
- `api/schema-multitenant.sql` anbefalt tabellstruktur.

Nye UI-sider:
- `super-admin.html`
- `store-admin.html`
- `ambassador-dashboard.html`
- `app.html` (rolle-basert redirect)

---

## Test av multi-tenant flow (end-to-end)

Kjør disse stegene i rekkefølge:

1. **Seed demo-data (kun development):**
   - Endpoint: `GET /api/dev/seed-demo-data.php?allow_seed=1`
   - Krever `APP_ENV=dev|development|local|test` (eller query `allow_seed=1`).
   - Oppretter:
     - 1 `super_admin`
     - 2 butikker
     - 2 `store_admin`
     - 3 ambassadører per butikk
     - demo clicks/orders/payouts

2. **Login routing via `app.html`:**
   - `super_admin` → `super-admin.html`
   - `store_admin` → `store-admin.html`
   - `ambassador` → `ambassador-dashboard.html`

3. **Super admin validering:**
   - Se alle butikker
   - Opprett/rediger butikk
   - Tildele `store_admin` via `owner_user_id`
   - Se ambassadører og ordre globalt
   - Filtrere data per `store_id`

4. **Store admin validering:**
   - Ser kun egen butikk
   - Pending ambassador applications for egen butikk
   - Approve/reject/pause kun egne ambassadører
   - Ser kun egne ordre/payouts
   - Oppdaterer `default_commission_percent` for egen butikk

5. **Ambassador signup:**
   - URL-format: `/ambassador-signup.html?store_id=STORE_ID`
   - Søknad lagres med riktig `store_id`
   - Status starter som `pending`
   - Ved approve blir ambassadør brukerlinket og får referral-code tilgjengelig i dashboard

6. **Ambassador dashboard:**
   - Viser kun egne klikk/ordre/provisjon
   - Referral-link format: `/r/{store_id}/{referral_code}`

7. **Shopify webhook:**
   - Endpoint: `POST /api/shopify-order-webhook.php`
   - Bruk butikkdomene som finnes i `stores.domain`
   - Matcher ambassador **kun innen samme `store_id`**
   - Lager order med `store_id`
   - Lager `ambassador_id` ved gyldig match
   - Beregner `commission_amount`
   - Hvis referral code ikke finnes i samme butikk: order lagres kontrollert uten ambassador attribution

8. **Security smoke-tests:**
   - Forsøk å sende feil `store_id`/`ambassador_id`
   - Backend stopper:
     - `store_admin` mot annen butikk
     - `ambassador` mot annen ambassador
     - ikke-innlogget bruker
     - feil rolle

---

## Demo testbrukere (seed)

- Super admin:
  - `super-admin-demo@trustai.local`
- Store admins:
  - `store-admin-alpha@trustai.local`
  - `store-admin-beta@trustai.local`
- Ambassadors (eksempel):
  - `ambassador-1-1@trustai.local`
  - `ambassador-1-2@trustai.local`
  - `ambassador-1-3@trustai.local`
  - `ambassador-2-1@trustai.local`
  - `ambassador-2-2@trustai.local`
  - `ambassador-2-3@trustai.local`

> Merk: autentisering i dette repoet er sesjon/header-basert i dev; passordflyt avhenger av miljøet.

---

## Eksempler

### Ambassador signup URL

```text
/ambassador-signup.html?store_id=1
```

### Referral URL

```text
/r/1/s1a1ref
```

### Shopify webhook payload (eksempel)

```json
{
  "id": "10002345",
  "shop_domain": "alpha-demo.myshopify.com",
  "email": "kunde@example.com",
  "total_price": "899.00",
  "customer": {
    "first_name": "Ola",
    "last_name": "Nordmann"
  },
  "note_attributes": [
    { "name": "trustai_ref", "value": "s1a1ref" }
  ]
}
```
