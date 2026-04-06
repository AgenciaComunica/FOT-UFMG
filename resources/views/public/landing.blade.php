<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pós-Graduação em Fisioterapia em Ortopedia e Trauma – UFMG</title>
<link rel="icon" type="image/png" href="{{ asset('images/Icone-FTO.png') }}">
<style>
  :root {
    --green: #2565aa;
    --green-light: #3b7fc8;
    --green-dark: #2565aa;
    --gold: #c8a84b;
    --gold-light: #e6c97a;
    --white: #f8f6f1;
    --off-white: #ede9e0;
    --text: #1a1a1a;
    --text-muted: #5a5a5a;
    --font-sans: "Segoe UI", -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
    --font-serif: Georgia, "Times New Roman", serif;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  html { scroll-behavior: smooth; }

  body {
    font-family: var(--font-sans);
    background: var(--white);
    color: var(--text);
    overflow-x: hidden;
    text-rendering: optimizeLegibility;
  }

  /* ── TOP BAR ── */
  .topbar {
    background: var(--green-dark);
    color: var(--gold-light);
    text-align: center;
    padding: 10px 20px;
    font-size: 0.82rem;
    font-weight: 500;
    letter-spacing: 0.04em;
  }
  .topbar strong { color: #fff; }

  /* ── NAV ── */
  nav {
    position: sticky; top: 0; z-index: 100;
    background: rgba(248,246,241,0.95);
    backdrop-filter: blur(8px);
    border-bottom: 1px solid rgba(0,0,0,0.08);
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 5vw;
  }
  .nav-logo {
    display: block;
    width: min(200px, 42vw);
    height: auto;
    object-fit: contain;
  }
  .nav-brand {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .section-logo {
    height: 44px;
    width: auto;
    display: block;
  }
  .section-logo.centered {
    margin: 0 auto 20px;
  }
  .section-logo.inline {
    margin-bottom: 18px;
  }
  .curso-cta-brand {
    margin-top: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
  }
  .curso-cta-brand .section-logo {
    height: auto;
    max-width: 280px;
  }
  .curso-cta-brand .course-signup-btn {
    background: var(--green);
    border-radius: 999px;
    box-shadow: 0 10px 24px rgba(37,101,170,0.22);
  }
  .curso-cta-brand .course-signup-btn:hover {
    background: var(--green-light);
  }
  .nav-cta {
    background: var(--green);
    color: #fff;
    padding: 10px 22px;
    border-radius: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
  }
  .nav-cta:hover { background: var(--green-light); }
  .nav-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .nav-menu-button {
    display: none;
    width: 46px;
    height: 46px;
    border: 1px solid rgba(37,101,170,0.18);
    border-radius: 14px;
    background: #fff;
    color: var(--green-dark);
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
  }
  .nav-menu-button svg {
    width: 22px;
    height: 22px;
  }
  .nav-cta-secondary {
    background: transparent;
    color: var(--green-dark);
    border: 1px solid rgba(15, 61, 43, 0.16);
    padding: 10px 22px;
    border-radius: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: border-color 0.2s, background 0.2s, color 0.2s;
  }
  .nav-cta-secondary:hover {
    background: rgba(15, 61, 43, 0.05);
    border-color: rgba(15, 61, 43, 0.26);
  }
  .mobile-nav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.52);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.22s ease;
    z-index: 140;
  }
  .mobile-nav {
    position: fixed;
    top: 0;
    right: 0;
    width: min(86vw, 360px);
    height: 100vh;
    background: linear-gradient(180deg, #f7f9fc 0%, #edf3fb 100%);
    box-shadow: -18px 0 42px rgba(15, 23, 42, 0.18);
    transform: translateX(100%);
    transition: transform 0.24s ease;
    z-index: 150;
    padding: 24px 22px;
    display: flex;
    flex-direction: column;
    gap: 22px;
  }
  body.menu-open .mobile-nav-overlay {
    opacity: 1;
    pointer-events: auto;
  }
  body.menu-open .mobile-nav {
    transform: translateX(0);
  }
  body.menu-open {
    overflow: hidden;
  }
  .mobile-nav-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  .mobile-nav-close {
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 12px;
    background: rgba(37,101,170,0.08);
    color: var(--green-dark);
    cursor: pointer;
    font-size: 1.5rem;
    line-height: 1;
  }
  .mobile-nav-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .mobile-nav-link {
    display: block;
    padding: 14px 16px;
    border-radius: 16px;
    background: #fff;
    color: var(--green-dark);
    text-decoration: none;
    font-weight: 600;
    border: 1px solid rgba(37,101,170,0.12);
  }
  .mobile-nav-meta {
    color: var(--text-muted);
    font-size: 0.92rem;
    line-height: 1.65;
  }

  /* ── HERO ── */
  .hero {
    background: var(--green-dark);
    min-height: 100svh;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 60px 5vw 80px;
    position: relative;
    overflow: hidden;
    text-align: center;
  }
  .hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 60% 40%, rgba(200,168,75,0.12) 0%, transparent 60%),
                radial-gradient(ellipse at 20% 80%, rgba(45,145,104,0.15) 0%, transparent 50%);
    pointer-events: none;
  }

  .hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(200,168,75,0.15);
    border: 1px solid rgba(200,168,75,0.4);
    color: var(--gold-light);
    padding: 6px 18px;
    border-radius: 100px;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 32px;
    position: relative;
  }
  .hero-badge::before {
    content: '';
    width: 7px; height: 7px;
    background: var(--gold);
    border-radius: 50%;
    animation: pulse 2s infinite;
  }
  @keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50% { opacity:0.5; transform:scale(1.4); }
  }

  .hero h1 {
    font-family: var(--font-serif);
    font-size: clamp(2rem, 5.5vw, 4.2rem);
    color: #fff;
    line-height: 1.12;
    max-width: 820px;
    margin-bottom: 20px;
    position: relative;
  }
  .hero h1 em {
    font-style: normal;
    color: var(--gold-light);
  }

  .hero-sub {
    color: rgba(255,255,255,0.72);
    font-size: clamp(1rem, 2vw, 1.18rem);
    max-width: 560px;
    line-height: 1.65;
    margin-bottom: 40px;
    position: relative;
  }

  /* VIDEO WRAPPER */
  .video-wrapper {
    width: 100%;
    max-width: 780px;
    aspect-ratio: 16/9;
    border-radius: 16px;
    overflow: hidden;
    background: #000;
    border: 2px solid rgba(200,168,75,0.3);
    box-shadow: 0 32px 80px rgba(0,0,0,0.5);
    margin-bottom: 44px;
    position: relative;
  }
  .video-wrapper iframe,
  .video-wrapper video {
    width: 100%; height: 100%;
    display: block;
  }
  /* Placeholder while no real video */
  .video-placeholder {
    width: 100%; height: 100%;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 16px;
    background: linear-gradient(135deg, #163f73 0%, #2565aa 100%);
    color: rgba(255,255,255,0.5);
    font-size: 0.9rem;
  }
  .play-icon {
    width: 68px; height: 68px;
    background: rgba(200,168,75,0.9);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: transform 0.2s, background 0.2s;
  }
  .play-icon:hover { transform: scale(1.1); background: var(--gold); }
  .play-icon svg { margin-left: 5px; }

  /* CTA GROUP */
  .cta-group {
    display: flex; flex-direction: column; align-items: center; gap: 14px;
    position: relative;
    width: 100%;
  }
.btn-primary {
    background: var(--gold);
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
    padding: 18px 48px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s, transform 0.15s;
    letter-spacing: 0.01em;
    box-shadow: 0 8px 30px rgba(200,168,75,0.35);
    width: min(100%, 420px);
    text-align: center;
  }
  .btn-primary:hover { background: var(--gold-light); transform: translateY(-2px); }

  .btn-secondary {
    background: transparent;
    border: 1.5px solid rgba(255,255,255,0.3);
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
    font-weight: 500;
    padding: 14px 40px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    transition: border-color 0.2s, color 0.2s;
    width: min(100%, 420px);
    text-align: center;
  }
  .btn-secondary:hover { border-color: var(--gold); color: var(--gold-light); }

  .cta-note {
    color: rgba(255,255,255,0.45);
    font-size: 0.78rem;
    margin-top: 4px;
  }

  /* ── TRUST BAR ── */
  .trust-bar {
    background: var(--off-white);
    border-bottom: 1px solid rgba(0,0,0,0.07);
    padding: 28px 5vw;
    display: flex; align-items: center; justify-content: center;
    flex-wrap: wrap; gap: 32px 48px;
  }
  .trust-item {
    display: flex; align-items: center; gap: 10px;
    font-size: 0.88rem;
    color: var(--text-muted);
    font-weight: 500;
  }
  .trust-item svg { color: var(--green); flex-shrink: 0; }

  /* ── SECTION BASE ── */
  section { padding: 80px 5vw; }
  .section-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--green);
    margin-bottom: 12px;
  }
  .section-title {
    font-family: var(--font-serif);
    font-size: clamp(1.7rem, 3.5vw, 2.8rem);
    line-height: 1.2;
    margin-bottom: 16px;
    max-width: 680px;
  }
  .section-sub {
    color: var(--text-muted);
    font-size: 1.02rem;
    line-height: 1.7;
    max-width: 620px;
    margin-bottom: 48px;
  }

  /* ── SOBRE O CURSO ── */
  #curso { background: var(--white); }
  .curso-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 48px;
    align-items: start;
  }
  .curso-copy {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
  .curso-copy .section-title,
  .curso-copy .section-sub {
    max-width: none;
    margin-bottom: 0;
  }
  .curso-features { display: flex; flex-direction: column; gap: 20px; }
  .feature-item {
    display: flex; gap: 16px; align-items: flex-start;
    padding: 20px;
    background: var(--off-white);
    border-radius: 12px;
    border-left: 3px solid var(--green);
  }
  .feature-icon {
    width: 40px; height: 40px; border-radius: 8px;
    background: var(--green);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fff;
    font-size: 1.1rem;
  }
  .feature-item h3 { font-size: 0.95rem; font-weight: 600; margin-bottom: 4px; }
  .feature-item p { font-size: 0.87rem; color: var(--text-muted); line-height: 1.5; }

  /* ── EDITAL / OFERTA ── */
  #edital {
    background: var(--green-dark);
    color: #fff;
    text-align: center;
  }
  #edital .section-title { color: #fff; margin: 0 auto 16px; }
  #edital .section-sub { color: rgba(255,255,255,0.65); margin: 0 auto 48px; }
  #edital .section-label { color: var(--gold-light); }

  .offer-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(200,168,75,0.3);
    border-radius: 20px;
    padding: 48px;
    max-width: 600px;
    margin: 0 auto 32px;
    position: relative;
    overflow: hidden;
  }
  .offer-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light), var(--gold));
  }
  .offer-tag {
    display: inline-block;
    background: var(--gold);
    color: var(--green-dark);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 100px;
    margin-bottom: 24px;
  }
  .offer-title {
    font-family: var(--font-serif);
    font-size: 1.6rem;
    margin-bottom: 12px;
  }
  .offer-desc {
    color: rgba(255,255,255,0.65);
    font-size: 0.95rem;
    line-height: 1.7;
    margin-bottom: 32px;
  }

  /* ── CURRÍCULO ── */
  #curriculo { background: var(--off-white); }
  .nucleo-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 760px;
    margin: 0 auto;
  }
  .nucleo-item {
    background: var(--white);
    border-radius: 12px;
    padding: 24px 28px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .nucleo-item:hover { border-color: var(--green); box-shadow: 0 4px 20px rgba(37,101,170,0.12); }
  .nucleo-header {
    display: flex; justify-content: space-between; align-items: center;
    font-weight: 600; font-size: 1rem;
  }
  .nucleo-header span { color: var(--green); font-size: 0.85rem; font-weight: 500; }
  .nucleo-body {
    margin-top: 12px;
    font-size: 0.88rem;
    color: var(--text-muted);
    line-height: 1.6;
  }

  /* ── FAQ ── */
  #faq { background: var(--off-white); }
  .faq-list {
    max-width: 720px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 0 auto;
  }
  .faq-item {
    background: var(--white);
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.06);
  }
  .faq-q {
    padding: 20px 24px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex; justify-content: space-between; align-items: center;
    user-select: none;
  }
  .faq-q::after { content: '+'; font-size: 1.3rem; color: var(--green); }
  .faq-a {
    padding: 0 24px 20px;
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.65;
  }

  /* ── CTA FINAL ── */
  #contato {
    background: linear-gradient(135deg, var(--green-dark) 0%, #163f73 100%);
    color: #fff;
    text-align: center;
    padding: 100px 5vw;
  }
  #contato .section-title { color: #fff; margin: 0 auto 16px; }
  #contato .section-sub { color: rgba(255,255,255,0.6); margin: 0 auto 48px; }
  #contato .section-label { color: var(--gold-light); }
  .final-cta-group { display: flex; flex-direction: column; align-items: center; gap: 16px; }

  /* ── FOOTER ── */
  footer {
    background: #EDEDEC;
    color: #475569;
    padding: 36px 5vw;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
    font-size: 0.82rem;
  }
  footer a { color: #334155; text-decoration: none; }
  footer a:hover { color: var(--green-dark); }

  /* ── RESPONSIVE ── */
  @media (max-width: 860px) {
    nav {
      padding: 12px 4.5vw;
    }
    .nav-actions {
      display: none;
    }
    .nav-menu-button {
      display: inline-flex;
    }
    .hero {
      min-height: auto;
      padding: 46px 4.5vw 64px;
    }
    .hero-badge {
      margin-bottom: 24px;
      font-size: 0.7rem;
      padding: 6px 14px;
    }
    .hero h1 {
      font-size: clamp(2.2rem, 12vw, 3.6rem);
      line-height: 1.04;
      margin-bottom: 16px;
    }
    .hero-sub {
      font-size: 0.98rem;
      margin-bottom: 28px;
    }
    .video-wrapper {
      margin-bottom: 28px;
      border-radius: 18px;
      aspect-ratio: 16 / 10;
    }
    section {
      padding: 56px 4.5vw;
    }
    .trust-bar {
      padding: 22px 4.5vw;
      gap: 16px;
      justify-content: flex-start;
    }
    .trust-item {
      width: 100%;
      font-size: 0.9rem;
    }
    .curso-grid {
      grid-template-columns: 1fr;
      gap: 28px;
    }
    .curso-copy,
    .curso-features {
      gap: 16px;
    }
    .feature-item {
      padding: 18px;
      border-radius: 16px;
    }
    .offer-card {
      padding: 28px 20px;
      border-radius: 18px;
    }
    .nucleo-item,
    .faq-q,
    .faq-a {
      padding-left: 18px;
      padding-right: 18px;
    }
    .section-title,
    .section-sub {
      max-width: none;
    }
    footer {
      padding: 28px 4.5vw 32px;
      flex-direction: column;
      align-items: flex-start;
    }
    footer > div:last-child {
      flex-direction: column;
      gap: 10px;
    }
  }

  @media (max-width: 520px) {
    .topbar {
      padding: 10px 14px;
      font-size: 0.74rem;
      line-height: 1.5;
    }
    .nav-logo {
      width: min(172px, 46vw);
      height: auto;
    }
    .btn-primary,
    .btn-secondary {
      width: 100%;
      padding: 16px 18px;
      font-size: 0.96rem;
    }
    .cta-group {
      gap: 12px;
    }
    .section-label {
      font-size: 0.68rem;
      margin-bottom: 10px;
    }
    .section-title {
      font-size: clamp(1.75rem, 9vw, 2.4rem);
    }
    .section-sub,
    .feature-item p,
    .nucleo-body,
    .faq-a {
      font-size: 0.94rem;
      line-height: 1.65;
    }
    .curso-cta-brand .section-logo {
      max-width: 220px;
    }
  }

  /* ── ANIMATIONS ── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .hero h1, .hero-sub, .video-wrapper, .cta-group {
    animation: fadeUp 0.7s ease both;
  }
  .hero-sub { animation-delay: 0.1s; }
  .video-wrapper { animation-delay: 0.2s; }
  .cta-group { animation-delay: 0.35s; }
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  @if ($temEditalAberto)
    🎓 <strong>Edital Aberto</strong> — Condições especiais para inscrições nos primeiros dias. <strong>Vagas limitadas.</strong>
  @else
    🎓 <strong>Portal de Inscrições</strong> — Acompanhe editais, consulte inscrições e acesse o processo seletivo da especialização. <strong>Vagas limitadas.</strong>
  @endif
</div>

<!-- NAV -->
<nav>
  <div class="nav-brand">
    <img class="nav-logo" src="{{ asset('images/Logo-FTO.png') }}" alt="FTO UFMG" width="200" height="51" decoding="async">
  </div>
  <div class="nav-actions">
    <a href="{{ $editalUrl }}" class="nav-cta-secondary">Acesse o Edital</a>
    <a href="{{ $inscricaoUrl }}" class="nav-cta">Inscreva-se</a>
  </div>
  <button type="button" class="nav-menu-button" aria-label="Abrir menu" data-menu-open>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
      <path d="M4 7h16M4 12h16M4 17h16"/>
    </svg>
  </button>
</nav>

<div class="mobile-nav-overlay" data-menu-close></div>
<aside class="mobile-nav" aria-hidden="true" data-mobile-nav>
  <div class="mobile-nav-top">
    <img class="nav-logo" src="{{ asset('images/Logo-FTO.png') }}" alt="FTO UFMG" width="200" height="51" decoding="async">
    <button type="button" class="mobile-nav-close" aria-label="Fechar menu" data-menu-close>×</button>
  </div>
  <div class="mobile-nav-links">
    <a href="{{ $editalUrl }}" class="mobile-nav-link">Acesse o Edital</a>
    <a href="{{ $inscricaoUrl }}" class="mobile-nav-link">Inscreva-se</a>
    <a href="{{ $mainSiteUrl }}" class="mobile-nav-link">Conheça o curso</a>
    <a href="{{ $loginUrl }}" class="mobile-nav-link">Área Restrita</a>
  </div>
  <p class="mobile-nav-meta">
    Navegação simplificada para o processo seletivo, consulta do edital e inscrição no curso.
  </p>
</aside>

<!-- HERO -->
<section class="hero">
  <div class="hero-badge">{{ $temEditalAberto ? 'Edital publicado · Inscrições abertas' : 'Portal público · Acompanhe o processo seletivo' }}</div>

  <h1>
    Pós-Graduação em<br>
    <em>Fisioterapia em Ortopedia<br>e Trauma</em> — UFMG
  </h1>

  <p class="hero-sub">
    Curso online. Formação de excelência com o rigor acadêmico da UFMG,
    para fisioterapeutas que querem elevar sua prática clínica ao mais alto padrão.
  </p>

  <!-- VIDEO (substitua o src pelo link real do vídeo do Prof. Renan) -->
  <div class="video-wrapper">
    <!-- 
      OPÇÃO 1 — YouTube embed:
      <iframe src="https://www.youtube.com/embed/SEU_VIDEO_ID?autoplay=1&mute=1" frameborder="0" allowfullscreen allow="autoplay"></iframe>
      
      OPÇÃO 2 — Vídeo direto:
      <video src="URL_DO_VIDEO.mp4" autoplay muted loop playsinline></video>
    -->
    <div class="video-placeholder">
      <div class="play-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
      </div>
      <span>Mensagem do Prof. Renan Resende</span>
    </div>
  </div>

  <!-- CTAs -->
  <div class="cta-group">
    <a href="{{ $inscricaoUrl }}" class="btn-primary">
      Inscreva-se Agora
    </a>
    <a href="{{ $mainSiteUrl }}" class="btn-secondary">
      Conheça o curso
    </a>
    <p class="cta-note">Condições especiais disponíveis apenas nos primeiros dias</p>
  </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
  <div class="trust-item">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3.33 1.67 8.67 1.67 12 0v-5"/></svg>
    Certificado pela UFMG
  </div>
  <div class="trust-item">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
    Curso online
  </div>
  <div class="trust-item">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    18 meses de duração
  </div>
  <div class="trust-item">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Corpo docente UFMG
  </div>
  <div class="trust-item">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    Prática baseada em evidências
  </div>
</div>

<!-- SOBRE O CURSO -->
<section id="curso">
  <p class="section-label">O Curso</p>
  <div class="curso-grid">
    <div class="curso-copy">
      <h2 class="section-title">Formação de excelência com flexibilidade real</h2>
      <p class="section-sub">
        O único programa de pós-graduação lato sensu em Fisioterapia Ortopédica da UFMG, 
        em formato de curso online — com todo o rigor acadêmico que você espera da universidade pública federal mais renomada de Minas Gerais.
      </p>
      <p style="font-size:1rem; line-height:1.75; color:var(--text-muted);">
        O curso é voltado para fisioterapeutas que desejam aprofundar seus conhecimentos 
        na avaliação e no tratamento de disfunções musculoesqueléticas decorrentes de traumas, 
        processos degenerativos e alterações relacionadas à sobrecarga mecânica.
      </p>
      <p style="font-size:1rem; line-height:1.75; color:var(--text-muted);">
        A proposta formativa enfatiza biomecânica, cinesiopatologia e mecanismos de dor, 
        com foco na prática clínica baseada em evidências — estruturada em aulas síncronas, 
        assíncronas e atividades práticas supervisionadas.
      </p>
    </div>
    <div class="curso-features">
      <div class="feature-item">
        <div class="feature-icon">🎯</div>
        <div>
          <h3>Avaliação funcional completa</h3>
          <p>Aprenda a realizar diagnósticos fisioterapêuticos precisos e sistematizados.</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">💡</div>
        <div>
          <h3>Prática baseada em evidências</h3>
          <p>Currículo atualizado com as melhores evidências da fisioterapia ortopédica contemporânea.</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">🏛️</div>
        <div>
          <h3>Certificação UFMG</h3>
          <p>Diploma reconhecido pela Universidade Federal de Minas Gerais com apoio da DEDD.</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">📱</div>
        <div>
          <h3>Flexibilidade total</h3>
          <p>Curso online com aulas síncronas e assíncronas adaptadas à sua rotina clínica.</p>
        </div>
      </div>
    </div>
  </div>
  <div class="curso-cta-brand">
    <img class="section-logo" src="{{ asset('images/Logo-FTO.png') }}" alt="FTO UFMG" width="200" height="51" decoding="async">
    <a href="{{ $inscricaoUrl }}" class="btn-primary course-signup-btn" style="width: min(100%, 320px);">Faça sua inscrição</a>
  </div>
</section>

<!-- EDITAL / OFERTA -->
<section id="edital">
  <p class="section-label">{{ $temEditalAberto ? 'Edital Aberto' : 'Portal de Editais' }}</p>
  <h2 class="section-title">Condições especiais nos primeiros dias</h2>
  <p class="section-sub">O edital foi publicado. Aproveite as condições exclusivas disponíveis apenas para os primeiros inscritos.</p>

  <div class="offer-card">
    <span class="offer-tag">⚡ Oferta por tempo limitado</span>
    <h3 class="offer-title">Inscrição com condição especial</h3>
    <p class="offer-desc">
      Acesse o edital completo para conferir os requisitos, datas, valores e condições diferenciadas 
      para quem se inscrever nos primeiros dias do processo seletivo.
    </p>
    <a href="{{ $inscricaoUrl }}" class="btn-primary" style="display:block; width:100%;">
      Inscreva-se Agora
    </a>
  </div>

  <p style="color:rgba(255,255,255,0.4); font-size:0.82rem;">Vagas limitadas. Inscrições sujeitas às condições do edital vigente.</p>
</section>

<!-- CURRÍCULO -->
<section id="curriculo">
  <p class="section-label">Estrutura Curricular</p>
  <h2 class="section-title">O que você vai aprender</h2>
  <p class="section-sub">Currículo organizado em núcleos de conhecimento para uma formação técnica e estratégica completa.</p>

  <div class="nucleo-list">
    <div class="nucleo-item">
      <div class="nucleo-header">
        Núcleo de Fundamentos Avançados
        <span>150h</span>
      </div>
      <div class="nucleo-body">
        Cinesiologia Clínica, Raciocínio Clínico, Ciência da Dor, Prática Baseada em Evidências e Metodologia para TCC.
      </div>
    </div>
    <div class="nucleo-item">
      <div class="nucleo-header">
        Núcleo de Métodos de Avaliação
        <span>45h</span>
      </div>
      <div class="nucleo-body">
        Princípios de Avaliação em Ortopedia e Avaliação Clínica do Movimento — incluindo análise bidimensional com recursos acessíveis.
      </div>
    </div>
    <div class="nucleo-item">
      <div class="nucleo-header">
        Núcleo de Conhecimento Aplicado
        <span>Maior carga horária</span>
      </div>
      <div class="nucleo-body">
        Reabilitação de membros inferiores e superiores, coluna vertebral, dor orofacial, dor crônica, traumas musculoesqueléticos, cinesioterapia, terapia manual e agentes eletrofísicos.
      </div>
    </div>
    <div class="nucleo-item">
      <div class="nucleo-header">
        Núcleo de Tecnologia, Gestão e Liderança
        <span>Formação estratégica</span>
      </div>
      <div class="nucleo-body">
        Conteúdos de gestão, inovação e liderança para completar sua formação técnica com visão estratégica de carreira.
      </div>
    </div>
  </div>

  <div class="curso-cta-brand">
    <img class="section-logo" src="{{ asset('images/Logo-FTO.png') }}" alt="FTO UFMG" width="200" height="51" decoding="async">
    <a href="{{ $inscricaoUrl }}" class="btn-primary course-signup-btn" style="width: min(100%, 320px);">Faça sua inscrição</a>
  </div>
</section>

<!-- FAQ -->
<section id="faq">
  <p class="section-label">Dúvidas Frequentes</p>
  <h2 class="section-title">Perguntas frequentes</h2>
  <p class="section-sub">Esclareça os principais pontos antes de realizar sua inscrição.</p>

  <div class="faq-list">
    <div class="faq-item">
      <div class="faq-q">O curso é reconhecido pelo MEC?</div>
      <div class="faq-a">Sim. É um curso de pós-graduação lato sensu vinculado à UFMG, com apoio da Diretoria de Educação à Distância e Educação Digital (DEDD) da universidade.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Qual a duração e carga horária?</div>
      <div class="faq-a">O curso tem duração de 18 meses com carga horária distribuída em núcleos de conhecimento que combinam aulas síncronas e assíncronas.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Precisa ir presencialmente em algum momento?</div>
      <div class="faq-a">Não. O curso foi estruturado em formato online para que você possa conciliar a especialização com sua rotina clínica de qualquer lugar do Brasil.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Quem pode se inscrever?</div>
      <div class="faq-a">Fisioterapeutas graduados que desejam aprofundar sua atuação em ortopedia e trauma. Consulte o edital completo para requisitos específicos do processo seletivo.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Quais são as condições especiais dos primeiros dias?</div>
      <div class="faq-a">As condições especiais estão detalhadas no edital. Acesse a plataforma para conferir prazos e valores aplicáveis às primeiras inscrições.</div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section id="contato">
  <p class="section-label">Garanta sua vaga</p>
  <h2 class="section-title">Pronto para dar o próximo passo na sua carreira?</h2>
  <p class="section-sub">O edital está aberto. Condições especiais disponíveis apenas para os primeiros inscritos — não perca o prazo.</p>

  <div class="final-cta-group">
    <a href="{{ $inscricaoUrl }}" class="btn-primary">
      Inscreva-se Agora
    </a>
    <p style="color:rgba(255,255,255,0.4); font-size:0.82rem; margin-top:8px;">
      Vagas limitadas · UFMG · Curso online
    </p>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div style="display:flex; align-items:center; gap:14px;">
    <img class="section-logo" src="{{ asset('images/Logo-FTO.png') }}" alt="FTO UFMG" width="200" height="51" decoding="async" style="height:40px;">
    <span>© 2026 FTO UFMG — Fisioterapia em Ortopedia e Trauma</span>
  </div>
  <div style="display:flex; gap:20px;">
    <a href="{{ $mainSiteUrl }}">Site completo</a>
    <a href="{{ $loginUrl }}">Área Restrita</a>
    <a href="{{ $portalUrl }}">Plataforma</a>
  </div>
</footer>

<script>
  // Simple FAQ toggle
  document.querySelectorAll('.faq-q').forEach(q => {
    q.addEventListener('click', () => {
      const item = q.parentElement;
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.toggle('open');
    });
  });

  // Add open state styles dynamically
  const style = document.createElement('style');
  style.textContent = `.faq-item.open .faq-q::after { content: '−'; } .faq-item:not(.open) .faq-a { display: none; }`;
  document.head.appendChild(style);

  // Nucleo accordion
  document.querySelectorAll('.nucleo-item').forEach(item => {
    item.querySelector('.nucleo-header').addEventListener('click', () => {
      item.classList.toggle('active');
    });
  });

  const body = document.body;
  const menuOpenButton = document.querySelector('[data-menu-open]');
  const menuCloseButtons = document.querySelectorAll('[data-menu-close]');

  const openMenu = () => body.classList.add('menu-open');
  const closeMenu = () => body.classList.remove('menu-open');

  if (menuOpenButton) {
    menuOpenButton.addEventListener('click', openMenu);
  }

  menuCloseButtons.forEach(button => {
    button.addEventListener('click', closeMenu);
  });

  document.querySelectorAll('.mobile-nav-link').forEach(link => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeMenu();
    }
  });
</script>
</body>
</html>
