/**
 * ====================================================================
 * SCRIPT JAVASCRIPT PRINCIPAL : Centre Médical Hope v2.0
 * ====================================================================
 * Fonctionnalités :
 * - Gestion fluide du menu responsive mobile (burger)
 * - Effet 3D dynamique et interactif au survol des cartes
 * - Boîtes de dialogue de confirmation d'action destructive
 * - Calcul dynamique en direct de l'heure de fin des rendez-vous
 * - Auto-dismiss optionnel pour les messages flash
 */

document.addEventListener('DOMContentLoaded', function () {
    // ----------------------------------------------------------------
    // 1. MENU MOBILE RESPONSIVE / SIDEBAR DASHBOARD & BOTTOM NAV
    // ----------------------------------------------------------------
    const topbarMenuToggle = document.getElementById('topbarMenuToggle');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        if (appSidebar) appSidebar.classList.add('open');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('active');
        document.body.style.overflow = window.innerWidth <= 1024 ? 'hidden' : '';
    }

    function closeSidebar() {
        if (appSidebar) appSidebar.classList.remove('open');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (topbarMenuToggle) {
        topbarMenuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (appSidebar && appSidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (appSidebar && appSidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeSidebar();
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeSidebar);
    }

    // Fermer la sidebar quand on clique sur un lien de navigation sur mobile
    if (appSidebar) {
        const sidebarLinks = appSidebar.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
        });

        // Swipe gauche sur la sidebar pour la refermer au doigt
        let sidebarTouchStartX = 0;
        appSidebar.addEventListener('touchstart', function (e) {
            sidebarTouchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        appSidebar.addEventListener('touchend', function (e) {
            const touchEndX = e.changedTouches[0].screenX;
            if (sidebarTouchStartX - touchEndX > 50) {
                closeSidebar();
            }
        }, { passive: true });
    }

    // Fermer la sidebar sur mobile si clic en dehors
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 1024 && appSidebar && appSidebar.classList.contains('open')) {
            if (!appSidebar.contains(e.target) && (!topbarMenuToggle || !topbarMenuToggle.contains(e.target)) && (!mobileMenuBtn || !mobileMenuBtn.contains(e.target))) {
                closeSidebar();
            }
        }
    });

    // Support rétrocompatible pour navToggle / navMenu si existant
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = navMenu.classList.toggle('open');
            navMenu.classList.toggle('active', isOpen);
            navToggle.classList.toggle('open', isOpen);
            navToggle.setAttribute('aria-expanded', isOpen);
        });

        document.addEventListener('click', function (e) {
            if (!navMenu.contains(e.target) && !navToggle.contains(e.target)) {
                navMenu.classList.remove('open', 'active');
                navToggle.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ----------------------------------------------------------------
    // 2. EFFET 3D INTERACTIF SUR LES CARTES (.card-3d)
    // ----------------------------------------------------------------
    const cards3d = document.querySelectorAll('.card-3d');
    cards3d.forEach(card => {
        card.addEventListener('mousemove', function (e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * -6; // max 6 deg
            const rotateY = ((x - centerX) / centerX) * 6;
            
            card.style.transform = `perspective(900px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-3px)`;
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform = 'perspective(900px) rotateX(0deg) rotateY(0deg) translateY(0)';
        });
    });

    // ----------------------------------------------------------------
    // 3. CONFIRMATION DES ACTIONS DE DESTRUCTION / SUPPRESSION
    // ----------------------------------------------------------------
    const deleteButtons = document.querySelectorAll('.btn-confirm-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            const message = btn.getAttribute('data-confirm') || "Êtes-vous certain de vouloir effectuer cette suppression ? Cette action est irréversible.";
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // ----------------------------------------------------------------
    // 4. CALCUL AUTOMATIQUE DE L'HEURE DE FIN ESTIMÉE (RDV)
    // ----------------------------------------------------------------
    const inputDateHeure = document.getElementById('date_heure');
    const selectDuree = document.getElementById('duree_minutes');
    const displayFin = document.getElementById('apercu_heure_fin');

    function actualiserApercuFin() {
        if (!inputDateHeure || !selectDuree || !displayFin) return;
        const valDate = inputDateHeure.value;
        const duree = parseInt(selectDuree.value, 10);

        if (valDate && !isNaN(duree)) {
            const dateObj = new Date(valDate);
            if (!isNaN(dateObj.getTime())) {
                dateObj.setMinutes(dateObj.getMinutes() + duree);
                const heures = String(dateObj.getHours()).padStart(2, '0');
                const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                displayFin.textContent = `⏰ Fin estimée : ${heures}h${minutes}`;
                displayFin.style.display = 'inline-block';
                return;
            }
        }
        displayFin.style.display = 'none';
    }

    // Export global pour être appelé par les boutons de suggestions
    window.actualiserApercuFin = actualiserApercuFin;

    if (inputDateHeure && selectDuree) {
        inputDateHeure.addEventListener('input', actualiserApercuFin);
        inputDateHeure.addEventListener('change', actualiserApercuFin);
        selectDuree.addEventListener('change', actualiserApercuFin);
        actualiserApercuFin();
    }

    // ----------------------------------------------------------------
    // 5. CARROUSEL HERO DE SENSIBILISATION (Ébola, Cancer, Paludisme, etc.)
    // ----------------------------------------------------------------
    const carouselWrapper = document.getElementById('awarenessCarousel');
    const carouselTrack = document.getElementById('carouselTrack');
    const prevBtn = document.getElementById('carouselPrevBtn');
    const nextBtn = document.getElementById('carouselNextBtn');
    const dots = document.querySelectorAll('.carousel-dot');
    const progressFill = document.getElementById('carouselProgressFill');

    if (carouselWrapper && carouselTrack) {
        const slides = carouselTrack.querySelectorAll('.awareness-slide');
        const totalSlides = slides.length;
        let currentIndex = 0;
        let autoplayTimer = null;
        const slideDuration = 6500; // 6.5 secondes par diapositive

        function updateCarousel(index) {
            if (index < 0) {
                currentIndex = totalSlides - 1;
            } else if (index >= totalSlides) {
                currentIndex = 0;
            } else {
                currentIndex = index;
            }

            carouselTrack.style.transform = `translateX(-${currentIndex * 100}%)`;

            // Mettre à jour les indicateurs
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentIndex);
            });

            // Réinitialiser la barre de progression
            startProgress();
        }

        function startProgress() {
            if (!progressFill) return;
            progressFill.style.transition = 'none';
            progressFill.style.width = '0%';

            setTimeout(() => {
                progressFill.style.transition = `width ${slideDuration}ms linear`;
                progressFill.style.width = '100%';
            }, 50);
        }

        function startAutoplay() {
            stopAutoplay();
            startProgress();
            autoplayTimer = setInterval(() => {
                updateCarousel(currentIndex + 1);
            }, slideDuration);
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
            if (progressFill) {
                progressFill.style.transition = 'none';
                progressFill.style.width = '0%';
            }
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                updateCarousel(currentIndex + 1);
                startAutoplay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                updateCarousel(currentIndex - 1);
                startAutoplay();
            });
        }

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const targetIndex = parseInt(dot.getAttribute('data-slide-index'), 10);
                if (!isNaN(targetIndex)) {
                    updateCarousel(targetIndex);
                    startAutoplay();
                }
            });
        });

        // Pause au survol de la souris
        carouselWrapper.addEventListener('mouseenter', stopAutoplay);
        carouselWrapper.addEventListener('mouseleave', startAutoplay);

        // Support Swipe tactile pour smartphones et tablettes
        let touchStartX = 0;
        let touchEndX = 0;

        carouselWrapper.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        }, { passive: true });

        carouselWrapper.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diffX = touchStartX - touchEndX;
            if (Math.abs(diffX) > 40) {
                if (diffX > 0) {
                    updateCarousel(currentIndex + 1);
                } else {
                    updateCarousel(currentIndex - 1);
                }
            }
            startAutoplay();
        }, { passive: true });

        // Lancement initial
        updateCarousel(0);
        startAutoplay();
    }
});
