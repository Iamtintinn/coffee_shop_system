<header class="navbar">
    <div class="navbar-brand">
        <span class="navbar-toggle" id="sidebarToggle">☰</span>
        <span class="navbar-brand-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                <line x1="6" y1="1" x2="6" y2="4"/>
                <line x1="10" y1="1" x2="10" y2="4"/>
                <line x1="14" y1="1" x2="14" y2="4"/>
            </svg>
        </span>
        <span class="brand-text"><?= APP_NAME ?></span>
    </div>
    <div class="navbar-right">
        <a href="#" class="navbar-notif" title="Notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="navbar-notif-dot"></span>
        </a>
        <div class="navbar-user">
            <span class="navbar-avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?></span>
            <div class="navbar-user-info">
                <span class="navbar-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
                <span class="navbar-role badge-<?= $_SESSION['role'] ?>"><?= ucfirst($_SESSION['role']) ?></span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/modules/authentication/logout.php" class="navbar-logout">
            <span>Logout</span>
        </a>
    </div>
</header>
