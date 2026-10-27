<div x-data="{ faqOpen: false, activeFaq: 1 }">
  <style>
    :root {
      --navy: #103b73;
      --blue: #1764dc;
      --blue-2: #078fd0;
      --cyan: #65d9f4;
      --muted: #6680a5;
      --line: #dce8f7;
      --white: rgba(255,255,255,.88);
    }
    .login-page {
      min-height: 100vh;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--navy);
      background:
        radial-gradient(ellipse at 8% 12%, rgba(117, 213, 255, .42), transparent 27%),
        radial-gradient(ellipse at 96% 82%, rgba(105, 192, 255, .35), transparent 28%),
        linear-gradient(135deg, #f7fbff 0%, #edf7ff 48%, #f7fbff 100%);
      overflow-x: hidden;
      position: relative;
    }
    .login-page::before, .login-page::after {
      content: "";
      position: fixed;
      z-index: 0;
      pointer-events: none;
      border-radius: 50%;
      filter: blur(1px);
      animation: float 13s ease-in-out infinite;
    }
    .login-page::before {
      width: 430px; height: 430px; left: -210px; top: -220px;
      background: linear-gradient(135deg, rgba(102,205,255,.26), rgba(255,255,255,.04));
      border: 1px solid rgba(255,255,255,.65);
    }
    .login-page::after {
      width: 520px; height: 520px; right: -300px; bottom: -300px;
      background: linear-gradient(145deg, rgba(91,222,246,.23), rgba(53,145,255,.1));
      border: 1px solid rgba(255,255,255,.55);
      animation-delay: -5s;
    }
    .page {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 42px 24px;
      position: relative;
      z-index: 1;
    }
    .login-shell {
      width: min(1120px, 100%);
      min-height: 650px;
      display: grid;
      grid-template-columns: 1fr .96fr;
      gap: 22px;
      padding: 22px;
      border: 1px solid rgba(255,255,255,.92);
      border-radius: 28px;
      background: rgba(255,255,255,.48);
      box-shadow: 0 28px 80px rgba(36, 93, 151, .13), inset 0 1px rgba(255,255,255,.9);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      overflow: hidden;
      position: relative;
      animation: shell-in .85s cubic-bezier(.2,.8,.2,1) both;
    }
    .brand-panel {
      position: relative;
      padding: 42px 28px 30px 42px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      isolation: isolate;
    }
    .brand-panel::before {
      content: "";
      position: absolute;
      width: 420px; height: 420px;
      border-radius: 50%;
      right: -190px; top: 200px;
      background: linear-gradient(135deg, rgba(125,218,255,.16), rgba(255,255,255,0));
      border: 1px solid rgba(255,255,255,.7);
      z-index: -1;
    }
    .logo {
      width: 250px;
      max-width: 100%;
      height: auto;
      margin-bottom: 34px;
      filter: drop-shadow(0 8px 10px rgba(16,59,115,.08));
      animation: logo-in .8s .15s both;
      display: block;
    }
    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #1b75bf;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: .14em;
      text-transform: uppercase;
      margin-bottom: 14px;
    }
    .eyebrow::before {
      content: "";
      width: 23px; height: 2px; border-radius: 99px; background: #f18a32;
    }
    .brand-title {
      font-size: clamp(30px, 3vw, 43px);
      line-height: 1.12;
      letter-spacing: -.045em;
      margin: 0;
      max-width: 460px;
      font-weight: 800;
      color: #10396e;
    }
    .brand-description {
      margin: 17px 0 30px;
      color: #5c769b;
      font-size: 15px;
      line-height: 1.75;
      max-width: 410px;
    }
    .features { display: grid; gap: 18px; margin-top: 3px; }
    .feature {
      display: flex;
      align-items: center;
      gap: 14px;
      animation: rise-in .65s both;
    }
    .feature:nth-child(2) { animation-delay: .12s; }
    .feature:nth-child(3) { animation-delay: .24s; }
    .feature-icon {
      width: 47px; height: 47px; flex: 0 0 47px;
      display: grid; place-items: center;
      border-radius: 15px;
      color: var(--blue);
      background: linear-gradient(145deg, rgba(255,255,255,.95), rgba(220,242,255,.8));
      border: 1px solid rgba(255,255,255,.95);
      box-shadow: 0 8px 20px rgba(28,100,170,.08);
    }
    .feature-icon svg { width: 23px; height: 23px; }
    .feature strong { display: block; font-size: 13px; margin-bottom: 4px; color: #10396e; }
    .feature span { display: block; color: #6b84a6; font-size: 12px; line-height: 1.55; }
    .chart-art {
      position: absolute;
      bottom: 5px; left: 10px;
      width: min(96%, 490px);
      opacity: .58;
      z-index: -1;
      animation: chart-in 1.2s .3s both;
      pointer-events: none;
    }
    .form-panel {
      align-self: stretch;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px clamp(25px, 4.3vw, 58px);
      border: 1px solid rgba(255,255,255,.96);
      border-radius: 23px;
      background: rgba(255,255,255,.86);
      box-shadow: 0 20px 55px rgba(42, 96, 152, .09), inset 0 1px rgba(255,255,255,1);
      animation: form-in .8s .12s both;
    }
    .form-inner { width: 100%; max-width: 410px; }
    .mobile-logo { display: none; }
    .form-kicker {
      color: #2784c4; font-size: 11px; font-weight: 800;
      letter-spacing: .14em; text-transform: uppercase; margin-bottom: 11px;
    }
    .form-title {
      font-size: clamp(28px, 2.5vw, 35px);
      line-height: 1.2; letter-spacing: -.04em;
      margin: 0 0 10px; color: #123c75; font-weight: 800;
    }
    .form-subtitle { margin: 0 0 32px; color: #7186a5; font-size: 14px; }
    .field { margin-bottom: 20px; }
    .field-label { display: block; font-size: 12px; font-weight: 750; margin-bottom: 9px; color: #28466f; }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
      color: #8198b8; display: grid; place-items: center; pointer-events: none;
    }
    .input-icon svg, .toggle-password svg { width: 19px; height: 19px; }
    .login-input {
      width: 100%; height: 51px; border-radius: 12px;
      border: 1px solid #d9e5f4; outline: none;
      background: rgba(255,255,255,.88); color: #193e70;
      padding: 0 46px; font: inherit; font-size: 13px;
      box-shadow: 0 2px 5px rgba(22,72,125,.02), inset 0 1px 2px rgba(30,70,120,.02);
      transition: border-color .2s, box-shadow .2s, background .2s, transform .2s;
      box-sizing: border-box;
    }
    .login-input::placeholder { color: #9aabc2; }
    .login-input:focus {
      border-color: #4c9df1;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(42,132,233,.11);
    }
    .login-input.has-error {
      border-color: #ef4444;
      background: #fff8f8;
    }
    .toggle-password {
      position: absolute; right: 9px; top: 50%; transform: translateY(-50%);
      width: 34px; height: 34px; border: 0; border-radius: 9px;
      background: transparent; color: #8097b8; cursor: pointer;
      display: grid; place-items: center; transition: background .2s, color .2s;
    }
    .toggle-password:hover { background: #edf6ff; color: var(--blue); }
    .form-options {
      display: flex; justify-content: space-between; align-items: center;
      gap: 12px; margin: 3px 0 27px; font-size: 12px;
    }
    .remember { display: inline-flex; align-items: center; gap: 8px; color: #60799b; cursor: pointer; }
    .remember input { accent-color: var(--blue); width: 15px; height: 15px; margin: 0; }
    .help-link { color: #1E60D5; text-decoration: none; font-weight: 600; cursor: pointer; background: transparent; border: 0; padding: 0; font: inherit; font-size: 12px; transition: color .2s; }
    .help-link:hover { color: #0F2F57; text-decoration: underline; }

    /* Google Material Design 3 (M3) Filled Button */
    .submit-btn {
      width: 100%;
      height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: 0;
      border-radius: 9999px; /* M3 Pill Shape */
      cursor: pointer;
      color: #ffffff; /* M3 On-Primary */
      background-color: #1E60D5; /* M3 Primary */
      font-family: inherit;
      font-size: 14px; /* M3 Label Large */
      font-weight: 600;
      letter-spacing: 0.1px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08); /* M3 Elevation 0 */
      transition: background-color 0.2s cubic-bezier(0.2, 0, 0, 1),
                  box-shadow 0.2s cubic-bezier(0.2, 0, 0, 1),
                  transform 0.15s cubic-bezier(0.2, 0, 0, 1),
                  opacity 0.2s cubic-bezier(0.2, 0, 0, 1);
      position: relative;
      outline: none;
      user-select: none;
    }
    .submit-btn:hover:not(:disabled) {
      background-color: #1554BE; /* M3 State Layer +8% */
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24); /* M3 Elevation 1 */
    }
    .submit-btn:focus-visible {
      outline: 2px solid #1E60D5;
      outline-offset: 2px;
    }
    .submit-btn:active:not(:disabled) {
      background-color: #0F2F57; /* M3 Pressed State Layer */
      transform: scale(0.99);
    }
    .submit-btn:disabled {
      background-color: rgba(30, 96, 213, 0.45);
      color: rgba(255, 255, 255, 0.7);
      cursor: not-allowed;
      box-shadow: none;
    }
    [wire\:loading] {
      display: none !important;
    }
    .loading-spinner {
      width: 18px;
      height: 18px;
      animation: spin 0.9s linear infinite;
    }
    .status-message {
      margin-top: 8px; padding: 9px 12px; border-radius: 10px; font-size: 12px; line-height: 1.45;
    }
    .status-message.error { color: #a33b36; background: #fff2f0; border: 1px solid #ffd8d2; }
    .form-footer {
      display: flex; align-items: center; justify-content: center; gap: 9px;
      border-top: 1px solid #e8eef7; margin-top: 28px; padding-top: 22px;
      color: #8295b0; font-size: 11px;
    }
    .footer-dot { width: 4px; height: 4px; background: #f19a4a; border-radius: 50%; }
    .secure-note { display: flex; justify-content: center; align-items: center; gap: 6px; color: #9aaac0; font-size: 10px; margin-top: 15px; }
    .secure-note svg { width: 13px; height: 13px; color: #3e9bca; }
    .orb { position: absolute; border: 1px solid rgba(255,255,255,.85); border-radius: 50%; pointer-events: none; }
    .orb.one { width: 48px; height: 48px; top: 42px; left: 41%; animation: float 8s ease-in-out infinite; }
    .orb.two { width: 20px; height: 20px; right: 38px; bottom: 50px; border-radius: 6px; transform: rotate(45deg); animation: float 7s ease-in-out infinite reverse; }

    /* Quick Test Account Selector (Local Environment) */
    .demo-section {
      margin-top: 24px;
      padding-top: 18px;
      border-top: 1px solid #e8eef7;
    }
    .demo-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 9px;
    }
    .demo-title {
      font-size: 10px;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: #8298b4;
    }
    .demo-hint {
      font-size: 10px;
      color: #9aaac0;
      font-family: monospace;
    }
    .demo-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 6px;
    }
    .demo-btn {
      border: 1px solid #dce8f7;
      background: rgba(255,255,255,.8);
      border-radius: 9px;
      padding: 6px 8px;
      text-align: left;
      cursor: pointer;
      transition: all .2s;
    }
    .demo-btn:hover {
      border-color: #4c9df1;
      background: #edf6ff;
    }
    .demo-btn strong {
      display: block;
      font-size: 11px;
      color: #103b73;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .demo-btn span {
      display: block;
      font-size: 9px;
      color: #6b84a6;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    @keyframes shell-in { from { opacity: 0; transform: translateY(22px) scale(.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
    @keyframes form-in { from { opacity: 0; transform: translateX(18px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes logo-in { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes rise-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes chart-in { from { opacity: 0; transform: translateY(20px); } to { opacity: .58; transform: translateY(0); } }
    @keyframes float { 0%,100% { transform: translate3d(0,0,0) rotate(0deg); } 50% { transform: translate3d(0,-13px,0) rotate(3deg); } }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    @media (max-width: 850px) {
      .page { padding: 20px 14px; }
      .login-shell { grid-template-columns: 1fr; max-width: 510px; min-height: auto; padding: 14px; gap: 12px; border-radius: 23px; }
      .brand-panel { padding: 24px 20px 14px; }
      .logo { width: 190px; margin-bottom: 22px; }
      .brand-title { font-size: 28px; }
      .brand-description { margin-bottom: 22px; font-size: 13px; }
      .features { grid-template-columns: 1fr; gap: 12px; }
      .feature-icon { width: 40px; height: 40px; flex-basis: 40px; border-radius: 12px; }
      .chart-art { width: 72%; left: auto; right: -40px; bottom: -15px; opacity: .28; }
      .form-panel { padding: 28px 20px 22px; }
      .form-subtitle { margin-bottom: 24px; }
      .orb.one { top: 30px; right: 24px; left: auto; }
      .orb.two { display: none; }
      .demo-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 420px) {
      .brand-panel { padding: 18px 14px 10px; }
      .brand-title { font-size: 24px; }
      .brand-description { max-width: 290px; }
      .feature span { font-size: 11px; }
      .form-panel { padding: 22px 14px 18px; }
      .form-options { align-items: flex-start; flex-direction: column; gap: 13px; }
      .help-link { padding-left: 2px; }
      .form-footer { font-size: 10px; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; }
    }
  </style>

  <div class="login-page">
    <main class="page">
      <section class="login-shell" aria-label="Login SIGMA">
        <span class="orb one"></span><span class="orb two"></span>

        {{-- Left Brand Panel --}}
        <section class="brand-panel">
          {{-- Official SIGMA Logo --}}
          <img
            src="{{ asset('images/logo-sigma.svg') }}"
            alt="SIGMA — Sistem Informasi & Monitoring Anggaran LAZ Al Azhar"
            class="logo"
          />

          <div class="eyebrow">Sistem internal LAZ Al Azhar</div>
          <h1 class="brand-title">Kelola Anggaran dengan Lebih Terarah</h1>
          <p class="brand-description">Satu ruang kerja untuk memantau anggaran, menata pengajuan, dan menjaga setiap proses tetap transparan.</p>

          <div class="features">
            <div class="feature">
              <div class="feature-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 3 20 6v5c0 5-3.4 8.2-8 10-4.6-1.8-8-5-8-10V6l8-3Z"/>
                  <path d="m8.5 12 2.2 2.2 4.8-5"/>
                </svg>
              </div>
              <div>
                <strong>Transparansi Anggaran</strong>
                <span>Catatan anggaran tersusun jelas dan mudah ditelusuri.</span>
              </div>
            </div>

            <div class="feature">
              <div class="feature-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="13" rx="2"/>
                  <path d="M7 20h10M12 17v3M7 13v-2M11 13V8M15 13v-4"/>
                </svg>
              </div>
              <div>
                <strong>Monitoring Real-time</strong>
                <span>Pantau progres dan kondisi anggaran dengan praktis.</span>
              </div>
            </div>

            <div class="feature">
              <div class="feature-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M7 3h10a2 2 0 0 1 2 2v5"/>
                  <path d="M7 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5"/>
                  <path d="M9 8h6M9 12h5M9 16h2"/>
                  <circle cx="17.5" cy="17.5" r="4.5"/>
                  <path d="m15.8 17.5 1.2 1.2 2.3-2.5"/>
                </svg>
              </div>
              <div>
                <strong>Persetujuan Terstruktur</strong>
                <span>Alur verifikasi dan persetujuan berjenjang.</span>
              </div>
            </div>
          </div>

          {{-- Vector Graphic Accents --}}
          <svg class="chart-art" viewBox="0 0 500 220" fill="none" aria-hidden="true">
            <path d="M15 190 C100 170 130 205 185 162 S285 140 335 110 S405 76 475 20" stroke="#64c9ff" stroke-width="2"/>
            <path d="M433 27 475 20 463 61" stroke="#64c9ff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <g fill="#8dd8ff" fill-opacity=".48">
              <rect x="110" y="147" width="31" height="50" rx="5"/>
              <rect x="160" y="126" width="31" height="71" rx="5"/>
              <rect x="210" y="104" width="31" height="93" rx="5"/>
              <rect x="260" y="78" width="31" height="119" rx="5"/>
              <rect x="310" y="54" width="31" height="143" rx="5"/>
            </g>
            <g stroke="#69cfff" fill="#e8f8ff">
              <circle cx="15" cy="190" r="6"/>
              <circle cx="185" cy="162" r="8"/>
              <circle cx="335" cy="110" r="8"/>
            </g>
          </svg>
        </section>

        {{-- Right Form Panel --}}
        <section class="form-panel">
          <div class="form-inner">
            <div class="form-kicker">Portal akses pengguna</div>
            <h2 class="form-title">Selamat Datang Kembali</h2>
            <p class="form-subtitle">Masuk untuk melanjutkan ke SIGMA.</p>

            <form wire:submit="login" action="#" onsubmit="return false;" novalidate>
              {{-- Username or Email Field --}}
              <div class="field">
                <label for="login_id" class="field-label">Username atau Email</label>
                <div class="input-wrap">
                  <span class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="8" r="4"/>
                      <path d="M4 20c.7-3.7 3.5-5.5 8-5.5s7.3 1.8 8 5.5"/>
                    </svg>
                  </span>
                  <input
                    id="login_id"
                    wire:model="login_id"
                    type="text"
                    placeholder="Masukkan username atau email"
                    autocomplete="username"
                    class="login-input @error('login_id') has-error @enderror"
                    required
                    autofocus
                  />
                </div>
                @error('login_id')
                  <div class="status-message error">{{ $message }}</div>
                @enderror
              </div>

              {{-- Password Field --}}
              <div class="field">
                <label for="password" class="field-label">Kata Sandi</label>
                <div class="input-wrap">
                  <span class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="4" y="10" width="16" height="11" rx="2"/>
                      <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                      <path d="M12 14v3"/>
                    </svg>
                  </span>
                  <input
                    id="password"
                    wire:model="password"
                    type="{{ $show_password ? 'text' : 'password' }}"
                    placeholder="Masukkan kata sandi"
                    autocomplete="current-password"
                    class="login-input @error('password') has-error @enderror"
                    required
                  />
                  <button
                    class="toggle-password"
                    type="button"
                    wire:click="togglePassword"
                    aria-label="{{ $show_password ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi' }}"
                    title="Tampilkan/sembunyikan kata sandi"
                  >
                    @if ($show_password)
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 3 18 18"/>
                        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                        <path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.1 0 9.5 7 9.5 7a16 16 0 0 1-3.1 3.8"/>
                        <path d="M6.2 6.2C3.8 7.8 2.5 12 2.5 12s3.4 7 9.5 7c1.2 0 2.3-.3 3.3-.7"/>
                      </svg>
                    @else
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6Z"/>
                        <circle cx="12" cy="12" r="2.6"/>
                      </svg>
                    @endif
                  </button>
                </div>
                @error('password')
                  <div class="status-message error">{{ $message }}</div>
                @enderror
              </div>

              {{-- Options: Remember & Help Link --}}
              <div class="form-options">
                <label class="remember">
                  <input type="checkbox" wire:model="remember" />
                  <span>Ingat saya</span>
                </label>
                <button type="button" @click="faqOpen = true" class="help-link">
                  Bantuan & FAQ Masuk
                </button>
              </div>

              {{-- Google Material Design 3 (M3) Filled Button --}}
              <button class="submit-btn" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Masuk ke Sistem</span>
                <span wire:loading.flex wire:target="login" style="align-items: center; justify-content: center; gap: 8px;">
                  <svg class="loading-spinner" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                    <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Memverifikasi...</span>
                </span>
              </button>
            </form>

            {{-- 1-Click Quick Selector (Local Dev Environment) --}}
            @if (app()->environment('local'))
              <div class="demo-section">
                <div class="demo-header">
                  <span class="demo-title">Pilih Akun Uji Cepat (1-Klik)</span>
                  <span class="demo-hint">Pass: password</span>
                </div>
                <div class="demo-grid">
                  <button type="button" wire:click="fillDemo('admin')" class="demo-btn">
                    <strong>admin</strong><span>Admin IT</span>
                  </button>
                  <button type="button" wire:click="fillDemo('rudiansah')" class="demo-btn">
                    <strong>rudiansah</strong><span>Koordinator IT</span>
                  </button>
                  <button type="button" wire:click="fillDemo('sigit.nugroho')" class="demo-btn">
                    <strong>sigit.nugroho</strong><span>Manager Humas</span>
                  </button>
                  <button type="button" wire:click="fillDemo('rahmatullah.sidik')" class="demo-btn">
                    <strong>rahmatullah</strong><span>Kadiv / Dirops</span>
                  </button>
                  <button type="button" wire:click="fillDemo('rochadi.kohar')" class="demo-btn">
                    <strong>rochadi.kohar</strong><span>Keuangan</span>
                  </button>
                  <button type="button" wire:click="fillDemo('subakti')" class="demo-btn">
                    <strong>subakti</strong><span>Petugas Input</span>
                  </button>
                </div>
              </div>
            @endif

            <div class="form-footer">
              <span>Akses internal</span>
              <span class="footer-dot"></span>
              <span>LAZ Al Azhar</span>
            </div>
            <div class="secure-note">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="10" width="16" height="11" rx="2"/>
                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
              </svg>
              <span>Gunakan akun resmi yang telah terdaftar</span>
            </div>
          </div>
        </section>
      </section>
    </main>
  </div>

  {{-- Interactive Modal: Bantuan & FAQ Masuk Sistem --}}
  <div
    x-show="faqOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
  >
    <div
      class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto space-y-4"
      @click.outside="faqOpen = false"
    >
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#1764dc] flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
            </svg>
          </div>
          <div>
            <h2 class="text-sm sm:text-base font-bold text-[#0F2F57] leading-tight">
              Bantuan &amp; FAQ Masuk SIGMA
            </h2>
            <p class="text-[11px] text-slate-500 mt-0.5">
              Panduan akses dan pengelolaan anggaran LAZ Al Azhar
            </p>
          </div>
        </div>
        <button
          type="button"
          @click="faqOpen = false"
          class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
          aria-label="Tutup Bantuan"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="space-y-2 text-xs">
        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
          <button
            type="button"
            @click="activeFaq = activeFaq === 1 ? null : 1"
            class="w-full text-left p-3.5 font-semibold text-slate-900 flex items-center justify-between gap-2 hover:bg-slate-100/70 transition-colors cursor-pointer"
          >
            <span>Kredensial apa yang digunakan untuk masuk?</span>
            <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180 text-[#1764dc]': activeFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div x-show="activeFaq === 1" x-cloak class="px-3.5 pb-3.5 pt-1 text-slate-600 leading-relaxed border-t border-slate-200/60 bg-white">
            Gunakan username resmi kedinasan Anda (misal: rudiansah, sigit.nugroho, admin) atau email @alazharpeduli.or.id yang telah terdaftar.
          </div>
        </div>

        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
          <button
            type="button"
            @click="activeFaq = activeFaq === 2 ? null : 2"
            class="w-full text-left p-3.5 font-semibold text-slate-900 flex items-center justify-between gap-2 hover:bg-slate-100/70 transition-colors cursor-pointer"
          >
            <span>Lupa kata sandi atau akun nonaktif?</span>
            <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180 text-[#1764dc]': activeFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div x-show="activeFaq === 2" x-cloak class="px-3.5 pb-3.5 pt-1 text-slate-600 leading-relaxed border-t border-slate-200/60 bg-white">
            Hubungi Administrator IT LAZ Al Azhar untuk melakukan reset kata sandi atau pengaktifan status akun Anda melalui menu Manajemen Pengguna.
          </div>
        </div>

        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
          <button
            type="button"
            @click="activeFaq = activeFaq === 3 ? null : 3"
            class="w-full text-left p-3.5 font-semibold text-slate-900 flex items-center justify-between gap-2 hover:bg-slate-100/70 transition-colors cursor-pointer"
          >
            <span>Siapa yang berwenang menyetujui pengajuan dana?</span>
            <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180 text-[#1764dc]': activeFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div x-show="activeFaq === 3" x-cloak class="px-3.5 pb-3.5 pt-1 text-slate-600 leading-relaxed border-t border-slate-200/60 bg-white">
            Persetujuan dana dilakukan berjenjang sesuai leveling: Atasan Langsung, Manager Unit (s.d Rp 10jt), Kepala Divisi (> Rp 10jt), hingga verifikasi akhir oleh Divisi Keuangan.
          </div>
        </div>
      </div>

      <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
        <button
          type="button"
          @click="faqOpen = false"
          class="min-h-[38px] px-5 py-2 rounded-xl bg-[#0F2F57] hover:bg-slate-800 text-white text-xs font-semibold transition-colors cursor-pointer"
        >
          Tutup Bantuan
        </button>
      </div>
    </div>
  </div>
</div>
