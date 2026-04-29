import React, { useState } from "react";

/* ============================================================
   FORGE & FOULÉE — Codex Athlétique
   Medieval fantasy / D&D inspired training tracker
   Two views: Chronique (workout detail) & Almanach (planner)
   ============================================================ */

// ---------- DECORATIVE SVG COMPONENTS ----------

const Crest = () => (
  <svg viewBox="0 0 100 110" width="64" height="70" aria-hidden="true">
    <defs>
      <linearGradient id="shieldG" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0" stopColor="#7a1f1f" />
        <stop offset="0.5" stopColor="#5c1717" />
        <stop offset="1" stopColor="#3a0f0f" />
      </linearGradient>
    </defs>
    <path
      d="M50 5 L92 16 L92 55 Q92 90 50 105 Q8 90 8 55 L8 16 Z"
      fill="url(#shieldG)"
      stroke="#b8893d"
      strokeWidth="2.5"
    />
    <path
      d="M50 12 L86 21 L86 55 Q86 84 50 97 Q14 84 14 55 L14 21 Z"
      fill="none"
      stroke="#d4a851"
      strokeWidth="0.8"
      opacity="0.7"
    />
    {/* Crossed sword + barbell */}
    <g stroke="#e8c878" strokeWidth="2.2" strokeLinecap="round" fill="none">
      <line x1="28" y1="32" x2="72" y2="76" />
      <line x1="72" y1="32" x2="28" y2="76" />
    </g>
    <circle cx="50" cy="54" r="9" fill="#1a0f08" stroke="#d4a851" strokeWidth="1.5" />
    <text
      x="50"
      y="58"
      textAnchor="middle"
      fontFamily="Cinzel"
      fontSize="9"
      fontWeight="700"
      fill="#e8c878"
    >
      F
    </text>
  </svg>
);

const Flourish = ({ width = 280 }) => (
  <svg viewBox="0 0 280 24" width={width} height="24" aria-hidden="true">
    <g stroke="#b8893d" strokeWidth="1.2" fill="none" strokeLinecap="round">
      <line x1="20" y1="12" x2="115" y2="12" />
      <line x1="165" y1="12" x2="260" y2="12" />
      <path d="M115 12 Q120 6 128 12 Q132 16 140 12 Q148 6 152 12 Q160 18 165 12" />
      <circle cx="140" cy="12" r="2.2" fill="#b8893d" />
      <path d="M5 12 Q10 8 15 12 Q10 16 5 12 Z" fill="#b8893d" />
      <path d="M275 12 Q270 8 265 12 Q270 16 275 12 Z" fill="#b8893d" />
    </g>
  </svg>
);

const CornerOrnament = ({ rotate = 0 }) => (
  <svg
    viewBox="0 0 60 60"
    width="42"
    height="42"
    style={{ transform: `rotate(${rotate}deg)` }}
    aria-hidden="true"
  >
    <g stroke="#b8893d" strokeWidth="1.4" fill="none" strokeLinecap="round">
      <path d="M4 4 L4 28 Q4 38 14 38 Q24 38 24 28 Q24 20 16 20" />
      <path d="M4 4 L28 4 Q38 4 38 14 Q38 24 28 24 Q20 24 20 16" />
      <circle cx="14" cy="14" r="2" fill="#b8893d" />
    </g>
  </svg>
);

const WaxSeal = ({ size = 76, label = "VII" }) => (
  <svg viewBox="0 0 100 100" width={size} height={size} aria-hidden="true">
    <defs>
      <radialGradient id="waxG" cx="0.4" cy="0.35">
        <stop offset="0" stopColor="#a83232" />
        <stop offset="0.5" stopColor="#7a1f1f" />
        <stop offset="1" stopColor="#3a0f0f" />
      </radialGradient>
    </defs>
    {/* drips */}
    <path
      d="M22 70 Q18 88 26 92 Q30 86 28 78 Z"
      fill="#5c1717"
      opacity="0.85"
    />
    <path
      d="M76 76 Q82 92 74 94 Q70 88 72 80 Z"
      fill="#5c1717"
      opacity="0.85"
    />
    <circle cx="50" cy="50" r="38" fill="url(#waxG)" />
    <circle
      cx="50"
      cy="50"
      r="34"
      fill="none"
      stroke="#3a0f0f"
      strokeWidth="0.8"
      opacity="0.6"
    />
    <circle
      cx="50"
      cy="50"
      r="29"
      fill="none"
      stroke="#e8c878"
      strokeWidth="0.7"
      strokeDasharray="2 3"
      opacity="0.7"
    />
    <text
      x="50"
      y="56"
      textAnchor="middle"
      fontFamily="UnifrakturMaguntia, serif"
      fontSize="22"
      fill="#e8c878"
    >
      {label}
    </text>
  </svg>
);

const SwordsIcon = ({ size = 22 }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} aria-hidden="true">
    <g stroke="currentColor" strokeWidth="1.4" fill="none" strokeLinecap="round">
      <path d="M3 21 L13 11 M5 19 L7 21 M3 21 L7 21 L7 17" />
      <path d="M21 21 L11 11 M19 19 L17 21 M21 21 L17 21 L17 17" />
      <path d="M13 11 L17 7 L19 5 L21 3 L17 5 L15 7 L13 9" />
      <path d="M11 11 L7 7 L5 5 L3 3 L7 5 L9 7 L11 9" />
    </g>
  </svg>
);

const BootIcon = ({ size = 22 }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} aria-hidden="true">
    <g stroke="currentColor" strokeWidth="1.4" fill="none" strokeLinejoin="round">
      <path d="M7 4 L13 4 L13 13 L19 13 Q21 13 21 16 L21 19 Q21 20 20 20 L8 20 Q7 20 7 19 Z" />
      <path d="M13 8 L11 8 M13 11 L11 11" />
      <path d="M9 16 L11 16 M13 16 L15 16 M17 16 L19 16" />
    </g>
  </svg>
);

const HeartIcon = ({ size = 18 }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} aria-hidden="true">
    <path
      d="M12 21 C 5 16 2 12 2 8 A 5 5 0 0 1 12 6 A 5 5 0 0 1 22 8 C 22 12 19 16 12 21 Z"
      fill="currentColor"
    />
  </svg>
);

const StarIcon = ({ size = 16 }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} aria-hidden="true">
    <path
      d="M12 2 L14.6 8.6 L21.6 9.2 L16.3 13.8 L17.9 20.6 L12 17 L6.1 20.6 L7.7 13.8 L2.4 9.2 L9.4 8.6 Z"
      fill="currentColor"
    />
  </svg>
);

const HourglassIcon = ({ size = 16 }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} aria-hidden="true">
    <g stroke="currentColor" strokeWidth="1.4" fill="none">
      <path d="M6 3 L18 3 M6 21 L18 21" />
      <path d="M7 3 L7 7 Q7 10 12 12 Q17 14 17 17 L17 21" />
      <path d="M17 3 L17 7 Q17 10 12 12 Q7 14 7 17 L7 21" />
    </g>
  </svg>
);

const FeatherIcon = ({ size = 18 }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} aria-hidden="true">
    <g stroke="currentColor" strokeWidth="1.3" fill="none" strokeLinecap="round">
      <path d="M4 20 L11 13" />
      <path d="M20 4 Q14 3 9 8 Q4 13 6 19 Q12 21 17 16 Q22 11 20 4 Z" />
      <path d="M9 8 L9 16 M12 7 L12 14 M15 6 L15 12" />
    </g>
  </svg>
);

// ---------- WORKOUT DATA ----------

const workout = {
  title: "L'Assaut du Lundi",
  subtitle: "Forge du Haut du Corps · Acte III",
  date: "Mardi 28 Avril, An MMXXVI",
  duration: "1h 17min",
  intensity: "Ardente",
  location: "Salle des Forges, Tholosa",
  weather: "☾ Soir frais · 14°C",
  stats: [
    { label: "Force", value: 18, max: 20 },
    { label: "Endurance", value: 14, max: 20 },
    { label: "Résolution", value: 16, max: 20 },
    { label: "Vigueur", value: 12, max: 20 },
  ],
  exercises: [
    { name: "Développé Couché", sets: 4, reps: "8", weight: "80 kg", rpe: 8, note: "Le fer m'obéit." },
    { name: "Tractions Lestées", sets: 4, reps: "10", weight: "+5 kg", rpe: 9, note: "Dernière série âpre." },
    { name: "Développé Militaire", sets: 3, reps: "10", weight: "45 kg", rpe: 7 },
    { name: "Rowing Barre", sets: 4, reps: "8", weight: "70 kg", rpe: 8 },
    { name: "Curl Marteau", sets: 3, reps: "12", weight: "14 kg", rpe: 7 },
    { name: "Extensions Triceps", sets: 3, reps: "12", weight: "22 kg", rpe: 7 },
  ],
  rewards: { xp: 245, streakDays: 23, achievement: "Bras d'Acier — Palier IV" },
  journal:
    "Le fer m'a paru léger ce soir. Les tractions ont mordu — la cinquième fait toujours grogner — mais la quatrième série est tombée comme un arbre sec. Demain, la foulée du matin avant l'aurore.",
};

// ---------- PLANNER DATA ----------

// April 2026: 1st = Wednesday. Today = April 29 (Wednesday).
const monthLabel = "Avril · MMXXVI";
const weekdays = ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"];
// Build 30 days for April starting offset (April 1 2026 = Wednesday => offset 2 in Mon-start grid)
const monthOffset = 2;
const daysInMonth = 30;

const trainings = {
  1: { kind: "forge", title: "Pectoraux & Dos" },
  2: { kind: "foulee", title: "Sortie Tempo · 6km" },
  3: { kind: "rest", title: "Repos du Guerrier" },
  4: { kind: "forge", title: "Jambes" },
  5: { kind: "foulee", title: "Endurance · 10km" },
  6: { kind: "rest", title: "Repos" },
  7: { kind: "forge", title: "Épaules & Bras" },
  8: { kind: "foulee", title: "Fractionné · 8x400m" },
  9: { kind: "forge", title: "Pectoraux & Dos" },
  10: { kind: "rest", title: "Repos" },
  11: { kind: "foulee", title: "Sortie Longue · 14km" },
  12: { kind: "rest", title: "Repos" },
  13: { kind: "forge", title: "Jambes" },
  14: { kind: "foulee", title: "Tempo · 7km" },
  15: { kind: "forge", title: "Haut du Corps" },
  16: { kind: "rest", title: "Repos" },
  17: { kind: "foulee", title: "Côtes · 5x200m" },
  18: { kind: "forge", title: "Tirage & Biceps" },
  19: { kind: "foulee", title: "Sortie Longue · 16km" },
  20: { kind: "rest", title: "Repos" },
  21: { kind: "forge", title: "Jambes" },
  22: { kind: "foulee", title: "Allure Course · 8km" },
  23: { kind: "forge", title: "Pectoraux & Triceps" },
  24: { kind: "rest", title: "Repos" },
  25: { kind: "foulee", title: "Sortie Longue · 18km" },
  26: { kind: "rest", title: "Repos" },
  27: { kind: "forge", title: "Dos & Épaules" },
  28: { kind: "forge", title: "L'Assaut du Lundi" }, // The chronicled one
  29: { kind: "foulee", title: "Récupération · 5km" }, // Today
  30: { kind: "forge", title: "Jambes Lourdes" },
};

const today = 29;

const upcoming = [
  { day: 29, weekday: "Mer", kind: "foulee", title: "Récupération · 5km", time: "06:30", duration: "30min" },
  { day: 30, weekday: "Jeu", kind: "forge", title: "Jambes Lourdes", time: "18:00", duration: "1h 20" },
  { day: 1, weekday: "Ven", kind: "rest", title: "Repos du Guerrier", time: "—", duration: "—", monthShift: "Mai" },
  { day: 2, weekday: "Sam", kind: "foulee", title: "Sortie Longue · 20km", time: "07:00", duration: "2h" },
];

// ---------- SUB COMPONENTS ----------

const StatBar = ({ label, value, max }) => {
  const pct = (value / max) * 100;
  return (
    <div className="stat">
      <div className="stat-row">
        <span className="stat-label">{label}</span>
        <span className="stat-value">
          {value}<span className="stat-max">/{max}</span>
        </span>
      </div>
      <div className="stat-track">
        <div className="stat-fill" style={{ width: `${pct}%` }} />
        <div className="stat-pips">
          {Array.from({ length: max }).map((_, i) => (
            <span key={i} className={`pip ${i < value ? "pip-on" : ""}`} />
          ))}
        </div>
      </div>
    </div>
  );
};

const RpeStars = ({ rpe }) => (
  <span className="rpe">
    {Array.from({ length: 10 }).map((_, i) => (
      <span key={i} className={`rpe-dot ${i < rpe ? "on" : ""}`} />
    ))}
  </span>
);

const ExerciseRow = ({ ex, idx }) => (
  <article className="ex-row">
    <div className="ex-numeral">
      <span>{["I", "II", "III", "IV", "V", "VI", "VII", "VIII"][idx]}</span>
    </div>
    <div className="ex-body">
      <header className="ex-head">
        <h4 className="ex-name">{ex.name}</h4>
        <div className="ex-load">
          <span className="ex-sets">{ex.sets}</span>
          <span className="ex-mult">×</span>
          <span className="ex-reps">{ex.reps}</span>
          <span className="ex-at">@</span>
          <span className="ex-weight">{ex.weight}</span>
        </div>
      </header>
      <div className="ex-meta">
        <span className="ex-rpe-label">Effort</span>
        <RpeStars rpe={ex.rpe} />
        {ex.note && <em className="ex-note">« {ex.note} »</em>}
      </div>
    </div>
  </article>
);

const TopBar = ({ view, setView }) => (
  <header className="topbar">
    <div className="topbar-inner">
      <div className="brand">
        <Crest />
        <div className="brand-text">
          <div className="brand-kicker">Codex Athlétique · MMXXVI</div>
          <h1 className="brand-name">Forge &amp; Foulée</h1>
        </div>
      </div>
      <nav className="nav">
        <button
          className={`nav-btn ${view === "detail" ? "active" : ""}`}
          onClick={() => setView("detail")}
        >
          <span className="nav-marker">❦</span>
          Chronique du Jour
        </button>
        <button
          className={`nav-btn ${view === "planner" ? "active" : ""}`}
          onClick={() => setView("planner")}
        >
          <span className="nav-marker">❦</span>
          Almanach
        </button>
        <div className="nav-hero">
          <span className="hero-level">Niv. 7</span>
          <span className="hero-streak">🔥 23j</span>
        </div>
      </nav>
    </div>
    <div className="topbar-rule" />
  </header>
);

// ---------- WORKOUT DETAIL VIEW ----------

const WorkoutDetail = () => (
  <div className="page page-detail">
    <div className="parchment">
      <div className="corners">
        <span className="corner tl"><CornerOrnament rotate={0} /></span>
        <span className="corner tr"><CornerOrnament rotate={90} /></span>
        <span className="corner bl"><CornerOrnament rotate={270} /></span>
        <span className="corner br"><CornerOrnament rotate={180} /></span>
      </div>

      {/* HEADER */}
      <section className="chron-head">
        <div className="chron-kicker">Chronique de l'Épreuve</div>
        <h2 className="chron-title">{workout.title}</h2>
        <p className="chron-sub">{workout.subtitle}</p>
        <div className="chron-flourish"><Flourish /></div>
        <div className="chron-meta">
          <span><HourglassIcon /> {workout.duration}</span>
          <span className="dot">·</span>
          <span>{workout.date}</span>
          <span className="dot">·</span>
          <span>{workout.location}</span>
          <span className="dot">·</span>
          <span>{workout.weather}</span>
          <span className="dot">·</span>
          <span className="intensity">Intensité : {workout.intensity}</span>
        </div>
        <div className="chron-seal">
          <WaxSeal label="VII" />
          <span className="seal-caption">Niveau du Héraut</span>
        </div>
      </section>

      {/* STATS */}
      <section className="stats-block">
        <h3 className="section-h">Les Quatre Vertus</h3>
        <div className="stats-grid">
          {workout.stats.map((s) => (
            <StatBar key={s.label} {...s} />
          ))}
        </div>
      </section>

      {/* TWO COLUMN: EXERCISES + JOURNAL */}
      <section className="two-col">
        <div className="col-main">
          <h3 className="section-h">Les Épreuves</h3>
          <div className="ex-list">
            {workout.exercises.map((ex, i) => (
              <ExerciseRow key={i} ex={ex} idx={i} />
            ))}
          </div>
        </div>

        <aside className="col-side">
          <div className="journal">
            <h3 className="section-h">Carnet du Soldat</h3>
            <p className="journal-text">
              <span className="dropcap">{workout.journal[0]}</span>
              {workout.journal.slice(1)}
            </p>
            <div className="signature">
              — scellé à la chandelle, en l'an 2026
            </div>
          </div>

          <div className="rewards">
            <h3 className="section-h">Récompenses</h3>
            <div className="reward-row">
              <StarIcon size={18} />
              <span className="reward-label">Expérience</span>
              <span className="reward-val">+{workout.rewards.xp} XP</span>
            </div>
            <div className="reward-row">
              <HeartIcon size={16} />
              <span className="reward-label">Série de zèle</span>
              <span className="reward-val">{workout.rewards.streakDays} jours</span>
            </div>
            <div className="reward-row reward-feat">
              <span className="feat-icon">⚜</span>
              <span className="reward-label">Haut Fait</span>
              <span className="reward-val">{workout.rewards.achievement}</span>
            </div>
          </div>

          <div className="muscles">
            <h3 className="section-h">Bestiaire des Muscles</h3>
            <div className="muscle-tags">
              {["Pectoraux", "Dorsaux", "Trapèzes", "Biceps", "Triceps", "Deltoïdes"].map((m) => (
                <span key={m} className="muscle-tag">{m}</span>
              ))}
            </div>
          </div>
        </aside>
      </section>

      <footer className="chron-foot">
        <div className="chron-flourish"><Flourish width={220} /></div>
        <div className="actions">
          <button className="btn-primary">⚔ Sceller la Chronique</button>
          <button className="btn-ghost">Inscrire au Codex</button>
          <button className="btn-ghost">Partager au Conseil</button>
        </div>
      </footer>
    </div>
  </div>
);

// ---------- PLANNER VIEW ----------

const KindIcon = ({ kind, size = 16 }) => {
  if (kind === "forge") return <SwordsIcon size={size} />;
  if (kind === "foulee") return <BootIcon size={size} />;
  return <FeatherIcon size={size} />;
};

const kindLabel = (k) =>
  k === "forge" ? "Forge" : k === "foulee" ? "Foulée" : "Repos";

const Planner = () => {
  const cells = [];
  for (let i = 0; i < monthOffset; i++) cells.push({ blank: true, key: `b${i}` });
  for (let d = 1; d <= daysInMonth; d++) cells.push({ day: d, key: `d${d}` });
  while (cells.length % 7 !== 0) cells.push({ blank: true, key: `e${cells.length}` });

  return (
    <div className="page page-planner">
      <div className="parchment book">
        <div className="book-spine" />
        <div className="corners">
          <span className="corner tl"><CornerOrnament rotate={0} /></span>
          <span className="corner tr"><CornerOrnament rotate={90} /></span>
          <span className="corner bl"><CornerOrnament rotate={270} /></span>
          <span className="corner br"><CornerOrnament rotate={180} /></span>
        </div>

        <header className="alm-head">
          <div className="alm-kicker">Almanach des Entraînements</div>
          <h2 className="alm-title">{monthLabel}</h2>
          <div className="chron-flourish"><Flourish /></div>
          <div className="alm-hero">
            <div className="hero-stat">
              <span className="hero-stat-num">VII</span>
              <span className="hero-stat-lab">Niveau du Héraut</span>
            </div>
            <div className="hero-stat">
              <span className="hero-stat-num">23</span>
              <span className="hero-stat-lab">Jours de Zèle</span>
            </div>
            <div className="hero-stat">
              <span className="hero-stat-num">14 / 22</span>
              <span className="hero-stat-lab">Quêtes Accomplies</span>
            </div>
            <div className="hero-stat">
              <span className="hero-stat-num">187 km</span>
              <span className="hero-stat-lab">Lieues Foulées</span>
            </div>
          </div>
        </header>

        <div className="legend">
          <span className="leg-item leg-forge"><SwordsIcon size={14} /> Forge — Musculation</span>
          <span className="leg-item leg-foulee"><BootIcon size={14} /> Foulée — Course</span>
          <span className="leg-item leg-rest"><FeatherIcon size={14} /> Repos du Guerrier</span>
          <span className="leg-spacer" />
          <button className="filter-btn">‹ Mars</button>
          <button className="filter-btn">Mai ›</button>
        </div>

        <div className="alm-body">
          {/* LEFT PAGE — CALENDAR */}
          <div className="cal-page">
            <div className="cal-grid">
              {weekdays.map((w) => (
                <div key={w} className="cal-weekday">{w}</div>
              ))}
              {cells.map((c) => {
                if (c.blank) return <div key={c.key} className="cal-cell blank" />;
                const t = trainings[c.day];
                const isToday = c.day === today;
                const isPast = c.day < today;
                return (
                  <div
                    key={c.key}
                    className={`cal-cell ${t ? `kind-${t.kind}` : ""} ${
                      isToday ? "is-today" : ""
                    } ${isPast ? "is-past" : ""}`}
                  >
                    <span className="cal-num">{c.day}</span>
                    {t && (
                      <div className="cal-mark">
                        <span className="cal-icon"><KindIcon kind={t.kind} /></span>
                        <span className="cal-title">{t.title}</span>
                      </div>
                    )}
                    {isToday && <span className="today-pin">Auj.</span>}
                  </div>
                );
              })}
            </div>
          </div>

          {/* RIGHT PAGE — UPCOMING + QUEST */}
          <div className="side-page">
            <div className="quest">
              <div className="quest-banner">⚔ Quête Hebdomadaire</div>
              <h3 className="quest-title">« Conquérir 30 lieues »</h3>
              <p className="quest-desc">
                Cumule trente kilomètres de course avant la lune nouvelle.
                Le Cartographe consignera ton exploit.
              </p>
              <div className="quest-progress">
                <div className="quest-bar">
                  <div className="quest-fill" style={{ width: "67%" }} />
                </div>
                <div className="quest-stats">
                  <span>20.1 / 30 km</span>
                  <span>Récompense : 500 XP</span>
                </div>
              </div>
            </div>

            <div className="upcoming">
              <h3 className="section-h alt">Expéditions à Venir</h3>
              <ul className="up-list">
                {upcoming.map((u, i) => (
                  <li key={i} className={`up-item kind-${u.kind}`}>
                    <div className="up-date">
                      <span className="up-num">{u.day}</span>
                      <span className="up-wk">{u.weekday}{u.monthShift ? ` · ${u.monthShift}` : ""}</span>
                    </div>
                    <div className="up-mid">
                      <div className="up-kind">
                        <KindIcon kind={u.kind} size={14} /> {kindLabel(u.kind)}
                      </div>
                      <div className="up-title">{u.title}</div>
                    </div>
                    <div className="up-time">
                      <div className="up-clock">{u.time}</div>
                      <div className="up-dur">{u.duration}</div>
                    </div>
                  </li>
                ))}
              </ul>
            </div>

            <div className="add-quest">
              <button className="btn-primary wide">＋ Inscrire une Nouvelle Épreuve</button>
            </div>
          </div>
        </div>

        <footer className="alm-foot">
          <span>fol. XXVIII</span>
          <span className="foot-cite">— consigné par main de Maître d'Armes —</span>
          <span>fol. XXIX</span>
        </footer>
      </div>
    </div>
  );
};

// ---------- ROOT ----------

export default function App() {
  const [view, setView] = useState("detail");
  return (
    <div className="root">
      <style>{styles}</style>
      <TopBar view={view} setView={setView} />
      <main className="main">
        {view === "detail" ? <WorkoutDetail /> : <Planner />}
      </main>
      <div className="vignette" />
    </div>
  );
}

// ---------- STYLES ----------

const styles = `
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cinzel+Decorative:wght@400;700;900&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=MedievalSharp&family=UnifrakturMaguntia&display=swap');

:root {
  --ink: #1a0f08;
  --ink-soft: #3a2817;
  --ink-faded: #6a4a30;
  --parchment: #efdcad;
  --parchment-light: #f6e8c4;
  --parchment-mid: #e4cf99;
  --parchment-dark: #c9b181;
  --blood: #7a1f1f;
  --blood-deep: #4a0f0f;
  --blood-bright: #9a2828;
  --gold: #b8893d;
  --gold-light: #d4a851;
  --gold-pale: #e8c878;
  --forest: #2d4a2b;
  --leather: #5a3e2b;
  --leather-dark: #2e1f12;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

.root {
  min-height: 100vh;
  font-family: 'EB Garamond', 'Garamond', serif;
  color: var(--ink);
  background:
    radial-gradient(ellipse at 50% -10%, #2a160c 0%, #160a08 50%, #0c0506 100%);
  position: relative;
  overflow-x: hidden;
  padding-bottom: 80px;
}

.vignette {
  position: fixed;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.5) 100%);
  z-index: 50;
}

/* ---------- TOPBAR ---------- */
.topbar {
  position: sticky;
  top: 0;
  z-index: 40;
  background:
    linear-gradient(180deg, #1a0d08 0%, #100706 100%);
  border-bottom: 1px solid #3a2817;
  box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}
.topbar-inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 14px 32px;
  display: flex;
  align-items: center;
  gap: 28px;
}
.brand {
  display: flex;
  align-items: center;
  gap: 14px;
}
.brand-kicker {
  font-family: 'Cinzel', serif;
  font-size: 9.5px;
  letter-spacing: 0.32em;
  color: var(--gold);
  text-transform: uppercase;
}
.brand-name {
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 30px;
  color: var(--gold-pale);
  line-height: 1;
  letter-spacing: 0.5px;
  text-shadow: 0 2px 0 rgba(0,0,0,0.7);
}
.nav {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 6px;
}
.nav-btn {
  background: transparent;
  border: 1px solid transparent;
  color: #b8a07a;
  padding: 9px 18px;
  font-family: 'Cinzel', serif;
  font-size: 12px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}
.nav-btn:hover {
  color: var(--gold-pale);
  border-color: var(--gold);
  background: rgba(184, 137, 61, 0.08);
}
.nav-btn.active {
  color: var(--ink);
  background: var(--gold-pale);
  border-color: var(--gold);
  box-shadow: 0 0 0 1px rgba(0,0,0,0.4) inset, 0 4px 12px rgba(184, 137, 61, 0.25);
}
.nav-marker { color: var(--blood); font-size: 14px; }
.nav-btn.active .nav-marker { color: var(--blood-deep); }
.nav-hero {
  margin-left: 18px;
  padding-left: 18px;
  border-left: 1px solid #3a2817;
  display: flex;
  gap: 14px;
  font-family: 'Cinzel', serif;
  font-size: 12px;
  color: var(--gold-pale);
}
.hero-streak { color: #d99a55; }
.topbar-rule {
  height: 4px;
  background:
    linear-gradient(90deg, transparent, var(--gold) 20%, var(--gold-pale) 50%, var(--gold) 80%, transparent);
  opacity: 0.4;
}

/* ---------- MAIN ---------- */
.main {
  max-width: 1280px;
  margin: 36px auto;
  padding: 0 32px;
}
.page {
  position: relative;
}

/* ---------- PARCHMENT BASE ---------- */
.parchment {
  position: relative;
  padding: 56px 64px 48px;
  background:
    radial-gradient(ellipse at 30% 20%, rgba(122, 31, 31, 0.08), transparent 50%),
    radial-gradient(ellipse at 80% 80%, rgba(74, 15, 15, 0.10), transparent 60%),
    radial-gradient(ellipse at 60% 40%, rgba(184, 137, 61, 0.06), transparent 70%),
    linear-gradient(180deg, var(--parchment-light) 0%, var(--parchment) 50%, var(--parchment-dark) 100%);
  border: 1px solid #8a6c3d;
  box-shadow:
    0 0 0 1px rgba(74, 15, 15, 0.15) inset,
    0 0 80px rgba(58, 21, 8, 0.35) inset,
    0 0 30px rgba(122, 31, 31, 0.18) inset,
    0 18px 50px rgba(0,0,0,0.6),
    0 8px 16px rgba(0,0,0,0.4);
  border-radius: 2px;
}
.parchment::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 12% 88%, rgba(74, 15, 15, 0.12), transparent 12%),
    radial-gradient(circle at 88% 12%, rgba(74, 15, 15, 0.10), transparent 10%),
    radial-gradient(circle at 70% 30%, rgba(58, 40, 23, 0.06), transparent 15%);
  pointer-events: none;
  border-radius: 2px;
}
.parchment::after {
  content: '';
  position: absolute;
  inset: 12px;
  border: 0.8px solid rgba(122, 31, 31, 0.28);
  border-radius: 1px;
  pointer-events: none;
}

/* corner ornaments */
.corners { position: absolute; inset: 0; pointer-events: none; }
.corner { position: absolute; color: var(--gold); }
.corner.tl { top: 18px; left: 18px; }
.corner.tr { top: 18px; right: 18px; }
.corner.bl { bottom: 18px; left: 18px; }
.corner.br { bottom: 18px; right: 18px; }

/* ---------- CHRONIQUE / WORKOUT DETAIL ---------- */
.chron-head {
  text-align: center;
  position: relative;
  padding-bottom: 28px;
  margin-bottom: 28px;
  border-bottom: 1px dashed rgba(58, 40, 23, 0.4);
}
.chron-kicker {
  font-family: 'Cinzel', serif;
  font-size: 11px;
  letter-spacing: 0.4em;
  color: var(--blood);
  text-transform: uppercase;
  margin-bottom: 10px;
}
.chron-title {
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 64px;
  color: var(--ink);
  line-height: 1;
  margin-bottom: 8px;
  text-shadow: 0 1px 0 rgba(255, 240, 200, 0.3);
}
.chron-sub {
  font-family: 'Cinzel', serif;
  font-size: 13px;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--ink-soft);
  margin-bottom: 14px;
}
.chron-flourish {
  display: flex;
  justify-content: center;
  margin: 8px 0;
  opacity: 0.9;
}
.chron-meta {
  font-family: 'EB Garamond', serif;
  font-size: 14.5px;
  font-style: italic;
  color: var(--ink-soft);
  margin-top: 14px;
  display: flex;
  gap: 10px;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
}
.chron-meta .dot { color: var(--gold); font-style: normal; }
.chron-meta svg { vertical-align: -3px; margin-right: 4px; color: var(--blood); }
.intensity { color: var(--blood); font-weight: 500; }

.chron-seal {
  position: absolute;
  top: -12px;
  right: -8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  transform: rotate(8deg);
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
}
.seal-caption {
  font-family: 'Cinzel', serif;
  font-size: 8.5px;
  letter-spacing: 0.2em;
  color: var(--blood-deep);
  text-transform: uppercase;
  margin-top: -4px;
}

/* SECTION H */
.section-h {
  font-family: 'Cinzel', serif;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.36em;
  text-transform: uppercase;
  color: var(--blood);
  margin-bottom: 18px;
  padding-bottom: 6px;
  border-bottom: 1px solid rgba(122, 31, 31, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
}
.section-h::before {
  content: '✦';
  color: var(--gold);
  font-size: 12px;
}
.section-h.alt::before { content: '❦'; }

/* ---------- STATS ---------- */
.stats-block { margin-bottom: 36px; }
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 28px;
}
.stat {}
.stat-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 6px;
}
.stat-label {
  font-family: 'Cinzel', serif;
  font-size: 11px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: var(--ink-soft);
}
.stat-value {
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 24px;
  color: var(--blood);
  line-height: 1;
}
.stat-max {
  font-family: 'Cinzel', serif;
  font-size: 11px;
  color: var(--ink-faded);
  margin-left: 2px;
}
.stat-track {
  position: relative;
  height: 12px;
  background:
    linear-gradient(180deg, #5a3e2b, #3a2817);
  border: 1px solid var(--leather-dark);
  border-radius: 2px;
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.5);
}
.stat-fill {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(180deg, var(--gold-pale), var(--gold-light) 50%, var(--gold) 100%);
  box-shadow:
    inset 0 1px 0 rgba(255, 240, 200, 0.6),
    inset 0 -1px 0 rgba(0,0,0,0.3);
}
.stat-pips {
  position: absolute;
  inset: 0;
  display: flex;
  pointer-events: none;
}
.pip {
  flex: 1;
  border-right: 1px solid rgba(46, 31, 18, 0.4);
}
.pip:last-child { border-right: none; }

/* ---------- TWO COL ---------- */
.two-col {
  display: grid;
  grid-template-columns: 1.55fr 1fr;
  gap: 48px;
  margin-top: 8px;
}

/* exercises */
.ex-list { display: flex; flex-direction: column; gap: 14px; }
.ex-row {
  display: grid;
  grid-template-columns: 60px 1fr;
  gap: 16px;
  padding: 14px 14px 14px 0;
  border-bottom: 1px solid rgba(58, 40, 23, 0.18);
}
.ex-row:last-child { border-bottom: none; }
.ex-numeral {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding-top: 2px;
}
.ex-numeral span {
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 30px;
  color: var(--blood);
  line-height: 1;
  text-shadow: 0 1px 0 rgba(255, 240, 200, 0.4);
}
.ex-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 6px;
}
.ex-name {
  font-family: 'Cinzel', serif;
  font-size: 16px;
  font-weight: 600;
  color: var(--ink);
  letter-spacing: 0.04em;
}
.ex-load {
  font-family: 'EB Garamond', serif;
  font-size: 16px;
  color: var(--ink-soft);
  white-space: nowrap;
}
.ex-sets, .ex-reps { font-weight: 600; color: var(--ink); }
.ex-mult, .ex-at { color: var(--gold); margin: 0 4px; }
.ex-weight {
  font-weight: 600;
  color: var(--blood);
  font-variant: small-caps;
}
.ex-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  font-size: 13.5px;
  color: var(--ink-soft);
}
.ex-rpe-label {
  font-family: 'Cinzel', serif;
  font-size: 9.5px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--ink-faded);
}
.rpe { display: inline-flex; gap: 3px; }
.rpe-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: rgba(58, 40, 23, 0.18);
  border: 1px solid rgba(58, 40, 23, 0.4);
}
.rpe-dot.on {
  background: var(--blood);
  border-color: var(--blood-deep);
  box-shadow: 0 0 4px rgba(122, 31, 31, 0.5);
}
.ex-note {
  font-style: italic;
  color: var(--ink-soft);
  font-size: 13.5px;
}

/* journal */
.col-side { display: flex; flex-direction: column; gap: 32px; }
.journal {}
.journal-text {
  font-family: 'EB Garamond', serif;
  font-size: 16px;
  font-style: italic;
  line-height: 1.7;
  color: var(--ink-soft);
  text-align: justify;
}
.dropcap {
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 56px;
  color: var(--blood);
  float: left;
  line-height: 0.85;
  padding: 6px 8px 0 0;
  font-style: normal;
  text-shadow: 0 1px 0 rgba(255, 240, 200, 0.5);
}
.signature {
  margin-top: 14px;
  text-align: right;
  font-style: italic;
  font-size: 12.5px;
  color: var(--ink-faded);
}

/* rewards */
.rewards {}
.reward-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px dotted rgba(58, 40, 23, 0.25);
  font-size: 14.5px;
}
.reward-row:last-child { border-bottom: none; }
.reward-row svg { color: var(--gold); flex-shrink: 0; }
.reward-row .feat-icon { color: var(--gold); font-size: 18px; }
.reward-label {
  font-family: 'Cinzel', serif;
  font-size: 11px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--ink-soft);
  flex: 1;
}
.reward-val {
  font-family: 'EB Garamond', serif;
  font-weight: 600;
  color: var(--blood);
}
.reward-feat .reward-val { font-style: italic; }

/* muscles */
.muscle-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.muscle-tag {
  font-family: 'Cinzel', serif;
  font-size: 10.5px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  padding: 5px 11px;
  background: rgba(122, 31, 31, 0.08);
  border: 1px solid rgba(122, 31, 31, 0.35);
  color: var(--blood-deep);
  border-radius: 1px;
}

/* foot */
.chron-foot {
  margin-top: 36px;
  text-align: center;
  border-top: 1px dashed rgba(58, 40, 23, 0.4);
  padding-top: 24px;
}
.actions {
  margin-top: 16px;
  display: flex;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}
.btn-primary, .btn-ghost {
  font-family: 'Cinzel', serif;
  font-size: 11.5px;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  padding: 11px 22px;
  cursor: pointer;
  transition: all 0.2s;
  border-radius: 1px;
}
.btn-primary {
  background:
    linear-gradient(180deg, #9a2828 0%, var(--blood) 50%, var(--blood-deep) 100%);
  color: var(--gold-pale);
  border: 1px solid var(--blood-deep);
  box-shadow:
    0 0 0 1px rgba(184, 137, 61, 0.4) inset,
    0 2px 0 rgba(0,0,0,0.3),
    0 4px 12px rgba(122, 31, 31, 0.4);
}
.btn-primary:hover {
  filter: brightness(1.1);
  box-shadow:
    0 0 0 1px rgba(232, 200, 120, 0.6) inset,
    0 2px 0 rgba(0,0,0,0.3),
    0 6px 16px rgba(122, 31, 31, 0.5);
}
.btn-primary.wide { width: 100%; padding: 14px 22px; }
.btn-ghost {
  background: transparent;
  color: var(--ink-soft);
  border: 1px solid rgba(58, 40, 23, 0.5);
}
.btn-ghost:hover {
  background: rgba(58, 40, 23, 0.08);
  border-color: var(--gold);
  color: var(--ink);
}

/* ---------- ALMANACH / PLANNER ---------- */
.book {
  padding: 48px 56px 36px;
  position: relative;
}
.book-spine {
  position: absolute;
  left: 50%;
  top: 24px;
  bottom: 24px;
  width: 26px;
  margin-left: -13px;
  background:
    linear-gradient(90deg,
      rgba(58, 40, 23, 0) 0%,
      rgba(58, 40, 23, 0.18) 30%,
      rgba(58, 40, 23, 0.35) 50%,
      rgba(58, 40, 23, 0.18) 70%,
      rgba(58, 40, 23, 0) 100%);
  pointer-events: none;
  z-index: 1;
}

.alm-head {
  text-align: center;
  margin-bottom: 24px;
  position: relative;
  z-index: 2;
}
.alm-kicker {
  font-family: 'Cinzel', serif;
  font-size: 11px;
  letter-spacing: 0.4em;
  color: var(--blood);
  text-transform: uppercase;
  margin-bottom: 8px;
}
.alm-title {
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 56px;
  color: var(--ink);
  line-height: 1;
  margin-bottom: 6px;
  text-shadow: 0 1px 0 rgba(255, 240, 200, 0.3);
}
.alm-hero {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-top: 22px;
  padding: 16px 24px;
  background: rgba(58, 40, 23, 0.06);
  border: 1px solid rgba(122, 31, 31, 0.18);
  border-radius: 1px;
}
.hero-stat { text-align: center; }
.hero-stat-num {
  display: block;
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 32px;
  color: var(--blood);
  line-height: 1;
}
.hero-stat-lab {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 9.5px;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: var(--ink-soft);
  margin-top: 6px;
}

/* legend bar */
.legend {
  display: flex;
  align-items: center;
  gap: 20px;
  margin: 24px 0 18px;
  padding: 10px 16px;
  border-top: 1px solid rgba(58, 40, 23, 0.25);
  border-bottom: 1px solid rgba(58, 40, 23, 0.25);
  flex-wrap: wrap;
  position: relative;
  z-index: 2;
}
.leg-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: 'Cinzel', serif;
  font-size: 10px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
}
.leg-forge { color: var(--blood); }
.leg-foulee { color: var(--forest); }
.leg-rest { color: var(--leather); }
.leg-spacer { flex: 1; }
.filter-btn {
  background: transparent;
  border: 1px solid rgba(58, 40, 23, 0.4);
  color: var(--ink-soft);
  padding: 5px 12px;
  font-family: 'Cinzel', serif;
  font-size: 10.5px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  border-radius: 1px;
  transition: all 0.15s;
}
.filter-btn:hover { background: rgba(184, 137, 61, 0.12); border-color: var(--gold); }

/* book body */
.alm-body {
  display: grid;
  grid-template-columns: 1.55fr 1fr;
  gap: 48px;
  position: relative;
  z-index: 2;
}

/* calendar */
.cal-page {}
.cal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  background: rgba(58, 40, 23, 0.12);
  padding: 4px;
  border: 1px solid rgba(58, 40, 23, 0.25);
}
.cal-weekday {
  font-family: 'Cinzel', serif;
  font-size: 10.5px;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  text-align: center;
  padding: 8px 0;
  color: var(--blood);
  background: rgba(239, 220, 173, 0.6);
  border-bottom: 1px solid rgba(122, 31, 31, 0.3);
}
.cal-cell {
  position: relative;
  background: var(--parchment-light);
  min-height: 84px;
  padding: 6px 6px 4px;
  border: 1px solid rgba(58, 40, 23, 0.12);
  font-size: 11.5px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  transition: all 0.15s;
}
.cal-cell:hover:not(.blank) {
  background: var(--parchment);
  border-color: var(--gold);
  cursor: pointer;
}
.cal-cell.blank { background: transparent; border: none; }
.cal-num {
  font-family: 'Cinzel', serif;
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-soft);
  align-self: flex-end;
}
.cal-mark {
  margin-top: auto;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 3px;
  padding: 4px 5px;
  border-radius: 1px;
  border-left: 2px solid;
}
.cal-icon { display: flex; }
.cal-title {
  font-family: 'EB Garamond', serif;
  font-size: 11.5px;
  line-height: 1.15;
  color: var(--ink);
  font-weight: 500;
}
.kind-forge .cal-mark {
  background: rgba(122, 31, 31, 0.12);
  border-color: var(--blood);
  color: var(--blood);
}
.kind-foulee .cal-mark {
  background: rgba(45, 74, 43, 0.12);
  border-color: var(--forest);
  color: var(--forest);
}
.kind-rest .cal-mark {
  background: rgba(90, 62, 43, 0.10);
  border-color: var(--leather);
  color: var(--leather);
}
.cal-cell.is-past { opacity: 0.6; }
.cal-cell.is-past .cal-mark { background: rgba(58, 40, 23, 0.05); }
.cal-cell.is-today {
  background: linear-gradient(180deg, var(--parchment-light), var(--parchment));
  border: 1.5px solid var(--gold);
  box-shadow: 0 0 0 1px rgba(184, 137, 61, 0.3), 0 0 16px rgba(184, 137, 61, 0.25);
  opacity: 1;
}
.today-pin {
  position: absolute;
  top: -8px;
  left: 6px;
  font-family: 'Cinzel', serif;
  font-size: 8.5px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  background: var(--blood);
  color: var(--gold-pale);
  padding: 2px 6px;
  border: 1px solid var(--gold);
  border-radius: 1px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* side page */
.side-page { display: flex; flex-direction: column; gap: 28px; }

.quest {
  position: relative;
  padding: 22px 24px 20px;
  background:
    linear-gradient(180deg, rgba(122, 31, 31, 0.08), rgba(74, 15, 15, 0.04));
  border: 1px solid rgba(122, 31, 31, 0.4);
  border-radius: 1px;
}
.quest::before {
  content: '';
  position: absolute;
  inset: 4px;
  border: 0.6px dashed rgba(122, 31, 31, 0.3);
  pointer-events: none;
}
.quest-banner {
  font-family: 'Cinzel', serif;
  font-size: 10.5px;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--gold-pale);
  background: var(--blood-deep);
  display: inline-block;
  padding: 5px 14px;
  margin-bottom: 12px;
  border: 1px solid var(--gold);
  border-radius: 1px;
}
.quest-title {
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 28px;
  color: var(--ink);
  line-height: 1.1;
  margin-bottom: 8px;
}
.quest-desc {
  font-family: 'EB Garamond', serif;
  font-style: italic;
  font-size: 14px;
  color: var(--ink-soft);
  line-height: 1.5;
  margin-bottom: 14px;
}
.quest-progress {}
.quest-bar {
  height: 14px;
  background: linear-gradient(180deg, #5a3e2b, #3a2817);
  border: 1px solid var(--leather-dark);
  border-radius: 2px;
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.5);
  margin-bottom: 6px;
}
.quest-fill {
  height: 100%;
  background:
    linear-gradient(180deg, #c44848, var(--blood) 60%, var(--blood-deep) 100%);
  box-shadow: inset 0 1px 0 rgba(255, 200, 180, 0.3);
}
.quest-stats {
  display: flex;
  justify-content: space-between;
  font-family: 'Cinzel', serif;
  font-size: 10.5px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--ink-soft);
}

/* upcoming */
.up-list { list-style: none; display: flex; flex-direction: column; gap: 10px; }
.up-item {
  display: grid;
  grid-template-columns: 56px 1fr auto;
  gap: 14px;
  align-items: center;
  padding: 11px 14px;
  background: var(--parchment-light);
  border: 1px solid rgba(58, 40, 23, 0.18);
  border-left: 3px solid;
  border-radius: 1px;
  transition: all 0.15s;
  cursor: pointer;
}
.up-item:hover {
  border-color: var(--gold);
  border-left-color: var(--gold);
  background: var(--parchment);
}
.up-item.kind-forge { border-left-color: var(--blood); }
.up-item.kind-foulee { border-left-color: var(--forest); }
.up-item.kind-rest { border-left-color: var(--leather); }
.up-date {
  text-align: center;
  border-right: 1px dashed rgba(58, 40, 23, 0.3);
  padding-right: 14px;
}
.up-num {
  display: block;
  font-family: 'UnifrakturMaguntia', serif;
  font-size: 26px;
  color: var(--blood);
  line-height: 1;
}
.up-wk {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 9.5px;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: var(--ink-soft);
  margin-top: 3px;
}
.up-mid {}
.up-kind {
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: 'Cinzel', serif;
  font-size: 9.5px;
  letter-spacing: 0.24em;
  text-transform: uppercase;
  color: var(--ink-soft);
  margin-bottom: 3px;
}
.kind-forge .up-kind { color: var(--blood); }
.kind-foulee .up-kind { color: var(--forest); }
.kind-rest .up-kind { color: var(--leather); }
.up-title {
  font-family: 'EB Garamond', serif;
  font-size: 16px;
  font-weight: 500;
  color: var(--ink);
  line-height: 1.2;
}
.up-time { text-align: right; }
.up-clock {
  font-family: 'Cinzel', serif;
  font-size: 14px;
  font-weight: 500;
  color: var(--ink);
}
.up-dur {
  font-family: 'EB Garamond', serif;
  font-style: italic;
  font-size: 12.5px;
  color: var(--ink-faded);
  margin-top: 2px;
}

.add-quest {}

/* foot */
.alm-foot {
  margin-top: 24px;
  padding-top: 14px;
  border-top: 1px dashed rgba(58, 40, 23, 0.4);
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: 'Cinzel', serif;
  font-size: 10px;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--ink-faded);
  position: relative;
  z-index: 2;
}
.foot-cite { font-style: italic; text-transform: none; letter-spacing: 0.04em; font-family: 'EB Garamond', serif; font-size: 12px; }

/* responsive-ish */
@media (max-width: 1100px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .two-col, .alm-body { grid-template-columns: 1fr; }
  .book-spine { display: none; }
  .alm-hero { grid-template-columns: repeat(2, 1fr); }
  .chron-title { font-size: 48px; }
  .alm-title { font-size: 42px; }
  .parchment, .book { padding: 36px 28px; }
}
`;
