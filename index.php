<?php
/**
 * PAGE D'ACCUEIL — CENTRE MÉDICAL HOPE / Universitaire UCC
 * Design fidèle aux maquettes de référence WhatsApp
 */

$pageTitle = "Accueil — CENTRE MÉDICAL HOPE UCC";
require_once __DIR__ . '/config/database.php';

try {
    $nbMedecins   = $pdo->query("SELECT COUNT(*) FROM medecins WHERE actif = 1")->fetchColumn();
    $nbPatients   = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $nbSalles     = $pdo->query("SELECT COUNT(*) FROM salles WHERE statut = 'disponible'")->fetchColumn();
    $nbRdvAujourd = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE DATE(date_heure) = CURRENT_DATE() AND statut != 'annule'")->fetchColumn();
    $medecinsVedettes = $pdo->query("SELECT * FROM medecins WHERE actif = 1 ORDER BY id ASC LIMIT 4")->fetchAll();
    $sallesVedettes   = $pdo->query("SELECT * FROM salles ORDER BY id ASC LIMIT 6")->fetchAll();
} catch (PDOException $e) {
    $nbMedecins = $nbPatients = $nbSalles = $nbRdvAujourd = 0;
    $medecinsVedettes = $sallesVedettes = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-container">

    <!-- ============================================================
       CARROUSEL HERO DE SENSIBILISATION (Ébola, Cancer du Sein, Paludisme, etc.)
       ============================================================ -->
    <section class="awareness-carousel-wrapper" id="awarenessCarousel" aria-label="Campagnes de sensibilisation et santé publique">
        <div class="carousel-track-container">
            <div class="carousel-track" id="carouselTrack">

                <!-- ----------------------------------------------------
                   SLIDE 1 : SENSIBILISATION MALADIE À VIRUS ÉBOLA (MVE)
                   ---------------------------------------------------- -->
                <div class="awareness-slide slide-ebola" data-slide-index="0">
                    <div class="awareness-content-col">
                        <div class="awareness-badge-row">
                            <span class="awareness-badge badge-ebola">🛡️ SANTÉ PUBLIQUE RDC &bull; VIGILANCE ÉBOLA</span>
                            <span class="campaign-tag">Campagne Prévention 2026</span>
                        </div>
                        <h2 class="awareness-title">
                            Riposte et prévention contre la Maladie à Virus Ébola (MVE)
                        </h2>
                        <p class="awareness-desc">
                            La vigilance collective sauve des vies. Protégez votre famille et votre communauté en adoptant dès aujourd'hui les réflexes d'hygiène et en signalant immédiatement tout cas suspect.
                        </p>
                        <div class="awareness-tips-list">
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Symptômes d'alerte :</strong> Fièvre brutale, faiblesse intense, douleurs musculaires, maux de gorge, vomissements et diarrhées inexpliquées.</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Gestes barrières :</strong> Lavage systématique des mains à l'eau et au savon ou solution chlorée, zéro contact avec les fluides corporels.</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Triage UCC :</strong> Notre unité médicale dispose d'un circuit de dépistage et de prise en charge sécurisé aux normes OMS.</div>
                            </div>
                        </div>
                        <div class="awareness-btn-row">
                            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-teal-action">
                                Prendre un rendez-vous médical
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-outline-teal">
                                Nos médecins spécialistes
                            </a>
                        </div>
                    </div>

                    <div class="awareness-visual-col">
                        <div class="awareness-visual-frame">
                            <svg viewBox="0 0 380 330" width="100%" height="100%" class="awareness-svg" preserveAspectRatio="xMidYMid meet">
                                <defs>
                                    <linearGradient id="bgEbola" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#ecfdf5"/>
                                        <stop offset="100%" stop-color="#d1fae5"/>
                                    </linearGradient>
                                    <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#10b981"/>
                                        <stop offset="100%" stop-color="#047857"/>
                                    </linearGradient>
                                </defs>
                                <rect width="380" height="330" rx="20" fill="url(#bgEbola)"/>
                                
                                <!-- Cercles décoratifs -->
                                <circle cx="300" cy="80" r="90" fill="#ffffff" opacity="0.6"/>
                                <circle cx="90" cy="250" r="75" fill="#ffffff" opacity="0.5"/>

                                <!-- Grand Bouclier de protection -->
                                <path d="M190 40 C250 40 280 65 280 120 C280 200 190 250 190 250 C190 250 100 200 100 120 C100 65 130 40 190 40 Z" fill="url(#shieldGrad)" filter="drop-shadow(0 8px 16px rgba(4, 120, 87, 0.25))"/>
                                <path d="M190 52 C240 52 268 74 268 122 C268 190 190 236 190 236 C190 236 112 190 112 122 C112 74 140 52 190 52 Z" fill="#065f46" opacity="0.15"/>

                                <!-- Croix médicale blanche dans le bouclier -->
                                <rect x="178" y="90" width="24" height="65" rx="6" fill="#ffffff"/>
                                <rect x="157" y="111" width="66" height="23" rx="6" fill="#ffffff"/>

                                <!-- Mains hygiéniques lavées avec eau et savon -->
                                <g transform="translate(110, 180)">
                                    <ellipse cx="80" cy="80" rx="95" ry="40" fill="#ffffff" opacity="0.95" filter="drop-shadow(0 4px 10px rgba(0,0,0,0.06))"/>
                                    <!-- Robinet d'eau / Gouttes -->
                                    <circle cx="80" cy="50" r="6" fill="#38bdf8"/>
                                    <path d="M80 40 Q80 50 80 54 Q83 50 80 40" fill="#0284c7"/>
                                    <circle cx="68" cy="62" r="4.5" fill="#38bdf8"/>
                                    <circle cx="94" cy="64" r="5" fill="#0284c7"/>
                                    <!-- Bulles de savon protectrices -->
                                    <circle cx="50" cy="74" r="9" fill="#e0f2fe" stroke="#38bdf8" stroke-width="1.5" opacity="0.85"/>
                                    <circle cx="112" cy="72" r="11" fill="#e0f2fe" stroke="#38bdf8" stroke-width="1.5" opacity="0.85"/>
                                    <text x="80" y="94" text-anchor="middle" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" fill="#065f46">EAU PROPRE &amp; SAVON</text>
                                </g>

                                <!-- Thermomètre sans contact (36.6°C) -->
                                <g transform="translate(245, 65)">
                                    <rect width="90" height="42" rx="10" fill="#ffffff" filter="drop-shadow(0 4px 12px rgba(0,0,0,0.1))"/>
                                    <circle cx="22" cy="21" r="10" fill="#d1fae5"/>
                                    <text x="22" y="25" text-anchor="middle" font-family="'Inter', sans-serif" font-size="11" fill="#047857">🌡️</text>
                                    <text x="38" y="20" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" fill="#065f46">36.6 °C</text>
                                    <text x="38" y="32" font-family="'Inter', sans-serif" font-size="8.5" font-weight="600" fill="#10b981">Normale</text>
                                </g>

                                <!-- Badge Alerte Zéro Contact -->
                                <g transform="translate(35, 75)">
                                    <rect width="95" height="38" rx="10" fill="#ffffff" filter="drop-shadow(0 4px 12px rgba(0,0,0,0.08))"/>
                                    <circle cx="20" cy="19" r="10" fill="#fef3c7"/>
                                    <text x="20" y="23" text-anchor="middle" font-family="'Inter', sans-serif" font-size="11">⚠️</text>
                                    <text x="36" y="18" font-family="'Poppins', sans-serif" font-size="10" font-weight="700" fill="#92400e">Isolement</text>
                                    <text x="36" y="29" font-family="'Inter', sans-serif" font-size="8" font-weight="600" fill="#b45309">Dépistage précoce</text>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- ----------------------------------------------------
                   SLIDE 2 : OCTOBRE ROSE & CANCER DU SEIN
                   ---------------------------------------------------- -->
                <div class="awareness-slide slide-cancer-sein" data-slide-index="1">
                    <div class="awareness-content-col">
                        <div class="awareness-badge-row">
                            <span class="awareness-badge badge-rose">🎗️ OCTOBRE ROSE &bull; DÉPISTAGE DU CANCER DU SEIN</span>
                            <span class="campaign-tag" style="color: #be185d;">Campagne Nationale Féminine</span>
                        </div>
                        <h2 class="awareness-title">
                            Détecté tôt, le cancer du sein se guérit dans 9 cas sur 10
                        </h2>
                        <p class="awareness-desc">
                            Prenez soin de vous : un examen rapide et régulier fait toute la différence. Nos gynécologues et spécialistes de la Centre Médical Hope vous accompagnent en toute bienveillance et confidentialité.
                        </p>
                        <div class="awareness-tips-list tip-rose">
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Autopalpation mensuelle :</strong> Dès 20 ans, palpez délicatement vos seins chaque mois quelques jours après la fin des règles pour détecter toute grosseur ou anomalie.</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Examen clinique annuel :</strong> Bilan annuel préventif avec un médecin pour inspecter les tissus mammaires et ganglions.</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Mammographie &amp; Échographie :</strong> Recommandées dès 40-50 ans, ou plus tôt selon vos antécédents familiaux, pour détecter les lésions invisibles à l'œil nu.</div>
                            </div>
                        </div>
                        <div class="awareness-btn-row">
                            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-teal-action" style="background-color: #be185d; border-color: #be185d;">
                                Prendre RDV en Gynécologie
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-outline-teal" style="color: #be185d; border-color: #f472b6;">
                                Équipe de santé féminine
                            </a>
                        </div>
                    </div>

                    <div class="awareness-visual-col">
                        <div class="awareness-visual-frame" style="border-color: #fbcfe8;">
                            <svg viewBox="0 0 380 330" width="100%" height="100%" class="awareness-svg" preserveAspectRatio="xMidYMid meet">
                                <defs>
                                    <linearGradient id="bgRose" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#fff1f6"/>
                                        <stop offset="100%" stop-color="#fce7f3"/>
                                    </linearGradient>
                                    <linearGradient id="ribbonGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#f43f5e"/>
                                        <stop offset="50%" stop-color="#ec4899"/>
                                        <stop offset="100%" stop-color="#be185d"/>
                                    </linearGradient>
                                </defs>
                                <rect width="380" height="330" rx="20" fill="url(#bgRose)"/>

                                <!-- Cercles d'ambiance bienveillante -->
                                <circle cx="190" cy="150" r="115" fill="#ffffff" opacity="0.7"/>
                                <circle cx="280" cy="80" r="60" fill="#fbcfe8" opacity="0.4"/>

                                <!-- Silhouette Coeur protecteur -->
                                <path d="M190 280 C130 220 70 170 70 115 C70 80 98 55 133 55 C160 55 178 70 190 85 C202 70 220 55 247 55 C282 55 310 80 310 115 C310 170 250 220 190 280 Z" fill="#fdf2f8" stroke="#fbcfe8" stroke-width="2.5" opacity="0.9"/>

                                <!-- Grand Ruban Rose Iconique (Octobre Rose) -->
                                <g filter="drop-shadow(0 10px 20px rgba(190, 24, 93, 0.3))">
                                    <!-- Branche gauche -->
                                    <path d="M140 250 C140 250 170 190 190 145 C205 110 215 75 190 60 C165 48 145 80 155 125 C170 175 240 250 240 250" fill="none" stroke="url(#ribbonGrad)" stroke-width="26" stroke-linecap="round" stroke-linejoin="round"/>
                                    <!-- Boucle croisée -->
                                    <path d="M140 250 L160 215" fill="none" stroke="#9d174d" stroke-width="26" stroke-linecap="round"/>
                                </g>

                                <!-- Badge 90% Guérison -->
                                <g transform="translate(230, 60)">
                                    <rect width="115" height="46" rx="12" fill="#ffffff" filter="drop-shadow(0 4px 14px rgba(190, 24, 93, 0.15))"/>
                                    <text x="18" y="24" font-family="'Poppins', sans-serif" font-size="16" font-weight="800" fill="#be185d">90 %</text>
                                    <text x="18" y="38" font-family="'Inter', sans-serif" font-size="9" font-weight="600" fill="#9d174d">de guérison précoce</text>
                                </g>

                                <!-- Pastille Message Solidaire -->
                                <g transform="translate(40, 220)">
                                    <rect width="140" height="44" rx="12" fill="#ffffff" filter="drop-shadow(0 4px 14px rgba(0,0,0,0.06))"/>
                                    <circle cx="24" cy="22" r="12" fill="#fce7f3"/>
                                    <text x="24" y="27" text-anchor="middle" font-family="'Inter', sans-serif" font-size="13">🌸</text>
                                    <text x="44" y="20" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" fill="#831843">Dépistage précoce</text>
                                    <text x="44" y="32" font-family="'Inter', sans-serif" font-size="8.5" font-weight="600" fill="#be185d">Bilan mammaire UCC</text>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- ----------------------------------------------------
                   SLIDE 3 : LUTTE CONTRE LE PALUDISME (MALARIA)
                   ---------------------------------------------------- -->
                <div class="awareness-slide slide-paludisme" data-slide-index="2">
                    <div class="awareness-content-col">
                        <div class="awareness-badge-row">
                            <span class="awareness-badge badge-palu">🦟 SANTÉ TROPICALE &bull; ZÉRO PALUDISME EN RDC</span>
                            <span class="campaign-tag" style="color: #b45309;">Prévention &amp; Dépistage Rapide</span>
                        </div>
                        <h2 class="awareness-title">
                            Fièvre ou frissons ? Faites le test TDR sans attendre
                        </h2>
                        <p class="awareness-desc">
                            Le paludisme reste la première cause de consultation en République Démocratique du Congo. Ne laissez pas l'automédication compliquer un tableau clinique simple.
                        </p>
                        <div class="awareness-tips-list tip-palu">
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Moustiquaire imprégnée (MILD) :</strong> Dormez chaque nuit sous moustiquaire imprégnée d'insecticide, en particulier les enfants et les femmes enceintes.</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Stop à l'eau stagnante :</strong> Assainissez les alentours de votre parcelle pour détruire les gîtes de reproduction des anophèles.</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Dépistage TDR en 15 minutes :</strong> Notre laboratoire Centre Médical Hope confirme la présence du parasite avant tout traitement CTA.</div>
                            </div>
                        </div>
                        <div class="awareness-btn-row">
                            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-teal-action">
                                Consultation &amp; Bilan sanguin
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/salles/index.php" class="btn btn-outline-teal">
                                Plateaux techniques &amp; Salles
                            </a>
                        </div>
                    </div>

                    <div class="awareness-visual-col">
                        <div class="awareness-visual-frame" style="border-color: #fde68a;">
                            <svg viewBox="0 0 380 330" width="100%" height="100%" class="awareness-svg" preserveAspectRatio="xMidYMid meet">
                                <defs>
                                    <linearGradient id="bgPalu" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#fefce8"/>
                                        <stop offset="100%" stop-color="#fef3c7"/>
                                    </linearGradient>
                                </defs>
                                <rect width="380" height="330" rx="20" fill="url(#bgPalu)"/>
                                
                                <circle cx="190" cy="150" r="105" fill="#ffffff" opacity="0.75"/>
                                
                                <!-- Moustiquaire protectrice suspendue (Structure conique) -->
                                <path d="M190 35 L120 180 L260 180 Z" fill="#e0f2fe" opacity="0.4" stroke="#38bdf8" stroke-width="1.5" stroke-dasharray="4,4"/>
                                <ellipse cx="190" cy="180" rx="70" ry="18" fill="#e0f2fe" opacity="0.5"/>
                                <circle cx="190" cy="35" r="7" fill="#0284c7"/>

                                <!-- Cassette de Test Rapide (TDR Paludisme) -->
                                <g transform="translate(100, 140)" filter="drop-shadow(0 8px 18px rgba(180, 83, 9, 0.15))">
                                    <rect width="180" height="70" rx="10" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5"/>
                                    <!-- Puits de dépôt de sang -->
                                    <circle cx="35" cy="35" r="14" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="1.5"/>
                                    <circle cx="35" cy="35" r="8" fill="#ef4444"/>
                                    <!-- Fenêtre de lecture (Contrôle C et Test T) -->
                                    <rect x="70" y="24" width="75" height="22" rx="4" fill="#f8fafc" stroke="#cbd5e1"/>
                                    <line x1="88" y1="26" x2="88" y2="44" stroke="#991b1b" stroke-width="2.5"/>
                                    <line x1="125" y1="26" x2="125" y2="44" stroke="#cbd5e1" stroke-width="2" stroke-dasharray="2,2"/>
                                    <text x="88" y="18" text-anchor="middle" font-family="'Inter', sans-serif" font-size="8" font-weight="700" fill="#64748b">C</text>
                                    <text x="125" y="18" text-anchor="middle" font-family="'Inter', sans-serif" font-size="8" font-weight="700" fill="#64748b">T</text>
                                    <!-- Inscription TDR -->
                                    <text x="150" y="58" font-family="'Poppins', sans-serif" font-size="8" font-weight="700" fill="#047857">MALARIA TDR</text>
                                </g>

                                <!-- Bouclier Anti-moustique barré -->
                                <g transform="translate(245, 50)">
                                    <circle cx="35" cy="35" r="30" fill="#ffffff" stroke="#ef4444" stroke-width="3" filter="drop-shadow(0 4px 10px rgba(239, 68, 68, 0.2))"/>
                                    <!-- Silhouette moustique -->
                                    <ellipse cx="35" cy="35" rx="6" ry="12" fill="#1e293b"/>
                                    <circle cx="35" cy="21" r="4" fill="#1e293b"/>
                                    <line x1="22" y1="28" x2="48" y2="42" stroke="#1e293b" stroke-width="2"/>
                                    <line x1="22" y1="42" x2="48" y2="28" stroke="#1e293b" stroke-width="2"/>
                                    <!-- Barre diagonale rouge d'interdiction -->
                                    <line x1="14" y1="14" x2="56" y2="56" stroke="#ef4444" stroke-width="3.5" stroke-linecap="round"/>
                                </g>

                                <!-- Badge Résultat Rapide -->
                                <g transform="translate(35, 70)">
                                    <rect width="110" height="40" rx="10" fill="#ffffff" filter="drop-shadow(0 4px 10px rgba(0,0,0,0.06))"/>
                                    <circle cx="22" cy="20" r="10" fill="#d1fae5"/>
                                    <text x="22" y="24" text-anchor="middle" font-family="'Inter', sans-serif" font-size="11">⏱️</text>
                                    <text x="38" y="17" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" fill="#065f46">15 Minutes</text>
                                    <text x="38" y="29" font-family="'Inter', sans-serif" font-size="8.5" font-weight="600" fill="#059669">Résultat laboratoire</text>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- ----------------------------------------------------
                   SLIDE 4 : HYPERTENSION & DIABÈTE (MALADIES SILENCIEUSES)
                   ---------------------------------------------------- -->
                <div class="awareness-slide slide-cardio" data-slide-index="3">
                    <div class="awareness-content-col">
                        <div class="awareness-badge-row">
                            <span class="awareness-badge badge-cardio">🩺 BILAN CARDIO-MÉTABOLIQUE &bull; DÉPISTAGE PRÉVENTIF</span>
                            <span class="campaign-tag" style="color: #0369a1;">Maladies Non Transmissibles</span>
                        </div>
                        <h2 class="awareness-title">
                            Hypertension et Diabète : Ne laissez pas le silence vous tromper
                        </h2>
                        <p class="awareness-desc">
                            Souvent sans douleur ni symptôme évident, l'hypertension et le diabète usent le cœur, le cerveau (AVC) et les reins. Un simple bilan régulier permet d'anticiper toute complication.
                        </p>
                        <div class="awareness-tips-list tip-cardio">
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Prise de tension semestrielle :</strong> Mesurez votre pression artérielle au moins deux fois par an (norme cible : &lt; 140/90 mmHg).</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Surveillance glycémique à jeun :</strong> Un dosage de sang capillaire rapide détecte le prédiabète avant qu'il ne s'installe.</div>
                            </div>
                            <div class="awareness-tip-item">
                                <span class="awareness-tip-icon">✓</span>
                                <div><strong>Hygiène de vie protectrice :</strong> Diminuez la consommation de sel et de sucres raffinés, pratiquez 30 minutes de marche par jour.</div>
                            </div>
                        </div>
                        <div class="awareness-btn-row">
                            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-teal-action" style="background-color: #0284c7; border-color: #0284c7;">
                                Prendre RDV en Cardiologie
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-outline-teal" style="color: #0284c7; border-color: #7dd3fc;">
                                Nos spécialistes en médecine interne
                            </a>
                        </div>
                    </div>

                    <div class="awareness-visual-col">
                        <div class="awareness-visual-frame" style="border-color: #bae6fd;">
                            <svg viewBox="0 0 380 330" width="100%" height="100%" class="awareness-svg" preserveAspectRatio="xMidYMid meet">
                                <defs>
                                    <linearGradient id="bgCardio" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#f0f9ff"/>
                                        <stop offset="100%" stop-color="#e0f2fe"/>
                                    </linearGradient>
                                </defs>
                                <rect width="380" height="330" rx="20" fill="url(#bgCardio)"/>
                                
                                <circle cx="190" cy="140" r="105" fill="#ffffff" opacity="0.8"/>

                                <!-- Moniteur Cardiaque avec ligne ECG -->
                                <g transform="translate(65, 80)" filter="drop-shadow(0 8px 20px rgba(2, 132, 199, 0.15))">
                                    <rect width="250" height="150" rx="14" fill="#0f172a" stroke="#334155" stroke-width="2"/>
                                    <!-- Écran intérieur -->
                                    <rect x="8" y="8" width="234" height="134" rx="8" fill="#091e2b"/>
                                    <!-- Grille fine d'oscilloscope -->
                                    <line x1="8" y1="75" x2="242" y2="75" stroke="#133647" stroke-width="1" stroke-dasharray="4,4"/>
                                    <!-- Tracé ECG dynamique vert néon -->
                                    <path d="M15 75 L60 75 L70 55 L80 95 L90 35 L102 110 L112 65 L125 75 L165 75 L175 55 L185 95 L195 35 L207 110 L217 65 L230 75" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    <!-- Valeur BPM -->
                                    <text x="25" y="32" font-family="'Poppins', sans-serif" font-size="16" font-weight="800" fill="#22c55e">72</text>
                                    <text x="50" y="30" font-family="'Inter', sans-serif" font-size="9" font-weight="600" fill="#86efac">BPM</text>
                                    <!-- Valeur Tension Artérielle -->
                                    <text x="145" y="32" font-family="'Poppins', sans-serif" font-size="15" font-weight="800" fill="#38bdf8">120/80</text>
                                    <text x="200" y="30" font-family="'Inter', sans-serif" font-size="8.5" font-weight="600" fill="#7dd3fc">mmHg</text>
                                </g>

                                <!-- Badge Glycémie Normale -->
                                <g transform="translate(45, 235)">
                                    <rect width="135" height="42" rx="10" fill="#ffffff" filter="drop-shadow(0 4px 10px rgba(0,0,0,0.06))"/>
                                    <circle cx="22" cy="21" r="10" fill="#dbeafe"/>
                                    <text x="22" y="25" text-anchor="middle" font-family="'Inter', sans-serif" font-size="11">🩸</text>
                                    <text x="40" y="18" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" fill="#0369a1">0.95 g/L</text>
                                    <text x="40" y="30" font-family="'Inter', sans-serif" font-size="8.5" font-weight="600" fill="#0284c7">Glycémie normale</text>
                                </g>

                                <!-- Badge Cœur Sain -->
                                <g transform="translate(200, 235)">
                                    <rect width="135" height="42" rx="10" fill="#ffffff" filter="drop-shadow(0 4px 10px rgba(0,0,0,0.06))"/>
                                    <circle cx="22" cy="21" r="10" fill="#fee2e2"/>
                                    <text x="22" y="25" text-anchor="middle" font-family="'Inter', sans-serif" font-size="11">❤️</text>
                                    <text x="40" y="18" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" fill="#b91c1c">Cœur Protégé</text>
                                    <text x="40" y="30" font-family="'Inter', sans-serif" font-size="8.5" font-weight="600" fill="#ef4444">Bilan préventif</text>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>

            </div><!-- /.carousel-track -->
        </div><!-- /.carousel-track-container -->

        <!-- Boutons flèches de navigation -->
        <button class="carousel-btn carousel-prev" id="carouselPrevBtn" aria-label="Campagne précédente" title="Campagne précédente">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="carousel-btn carousel-next" id="carouselNextBtn" aria-label="Campagne suivante" title="Campagne suivante">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>

        <!-- Indicateurs ronds de pagination (Dots) -->
        <div class="carousel-indicators" id="carouselIndicators">
            <button class="carousel-dot active" data-slide-index="0" aria-label="Campagne Ébola"></button>
            <button class="carousel-dot" data-slide-index="1" aria-label="Campagne Cancer du sein"></button>
            <button class="carousel-dot" data-slide-index="2" aria-label="Campagne Paludisme"></button>
            <button class="carousel-dot" data-slide-index="3" aria-label="Campagne Hypertension et Diabète"></button>
        </div>

        <!-- Ligne fine de progression automatique -->
        <div class="carousel-progress-bar">
            <div class="carousel-progress-fill" id="carouselProgressFill"></div>
        </div>
    </section>

    <!-- ============================================================
       ESPACE PATIENT EN LIGNE (Portail Dédié Patient UCC)
       ============================================================ -->
    <section class="patient-portal-banner" style="margin: 28px 0; background: linear-gradient(135deg, #052e16 0%, #064e3b 50%, #065f46 100%); border-radius: 20px; padding: 28px 32px; color: #fff; box-shadow: 0 10px 30px rgba(6,78,59,0.18);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
            <div style="max-width: 600px;">
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(52,211,153,0.18); border: 1px solid rgba(52,211,153,0.3); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #a7f3d0; margin-bottom: 12px;">
                    <span></span> NOUVEAU • PORTAIL PATIENT CENTRE MÉDICAL HOPE
                </div>
                <h3 style="font-family: 'Poppins', sans-serif; font-size: 1.4rem; font-weight: 700; margin: 0 0 8px; color: #fff;">
                    Prenez rendez-vous en ligne en toute simplicité
                </h3>
                <p style="font-size: 0.9rem; color: #d1fae5; line-height: 1.5; margin: 0;">
                    Créez votre dossier patient sécurisé, consultez les disponibilités de nos médecins en temps réel et suivez l'historique de vos consultations.
                </p>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <?php if (isPatientLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/patients/espace.php" class="btn btn-primary" style="background: #10b981; color: #fff; border: none; padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: 0.92rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <span><i class="fa-solid fa-person"></i></span> Mon Espace Patient
                    </a>
                    <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3); padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 0.92rem; text-decoration: none;">
                        + Nouveau RDV
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/patients/inscription.php" class="btn btn-primary" style="background: #10b981; color: #fff; border: none; padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: 0.92rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(16,185,129,0.4);">
                        <span><i class="fa-solid fa-user-plus"></i></span> Créer mon compte
                    </a>
                    <a href="<?= BASE_URL ?>/patients/connexion.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.35); padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 0.92rem; text-decoration: none;">
                        Se connecter
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
       SECTION SERVICES & HORAIRES (WhatsApp Image 18.35.26)
       ============================================================ -->
    <section class="home-services-section">
        <div class="section-title-wrap">
            <h2 class="section-main-title">Nos services</h2>
            <p class="section-sub-title">Une prise en charge adaptée à vos besoins.</p>
        </div>

        <div class="services-cards-grid">
            <!-- Carte 1 : Consultations -->
            <div class="service-pill-card">
                <div class="service-icon-circle">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/>
                        <path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/>
                        <circle cx="20" cy="10" r="2"/>
                    </svg>
                </div>
                <h3 class="service-card-title">Consultations</h3>
                <p class="service-card-desc">Consultez nos médecins selon leurs spécialités médicales.</p>
                <a href="<?= BASE_URL ?>/medecins/index.php" class="service-inline-link">Voir les spécialistes &rarr;</a>
            </div>

            <!-- Carte 2 : Suivi médical -->
            <div class="service-pill-card">
                <div class="service-icon-circle">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </div>
                <h3 class="service-card-title">Suivi médical</h3>
                <p class="service-card-desc">Bénéficiez d'un accompagnement personnalisé et attentif.</p>
                <a href="<?= BASE_URL ?>/patients/index.php" class="service-inline-link">Dossiers patients &rarr;</a>
            </div>

            <!-- Carte 3 : Rendez-vous -->
            <div class="service-pill-card">
                <div class="service-icon-circle">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h3 class="service-card-title">Rendez-vous</h3>
                <p class="service-card-desc">Consultez les créneaux disponibles et organisez vos visites.</p>
                <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="service-inline-link">Réserver un créneau &rarr;</a>
            </div>

            <!-- Carte 4 : Horaires d'ouverture -->
            <div class="hours-info-card">
                <div class="hours-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <h4>Nos horaires d'ouverture</h4>
                </div>
                <ul class="hours-list">
                    <li>
                        <span class="hours-day">Lundi - Vendredi :</span>
                        <strong class="hours-time">08h00 - 17h00</strong>
                    </li>
                    <li>
                        <span class="hours-day">Samedi :</span>
                        <strong class="hours-time">08h00 - 13h00</strong>
                    </li>
                    <li>
                        <span class="hours-day">Dimanche :</span>
                        <strong class="hours-time text-danger">Fermé</strong>
                    </li>
                </ul>
                <div class="hours-footnote">
                    Horaires indicatifs à adapter aux consultations réelles de la clinique.
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
       SECTION HISTOIRE DE L'HÔPITAL (Demande explicite de l'utilisateur)
       ============================================================ -->
    <section class="hospital-history-section">
        <div class="history-banner-header">
            <span class="history-pre-title">HÉRITAGE & MISSION ACADÉMIQUE</span>
            <h2 class="history-main-title">L'Histoire de la Centre Médical Hope</h2>
            <p class="history-lead-text">
                Une vocation médicale née de la rigueur universitaire et du dévouement au service de la vie humaine.
            </p>
        </div>

        <div class="history-grid-layout">
            <div class="history-text-col">
                <div class="history-narrative-card">
                    <h3 class="history-card-heading">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0f764a" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        Fondation au cœur de Kinshasa
                    </h3>
                    <p>
                        Créée au sein de la Commmune de limete <strong>Centre Médicale Hope</strong> à Kinshasa, la clinique a vu le jour avec une ambition noble : offrir aux étudiants, au corps professoral et à l'ensemble de la population kinoise un centre de soins de référence, alliant éthique chrétienne, excellence scientifique et accessibilité financière.
                    </p>
                    <p>
                        Depuis ses premiers jours, l'établissement s'est distingué par une organisation méthodique et une discipline clinique stricte. Chaque patient y est accueilli comme un être unique, avec dignité, discrétion et professionnalisme.
                    </p>
                </div>

                <div class="history-pillars-grid">
                    <div class="pillar-box">
                        <div class="pillar-num">01</div>
                        <h4 class="pillar-title">Excellence Clinique</h4>
                        <p class="pillar-desc">Des praticiens spécialisés formés aux meilleures pratiques internationales et engagés pour des diagnostics précis.</p>
                    </div>
                    <div class="pillar-box">
                        <div class="pillar-num">02</div>
                        <h4 class="pillar-title">Éthique & Bienveillance</h4>
                        <p class="pillar-desc">Une prise en charge humaine respectueuse du secret médical et guidée par le sens du service envers le prochain.</p>
                    </div>
                    <div class="pillar-box">
                        <div class="pillar-num">03</div>
                        <h4 class="pillar-title">Gestion Numérique Moderne</h4>
                        <p class="pillar-desc">Une traçabilité numérique des rendez-vous et des salles pour garantir zéro collision horaire et une ponctualité exemplaire.</p>
                    </div>
                </div>
            </div>

            <div class="history-stats-col">
                <div class="history-stats-card">
                    <h4 class="stats-card-heading">Indicateurs Opérationnels</h4>
                    <div class="live-metrics-list">
                        <div class="live-metric-item">
                            <span class="metric-val"><?= $nbMedecins ?></span>
                            <span class="metric-lbl">Médecins spécialistes actifs</span>
                        </div>
                        <div class="live-metric-item">
                            <span class="metric-val"><?= $nbSalles ?></span>
                            <span class="metric-lbl">Salles de consultation équipées</span>
                        </div>
                        <div class="live-metric-item">
                            <span class="metric-val"><?= $nbPatients ?></span>
                            <span class="metric-lbl">Patients enregistrés et suivis</span>
                        </div>
                        <div class="live-metric-item">
                            <span class="metric-val"><?= $nbRdvAujourd ?></span>
                            <span class="metric-lbl">Consultations prévues ce jour</span>
                        </div>
                    </div>
                    <div class="stats-card-cta">
                        <a href="<?= BASE_URL ?>/statistiques/index.php" class="btn btn-outline-teal btn-block" style="font-size: 0.85rem;">
                            Consulter le rapport statistique &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
       APERÇU DES MÉDECINS RÉFÉRENTS (Miroir WhatsApp Image 18.35.27)
       ============================================================ -->
    <section class="home-doctors-section">
        <div class="section-title-wrap flex-between">
            <div>
                <h2 class="section-main-title">Nos médecins</h2>
                <p class="section-sub-title">Une équipe de professionnels à votre service.</p>
            </div>
            <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-outline-teal btn-sm">
                Voir tous les médecins &rarr;
            </a>
        </div>

        <div class="whatsapp-doctors-grid">
            <?php foreach ($medecinsVedettes as $med): ?>
                <div class="doctor-clean-card">
                    <div class="doctor-card-top">
                        <div class="doctor-avatar-circle">
                            <img src="<?= BASE_URL ?>/<?= e($med['photo']) ?>"
                                 alt="<?= e($med['nom']) ?>"
                                 onerror="this.src='<?= BASE_URL ?>/assets/images/doctor1.svg'">
                        </div>
                        <div class="doctor-info-block">
                            <h3 class="doctor-card-name"><?= e($med['nom']) ?> <?= e($med['prenom']) ?></h3>
                            <span class="doctor-card-spec"><?= e($med['specialite']) ?></span>
                            <div class="doctor-card-schedule">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Lun - Ven : 08h00 - 16h00</span>
                            </div>
                        </div>
                    </div>
                    <div class="doctor-card-action">
                        <a href="<?= BASE_URL ?>/rendez_vous/prendre.php?medecin_id=<?= $med['id'] ?>" class="btn btn-teal-action btn-sm btn-block">
                            Prendre RDV
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============================================================
       SALLES DE CONSULTATION (Miroir WhatsApp Image 18.35.27 (2))
       ============================================================ -->
    <section class="home-rooms-section">
        <div class="section-title-wrap flex-between">
            <div>
                <h2 class="section-main-title">Salles de consultation</h2>
                <p class="section-sub-title">Gestion des salles et de leurs équipements.</p>
            </div>
            <a href="<?= BASE_URL ?>/salles/index.php" class="btn btn-outline-teal btn-sm">
                Voir toutes les salles &rarr;
            </a>
        </div>

        <div class="whatsapp-rooms-grid">
            <?php foreach ($sallesVedettes as $salle): ?>
                <?php
                $isDisponible = ($salle['statut'] === 'disponible');
                $statusClass = $isDisponible ? 'status-pill-green' : ($salle['statut'] === 'maintenance' ? 'status-pill-amber' : 'status-pill-red');
                $statusLabel = $isDisponible ? 'Disponible' : ($salle['statut'] === 'maintenance' ? 'Maintenance' : 'Occupée');
                ?>
                <div class="room-clean-card">
                    <div class="room-card-inner">
                        <div class="room-thumb-box">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#0f764a" stroke-width="1.8">
                                <rect x="3" y="3" width="18" height="18" rx="3"/>
                                <path d="M9 3v18"/><circle cx="6" cy="12" r="1"/>
                            </svg>
                        </div>
                        <div class="room-details-box">
                            <h3 class="room-card-title"><?= e($salle['nom']) ?></h3>
                            <span class="room-card-dept"><?= e($salle['etage']) ?></span>
                            <span class="room-card-capa">Capacité : <?= $salle['capacite'] ?> personne<?= $salle['capacite'] > 1 ? 's' : '' ?></span>
                            <p class="room-card-equips" title="<?= e($salle['equipements']) ?>">
                                Équipements : <?= e($salle['equipements']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="room-card-footer">
                        <span class="status-pill <?= $statusClass ?>"><?= $statusLabel ?></span>
                        <a href="<?= BASE_URL ?>/salles/index.php" class="btn btn-outline-teal btn-xs">
                            Voir détails
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

</div><!-- /.page-container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
