# TrustAI B2B Partner Network — rollout 2026-11-17

**Branch:** feature/b2b-partner-network-20261117
**Status:** source implementation for review. Not deployed: Remote Desktop VPS offline.

## Purpose

Extend TrustAI's existing ambassador infrastructure to SETAEI B2B development and marketing contracts. This is isolated from Shopify/WooCommerce order commissions.

1. Admin creates the SETAEI B2B program.
2. Admin invites RAWScales (root partner) and shares a single-use link privately.
3. Thomas signs in with Vipps, confirms 18+ when necessary, and accepts the invite.
4. Partners register companies by Norwegian organisation number. Checksum is verified and official Brønnøysund register lookup prefills the company name. One organisation number can be claimed only once per merchant program.
5. SETAEI admin records the invoice number, real revenue after any discount (ex VAT), attributable costs (ex VAT), payout model and invoice stage.
6. Partner can view the referral stage and commission but not internal revenue, expenses or margins.
7. Commission remains pending until the client invoice is paid, the admin explicitly verifies attributable costs and approves the sale; then it is ready. The partner invoices SETAEI separately; admin manually marks payout paid after actually paying it.
8. Partners can invite additional sellers/partners/ambassadors underneath them. Direct sponsors can receive a share from an actual completed sale by their direct recruit. No fee to join and no commission for recruitment alone.

## Commission

Default = 30% × max(0, actual revenue after discount - attributable direct costs).

Fixed agreed commission overrides are supported per sale, but must not exceed actual positive profit. Standard commission for loss/zero-margin sales is zero.

Stenhuset is the trading brand of STENPROSJEKT AS (organisation no. 992 214 341), verified against official registry and Stenhuset's own purchase terms. Proposed RAWScales payout is NOK 3,000 as a fixed exception, subject to verifying actual cost/positive margin. No actual Stenhuset invoice, expenses or partner payout has been registered.

The parent gets a configurable direct-child commission share (default 10% of the commission pool, admin-adjustable from 0–30%), taken from the child's commission rather than adding costs for SETAEI. A partner network may be nested, but only a directly sponsoring parent receives an upline share on each sale. Example: commission pool NOK 3,000, child NOK 2,700, parent NOK 300. A root RAWScales partner with no parent receives the entire NOK 3,000.

Cancelled sales create no commission; edits are blocked when the sale has a paid commission.

| Illustrative sale | Revenue ex VAT | Costs ex VAT | Positive profit | Commission |
|---|---:|---:|---:|---:|
| Standard | 30,000 | 20,000 | 10,000 | 3,000 |
| Special fixed | 30,000 | 20,000 | 10,000 | 3,000 |
| Discounted, low margin | 15,000 | 13,000 | 2,000 | Fixed 3,000 is rejected |
| Loss | 11,000 | 12,000 | 0 | 0 standard |

## Files

- database/migrations/2026-11-17_b2b_partner_network.sql — separate sales, partners, invites, referrals, commissions, audit trail.
- api/partner-network/index.php — scoped API, Vipps session validation, commission calculation and manual status management.
- partner-network.html — responsive portal in English by default with Norwegian flag toggle.
- api/auth/vipps/login.php, callback.php, set-role.php, api/auth/route.php — B2B invite routing and existing member login.
- public/index.html, super-admin.html, ambassador-dashboard.html — navigation links.
- .github/workflows/b2b-partner-lint.yml — lint checks.

## Release steps (NOT DONE)

1. Reconnect the VPS hosting TrustAI and confirm actual path/current version/production DB. Back up DB.
2. Review PR, CI and migration, then merge to main.
3. Apply migration once. Never run demo seed in production.
4. Deploy, test HTTPS portal, Vipps login, age verification and API. Verify one unique org per program, parent invitation, costs hidden, payout lock, expiry, admin permissions.
5. Admin creates SETAEI program and privately invites RAWScales. Only real Vipps-authenticated person claims it. Do not make up a password or store a public invitation token in GitHub.
6. Register Stenhuset (trade name) / STENPROSJEKT AS (legal name), org. 992 214 341, real invoice, attributable costs, and fixed NOK 3,000 only if sufficient margin. First confirmed customer payment, then partner invoicing/payout.
7. Check a low-profit sale rejects excess fixed payout and a cancelled sale has no payout.
8. Verify CORS/CSRF and no forged header identity; each partner sees only own clients/commissions.

## Outstanding

- All servers in current Remote Desktop connection are offline. No live database migration, account creation, payout or production deploy has been performed.
- Stenhuset organisation number is verified (992 214 341); project-specific cost inputs still need confirmation before live registration.
- Earlier 13 October email to Thomas offered 20% of billed revenue; clarify the new 30% *net-profit* model / NOK 3,000 Stenhuset exception in writing before financial settlement.
- VAT, invoicing and privacy terms need accounting/legal review. No automatic transfer is implemented.
