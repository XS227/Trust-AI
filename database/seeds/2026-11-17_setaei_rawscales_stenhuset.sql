-- OPTIONAL and idempotent admin-reviewed bootstrap; RUN AFTER the B2B migration.
-- The ProISP database has NOT been changed from this GitHub commit.
-- Names/company number are public business details; no private Vipps phone/email is stored in git.
-- This does NOT issue an invite, fabricate a verified Vipps user or create a payable invoice.

INSERT INTO b2b_programs (name, currency, default_commission_percent)
SELECT 'SETAEI', 'NOK', 30.00
WHERE NOT EXISTS (SELECT 1 FROM b2b_programs WHERE name='SETAEI');

INSERT INTO b2b_reserved_referrals
  (program_id, partner_label, org_number, company_name, brand_name,
   referral_status, approval_status, suggested_fixed_commission)
SELECT p.id, 'RAWScales / Thomas Hannaas', '992214341', 'STENPROSJEKT AS', 'Stenhuset',
       'won', 'approved', 3000.00
FROM b2b_programs p
WHERE p.name = 'SETAEI'
  AND p.id = (SELECT MIN(p2.id) FROM b2b_programs p2 WHERE p2.name='SETAEI')
  AND NOT EXISTS (
    SELECT 1 FROM b2b_reserved_referrals r
    WHERE r.program_id=p.id AND r.org_number='992214341'
  )
  AND NOT EXISTS (
    SELECT 1 FROM b2b_referrals r
    WHERE r.program_id=p.id AND r.org_number='992214341'
  );

-- After admin sends a private invitation linked to this reservation:
-- Vipps verifies Thomas -> invitation is redeemed -> portfolio is assigned
-- -> first approved referral creates a once-only Trusted One welcome event.
-- The 3000 NOK amount is an admin-only suggested override, NOT a payable commission.
