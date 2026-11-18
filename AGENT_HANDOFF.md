# TRUSTAI — NEXT AGENT HANDOFF (SETAEI B2B)
Updated: 2026-11-18 21:00 UTC. Read this before continuing.

**Repository:** https://github.com/XS227/Trust-AI
**Work branch:** feature/b2b-partner-network-20261117
**Open DRAFT PR:** https://github.com/XS227/Trust-AI/pull/36
**Related docs:** docs/B2B_SETAEI_VIPPS_INVOICES_2026-11-17.md and docs/B2B_PARTNER_NETWORK_2026-11-17.md

**LIVE STATUS OVERRIDE:** TrustAI B2B was deployed on 18 Nov 2026 to VPS 1431514 at release SHA 18864f69e25da3e38660a36e1d563fb471bfed4e. Public portal https://trustai.no/partner-network.html returns 200; Vipps login redirects 302; unauthorized API 401, forgery 401, foreign Origin 403; PHP private invoice storage confirmed. Two B2B migrations plus RAWScales/Stenhuset reservation SEED were applied; one reserved referral, zero real partner accounts, zero payouts. IMPORTANT: Thomas's actual Vipps login, admin approval, first welcome and real PDF invoice E2E are still untested/not done. PR #36 remains DRAFT and unmerged. [Full deployment record, backups and rollback](docs/PRODUCTION_DEPLOY_2026-11-18_TRUSTAI_B2B.md). Older paragraphs below describing the VPS as offline are historical pre-deployment context; do NOT repeat them as current status. Continue Issue #37 and complete real Vipps E2E, not the already finished migration/deploy.

## User requirements (source of truth)

- TrustAI is to be SETAEI's B2B sales, ambassador and partner referral engine, independent of the legacy Shopify/store ambassador workflow.
- Thomas Hannaas / RAWScales referred Stenhuset. Real legal customer is STENPROSJEKT AS (org 992 214 341); trading name Stenhuset.
- First approved introduction should appear in Thomas's portfolio after real Vipps identity verification and admin approval. One-time Norwegian popup: “Gratulerer med din første godkjente anbefaling! Din første anbefaling er nå godkjent i TrustAI. Du er nå en Trusted One.” Persistent Trusted One badge.
- SETAEI's standard partner commission policy: 30% of positive actual net project profit after discounts and attributable costs, NOT 30% of gross invoiced value. Fixed Stenhuset exception proposed at NOK 3,000 ONLY if actual positive profit is sufficient. No payment until customer paid, costs verified, invoice accepted and SETAEI paid the partner.
- Parent partner may share a percentage of direct recruit's actual earned commission FROM THE SAME commission pool, no payment simply for recruiting.
- Thomas must be able to register for SETAEI through Vipps (not by adding setai.no as an ecommerce store) and upload a PDF commission invoice in his TrustAI portal. Admin must see and approve document, request correction or mark externally paid.

## Reported production bug (Android screenshot)

User was redirected to https://trustai.no/ambassador-signup.html?vipps=1 after authenticating via Vipps, searched Setai.no as Store domain, and saw an endless “Submitting application…” state. This is legacy Shopify ambassador registration; api/auth/vipps/finish-ambassador.php searches stores, not B2B programs. The fix on this branch adds explicit B2B Vipps intent and application; the legacy signup redirects SETAEI cases to the new portal and bounds its submission with a timeout.

## Implementation currently present in branch

- partner-network.html: mobile-responsive bilingual partner/application/referrals/network/commissions/invoices/admin UI.
- api/partner-network/index.php: B2B program/partner membership, applications, admin identity review and approval, reserved referrals, Trusted One acknowledgement, commission ledger, invoice review and payment status.
- api/partner-network/invoice-upload.php: private PDF invoice upload and revised invoice replacement; real file bytes must live outside HTTP docroot.
- api/partner-network/invoice-file.php: authenticated role-scoped PDF download.
- api/auth/vipps/login.php, callback.php, set-role.php, api/auth/route.php: B2B Vipps intent and redirect and existing member detection through age confirmation.
- ambassador-signup.html: SETAEI service partners redirected to B2B rather than stores.
- database/migrations/2026-11-17_b2b_partner_network.sql: isolated partner/network/program/referral/commission/welcome tables.
- database/migrations/2026-11-17_b2b_partner_applications_invoices.sql: B2B application and invoice tables.
- database/seeds/2026-11-17_setaei_rawscales_stenhuset.sql: idempotent SETAEI program and RESERVED RAWScales-approved Stenhuset customer. It does not create Thomas, a Vipps-sub, a private phone, an invoice or payout.
- .github/workflows/b2b-partner-lint.yml: PHP and JavaScript syntax; MariaDB migrations and seed idempotency.
- public/index.html, super-admin.html, ambassador-dashboard.html: Partner Network links.
- docs/B2B_SETAEI_VIPPS_INVOICES_2026-11-17.md: detailed installation/security runbook.

Last confirmed pre-handoff CI: commit 0ccbffdfebf05857fc2fe968dff4fffa2d399910, GitHub Actions run 44147876904, SUCCESS for syntax and MariaDB seed/migration. These checks DO NOT exercise real Vipps OAuth, real PDF uploads, permissions, production deployment or E2E flows. Recheck current head and CI after new documentation commits.

## Hosting / live status

Historical ProISP VPS device: 1431514; historical TrustAI code root: /var/www/trustai. Older inventory: trustai.no resolved to 5.249.255.116. This is historical, not live verification. Latest Desktop Commander check: 1431514 OFFLINE, and the other connected VPS devices also OFFLINE. Verify current DNS, vhost, DB and path before touching any production system.

NOT DONE: no production code deployment, DB migration, optional seed, private invoice storage, Thomas Vipps-linked partner account, Stenhuset live portfolio assignment, real invoice, or external payment. Do not imply any of those are complete.

## Release steps for next agent (strict sequence)

1. Reconnect to the actual TrustAI production host. Check DNS/IP, host identity, Nginx vhost/document root, PHP-FPM user and environment, DB configuration, git working tree, live branch and whether any uncommitted server changes exist. Back up the entire current code/config and DB; confirm rollback.
2. Audit PR #36 and reconcile production changes before merge/deploy. Specifically inspect Vipps callback state + PKCE/role/18+ redirects, case where user already has a Shopify ambassador role, active-user authorization, CSRF/origin, claims of admin permissions, DB concurrency and invoice file privacy. Do not trust legacy X-User-Id HTTP headers for financial APIs.
3. Verify STENPROSJEKT AS org 992214341 is not already claimed by a different lead/partner. Review all migrations and run in order in a controlled environment:
   a. database/migrations/2026-11-17_b2b_partner_network.sql
   b. database/migrations/2026-11-17_b2b_partner_applications_invoices.sql
   c. database/seeds/2026-11-17_setaei_rawscales_stenhuset.sql ONLY after duplicate/ownership checks.
   Never run a demo seed on production.
4. Configure private, writable, backed-up PDF invoice storage outside HTTP docroot, e.g. /var/lib/trustai/partner-invoices owned by the actual PHP-FPM user with restrictive permissions. Set TRUSTAI_PRIVATE_INVOICE_DIR=/var/lib/trustai/partner-invoices in the actual PHP runtime configuration. Do not expose directory through an HTTP route or commit real invoices/passwords/phones to Git.
5. Deploy reviewed source (merging PR after review and staging test). Check https://trustai.no/partner-network.html, login endpoint /api/auth/vipps/login.php?intent=b2b, Vipps configured callback URL, authenticated cookie and age/role verification. Confirm login never goes into legacy Store domain form for B2B.
6. Thomas logs in through real Vipps. He submits a SETAEI partnership request with his name/RAWScales. SETAEI admin validates actual Vipps identity, then explicitly approves the application and selects RESERVED Stenhuset / STENPROSJEKT AS in the admin interface. Alternative single-use, privately bound Vipps invitation flow exists; prevent duplicate assignment if used. Never fabricate a Vipps user or store a public invite token in Git.
7. Verify Thomas sees just his own Stenhuset referral, first-approved popup once, Trusted One badge persistently, and correct 3000 NOK as a PROPOSED fixed exception (not a payable commission). Partner should not see internal SETAEI revenue/costs/profit or other partners' leads.
8. Admin enters actual Stenhuset invoice revenue AFTER discount, accurate attributable project costs, invoice paid state and commission approval. Fixed NOK 3,000 may only be authorized if enough positive profit remains. Do not pay just because the lead is approved.
9. Thomas uploads his real PDF commission invoice, invoice number and claimed amount. SETAEI admin reviews/downloads privately, may request revision, and can approve only if the commission matches same partner, same referral, exact amount, and sale was paid by customer with costs verified. Only mark invoice and commission PAID once external bank transfer has actually cleared/been confirmed; no automatic transfer exists.
10. Execute complete mobile and security E2E tests, log live deployed commit SHA, server, migrations, real behavior, and rollback instructions in the PR and this handoff. Leave draft state until deployment gates are satisfied.

## Acceptance tests / blockers

- New Vipps B2B user sees SETAEI application not Shopify Store domain. Legacy Shopify signup stays functional for genuine stores.
- No indefinite spinner; errors on login/signup shown.
- Applicant cannot approve themselves; admin must verify actual Vipps identity before attaching reserved Stenhuset.
- A referral/company org number has one owner per program. No duplicate or cross-partner reads.
- Popup appears once after first approved introduction; badge persists after refresh.
- Positive verified profit only; discount/loss cannot create unaffordable commission. Parent cut comes from existing pool.
- Partner PDF max 8 MiB; reject invalid files; PDF stored outside docroot, never publicly downloadable. Owners/admin only. Revisions and status are visible.
- Invoice approval impossible without eligible partner/customer/amount-matching commission; no commission paid without verified customer payment, actual costs and external transfer.
- Reject unauthorized API requests, forged X-User-Id, cross-account download, invalid CSRF, invite reuse, cancelled sale payments, duplicate invoices, exceeding fixed commission margin.
- Confirm expected behavior on Android Chrome with the original user navigation path and a real Vipps account.

## Business/legal note

Earlier October proposal to Thomas stated 20% of billed revenue. New direction is 30% of positive project profit and a special fixed NOK 3,000 for Stenhuset. Have the revised terms documented and accepted in writing before settlement. Check partner invoice VAT treatment with accounting.

## Agent finish line

Update PR #36 and this AGENT_HANDOFF.md with the exact live server, backup, migration/deployment SHA and test evidence; then change the task status. A GitHub SUCCESS check alone is NOT a production launch.

Links: PR https://github.com/XS227/Trust-AI/pull/36 ; historical deployment https://trustai.no ; future B2B portal https://trustai.no/partner-network.html ; CI https://github.com/XS227/Trust-AI/actions/runs/44147876904
