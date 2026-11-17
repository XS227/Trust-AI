# TrustAI B2B Partner Network — rollout 2026-11-17

**Branch:** feature/b2b-partner-network-20261117
**Status:** source implementation for review. Not deployed: Remote Desktop VPS offline.

## Purpose

Extend TrustAI's existing ambassador infrastructure to SETAEI B2B development and marketing contracts. This is isolated from Shopify/WooCommerce order commissions.

1. Admin applies the B2B schema, then the idempotent SETAEI/RAWScales/Stenhuset seed.
2. Admin opens the SETAEI program, creates a root invite for RAWScales / Thomas Hannaas, and explicitly attaches the reserved approved Stenhuset introduction.
3. Thomas signs in with Vipps, confirms 18+ when necessary, and accepts the private invite. The verified Vipps identity and consented phone number are read from the login callback, never fabricated from a public directory.
4. When the invitation is claimed, the **already approved** Stenhuset referral is atomically linked to Thomas's portfolio. He sees a one-time congratulatory popup, followed by a persistent **Trusted One** badge. The server stores whether the welcome was acknowledged to avoid repeating it. Partners register further companies by Norwegian organisation number. Checksum is verified and official Brønnøysund register lookup prefills the company name. One organisation number can be claimed only once per merchant program.
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

- database/migrations/2026-11-17_b2b_partner_network.sql — separate sales, partners, invites, referrals, reserved introductions, welcome acknowledgements, commissions and audit trail.
- database/seeds/2026-11-17_setaei_rawscales_stenhuset.sql — staged, approved introduction for RAWScales (STENPROSJEKT AS / Stenhuset, org 992 214 341). Does **not** create a verified Vipps account, invitation token or payable commission.
- api/partner-network/index.php — scoped API, Vipps session validation, commission calculation and manual status management.
- partner-network.html — responsive portal in English by default with Norwegian flag toggle.
- api/auth/vipps/login.php, callback.php, set-role.php, api/auth/route.php — B2B invite routing and existing member login.
- public/index.html, super-admin.html, ambassador-dashboard.html — navigation links.
- .github/workflows/b2b-partner-lint.yml — lint checks.

## Release steps (NOT DONE)

1. Reconnect the VPS hosting TrustAI and confirm actual path/current version/production DB. Back up DB.
2. Review PR, CI and migration, then merge to main.
3. Apply migration once and the **separate, explicitly approved** SETAEI/RAWScales seed once. This seed is safe to repeat but must not be run before confirming no competing pre-existing claim to Stenhuset. Never run legacy demo seed in production.
4. Deploy, test HTTPS portal, Vipps login, age verification and API. Verify one unique org per program, parent invitation, costs hidden, payout lock, expiry, admin permissions.
5. In SETAEI program admin invites RAWScales / Thomas Hannaas, **selects the reserved Stenhuset introduction** under the invitation form, and privately delivers the 14-day one-use link to Thomas. Only his verified Vipps login may claim his invitation; do not invent a phone number, password or public invitation token.
6. If Thomas' private Vipps mobile is available, enter it only in the **admin's optional Vipps phone field** when generating his invite. The API validates +47 formatting and rejects claims by another Vipps mobile. If unknown, leave blank and the existing Vipps callback stores the actual mobile on first verified login. Check it from the protected admin partner identity view. Never commit a personal mobile to GitHub.
7. Confirm Stenhuset / STENPROSJEKT AS (org 992 214 341) appears in his referral portfolio with **approved introduction** (this is distinct from customer payment); check the one-time Norwegian congratulatory popup and the Trusted One badge.
8. Admin later registers a real invoice, attributable costs, and fixed NOK 3,000 only if sufficient margin. Confirm customer payment before partner invoicing/payout.
9. Check a low-profit sale rejects excess fixed payout and a cancelled sale has no payout.
10. Verify CORS/CSRF and no forged header identity; each partner sees only own clients/commissions.

## Outstanding

- All servers in current Remote Desktop connection are offline. No live database migration, Vipps-linked account, portfolio claim, payout or production deploy has been performed.
- Historic hosting: TrustAI was deployed at /var/www/trustai on VPS 1431514, while the 2026-10-26 server audit lists trustai.no on 5.249.255.116. This is the old ProISP server in the user's server inventory; check actual DNS, Nginx, PHP and DB location before any migration.
- Stenhuset organisation number is verified (992 214 341); project-specific cost inputs still need confirmation before live registration.
- Earlier 13 October email to Thomas offered 20% of billed revenue; clarify the new 30% *net-profit* model / NOK 3,000 Stenhuset exception in writing before financial settlement.
- VAT, invoicing and privacy terms need accounting/legal review. No automatic transfer is implemented.

## RAWScales reserved first referral and Trusted One

The idempotent SQL file database/seeds/2026-11-17_setaei_rawscales_stenhuset.sql reserves the approved Stenhuset introduction for RAWScales / Thomas Hannaas under the SETAEI program. Legal customer: STENPROSJEKT AS, organisation no. 992 214 341; trading name Stenhuset. Suggested fixed commission NOK 3,000, strictly subject to actual verified positive margin and separate admin approval. The referral itself is approved, but neither the customer invoice nor any commission is marked paid.

To claim the reserved customer, a super admin creates an invite and explicitly chooses the Stenhuset reservation. This invitation must now be restricted to the intended Vipps account: enter the email known to be associated with Thomas's Vipps login or his actual Norwegian Vipps mobile in the private admin form. An approved reserved referral cannot be attached to an unrestricted invitation. The server checks the verified Vipps user profile against those expected identifiers on redemption.

Thomas's actual Vipps phone is NOT available in previously exchanged emails and MUST NOT be invented or committed. The authenticated phone_number from the Vipps login callback is stored in the private users table only after he consents, and is readable by super admin via the protected partner identity API. The invite URL remains single-use, 14 days, and must be shared privately.

Successful verified redemption atomically links Stenhuset to Thomas's personal portfolio and creates a one-time Trusted One recognition event. The Norwegian modal says: Gratulerer med din første godkjente anbefaling! Din første anbefaling er nå godkjent i TrustAI. Du er nå en Trusted One. The modal is acknowledged server-side once (seen_at), and the Trusted One badge remains on the account.

This code has not been deployed or migrated, since the authorized remote VPS connections are offline. Last inventory: TrustAI was at /var/www/trustai on server 1431514. A documented 26 October 2026 DNS check recorded trustai.no at 5.249.255.116. Check live server/DB location before applying any changes.
