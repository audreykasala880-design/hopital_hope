        </main><!-- /.app-content -->

        <!-- ============================================================
           PIED DE PAGE (Footer Dashboard)
           ============================================================ -->
        <footer class="app-footer">
            <div class="footer-container">
                <div class="footer-copy">
                    &copy; <?= date('Y') ?> <strong>Centre Médical Hope</strong> — Centre Médical Hope &bull; Département Informatique (PO II)
                </div>
                <div class="footer-extra-links">
                    <span>12 Rue Limete, Kinshasa</span>
                    <span class="footer-dot">&bull;</span>
                    <a href="<?= BASE_URL ?>/tests/run_tests.php">Banc d'essais</a>
                    <span class="footer-dot">&bull;</span>
                    <a href="<?= BASE_URL ?>/rendez_vous/prendre.php">Prendre RDV</a>
                </div>
            </div>
        </footer>

        <!-- ============================================================
           BARRE DE NAVIGATION INFÉRIEURE MOBILE (POUR SMARTPHONES)
           Affichée uniquement sur écran < 768px (mode téléphone)
           ============================================================ -->
        <nav class="mobile-bottom-nav" id="mobileBottomNav" aria-label="Navigation mobile">
            <a href="<?= BASE_URL ?>/index.php" class="mobile-nav-item <?= $currentScript === 'index.php' && $currentDir !== 'rendez_vous' && $currentDir !== 'patients' ? 'active' : '' ?>">
                <span class="mobile-nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </span>
                <span class="mobile-nav-label">Accueil</span>
            </a>

            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="mobile-nav-item mobile-nav-highlight <?= $currentScript === 'prendre.php' ? 'active' : '' ?>">
                <span class="mobile-nav-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                </span>
                <span class="mobile-nav-label">Prendre RDV</span>
            </a>

            <?php if ($isStaff): ?>
                <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="mobile-nav-item <?= $currentDir === 'rendez_vous' && $currentScript === 'index.php' ? 'active' : '' ?>">
                    <span class="mobile-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </span>
                    <span class="mobile-nav-label">Tous RDV</span>
                </a>
            <?php else: ?>
                <a href="<?= $isPatient ? BASE_URL . '/patients/espace.php' : BASE_URL . '/patients/connexion.php' ?>" class="mobile-nav-item <?= $currentScript === 'espace.php' ? 'active' : '' ?>">
                    <span class="mobile-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <span class="mobile-nav-label">Mes RDV</span>
                </a>
            <?php endif; ?>

            <?php if ($isPatient): ?>
                <a href="<?= BASE_URL ?>/patients/espace.php" class="mobile-nav-item <?= $currentScript === 'espace.php' ? 'active' : '' ?>">
                    <span class="mobile-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span class="mobile-nav-label">Mon Espace</span>
                </a>
            <?php elseif ($isStaff): ?>
                <a href="<?= BASE_URL ?>/medecins/index.php" class="mobile-nav-item <?= $currentDir === 'medecins' ? 'active' : '' ?>">
                    <span class="mobile-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    </span>
                    <span class="mobile-nav-label">Médecins</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/patients/connexion.php" class="mobile-nav-item <?= $currentScript === 'connexion.php' || $currentScript === 'inscription.php' ? 'active' : '' ?>">
                    <span class="mobile-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    </span>
                    <span class="mobile-nav-label">Connexion</span>
                </a>
            <?php endif; ?>

            <button type="button" class="mobile-nav-item mobile-menu-btn" id="mobileMenuBtn" aria-label="Ouvrir le menu complet">
                <span class="mobile-nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </span>
                <span class="mobile-nav-label">Menu</span>
            </button>
        </nav>

    </div><!-- /.app-main -->

</div><!-- /.app-layout -->

<!-- JavaScript avec vidage automatique de cache -->
<script src="<?= BASE_URL ?>/assets/js/script.js?v=<?= time() ?>"></script>
</body>
</html>
