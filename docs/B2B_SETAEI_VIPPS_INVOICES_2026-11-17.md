# TrustAI — SETAEI B2B login and invoice workflow

Date: 17 November 2026. Source branch: feature/b2b-partner-network-20261117. Review: PR #36.
**Source implementation only. Do not assert the live site is fixed until a production deployment and Vipps login test.**

## Diagnosis from Android screenshot

- The live URL /ambassador-signup.html?vipps=1 is the **ecommerce ambassador** registration.
- Entering Setai.no there calls /api/auth/vipps/finish-ambassador.php, which looks up a record in the **stores** table. SETAEI sells web, app and AI services, not necessarily a TrustAI store; this is not the correct B2B route.
- The "Submitting application…" state can also persist if the request never completes. The revised page now has a SETAEI Partner Network shortcut, automatically routes a setai.no form entry to the B2B page, and applies a 15-second request timeout with an actionable message.
- Use https://trustai.no/partner-network.html to access the new B2B portal **only after deployment**. Vipps login for B2B uses /api/auth/vipps/login.php?intent=b2b; it persists across age/role confirmation.
- A newly authenticated partner can submit a SETAEI application, no store domain or Shopify account required.
- An admin reviews the applicant's verified Vipps identity and company and explicitly approves the partnership. No self-approval.
- During approval, the administrator may select the already reserved RAWScales → Stenhuset introduction, which atomically assigns the approved referral and creates the one-time Trusted One popup event.

## Invoice workflow

- A Vipps-authenticated partner who has an approved referral can select a customer and upload a **genuine PDF invoice**, invoice number and amount to SETAEI. Maximum 8 MiB. The PDF lives outside the web document root in a private server folder. It is **never uploaded to GitHub** and the storage filename is server-generated.
- The partner sees Submitted, Revision needed, Approved or Paid. In case of revision the partner sees the admin note and can replace the PDF; the previous private file is removed.
- The admin can download the PDF via authenticated /api/partner-network/invoice-file.php?id=… and view the invoice in the same B2B partner dashboard.
- The admin can approve an invoice **only** if there is an eligible commission for the **same partner and referred customer** with exactly matching amount, and only after the client invoice has been paid, costs verified and sales/commission approved by SETAEI.
- The admin marks an invoice and its linked commission "Paid" atomically **only after actually making the external bank transfer**. There is no automatic Vipps payment or bank transfer.
- The original special Stenhuset amount NOK 3,000 is an admin-only **suggested** fixed commission; it is not payable until confirmed profit/costs and customer payment.

## Production installation (NOT YET DONE)

1. Recover remote access to the historically documented ProISP VPS "1431514" (TrustAI formerly deployed in /var/www/trustai). Confirm actual production DNS/server location before changing files. The remote device was offline when this PR was authored.
2. Back up current TrustAI code and full database and inspect any live data to avoid overwriting active user accounts or an existing Stenhuset registration.
3. Review and merge PR #36. Apply SQL in sequence:
   - database/migrations/2026-11-17_b2b_partner_network.sql
   - database/migrations/2026-11-17_b2b_partner_applications_invoices.sql
   - database/seeds/2026-11-17_setaei_rawscales_stenhuset.sql **ONLY after confirming the reservation is not duplicated**.
4. Create a private storage directory OUTSIDE the web root, e.g.:

      sudo install -d -m 0700 -o www-data -g www-data /var/lib/trustai/partner-invoices

   Set the secret-free production environment configuration to:

      TRUSTAI_PRIVATE_INVOICE_DIR=/var/lib/trustai/partner-invoices

   Expose this to PHP-FPM or the PHP runtime using its real environment configuration. Avoid public URL access to this folder. Include these invoice files in secure private backups. Do not commit PDFs, invoice content or account phones to the repo.
5. Deploy the updated B2B portal, Vipps callback/login/role files, existing ecommerce signup route and invoice endpoints. Verify HTTPS, session cookies, callback URI, post-login redirect and 18+ confirmation.
6. **Thomas' journey**: Navigate to the Partner Network, sign in with Vipps, complete the SETAEI application using his own name and RAWScales. In admin, verify his Vipps identity against the business relationship, approve the application, and select Stenhuset (STENPROSJEKT AS, org. 992214341) as his reserved first referral. Do not assign the reserved customer to an unverified user.
7. Confirm his first approved customer appears in his portfolio and the exact Norwegian popup ("Gratulerer med første anbefaling godkjent i TrustAI. Du er nå en Trusted One") appears once. The badge persists.
8. Thomas uploads his real commission invoice PDF with invoice number; admin reviews file and amount. Payment is gated until an eligible commission exists. If client payment/cost verification is not complete, the invoice should remain Submitted, not Approved/Paid.
9. Test cross-account isolation, CSRF, oversize/non-PDF rejection, path traversal prevention, wrong original invoice number, duplicate submissions, invoice revision and approval mismatch. Manually test both ecommerce ambassador signup and the new independent SETAEI flow on mobile.

## Security reminders

- Do not bypass Vipps by adding a person to users with fabricated sub/phone in a seed.
- Server-side admin account must validate real role from DB; do not trust X-User-Id headers.
- The authorized invoice owner and admin alone may retrieve the PDF. No storage filenames are disclosed through the dashboard API.
- The 13 October referral email originally mentioned 20% of billed revenue. Reconcile in writing before settling the new 30%-of-profit or fixed NOK 3,000 terms.
