# TrustAI B2B — Production Deployment Record

Deployment: 18 Nov 2026, 20:54–20:57 UTC (19 Nov local Asia/Baku).
Live source SHA: 18864f69e25da3e38660a36e1d563fb471bfed4e
Source: feature/b2b-partner-network-20261117 (linked, detached Git worktree).
Server: ProISP VPS 1431514; DNS trustai.no resolved to 5.249.255.116.
Nginx HTTPS document root: /var/www/trustai-release-b2b-20261118
Original code retained unmodified: /var/www/trustai
Review: GitHub PR #36 (still DRAFT / not merged).

## Completed on live VPS

1. Confirmed server identity, vhost, runtime, current code and MySQL 8.0.46.
2. Detected local uncommitted Vipps broker changes and non-Git media. Avoided resetting/overwriting them. Verified new release source includes current Bilvaskexpress and Numerologist brokers.
3. Created private backups in /root/trustai-backups/20261118T2050Z:
   - trustai-live-files.tar.gz (22 MB), database.sql.gz (mysqldump), nginx-trustai.conf.before, php-fpm-www.conf.before.
4. Checked out release source into its own detached worktree; copied local production DB config with restricted permissions and copied existing screenshots/public assets. Verified four partner/Vipps PHP files with php -l.
5. Applied both B2B migrations and the idempotent approved Stenhuset/RAWScales reservation seed. Verified exactly ONE reserved STENPROSJEKT AS (org 992214341) referral; ZERO real partner memberships and ZERO commissions.
6. Created private PDF invoice storage at /var/lib/trustai/partner-invoices (www-data owned, mode 0700), configured PHP-FPM env[TRUSTAI_PRIVATE_INVOICE_DIR] to that directory.
7. Updated Nginx trustai.no HTTPS document root to release path; nginx -t and php-fpm8.1 -t both passed; reloaded Nginx and PHP-FPM; both remain active.
8. HTTPS smoke test results:
   - / => 200
   - /partner-network.html => 200 (new UI)
   - /ambassador-signup.html => 200 (new SETAEI portal link)
   - /api/partner-network/index.php?action=dashboard => 401 when unauthenticated
   - /api/partner-network/invoice-file.php => 401 when unauthenticated
   - /api/auth/vipps/login.php?intent=b2b => 302 (initiates real Vipps OAuth authorization)
   - forged X-User-Id/X-User-Email headers => 401
   - foreign Origin => 403
   - temporary PHP probe returned ENV_STORAGE_OK for invoice storage permissions and environment; test probe removed immediately.

## Not yet verified; remaining work for next agent

- Real person's Vipps OAuth return/callback, role assignment and age confirmation. A redirect HTTP 302 is not a full completed login.
- Real Thomas Hannaas Vipps identity and SETAEI/RAWScales approval; no account is yet linked.
- Claim/assignment of the already reserved Stenhuset referral to his portfolio; first-approved Trusted One popup once, persistent badge.
- End-to-end authentic PDF invoice upload, admin review/revision, payable commission match and verified external bank payment. NO real invoice has been uploaded; NO payouts authorized or made.
- Confirm real Stenhuset customer payment, attributable SETAEI costs and margin. NOK 3,000 is only a suggested fixed special commission until verified.
- Revised referral agreement should replace the earlier 20%-of-billed-revenue email with current 30%-of-positive-net-profit / fixed Stenhuset exception in writing.

## Rollback (site code/config; avoid indiscriminate DB restore)

The old code tree remains at /var/www/trustai, and backups at /root/trustai-backups/20261118T2050Z.

From a privileged shell, after coordinating rollback:
1. Restore nginx-trustai.conf.before to /etc/nginx/sites-available/trustai.
2. Restore php-fpm-www.conf.before to /etc/php/8.1/fpm/pool.d/www.conf.
3. Verify nginx -t and php-fpm8.1 -t.
4. Reload php8.1-fpm and nginx.
5. Test the old site. Migrations are additive; DO NOT restore old DB over new user/order activity without a selective rollback review.

Do not remove /var/www/trustai/.git without migrating the linked release worktree metadata.

Follow-up issue: https://github.com/XS227/Trust-AI/issues/37
Review: https://github.com/XS227/Trust-AI/pull/36
Handoff: ../AGENT_HANDOFF.md
