import { useState } from "react";

// ── DESIGN TOKENS ──────────────────────────────────────────────────────────
const C = {
  bg: "#0A0C10",
  surface: "#111318",
  surfaceHover: "#16191F",
  border: "#1E2230",
  borderLight: "#252A38",
  accent: "#4F8EF7",
  accentSoft: "rgba(79,142,247,0.12)",
  accentGlow: "rgba(79,142,247,0.25)",
  green: "#2DD4A0",
  greenSoft: "rgba(45,212,160,0.12)",
  amber: "#F5A623",
  amberSoft: "rgba(245,166,35,0.12)",
  red: "#F75E5E",
  redSoft: "rgba(247,94,94,0.12)",
  purple: "#A78BFA",
  purpleSoft: "rgba(167,139,250,0.12)",
  textPrimary: "#F0F2F7",
  textSecondary: "#8892A4",
  textMuted: "#4A5568",
};

// ── STYLES ──────────────────────────────────────────────────────────────────
const globalCSS = `
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Mono:wght@300;400;500&family=Outfit:wght@300;400;500;600&display=swap');
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body, #root { height: 100%; background: ${C.bg}; color: ${C.textPrimary}; font-family: 'Outfit', sans-serif; }
  ::-webkit-scrollbar { width: 4px; height: 4px; }
  ::-webkit-scrollbar-track { background: ${C.bg}; }
  ::-webkit-scrollbar-thumb { background: ${C.border}; border-radius: 2px; }
  ::-webkit-scrollbar-thumb:hover { background: ${C.borderLight}; }
  button { cursor: pointer; border: none; outline: none; font-family: inherit; }
  input, select, textarea { font-family: inherit; outline: none; }
  .fade-in { animation: fadeIn 0.3s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
  @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
`;

// ── MOCK DATA ────────────────────────────────────────────────────────────────
const LEADS = [
  { id: "L-001", firm: "Nexus Media AS", contact: "Ola Nordmann", ambassador: "Tor Martin Olsen", status: "Tilbud sendt", value: 120000, commission: 12000, payment: "-", source: "LinkedIn", date: "2024-12-01" },
  { id: "L-002", firm: "Berg & Co", contact: "Kari Berg", ambassador: "Marthe Strøm", status: "Møte booket", value: 85000, commission: 8500, payment: "-", source: "E-post", date: "2024-12-03" },
  { id: "L-003", firm: "Solberg Invest", contact: "Erik Solberg", ambassador: "Jonas Lie", status: "Godkjent", value: 220000, commission: 22000, payment: "Faktura mottatt", source: "Facebook", date: "2024-11-28" },
  { id: "L-004", firm: "DataBridge AS", contact: "Lise Dahl", ambassador: "Tor Martin Olsen", status: "Åpen", value: 45000, commission: 4500, payment: "-", source: "Direkte", date: "2024-12-05" },
  { id: "L-005", firm: "Nordic HR Group", contact: "Petter Ås", ambassador: "Marthe Strøm", status: "Avslått", value: 60000, commission: 0, payment: "-", source: "LinkedIn", date: "2024-11-25" },
];

const AMBASSADORS = [
  { id: "A-001", name: "Tor Martin Olsen", status: "Aktiv", leads: 14, revenue: 440000, commissionPct: 15, earned: 66000, unpaid: 12000, registered: "2024-08-12", email: "tor@example.com", phone: "99887766" },
  { id: "A-002", name: "Marthe Strøm", status: "Aktiv", leads: 9, revenue: 275000, commissionPct: 10, earned: 27500, unpaid: 8000, registered: "2024-09-01", email: "marthe@example.com", phone: "91234567" },
  { id: "A-003", name: "Jonas Lie", status: "Søknad", leads: 0, revenue: 0, commissionPct: 5, earned: 0, unpaid: 0, registered: "2024-12-04", email: "jonas@example.com", phone: "45678901" },
  { id: "A-004", name: "Ingrid Viken", status: "Pauset", leads: 6, revenue: 155000, commissionPct: 10, earned: 15500, unpaid: 0, registered: "2024-07-20", email: "ingrid@example.com", phone: "93456789" },
];

const PAYOUTS = [
  { id: "U-001", ambassador: "Tor Martin Olsen", amount: 12000, invoice: "faktura_001.pdf", invoiceDate: "2024-11-30", actionDate: "-", status: "Venter" },
  { id: "U-002", ambassador: "Marthe Strøm", amount: 8000, invoice: "faktura_002.pdf", invoiceDate: "2024-11-28", actionDate: "2024-12-02", status: "Utbetalt" },
  { id: "U-003", ambassador: "Ingrid Viken", amount: 5500, invoice: "faktura_003.pdf", invoiceDate: "2024-11-15", actionDate: "2024-11-20", status: "Avvist" },
];

const TICKETS = [
  { id: 41, ambassador: "Tor Martin Olsen", subject: "Utbetaling forsinket", status: "Ubesvart", date: "2024-12-04" },
  { id: 42, ambassador: "Marthe Strøm", subject: "Spørsmål om provisjon", status: "Besvart", date: "2024-12-02" },
  { id: 43, ambassador: "Jonas Lie", subject: "Registreringsproblem", status: "Ubesvart", date: "2024-12-05" },
];

// ── UTILITY COMPONENTS ───────────────────────────────────────────────────────
const Badge = ({ label, color = C.accent, bg }) => (
  <span style={{
    display: "inline-flex", alignItems: "center", gap: 5,
    padding: "3px 10px", borderRadius: 20,
    background: bg || `${color}18`,
    color, fontSize: 11, fontWeight: 600, letterSpacing: 0.4,
    fontFamily: "'DM Mono', monospace", whiteSpace: "nowrap",
  }}>{label}</span>
);

const statusColor = (s) => {
  if (!s) return C.textMuted;
  const m = {
    "Aktiv": C.green, "Godkjent": C.green, "Utbetalt": C.green, "Besvart": C.green,
    "Søknad": C.amber, "Tilbud sendt": C.amber, "Møte booket": C.accent, "Venter": C.amber, "Faktura mottatt": C.amber,
    "Åpen": C.textSecondary, "Pauset": C.purple, "Ubesvart": C.red,
    "Avslått": C.red, "Avvist": C.red, "Avsluttet": C.textMuted,
  };
  return m[s] || C.textMuted;
};

const Stat = ({ label, value, sub, accent }) => (
  <div style={{
    background: C.surface, border: `1px solid ${C.border}`,
    borderRadius: 14, padding: "20px 22px", flex: 1, minWidth: 140,
    position: "relative", overflow: "hidden",
  }}>
    <div style={{ position: "absolute", inset: 0, background: `radial-gradient(ellipse at top left, ${accent || C.accent}10 0%, transparent 60%)`, pointerEvents: "none" }} />
    <div style={{ fontSize: 11, color: C.textMuted, fontWeight: 600, letterSpacing: 1, textTransform: "uppercase", marginBottom: 8 }}>{label}</div>
    <div style={{ fontSize: 26, fontWeight: 700, fontFamily: "'Syne', sans-serif", color: accent || C.textPrimary, lineHeight: 1 }}>{value}</div>
    {sub && <div style={{ fontSize: 11, color: C.textSecondary, marginTop: 6 }}>{sub}</div>}
  </div>
);

const Btn = ({ children, onClick, variant = "primary", size = "md", style: s }) => {
  const base = {
    display: "inline-flex", alignItems: "center", gap: 6,
    borderRadius: 8, fontWeight: 600, transition: "all 0.15s", cursor: "pointer",
    fontSize: size === "sm" ? 12 : 13,
    padding: size === "sm" ? "5px 12px" : "8px 16px",
  };
  const styles = {
    primary: { background: C.accent, color: "#fff", border: `1px solid ${C.accent}` },
    ghost: { background: "transparent", color: C.textSecondary, border: `1px solid ${C.border}` },
    danger: { background: C.redSoft, color: C.red, border: `1px solid ${C.red}40` },
    success: { background: C.greenSoft, color: C.green, border: `1px solid ${C.green}40` },
  };
  return <button onClick={onClick} style={{ ...base, ...styles[variant], ...s }}>{children}</button>;
};

const Table = ({ cols, rows, onRow }) => (
  <div style={{ overflowX: "auto" }}>
    <table style={{ width: "100%", borderCollapse: "collapse" }}>
      <thead>
        <tr>
          {cols.map(c => (
            <th key={c.key} style={{
              padding: "10px 14px", textAlign: "left",
              fontSize: 10, fontWeight: 700, letterSpacing: 1.2, textTransform: "uppercase",
              color: C.textMuted, borderBottom: `1px solid ${C.border}`,
              whiteSpace: "nowrap",
            }}>{c.label}</th>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((row, i) => (
          <tr key={i}
            onClick={() => onRow && onRow(row)}
            style={{
              borderBottom: `1px solid ${C.border}`,
              cursor: onRow ? "pointer" : "default",
              transition: "background 0.1s",
            }}
            onMouseEnter={e => e.currentTarget.style.background = C.surfaceHover}
            onMouseLeave={e => e.currentTarget.style.background = "transparent"}
          >
            {cols.map(c => (
              <td key={c.key} style={{ padding: "12px 14px", fontSize: 13, color: C.textPrimary, whiteSpace: "nowrap" }}>
                {c.render ? c.render(row[c.key], row) : row[c.key]}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  </div>
);

const Modal = ({ title, onClose, children, width = 720 }) => (
  <div style={{
    position: "fixed", inset: 0, background: "rgba(0,0,0,0.75)", backdropFilter: "blur(4px)",
    display: "flex", alignItems: "center", justifyContent: "center", zIndex: 1000, padding: 20,
  }} onClick={e => e.target === e.currentTarget && onClose()}>
    <div className="fade-in" style={{
      background: C.surface, border: `1px solid ${C.borderLight}`,
      borderRadius: 18, width: "100%", maxWidth: width, maxHeight: "90vh",
      overflow: "auto", boxShadow: "0 40px 80px rgba(0,0,0,0.6)",
    }}>
      <div style={{
        display: "flex", alignItems: "center", justifyContent: "space-between",
        padding: "20px 24px", borderBottom: `1px solid ${C.border}`, position: "sticky", top: 0,
        background: C.surface, zIndex: 1,
      }}>
        <div style={{ fontSize: 16, fontWeight: 700, fontFamily: "'Syne', sans-serif" }}>{title}</div>
        <button onClick={onClose} style={{ background: C.border, border: "none", color: C.textSecondary, borderRadius: 8, width: 32, height: 32, fontSize: 16, cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center" }}>✕</button>
      </div>
      <div style={{ padding: 24 }}>{children}</div>
    </div>
  </div>
);

const Section = ({ title, children, action }) => (
  <div style={{ marginBottom: 28 }}>
    <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 14 }}>
      <div style={{ fontSize: 13, fontWeight: 700, color: C.textSecondary, letterSpacing: 0.8, textTransform: "uppercase" }}>{title}</div>
      {action}
    </div>
    {children}
  </div>
);

// ── SIDEBAR ──────────────────────────────────────────────────────────────────
const NAV_SUPER = [
  { key: "dashboard", icon: "⬡", label: "Dashboard" },
  { key: "leads", icon: "◈", label: "Leads" },
  { key: "ambassadors", icon: "◉", label: "Ambassadører" },
  { key: "revenue", icon: "◫", label: "Inntekter" },
  { key: "payouts", icon: "◳", label: "Utbetalinger" },
  { key: "content", icon: "◪", label: "Innhold" },
  { key: "tickets", icon: "◷", label: "Support" },
  { key: "settings", icon: "◎", label: "Innstillinger" },
];
const NAV_BUSINESS = [
  { key: "bus_dashboard", icon: "⬡", label: "Dashboard" },
  { key: "bus_leads", icon: "◈", label: "Leads" },
  { key: "bus_ambassadors", icon: "◉", label: "Ambassadører" },
  { key: "bus_revenue", icon: "◫", label: "Økonomi" },
  { key: "bus_content", icon: "◪", label: "Innhold" },
  { key: "bus_settings", icon: "◎", label: "Innstillinger" },
];
const NAV_AMBASSADOR = [
  { key: "amb_dashboard", icon: "⬡", label: "Min side" },
  { key: "amb_leads", icon: "◈", label: "Mine leads" },
  { key: "amb_share", icon: "◪", label: "Del & rekrutter" },
  { key: "amb_earnings", icon: "◫", label: "Inntekter" },
  { key: "amb_payout", icon: "◳", label: "Utbetaling" },
  { key: "amb_support", icon: "◷", label: "Support" },
];

const Sidebar = ({ nav, active, onNav, role }) => {
  const roleColors = { superadmin: C.accent, business: C.green, ambassador: C.purple };
  const roleLabels = { superadmin: "Super Admin", business: "Business", ambassador: "Ambassadør" };
  const c = roleColors[role] || C.accent;

  return (
    <div style={{
      width: 220, background: C.surface, borderRight: `1px solid ${C.border}`,
      display: "flex", flexDirection: "column", height: "100vh", position: "fixed", left: 0, top: 0,
      zIndex: 100,
    }}>
      {/* Logo */}
      <div style={{ padding: "24px 20px 20px", borderBottom: `1px solid ${C.border}` }}>
        <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 16 }}>
          <div style={{
            width: 36, height: 36, borderRadius: 10,
            background: `linear-gradient(135deg, ${c}, ${c}80)`,
            display: "flex", alignItems: "center", justifyContent: "center",
            fontSize: 16, fontWeight: 800, color: "#fff", fontFamily: "'Syne', sans-serif",
            boxShadow: `0 0 20px ${c}40`,
          }}>T</div>
          <div>
            <div style={{ fontSize: 15, fontWeight: 800, fontFamily: "'Syne', sans-serif", color: C.textPrimary, letterSpacing: -0.3 }}>TrustAI</div>
            <div style={{ fontSize: 10, color: c, fontWeight: 600, letterSpacing: 0.5 }}>{roleLabels[role]}</div>
          </div>
        </div>
      </div>

      {/* Nav */}
      <nav style={{ flex: 1, padding: "12px 10px", overflowY: "auto" }}>
        {nav.map(item => {
          const isActive = active === item.key;
          return (
            <button key={item.key} onClick={() => onNav(item.key)}
              style={{
                display: "flex", alignItems: "center", gap: 10,
                width: "100%", padding: "9px 12px", borderRadius: 10,
                background: isActive ? `${c}18` : "transparent",
                color: isActive ? c : C.textSecondary,
                border: isActive ? `1px solid ${c}30` : "1px solid transparent",
                fontSize: 13, fontWeight: isActive ? 600 : 400,
                marginBottom: 2, transition: "all 0.15s", textAlign: "left",
              }}>
              <span style={{ fontSize: 14, opacity: isActive ? 1 : 0.6 }}>{item.icon}</span>
              {item.label}
              {item.badge && (
                <span style={{ marginLeft: "auto", background: C.red, color: "#fff", borderRadius: 10, padding: "1px 7px", fontSize: 10, fontWeight: 700 }}>{item.badge}</span>
              )}
            </button>
          );
        })}
      </nav>

      {/* User */}
      <div style={{ padding: "14px 16px", borderTop: `1px solid ${C.border}` }}>
        <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
          <div style={{
            width: 32, height: 32, borderRadius: 8, background: `${c}30`,
            display: "flex", alignItems: "center", justifyContent: "center",
            fontSize: 12, fontWeight: 700, color: c,
          }}>TM</div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontSize: 12, fontWeight: 600, color: C.textPrimary, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>Tor Martin Olsen</div>
            <div style={{ fontSize: 10, color: C.textMuted }}>Logg ut</div>
          </div>
        </div>
      </div>
    </div>
  );
};

// ── TOP BAR ──────────────────────────────────────────────────────────────────
const TopBar = ({ title, role }) => (
  <div style={{
    position: "sticky", top: 0, zIndex: 50,
    background: `${C.bg}E0`, backdropFilter: "blur(12px)",
    borderBottom: `1px solid ${C.border}`,
    display: "flex", alignItems: "center", gap: 16,
    padding: "12px 28px",
  }}>
    <div style={{ flex: 1, fontSize: 16, fontWeight: 700, fontFamily: "'Syne', sans-serif", color: C.textPrimary }}>{title}</div>
    {/* Search */}
    <div style={{
      display: "flex", alignItems: "center", gap: 8,
      background: C.surface, border: `1px solid ${C.border}`,
      borderRadius: 8, padding: "7px 14px", width: 240,
    }}>
      <span style={{ fontSize: 13, color: C.textMuted }}>⌕</span>
      <input placeholder="Søk leads, ambassadører..." style={{
        background: "none", border: "none", color: C.textSecondary, fontSize: 13, width: "100%",
      }} />
    </div>
    {/* Notifs */}
    {[
      { label: "3 nye leads", color: C.green },
      { label: "2 søknader", color: C.amber },
      { label: "1 faktura", color: C.accent },
    ].map((n, i) => (
      <div key={i} style={{
        padding: "5px 12px", borderRadius: 20, fontSize: 11, fontWeight: 600,
        background: `${n.color}18`, color: n.color, border: `1px solid ${n.color}30`,
        cursor: "pointer", whiteSpace: "nowrap",
      }}>{n.label}</div>
    ))}
  </div>
);

// ── PAGES ─────────────────────────────────────────────────────────────────────

// SUPER ADMIN DASHBOARD
const SuperDashboard = () => {
  const pipeline = [
    { label: "Åpne leads", count: 8, value: "320 000", color: C.textSecondary },
    { label: "Møte booket", count: 4, value: "195 000", color: C.accent },
    { label: "Tilbud sendt", count: 3, value: "280 000", color: C.amber },
    { label: "Godkjent", count: 6, value: "740 000", color: C.green },
    { label: "Avslått", count: 2, value: "145 000", color: C.red },
  ];
  return (
    <div className="fade-in">
      <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 28 }}>
        <Stat label="Totale leads" value="23" sub="↑ 4 siste uke" accent={C.accent} />
        <Stat label="Møter booket" value="11" sub="Denne måneden" accent={C.purple} />
        <Stat label="Tilbud sendt" value="8" sub="Totalt åpne" accent={C.amber} />
        <Stat label="Godkjent omsetning" value="740 000" sub="kr inkl. mva" accent={C.green} />
        <Stat label="Provisjon utestående" value="28 500" sub="Ubetalte krav" accent={C.red} />
        <Stat label="Aktive ambassadører" value="2" sub="av 4 totalt" accent={C.purple} />
      </div>

      <Section title="Salgspipeline">
        <div style={{ display: "flex", gap: 12, overflowX: "auto", paddingBottom: 8 }}>
          {pipeline.map((p, i) => (
            <div key={i} style={{
              flex: "0 0 180px", background: C.surface, border: `1px solid ${C.border}`,
              borderTop: `3px solid ${p.color}`, borderRadius: 12, padding: "16px 18px",
              cursor: "pointer", transition: "transform 0.1s",
            }}
              onMouseEnter={e => e.currentTarget.style.transform = "translateY(-2px)"}
              onMouseLeave={e => e.currentTarget.style.transform = "none"}
            >
              <div style={{ fontSize: 11, color: C.textMuted, fontWeight: 600, letterSpacing: 0.8, textTransform: "uppercase", marginBottom: 10 }}>{p.label}</div>
              <div style={{ fontSize: 28, fontWeight: 800, fontFamily: "'Syne', sans-serif", color: p.color, lineHeight: 1 }}>{p.count}</div>
              <div style={{ fontSize: 12, color: C.textSecondary, marginTop: 6, fontFamily: "'DM Mono', monospace" }}>{p.value} kr</div>
            </div>
          ))}
        </div>
      </Section>

      <Section title="Siste aktivitet">
        <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12, overflow: "hidden" }}>
          {LEADS.slice(0, 4).map((l, i) => (
            <div key={i} style={{
              display: "flex", alignItems: "center", gap: 14, padding: "13px 18px",
              borderBottom: i < 3 ? `1px solid ${C.border}` : "none",
            }}>
              <div style={{ width: 8, height: 8, borderRadius: "50%", background: statusColor(l.status), flexShrink: 0 }} />
              <div style={{ flex: 1 }}>
                <span style={{ fontSize: 13, fontWeight: 600 }}>{l.firm}</span>
                <span style={{ fontSize: 12, color: C.textMuted, marginLeft: 8 }}>via {l.ambassador}</span>
              </div>
              <Badge label={l.status} color={statusColor(l.status)} />
              <div style={{ fontSize: 12, color: C.textSecondary, fontFamily: "'DM Mono', monospace" }}>{l.value.toLocaleString()} kr</div>
            </div>
          ))}
        </div>
      </Section>
    </div>
  );
};

// LEADS PAGE
const LeadsPage = () => {
  const [selected, setSelected] = useState(null);
  const [filter, setFilter] = useState("Alle");

  const statuses = ["Alle", "Åpen", "Møte booket", "Tilbud sendt", "Godkjent", "Avslått"];
  const filtered = filter === "Alle" ? LEADS : LEADS.filter(l => l.status === filter);

  return (
    <div className="fade-in">
      {/* Filter pills */}
      <div style={{ display: "flex", gap: 8, marginBottom: 20, flexWrap: "wrap" }}>
        {statuses.map(s => (
          <button key={s} onClick={() => setFilter(s)} style={{
            padding: "6px 16px", borderRadius: 20, fontSize: 12, fontWeight: 600, cursor: "pointer",
            background: filter === s ? C.accent : C.surface,
            color: filter === s ? "#fff" : C.textSecondary,
            border: `1px solid ${filter === s ? C.accent : C.border}`,
            transition: "all 0.15s",
          }}>{s}</button>
        ))}
        <div style={{ marginLeft: "auto" }}>
          <Btn size="sm">+ Ny lead</Btn>
        </div>
      </div>

      <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
        <Table
          onRow={setSelected}
          cols={[
            { key: "firm", label: "Firma", render: v => <span style={{ fontWeight: 600 }}>{v}</span> },
            { key: "ambassador", label: "Ambassadør" },
            { key: "status", label: "Status", render: v => <Badge label={v} color={statusColor(v)} /> },
            { key: "value", label: "Verdi", render: v => <span style={{ fontFamily: "'DM Mono', monospace", color: C.textSecondary }}>{v.toLocaleString()} kr</span> },
            { key: "commission", label: "Provisjon", render: v => <span style={{ fontFamily: "'DM Mono', monospace", color: C.green }}>{v.toLocaleString()} kr</span> },
            { key: "source", label: "Kilde" },
            { key: "id", label: "Handling", render: (v, row) => <Btn size="sm" variant="ghost" onClick={e => { e.stopPropagation(); setSelected(row); }}>Åpne →</Btn> },
          ]}
          rows={filtered}
        />
      </div>

      {selected && (
        <Modal title={`Lead: ${selected.firm}`} onClose={() => setSelected(null)} width={800}>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 20 }}>
            {/* Left */}
            <div>
              <Section title="Kontaktinformasjon">
                {[["Firmanavn", selected.firm], ["Kontaktperson", selected.contact], ["Epost", "ola@nexus.no"], ["Telefon", "98765432"]].map(([l, v]) => (
                  <div key={l} style={{ marginBottom: 12 }}>
                    <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 600, letterSpacing: 0.8, textTransform: "uppercase" }}>{l}</label>
                    <input defaultValue={v} style={{
                      display: "block", width: "100%", padding: "9px 12px", marginTop: 4,
                      background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
                      color: C.textPrimary, fontSize: 13,
                    }} />
                  </div>
                ))}
                <div style={{ marginBottom: 12 }}>
                  <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 600, letterSpacing: 0.8, textTransform: "uppercase" }}>Oppfølgingsdato</label>
                  <input type="date" style={{
                    display: "block", width: "100%", padding: "9px 12px", marginTop: 4,
                    background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
                    color: C.textPrimary, fontSize: 13,
                  }} />
                </div>
                <div>
                  <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 600, letterSpacing: 0.8, textTransform: "uppercase" }}>Notat</label>
                  <textarea rows={3} placeholder="Skriv notat her..." style={{
                    display: "block", width: "100%", padding: "9px 12px", marginTop: 4,
                    background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
                    color: C.textPrimary, fontSize: 13, resize: "vertical",
                  }} />
                  <label style={{ display: "flex", alignItems: "center", gap: 6, marginTop: 6, fontSize: 12, color: C.textSecondary, cursor: "pointer" }}>
                    <input type="checkbox" /> Synlig for ambassadør
                  </label>
                </div>
              </Section>
            </div>

            {/* Right */}
            <div>
              <Section title="Pipeline & økonomi">
                {[["Status", selected.status], ["Ambassadør", selected.ambassador], ["Lead-ID", selected.id], ["Kilde", selected.source]].map(([l, v], i) => (
                  <div key={l} style={{ marginBottom: 12 }}>
                    <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 600, letterSpacing: 0.8, textTransform: "uppercase" }}>{l}</label>
                    {i === 0 ? (
                      <select defaultValue={v} style={{
                        display: "block", width: "100%", padding: "9px 12px", marginTop: 4,
                        background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
                        color: C.textPrimary, fontSize: 13,
                      }}>
                        {["Åpen", "Møte booket", "Tilbud sendt", "Godkjent", "Avslått"].map(s => <option key={s}>{s}</option>)}
                      </select>
                    ) : (
                      <div style={{ padding: "9px 12px", marginTop: 4, background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8, fontSize: 13, color: i > 1 ? C.textMuted : C.textPrimary }}>{v}</div>
                    )}
                  </div>
                ))}
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10, marginTop: 8 }}>
                  {[["Tilbudssum", selected.value + " kr"], ["Provisjon %", "10%"], ["Provisjonsbeløp", selected.commission + " kr"]].map(([l, v]) => (
                    <div key={l}>
                      <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 600, letterSpacing: 0.8, textTransform: "uppercase" }}>{l}</label>
                      <div style={{ padding: "9px 12px", marginTop: 4, background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8, fontSize: 13, color: C.green, fontFamily: "'DM Mono', monospace" }}>{v}</div>
                    </div>
                  ))}
                </div>
              </Section>
            </div>
          </div>

          {/* History */}
          <div style={{ borderTop: `1px solid ${C.border}`, paddingTop: 20, marginTop: 8 }}>
            <div style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 1, textTransform: "uppercase", marginBottom: 12 }}>Endringslogg</div>
            {[
              { date: "2024-12-03 14:22", text: "Status endret til «Tilbud sendt»", by: "TM Olsen" },
              { date: "2024-12-01 09:10", text: "Lead opprettet via LinkedIn", by: "System" },
            ].map((h, i) => (
              <div key={i} style={{ display: "flex", gap: 12, marginBottom: 10, fontSize: 12 }}>
                <div style={{ color: C.textMuted, fontFamily: "'DM Mono', monospace", whiteSpace: "nowrap" }}>{h.date}</div>
                <div style={{ color: C.textSecondary }}>{h.text}</div>
                <div style={{ color: C.textMuted, marginLeft: "auto" }}>{h.by}</div>
              </div>
            ))}
          </div>

          <div style={{ display: "flex", gap: 10, marginTop: 20 }}>
            <Btn>Lagre endringer</Btn>
            <Btn variant="ghost">Avbryt</Btn>
          </div>
        </Modal>
      )}
    </div>
  );
};

// AMBASSADORS PAGE
const AmbassadorsPage = () => {
  const [selected, setSelected] = useState(null);
  const [filter, setFilter] = useState("Alle");

  const statuses = ["Alle", "Søknad", "Aktiv", "Pauset", "Avsluttet"];
  const filtered = filter === "Alle" ? AMBASSADORS : AMBASSADORS.filter(a => a.status === filter);

  return (
    <div className="fade-in">
      <div style={{ display: "flex", gap: 8, marginBottom: 20, flexWrap: "wrap" }}>
        {statuses.map(s => (
          <button key={s} onClick={() => setFilter(s)} style={{
            padding: "6px 16px", borderRadius: 20, fontSize: 12, fontWeight: 600, cursor: "pointer",
            background: filter === s ? C.accent : C.surface,
            color: filter === s ? "#fff" : C.textSecondary,
            border: `1px solid ${filter === s ? C.accent : C.border}`,
          }}>{s}
            {s === "Søknad" && <span style={{ marginLeft: 6, background: C.amber, color: "#000", borderRadius: 10, padding: "0 5px", fontSize: 10 }}>1</span>}
          </button>
        ))}
      </div>

      <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
        <Table
          onRow={setSelected}
          cols={[
            { key: "name", label: "Navn", render: v => <span style={{ fontWeight: 600 }}>{v}</span> },
            { key: "status", label: "Status", render: v => <Badge label={v} color={statusColor(v)} /> },
            { key: "leads", label: "Leads" },
            { key: "revenue", label: "Omsetning", render: v => <span style={{ fontFamily: "'DM Mono', monospace" }}>{v.toLocaleString()} kr</span> },
            { key: "commissionPct", label: "Provisjon", render: v => <span style={{ color: C.green }}>{v}%</span> },
            { key: "earned", label: "Opptjent", render: v => <span style={{ fontFamily: "'DM Mono', monospace", color: C.green }}>{v.toLocaleString()} kr</span> },
            { key: "unpaid", label: "Uutbetalt", render: v => <span style={{ fontFamily: "'DM Mono', monospace", color: v > 0 ? C.amber : C.textMuted }}>{v.toLocaleString()} kr</span> },
            { key: "id", label: "", render: (v, row) => <Btn size="sm" variant="ghost" onClick={e => { e.stopPropagation(); setSelected(row); }}>Detaljer →</Btn> },
          ]}
          rows={filtered}
        />
      </div>

      {selected && (
        <Modal title={`Ambassadør: ${selected.name}`} onClose={() => setSelected(null)} width={820}>
          {/* Header info */}
          <div style={{ display: "flex", gap: 12, marginBottom: 20 }}>
            <div style={{ background: C.bg, border: `1px solid ${C.border}`, borderRadius: 10, padding: "10px 16px", flex: 1 }}>
              <div style={{ fontSize: 10, color: C.textMuted, fontWeight: 700, letterSpacing: 1, textTransform: "uppercase" }}>Ambassadør-ID</div>
              <div style={{ fontSize: 13, color: C.textSecondary, fontFamily: "'DM Mono', monospace", marginTop: 4 }}>{selected.id}</div>
            </div>
            <div style={{ background: C.bg, border: `1px solid ${C.border}`, borderRadius: 10, padding: "10px 16px", flex: 1 }}>
              <div style={{ fontSize: 10, color: C.textMuted, fontWeight: 700, letterSpacing: 1, textTransform: "uppercase" }}>Registrert</div>
              <div style={{ fontSize: 13, color: C.textSecondary, fontFamily: "'DM Mono', monospace", marginTop: 4 }}>{selected.registered}</div>
            </div>
            <div style={{ background: C.bg, border: `1px solid ${C.border}`, borderRadius: 10, padding: "10px 16px", flex: 1 }}>
              <div style={{ fontSize: 10, color: C.textMuted, fontWeight: 700, letterSpacing: 1, textTransform: "uppercase" }}>Status</div>
              <select defaultValue={selected.status} style={{
                display: "block", width: "100%", marginTop: 4, background: "transparent",
                border: "none", color: statusColor(selected.status), fontSize: 13, fontWeight: 600, cursor: "pointer",
              }}>
                {["Søknad", "Aktiv", "Pauset", "Avsluttet"].map(s => <option key={s}>{s}</option>)}
              </select>
            </div>
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 20 }}>
            {/* Contact info */}
            <div>
              <Section title="Kontaktinformasjon">
                {[["Fullt navn", selected.name], ["E-post", selected.email], ["Telefon", selected.phone], ["Org.nr", "988 765 432"], ["Adresse", "Storgata 12"], ["Postnr / Sted", "0152 Oslo"]].map(([l, v]) => (
                  <div key={l} style={{ marginBottom: 10 }}>
                    <label style={{ fontSize: 10, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>{l}</label>
                    <input defaultValue={v} style={{
                      display: "block", width: "100%", padding: "8px 10px", marginTop: 3,
                      background: C.bg, border: `1px solid ${C.border}`, borderRadius: 7,
                      color: C.textPrimary, fontSize: 13,
                    }} />
                  </div>
                ))}
                <div style={{ marginTop: 6, display: "flex", alignItems: "center", gap: 10 }}>
                  <label style={{ fontSize: 12, color: C.textSecondary }}>Rekrutteringsrettigheter</label>
                  <div style={{ display: "flex", gap: 6 }}>
                    {["Ja", "Nei"].map(v => (
                      <button key={v} style={{
                        padding: "4px 12px", borderRadius: 6, fontSize: 12, fontWeight: 600, cursor: "pointer",
                        background: v === "Nei" ? C.accentSoft : C.surface,
                        color: v === "Nei" ? C.accent : C.textMuted,
                        border: `1px solid ${v === "Nei" ? C.accent : C.border}`,
                      }}>{v}</button>
                    ))}
                  </div>
                </div>
              </Section>
            </div>

            {/* Performance */}
            <div>
              <Section title="Performance">
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 8 }}>
                  {[
                    ["Leads generert", selected.leads],
                    ["Møter booket", 4],
                    ["Tilbud sendt", 3],
                    ["Godkjente tilbud", 2],
                    ["Total omsetning", selected.revenue.toLocaleString() + " kr"],
                    ["Opptjent totalt", selected.earned.toLocaleString() + " kr"],
                    ["Til utbetaling", selected.unpaid.toLocaleString() + " kr"],
                  ].map(([l, v]) => (
                    <div key={l} style={{ background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8, padding: "10px 12px" }}>
                      <div style={{ fontSize: 10, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>{l}</div>
                      <div style={{ fontSize: 16, fontWeight: 700, color: C.textPrimary, marginTop: 4, fontFamily: "'DM Mono', monospace" }}>{v}</div>
                    </div>
                  ))}
                </div>
              </Section>

              <Section title="Provisjonsmatrise">
                <div style={{ background: C.bg, border: `1px solid ${C.border}`, borderRadius: 10, overflow: "hidden" }}>
                  {[["0 – 500 000 kr", "5%"], ["500 001 – 1 000 000 kr", "10%"], ["1 000 001+ kr", "15%"]].map(([range, pct], i) => (
                    <div key={i} style={{
                      display: "flex", justifyContent: "space-between", alignItems: "center",
                      padding: "10px 14px", borderBottom: i < 2 ? `1px solid ${C.border}` : "none",
                    }}>
                      <span style={{ fontSize: 12, color: C.textSecondary }}>{range}</span>
                      <span style={{ fontSize: 14, fontWeight: 700, color: C.green }}>{pct}</span>
                    </div>
                  ))}
                </div>
                <Btn size="sm" variant="ghost" style={{ marginTop: 8 }}>+ Rediger matrise</Btn>
              </Section>
            </div>
          </div>

          <div style={{ display: "flex", gap: 10, marginTop: 16 }}>
            <Btn>Lagre</Btn>
            {selected.status === "Søknad" && <Btn variant="success">Godkjenn søknad</Btn>}
            <Btn variant="danger">Pause / Avslutt</Btn>
            <Btn variant="ghost">Legg til notat</Btn>
          </div>
        </Modal>
      )}
    </div>
  );
};

// REVENUE PAGE
const RevenuePage = () => (
  <div className="fade-in">
    <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 24 }}>
      <Stat label="Total omsetning" value="715 000 kr" accent={C.green} />
      <Stat label="Fakturert" value="440 000 kr" accent={C.accent} />
      <Stat label="Ubetalt provisjon" value="28 500 kr" accent={C.amber} />
    </div>
    <div style={{ display: "flex", gap: 10, marginBottom: 16, flexWrap: "wrap" }}>
      {["Alle ambassadører", "Siste 30 dager", "Ikke fakturert"].map((f, i) => (
        <button key={i} style={{
          padding: "6px 14px", borderRadius: 20, fontSize: 12, fontWeight: 600, cursor: "pointer",
          background: i === 0 ? C.accent : C.surface, color: i === 0 ? "#fff" : C.textSecondary,
          border: `1px solid ${i === 0 ? C.accent : C.border}`,
        }}>{f}</button>
      ))}
    </div>
    <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
      <Table
        cols={[
          { key: "firm", label: "Kunde", render: v => <span style={{ fontWeight: 600 }}>{v}</span> },
          { key: "ambassador", label: "Ambassadør" },
          { key: "value", label: "Omsetning", render: v => <span style={{ fontFamily: "'DM Mono', monospace" }}>{v.toLocaleString()} kr</span> },
          { key: "commission", label: "Provisjon", render: v => <span style={{ color: C.green, fontFamily: "'DM Mono', monospace" }}>{v.toLocaleString()} kr</span> },
          { key: "payment", label: "Status", render: v => <Badge label={v === "-" ? "Ikke fakturert" : v} color={v === "-" ? C.textMuted : C.green} /> },
          { key: "date", label: "Dato" },
        ]}
        rows={LEADS.filter(l => l.status === "Godkjent" || l.value > 50000)}
      />
    </div>
  </div>
);

// PAYOUTS PAGE
const PayoutsPage = () => {
  const [payouts, setPayouts] = useState(PAYOUTS);
  return (
    <div className="fade-in">
      <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 24 }}>
        <Stat label="Venter godkjenning" value="12 000 kr" accent={C.amber} />
        <Stat label="Utbetalt totalt" value="8 000 kr" accent={C.green} />
        <Stat label="Avviste fakturaer" value="1" accent={C.red} />
      </div>
      <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
        <Table
          cols={[
            { key: "ambassador", label: "Ambassadør", render: v => <span style={{ fontWeight: 600 }}>{v}</span> },
            { key: "amount", label: "Beløp", render: v => <span style={{ fontFamily: "'DM Mono', monospace", color: C.textPrimary }}>{v.toLocaleString()} kr</span> },
            { key: "invoice", label: "Faktura", render: v => <span style={{ color: C.accent, cursor: "pointer", textDecoration: "underline", fontSize: 12 }}>↓ {v}</span> },
            { key: "invoiceDate", label: "Fakturadato" },
            { key: "status", label: "Status", render: v => <Badge label={v} color={statusColor(v)} /> },
            { key: "actionDate", label: "Behandlet" },
            {
              key: "id", label: "Handling", render: (v, row) => row.status === "Venter" ? (
                <div style={{ display: "flex", gap: 6 }}>
                  <Btn size="sm" variant="success" onClick={() => setPayouts(p => p.map(x => x.id === row.id ? { ...x, status: "Utbetalt", actionDate: "2024-12-06" } : x))}>Utbetalt</Btn>
                  <Btn size="sm" variant="danger" onClick={() => setPayouts(p => p.map(x => x.id === row.id ? { ...x, status: "Avvist", actionDate: "2024-12-06" } : x))}>Avvis</Btn>
                </div>
              ) : <span style={{ fontSize: 12, color: C.textMuted }}>—</span>
            },
          ]}
          rows={payouts}
        />
      </div>
    </div>
  );
};

// CONTENT PAGE
const ContentPage = () => {
  const [showNew, setShowNew] = useState(false);
  const content = [
    { source: "LinkedIn", title: "Sjekk ut TrustAI", preview: "Er du på jakt etter effektive løsninger..." },
    { source: "E-post", title: "Anbefaler TrustAI", preview: "Hei! Jeg ønsker å anbefale deg..." },
    { source: "Facebook", title: "TrustAI hjelper deg", preview: "Spennende nyhet for deg som driver..." },
  ];
  return (
    <div className="fade-in">
      <div style={{ display: "flex", justifyContent: "flex-end", marginBottom: 16 }}>
        <Btn onClick={() => setShowNew(true)}>+ Lag ny tekst</Btn>
      </div>
      <div style={{ display: "grid", gap: 12 }}>
        {content.map((c, i) => (
          <div key={i} style={{
            background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12,
            padding: "16px 20px", display: "flex", alignItems: "center", gap: 16,
          }}>
            <div style={{
              width: 36, height: 36, borderRadius: 8, background: C.accentSoft,
              display: "flex", alignItems: "center", justifyContent: "center", fontSize: 18,
            }}>
              {c.source === "LinkedIn" ? "in" : c.source === "E-post" ? "✉" : "f"}
            </div>
            <div style={{ flex: 1 }}>
              <div style={{ fontSize: 13, fontWeight: 600 }}>{c.title}</div>
              <div style={{ fontSize: 12, color: C.textMuted, marginTop: 2 }}>{c.preview}</div>
            </div>
            <Badge label={c.source} color={C.accent} />
            <Btn size="sm" variant="ghost">Rediger</Btn>
          </div>
        ))}
      </div>
      {showNew && (
        <Modal title="Lag ny delingstekst" onClose={() => setShowNew(false)} width={560}>
          <div style={{ marginBottom: 14 }}>
            <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>Kanal</label>
            <select style={{
              display: "block", width: "100%", padding: "9px 12px", marginTop: 6,
              background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
              color: C.textPrimary, fontSize: 13,
            }}>
              {["LinkedIn post", "LinkedIn DM", "E-post", "Facebook post", "Messenger DM", "Twitter post", "Annet"].map(o => <option key={o}>{o}</option>)}
            </select>
          </div>
          <div>
            <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>Tekst (inkl. din delingslenke)</label>
            <textarea rows={6} placeholder="Skriv delingstekst... Lenken din settes inn automatisk." style={{
              display: "block", width: "100%", padding: "9px 12px", marginTop: 6,
              background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
              color: C.textPrimary, fontSize: 13, resize: "vertical",
            }} />
          </div>
          <div style={{ display: "flex", gap: 10, marginTop: 16 }}>
            <Btn onClick={() => setShowNew(false)}>Lagre tekst</Btn>
            <Btn variant="ghost" onClick={() => setShowNew(false)}>Avbryt</Btn>
          </div>
        </Modal>
      )}
    </div>
  );
};

// TICKETS PAGE
const TicketsPage = () => {
  const [selected, setSelected] = useState(null);
  const [filter, setFilter] = useState("Alle");

  return (
    <div className="fade-in">
      <div style={{ display: "flex", gap: 8, marginBottom: 20 }}>
        {["Alle", "Ubesvart", "Besvart", "Avsluttet"].map(f => (
          <button key={f} onClick={() => setFilter(f)} style={{
            padding: "6px 14px", borderRadius: 20, fontSize: 12, fontWeight: 600, cursor: "pointer",
            background: filter === f ? C.accent : C.surface, color: filter === f ? "#fff" : C.textSecondary,
            border: `1px solid ${filter === f ? C.accent : C.border}`,
          }}>{f}</button>
        ))}
      </div>

      {/* FAQ section */}
      <div style={{ marginBottom: 20 }}>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 12 }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: C.textMuted, letterSpacing: 1, textTransform: "uppercase" }}>FAQ</div>
          <Btn size="sm">+ Legg til FAQ</Btn>
        </div>
        <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12, overflow: "hidden" }}>
          {["Hvordan beregnes provisjon?", "Når utbetales provisjon?", "Kan jeg rekruttere andre ambassadører?"].map((q, i, arr) => (
            <div key={i} style={{
              padding: "12px 18px", display: "flex", alignItems: "center", justifyContent: "space-between",
              borderBottom: i < arr.length - 1 ? `1px solid ${C.border}` : "none",
            }}>
              <span style={{ fontSize: 13 }}>{q}</span>
              <Btn size="sm" variant="ghost">Rediger</Btn>
            </div>
          ))}
        </div>
      </div>

      <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
        <Table
          onRow={setSelected}
          cols={[
            { key: "id", label: "ID", render: v => <span style={{ fontFamily: "'DM Mono', monospace", color: C.textMuted }}>#{v}</span> },
            { key: "ambassador", label: "Ambassadør", render: v => <span style={{ fontWeight: 600 }}>{v}</span> },
            { key: "subject", label: "Emne" },
            { key: "status", label: "Status", render: v => <Badge label={v} color={statusColor(v)} /> },
            { key: "date", label: "Dato" },
            { key: "id", label: "", render: (v, row) => <Btn size="sm" variant="ghost" onClick={e => { e.stopPropagation(); setSelected(row); }}>Åpne →</Btn> },
          ]}
          rows={TICKETS}
        />
      </div>

      {selected && (
        <Modal title={`Ticket #${selected.id}: ${selected.subject}`} onClose={() => setSelected(null)} width={620}>
          <div style={{ display: "flex", gap: 10, marginBottom: 20, flexWrap: "wrap" }}>
            {[["Ambassadør", selected.ambassador], ["Status", selected.status], ["Dato", selected.date]].map(([l, v]) => (
              <div key={l} style={{ background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8, padding: "8px 14px", flex: 1 }}>
                <div style={{ fontSize: 10, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>{l}</div>
                <div style={{ fontSize: 13, marginTop: 4, color: statusColor(v) || C.textPrimary }}>{v}</div>
              </div>
            ))}
          </div>

          {/* Message thread */}
          <div style={{ background: C.bg, border: `1px solid ${C.border}`, borderRadius: 10, padding: 16, marginBottom: 16, minHeight: 120 }}>
            <div style={{ display: "flex", gap: 10, marginBottom: 12 }}>
              <div style={{ width: 28, height: 28, borderRadius: 7, background: C.purpleSoft, display: "flex", alignItems: "center", justifyContent: "center", fontSize: 11, fontWeight: 700, color: C.purple, flexShrink: 0 }}>TM</div>
              <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 10, padding: "10px 14px", fontSize: 13, color: C.textPrimary, flex: 1 }}>
                Hei, jeg har ikke mottatt utbetalingen min enda. Kan dere sjekke statusen?
                <div style={{ fontSize: 10, color: C.textMuted, marginTop: 6 }}>{selected.date} 11:34</div>
              </div>
            </div>
          </div>

          <textarea rows={4} placeholder="Skriv svar her..." style={{
            display: "block", width: "100%", padding: "10px 14px",
            background: C.bg, border: `1px solid ${C.border}`, borderRadius: 10,
            color: C.textPrimary, fontSize: 13, resize: "vertical", marginBottom: 12,
          }} />
          <div style={{ display: "flex", gap: 8 }}>
            <Btn>Send svar</Btn>
            <Btn variant="ghost">Last opp vedlegg</Btn>
            <Btn variant="danger" style={{ marginLeft: "auto" }}>Avslutt ticket</Btn>
          </div>
        </Modal>
      )}
    </div>
  );
};

// SETTINGS PAGE
const SettingsPage = () => {
  const [tab, setTab] = useState("Bedrift");
  const tabs = ["Bedrift", "Brukere & roller", "Juridisk"];
  return (
    <div className="fade-in">
      <div style={{ display: "flex", gap: 4, marginBottom: 24, background: C.surface, border: `1px solid ${C.border}`, borderRadius: 10, padding: 4, width: "fit-content" }}>
        {tabs.map(t => (
          <button key={t} onClick={() => setTab(t)} style={{
            padding: "7px 18px", borderRadius: 7, fontSize: 13, fontWeight: 600, cursor: "pointer",
            background: tab === t ? C.accent : "transparent", color: tab === t ? "#fff" : C.textSecondary,
            border: "none", transition: "all 0.15s",
          }}>{t}</button>
        ))}
      </div>

      {tab === "Bedrift" && (
        <div style={{ maxWidth: 560 }}>
          <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, padding: 24 }}>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
              {[["Firmanavn", "Animer AS"], ["Org.nr", "987 654 321"], ["Adresse", "Storgata 1"], ["Postnr", "0152"], ["Poststed", "Oslo"], ["Kontaktperson", "Tor Martin Olsen"], ["E-post", "post@animer.no"], ["Telefon", "22334455"]].map(([l, v]) => (
                <div key={l}>
                  <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>{l}</label>
                  <input defaultValue={v} style={{
                    display: "block", width: "100%", padding: "9px 12px", marginTop: 4,
                    background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
                    color: C.textPrimary, fontSize: 13,
                  }} />
                </div>
              ))}
            </div>
            <div style={{ marginTop: 16, padding: "16px", background: C.bg, border: `1px solid ${C.border}`, borderRadius: 10, textAlign: "center", cursor: "pointer" }}>
              <div style={{ fontSize: 13, color: C.textMuted }}>📎 Last opp logo</div>
              <div style={{ fontSize: 11, color: C.textMuted, marginTop: 4 }}>PNG, SVG – maks 2MB</div>
            </div>
            <Btn style={{ marginTop: 16 }}>Lagre endringer</Btn>
          </div>
        </div>
      )}

      {tab === "Brukere & roller" && (
        <div style={{ maxWidth: 640 }}>
          <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden", marginBottom: 16 }}>
            {[{ name: "Tor Martin Olsen", role: "Super Admin", initials: "TM" }, { name: "Kari Regnskap", role: "Regnskap", initials: "KR" }].map((u, i) => (
              <div key={i} style={{
                display: "flex", alignItems: "center", gap: 14, padding: "14px 20px",
                borderBottom: i === 0 ? `1px solid ${C.border}` : "none",
              }}>
                <div style={{ width: 34, height: 34, borderRadius: 8, background: C.accentSoft, display: "flex", alignItems: "center", justifyContent: "center", fontSize: 12, fontWeight: 700, color: C.accent }}>{u.initials}</div>
                <div style={{ flex: 1 }}>
                  <div style={{ fontSize: 13, fontWeight: 600 }}>{u.name}</div>
                  <div style={{ fontSize: 11, color: C.textMuted }}>{u.role}</div>
                </div>
                <Btn size="sm" variant="ghost">Endre</Btn>
                <Btn size="sm" variant="danger">Slett</Btn>
              </div>
            ))}
          </div>
          <Btn>+ Legg til admin</Btn>
        </div>
      )}

      {tab === "Juridisk" && (
        <div style={{ maxWidth: 560, display: "grid", gap: 10 }}>
          {["Vilkår og betingelser", "Personvernerklæring", "Samtykker", "Ambassadøravtale (mal)"].map((item, i) => (
            <div key={i} style={{
              background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12,
              padding: "14px 18px", display: "flex", alignItems: "center", justifyContent: "space-between",
            }}>
              <div>
                <div style={{ fontSize: 13, fontWeight: 600 }}>{item}</div>
                <div style={{ fontSize: 11, color: C.textMuted, marginTop: 2 }}>Sist oppdatert: 2024-11-01</div>
              </div>
              <Btn size="sm" variant="ghost">Rediger</Btn>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// ── BUSINESS OWNER PAGES ─────────────────────────────────────────────────────
const BusinessDashboard = () => (
  <div className="fade-in">
    <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 24 }}>
      <Stat label="Aktive ambassadører" value="6" accent={C.green} />
      <Stat label="Leads denne måneden" value="18" accent={C.accent} />
      <Stat label="Godkjent omsetning" value="520 000 kr" accent={C.green} />
      <Stat label="Provisjon påløpt" value="36 400 kr" accent={C.amber} />
    </div>
    <div style={{ display: "grid", gridTemplateColumns: "2fr 1fr", gap: 20 }}>
      <Section title="Topp ambassadører">
        <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12, overflow: "hidden" }}>
          {AMBASSADORS.filter(a => a.status === "Aktiv").map((a, i) => (
            <div key={i} style={{
              display: "flex", alignItems: "center", gap: 14, padding: "14px 18px",
              borderBottom: i === 0 ? `1px solid ${C.border}` : "none",
            }}>
              <div style={{ width: 34, height: 34, borderRadius: 8, background: C.greenSoft, display: "flex", alignItems: "center", justifyContent: "center", fontSize: 12, fontWeight: 700, color: C.green }}>
                {a.name.split(" ").map(n => n[0]).join("")}
              </div>
              <div style={{ flex: 1 }}>
                <div style={{ fontSize: 13, fontWeight: 600 }}>{a.name}</div>
                <div style={{ fontSize: 11, color: C.textMuted }}>{a.leads} leads · {a.commissionPct}% provisjon</div>
              </div>
              <div style={{ fontSize: 13, color: C.green, fontFamily: "'DM Mono', monospace" }}>{a.revenue.toLocaleString()} kr</div>
            </div>
          ))}
        </div>
      </Section>
      <Section title="Hurtighandlinger">
        <div style={{ display: "grid", gap: 8 }}>
          {["Legg til ny ambassadør", "Godkjenn søknader (1)", "Se utbetalingskrav", "Last ned rapport"].map((a, i) => (
            <button key={i} style={{
              display: "flex", alignItems: "center", gap: 10, padding: "12px 14px",
              background: C.surface, border: `1px solid ${C.border}`, borderRadius: 10,
              color: C.textSecondary, fontSize: 13, fontWeight: 500, cursor: "pointer",
              textAlign: "left", width: "100%", transition: "all 0.15s",
            }}
              onMouseEnter={e => { e.currentTarget.style.background = C.surfaceHover; e.currentTarget.style.color = C.textPrimary; }}
              onMouseLeave={e => { e.currentTarget.style.background = C.surface; e.currentTarget.style.color = C.textSecondary; }}
            >
              <span>{["➕", "⏳", "💰", "📊"][i]}</span> {a}
            </button>
          ))}
        </div>
      </Section>
    </div>
  </div>
);

// ── AMBASSADOR PAGES ─────────────────────────────────────────────────────────
const AmbassadorDashboard = () => (
  <div className="fade-in">
    {/* Hero welcome */}
    <div style={{
      background: `linear-gradient(135deg, ${C.surface} 0%, #1a1f2e 100%)`,
      border: `1px solid ${C.border}`, borderRadius: 18, padding: "28px 32px", marginBottom: 24,
      position: "relative", overflow: "hidden",
    }}>
      <div style={{ position: "absolute", top: -20, right: -20, width: 200, height: 200, borderRadius: "50%", background: `${C.purple}10`, pointerEvents: "none" }} />
      <div style={{ fontSize: 11, color: C.purple, fontWeight: 700, letterSpacing: 1.2, textTransform: "uppercase", marginBottom: 8 }}>Velkommen tilbake</div>
      <div style={{ fontSize: 26, fontWeight: 800, fontFamily: "'Syne', sans-serif" }}>Tor Martin Olsen</div>
      <div style={{ fontSize: 13, color: C.textSecondary, marginTop: 6 }}>Ambassadør-ID: A-001 · Aktiv siden aug 2024</div>
      <div style={{ marginTop: 16, display: "flex", gap: 10 }}>
        <div style={{ padding: "6px 14px", background: C.greenSoft, border: `1px solid ${C.green}40`, borderRadius: 20, fontSize: 12, color: C.green, fontWeight: 600 }}>● Aktiv</div>
        <div style={{ padding: "6px 14px", background: C.accentSoft, border: `1px solid ${C.accent}40`, borderRadius: 20, fontSize: 12, color: C.accent, fontWeight: 600 }}>Provisjonsnivå: 15%</div>
      </div>
    </div>

    <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 24 }}>
      <Stat label="Mine leads" value="14" accent={C.accent} />
      <Stat label="Godkjent omsetning" value="440 000 kr" accent={C.green} />
      <Stat label="Opptjent provisjon" value="66 000 kr" accent={C.green} />
      <Stat label="Til utbetaling" value="12 000 kr" accent={C.amber} />
    </div>

    <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 20 }}>
      <Section title="Mine siste leads">
        <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12, overflow: "hidden" }}>
          {LEADS.filter(l => l.ambassador === "Tor Martin Olsen").map((l, i, arr) => (
            <div key={i} style={{
              display: "flex", alignItems: "center", gap: 12, padding: "12px 16px",
              borderBottom: i < arr.length - 1 ? `1px solid ${C.border}` : "none",
            }}>
              <div style={{ width: 8, height: 8, borderRadius: "50%", background: statusColor(l.status), flexShrink: 0 }} />
              <div style={{ flex: 1 }}>
                <div style={{ fontSize: 13, fontWeight: 600 }}>{l.firm}</div>
                <div style={{ fontSize: 11, color: C.textMuted }}>{l.date}</div>
              </div>
              <Badge label={l.status} color={statusColor(l.status)} />
            </div>
          ))}
        </div>
      </Section>

      <Section title="Provisjonsmatrise">
        <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12, overflow: "hidden", marginBottom: 12 }}>
          {[["0 – 500 000 kr", "5%", false], ["500k – 1M kr", "10%", false], ["1M+ kr", "15%", true]].map(([r, p, active], i) => (
            <div key={i} style={{
              display: "flex", justifyContent: "space-between", padding: "12px 16px",
              background: active ? C.greenSoft : "transparent",
              borderBottom: i < 2 ? `1px solid ${C.border}` : "none",
              borderLeft: active ? `3px solid ${C.green}` : "3px solid transparent",
            }}>
              <span style={{ fontSize: 12, color: active ? C.textPrimary : C.textSecondary }}>{r}</span>
              <span style={{ fontSize: 14, fontWeight: 700, color: active ? C.green : C.textMuted }}>{p}</span>
            </div>
          ))}
        </div>
        <div style={{ background: C.amberSoft, border: `1px solid ${C.amber}40`, borderRadius: 10, padding: "10px 14px", fontSize: 12, color: C.amber }}>
          Du er på 15% nivå · 440 000 kr registrert omsetning 🎉
        </div>
      </Section>
    </div>
  </div>
);

const AmbassadorShare = () => {
  const [copied, setCopied] = useState(false);
  const myLink = "https://trustai.no/ref/tm-olsen-a001";
  const content = [
    { source: "LinkedIn", title: "Sjekk ut TrustAI", preview: "Er du på jakt etter effektive løsninger for din bedrift? Jeg kan anbefale TrustAI – det har gjort en stor forskjell for oss." },
    { source: "E-post", title: "Personlig anbefaling", preview: "Hei! Jeg ønsker å anbefale deg en løsning som har hjulpet oss enormt..." },
  ];
  return (
    <div className="fade-in">
      <div style={{
        background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14,
        padding: 24, marginBottom: 20,
      }}>
        <div style={{ fontSize: 13, fontWeight: 700, marginBottom: 12 }}>Din personlige delingslenke</div>
        <div style={{ display: "flex", gap: 10 }}>
          <div style={{
            flex: 1, padding: "10px 16px", background: C.bg, border: `1px solid ${C.border}`,
            borderRadius: 8, fontSize: 13, color: C.accent, fontFamily: "'DM Mono', monospace",
          }}>{myLink}</div>
          <Btn onClick={() => { setCopied(true); setTimeout(() => setCopied(false), 2000); }} variant={copied ? "success" : "primary"}>
            {copied ? "✓ Kopiert!" : "Kopier"}
          </Btn>
        </div>
      </div>

      <Section title="Forhåndslagde delingstekster">
        <div style={{ display: "grid", gap: 12 }}>
          {content.map((c, i) => (
            <div key={i} style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 12, padding: 20 }}>
              <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 12 }}>
                <Badge label={c.source} color={C.accent} />
                <span style={{ fontSize: 13, fontWeight: 600 }}>{c.title}</span>
              </div>
              <div style={{ fontSize: 13, color: C.textSecondary, lineHeight: 1.6, marginBottom: 14 }}>{c.preview}<br /><span style={{ color: C.accent }}>{myLink}</span></div>
              <div style={{ display: "flex", gap: 8 }}>
                <Btn size="sm">Kopier tekst</Btn>
                <Btn size="sm" variant="ghost">Del direkte</Btn>
              </div>
            </div>
          ))}
        </div>
      </Section>
    </div>
  );
};

const AmbassadorEarnings = () => (
  <div className="fade-in">
    <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 24 }}>
      <Stat label="Totalt opptjent" value="66 000 kr" accent={C.green} />
      <Stat label="Utbetalt" value="54 000 kr" accent={C.green} />
      <Stat label="Til utbetaling" value="12 000 kr" accent={C.amber} />
    </div>
    <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
      <Table
        cols={[
          { key: "firm", label: "Kunde", render: v => <span style={{ fontWeight: 600 }}>{v}</span> },
          { key: "value", label: "Omsetning", render: v => <span style={{ fontFamily: "'DM Mono', monospace" }}>{v.toLocaleString()} kr</span> },
          { key: "commission", label: "Din provisjon", render: v => <span style={{ color: C.green, fontFamily: "'DM Mono', monospace" }}>{v.toLocaleString()} kr</span> },
          { key: "payment", label: "Status", render: v => <Badge label={v === "-" ? "Ikke fakturert" : v} color={v === "-" ? C.textMuted : C.green} /> },
          { key: "date", label: "Dato" },
        ]}
        rows={LEADS.filter(l => l.ambassador === "Tor Martin Olsen")}
      />
    </div>
  </div>
);

const AmbassadorPayout = () => {
  const [showRequest, setShowRequest] = useState(false);
  return (
    <div className="fade-in">
      <div style={{
        background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, padding: 24, marginBottom: 20,
      }}>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 16 }}>
          <div>
            <div style={{ fontSize: 22, fontWeight: 800, fontFamily: "'Syne', sans-serif", color: C.amber }}>12 000 kr</div>
            <div style={{ fontSize: 13, color: C.textMuted, marginTop: 4 }}>tilgjengelig for utbetaling</div>
          </div>
          <Btn onClick={() => setShowRequest(true)}>Send utbetalingsforespørsel</Btn>
        </div>
        <div style={{ fontSize: 12, color: C.textMuted, padding: "10px 14px", background: C.bg, borderRadius: 8 }}>
          💡 Last opp faktura for å be om utbetaling. Godkjennes normalt innen 3-5 virkedager.
        </div>
      </div>

      <Section title="Utbetalingshistorikk">
        <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
          <Table
            cols={[
              { key: "amount", label: "Beløp", render: v => <span style={{ fontFamily: "'DM Mono', monospace", fontWeight: 600 }}>{v.toLocaleString()} kr</span> },
              { key: "invoiceDate", label: "Sendt dato" },
              { key: "actionDate", label: "Behandlet" },
              { key: "status", label: "Status", render: v => <Badge label={v} color={statusColor(v)} /> },
            ]}
            rows={PAYOUTS.filter(p => p.ambassador === "Tor Martin Olsen")}
          />
        </div>
      </Section>

      {showRequest && (
        <Modal title="Send utbetalingsforespørsel" onClose={() => setShowRequest(false)} width={500}>
          <div style={{ marginBottom: 14 }}>
            <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>Beløp til utbetaling</label>
            <input defaultValue="12000" style={{
              display: "block", width: "100%", padding: "9px 12px", marginTop: 6,
              background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
              color: C.textPrimary, fontSize: 13,
            }} />
          </div>
          <div style={{ marginBottom: 16 }}>
            <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>Last opp faktura (PDF)</label>
            <div style={{
              marginTop: 6, padding: "24px", background: C.bg, border: `2px dashed ${C.border}`,
              borderRadius: 10, textAlign: "center", cursor: "pointer", color: C.textMuted, fontSize: 13,
            }}>📎 Dra og slipp faktura her, eller klikk for å velge fil</div>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn onClick={() => setShowRequest(false)}>Send forespørsel</Btn>
            <Btn variant="ghost" onClick={() => setShowRequest(false)}>Avbryt</Btn>
          </div>
        </Modal>
      )}
    </div>
  );
};

const AmbassadorSupport = () => {
  const [showNew, setShowNew] = useState(false);
  return (
    <div className="fade-in">
      <div style={{ display: "flex", justifyContent: "flex-end", marginBottom: 16 }}>
        <Btn onClick={() => setShowNew(true)}>+ Ny henvendelse</Btn>
      </div>
      <Section title="Mine tickets">
        <div style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 14, overflow: "hidden" }}>
          <Table
            cols={[
              { key: "id", label: "ID", render: v => <span style={{ fontFamily: "'DM Mono', monospace", color: C.textMuted }}>#{v}</span> },
              { key: "subject", label: "Emne", render: v => <span style={{ fontWeight: 600 }}>{v}</span> },
              { key: "date", label: "Dato" },
              { key: "status", label: "Status", render: v => <Badge label={v} color={statusColor(v)} /> },
            ]}
            rows={TICKETS.filter(t => t.ambassador === "Tor Martin Olsen")}
          />
        </div>
      </Section>

      <Section title="Vanlige spørsmål">
        <div style={{ display: "grid", gap: 8 }}>
          {["Hvordan beregnes provisjonen min?", "Når kan jeg be om utbetaling?", "Kan jeg rekruttere andre ambassadører?"].map((q, i) => (
            <div key={i} style={{ background: C.surface, border: `1px solid ${C.border}`, borderRadius: 10, padding: "12px 16px", cursor: "pointer" }}>
              <div style={{ fontSize: 13, fontWeight: 500 }}>{q}</div>
            </div>
          ))}
        </div>
      </Section>

      {showNew && (
        <Modal title="Ny støtteforespørsel" onClose={() => setShowNew(false)} width={540}>
          <div style={{ marginBottom: 12 }}>
            <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>Kategori</label>
            <select style={{
              display: "block", width: "100%", padding: "9px 12px", marginTop: 6,
              background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
              color: C.textPrimary, fontSize: 13,
            }}>
              {["Utbetaling", "Provisjon", "Teknisk problem", "Annet"].map(o => <option key={o}>{o}</option>)}
            </select>
          </div>
          <div style={{ marginBottom: 12 }}>
            <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>Emne</label>
            <input placeholder="Kort beskrivelse av henvendelsen" style={{
              display: "block", width: "100%", padding: "9px 12px", marginTop: 6,
              background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
              color: C.textPrimary, fontSize: 13,
            }} />
          </div>
          <div style={{ marginBottom: 16 }}>
            <label style={{ fontSize: 11, color: C.textMuted, fontWeight: 700, letterSpacing: 0.8, textTransform: "uppercase" }}>Melding</label>
            <textarea rows={5} style={{
              display: "block", width: "100%", padding: "9px 12px", marginTop: 6,
              background: C.bg, border: `1px solid ${C.border}`, borderRadius: 8,
              color: C.textPrimary, fontSize: 13, resize: "vertical",
            }} />
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn onClick={() => setShowNew(false)}>Send</Btn>
            <Btn variant="ghost" onClick={() => setShowNew(false)}>Avbryt</Btn>
          </div>
        </Modal>
      )}
    </div>
  );
};

// ── ROLE SWITCHER (for demo) ─────────────────────────────────────────────────
const RoleSwitcher = ({ role, setRole }) => {
  const roles = [
    { key: "superadmin", label: "Super Admin", color: C.accent },
    { key: "business", label: "Business Owner", color: C.green },
    { key: "ambassador", label: "Ambassadør", color: C.purple },
  ];
  return (
    <div style={{
      position: "fixed", bottom: 20, left: "50%", transform: "translateX(-50%)",
      zIndex: 200, display: "flex", gap: 6, padding: "6px", background: `${C.surface}F0`,
      backdropFilter: "blur(10px)", border: `1px solid ${C.border}`, borderRadius: 30,
      boxShadow: "0 20px 60px rgba(0,0,0,0.5)",
    }}>
      {roles.map(r => (
        <button key={r.key} onClick={() => setRole(r.key)} style={{
          padding: "7px 18px", borderRadius: 22, fontSize: 12, fontWeight: 700, cursor: "pointer",
          background: role === r.key ? r.color : "transparent",
          color: role === r.key ? "#fff" : C.textMuted,
          border: "none", transition: "all 0.2s", letterSpacing: 0.3,
        }}>{r.label}</button>
      ))}
    </div>
  );
};

// ── APP ───────────────────────────────────────────────────────────────────────
export default function TrustAI() {
  const [role, setRole] = useState("superadmin");
  const [page, setPage] = useState("dashboard");

  const navMap = {
    superadmin: NAV_SUPER,
    business: NAV_BUSINESS,
    ambassador: NAV_AMBASSADOR,
  };

  const defaultPages = { superadmin: "dashboard", business: "bus_dashboard", ambassador: "amb_dashboard" };

  const handleRoleChange = (r) => {
    setRole(r);
    setPage(defaultPages[r]);
  };

  const pageTitles = {
    dashboard: "Dashboard", leads: "Leads", ambassadors: "Ambassadører",
    revenue: "Inntekter", payouts: "Utbetalinger", content: "Innhold & deling",
    tickets: "Support & FAQ", settings: "Innstillinger",
    bus_dashboard: "Dashboard", bus_leads: "Leads", bus_ambassadors: "Ambassadører",
    bus_revenue: "Økonomi", bus_content: "Innhold", bus_settings: "Innstillinger",
    amb_dashboard: "Min side", amb_leads: "Mine leads", amb_share: "Del & rekrutter",
    amb_earnings: "Inntekter", amb_payout: "Utbetaling", amb_support: "Support",
  };

  const renderPage = () => {
    const pages = {
      // Super Admin
      dashboard: <SuperDashboard />,
      leads: <LeadsPage />,
      ambassadors: <AmbassadorsPage />,
      revenue: <RevenuePage />,
      payouts: <PayoutsPage />,
      content: <ContentPage />,
      tickets: <TicketsPage />,
      settings: <SettingsPage />,
      // Business
      bus_dashboard: <BusinessDashboard />,
      bus_leads: <LeadsPage />,
      bus_ambassadors: <AmbassadorsPage />,
      bus_revenue: <RevenuePage />,
      bus_content: <ContentPage />,
      bus_settings: <SettingsPage />,
      // Ambassador
      amb_dashboard: <AmbassadorDashboard />,
      amb_leads: <LeadsPage />,
      amb_share: <AmbassadorShare />,
      amb_earnings: <AmbassadorEarnings />,
      amb_payout: <AmbassadorPayout />,
      amb_support: <AmbassadorSupport />,
    };
    return pages[page] || <SuperDashboard />;
  };

  return (
    <>
      <style>{globalCSS}</style>
      <div style={{ display: "flex", minHeight: "100vh", background: C.bg }}>
        <Sidebar nav={navMap[role]} active={page} onNav={setPage} role={role} />
        <div style={{ marginLeft: 220, flex: 1, display: "flex", flexDirection: "column", minHeight: "100vh" }}>
          <TopBar title={pageTitles[page] || "TrustAI"} role={role} />
          <main style={{ flex: 1, padding: "28px 32px", maxWidth: 1200 }}>
            {renderPage()}
          </main>
        </div>
      </div>
      <RoleSwitcher role={role} setRole={handleRoleChange} />
    </>
  );
}
