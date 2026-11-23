<?php
/**
 * ====================================================================
 * STATISTIQUES ET INDICATEURS DE GESTION CLINIQUE
 * ====================================================================
 */

$pageTitle = "Statistiques & Tableau de Bord";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

// 1. Chiffres globaux
$totalRdv      = $pdo->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
$rdvAujourdhui = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE DATE(date_heure) = CURRENT_DATE()")->fetchColumn();
$rdvMois       = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE MONTH(date_heure) = MONTH(CURRENT_DATE()) AND YEAR(date_heure) = YEAR(CURRENT_DATE())")->fetchColumn();
$rdvConfirmes  = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'confirme'")->fetchColumn();
$rdvTermines   = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'termine'")->fetchColumn();
$rdvAnnules    = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'annule'")->fetchColumn();

// 2. Charge de travail par médecin
$stmtChargeMed = $pdo->query("SELECT m.id, m.nom, m.prenom, m.specialite, m.photo,
                              COUNT(r.id) AS total_consultations,
                              COALESCE(SUM(r.duree_minutes), 0) AS minutes_totales,
                              COUNT(CASE WHEN r.statut = 'termine' THEN 1 END) AS nb_termines,
                              COUNT(CASE WHEN r.statut = 'confirme' THEN 1 END) AS nb_a_venir
                              FROM medecins m
                              LEFT JOIN rendez_vous r ON m.id = r.id_medecin AND r.statut != 'annule'
                              GROUP BY m.id
                              ORDER BY total_consultations DESC");
$chargeMedecins = $stmtChargeMed->fetchAll();

// 3. Taux d'occupation des salles
$stmtSallesOcc = $pdo->query("SELECT s.id, s.nom, s.statut, s.etage,
                              COUNT(r.id) AS total_occupations,
                              COALESCE(SUM(r.duree_minutes), 0) AS minutes_salle
                              FROM salles s
                              LEFT JOIN rendez_vous r ON s.id = r.id_salle AND r.statut != 'annule'
                              GROUP BY s.id
                              ORDER BY total_occupations DESC");
$sallesOcc = $stmtSallesOcc->fetchAll();

$maxOccupations = 1;
foreach ($sallesOcc as $so) {
    if ($so['total_occupations'] > $maxOccupations) {
        $maxOccupations = $so['total_occupations'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- En-tête de page -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>📊 Statistiques & Indicateurs de Performance</h2>
            <p>Tableau de bord de pilotage des activités médicales de la Centre Médical Hope.</p>
        </div>
        <div class="page-header-actions">
            <span class="badge badge-primary" style="font-size: 0.78rem; padding: 6px 12px;">
                ● Synchronisé en direct
            </span>
        </div>
    </div>

    <!-- 4 Cartes d'indicateurs clés -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-info">
                <div class="stat-number"><?= $totalRdv ?></div>
                <div class="stat-label">Total des Rendez-vous</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">🕒</div>
            <div class="stat-info">
                <div class="stat-number"><?= $rdvAujourdhui ?></div>
                <div class="stat-label">Consultations Aujourd'hui</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">🗓️</div>
            <div class="stat-info">
                <div class="stat-number"><?= $rdvMois ?></div>
                <div class="stat-label">Consultations ce Mois</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">✕</div>
            <div class="stat-info">
                <div class="stat-number"><?= $rdvAnnules ?></div>
                <div class="stat-label">Rendez-vous Annulés</div>
            </div>
        </div>
    </div>

    <!-- Répartition par statut d'avancement -->
    <div class="card" style="margin-bottom: 28px;">
        <div class="card-header">
            <div class="card-title">
                <span>📈</span> Répartition des Rendez-vous par Statut
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div style="background: var(--success-light); border: 1.5px solid #a7f3d0; border-radius: var(--r-md); padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: 0.05em;">
                    Confirmés / À Venir
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #047857; line-height: 1.1; margin: 6px 0;">
                    <?= $rdvConfirmes ?>
                </div>
                <div style="font-size: 0.8rem; color: #065f46;">Consultations en attente de passage</div>
            </div>

            <div style="background: var(--info-light); border: 1.5px solid #bfdbfe; border-radius: var(--r-md); padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.05em;">
                    Consultations Honorées
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #1d4ed8; line-height: 1.1; margin: 6px 0;">
                    <?= $rdvTermines ?>
                </div>
                <div style="font-size: 0.8rem; color: #1e40af;">Soins et prescriptions dispensés</div>
            </div>

            <div style="background: var(--danger-light); border: 1.5px solid #fecaca; border-radius: var(--r-md); padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.05em;">
                    Annulations Enregistrées
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #b91c1c; line-height: 1.1; margin: 6px 0;">
                    <?= $rdvAnnules ?>
                </div>
                <div style="font-size: 0.8rem; color: #991b1b;">Créneaux restitués et libérés</div>
            </div>
        </div>
    </div>

    <!-- Deux colonnes : Charge des médecins & Occupation des salles -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 28px;">
        
        <!-- Charge de travail des médecins -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>👨‍⚕️</span> Charge de Travail des Praticiens
                </div>
                <span class="badge badge-primary">Volume</span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Praticien</th>
                            <th>Total RDV</th>
                            <th>Volume Horaire</th>
                            <th>À Venir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chargeMedecins as $cm): 
                            $heures = round($cm['minutes_totales'] / 60, 1);
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--text-primary);"><?= e($cm['nom']) ?> <?= e($cm['prenom']) ?></strong>
                                    <div style="font-size: 0.775rem; color: var(--text-muted);"><?= e($cm['specialite']) ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-teal"><?= $cm['total_consultations'] ?> RDV</span>
                                </td>
                                <td>
                                    <strong style="color: var(--text-primary);"><?= $heures ?> h</strong>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">(<?= $cm['minutes_totales'] ?>m)</span>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?= $cm['nb_a_venir'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Taux d'occupation des salles -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>🏥</span> Taux d'Occupation des Salles
                </div>
                <span class="badge badge-teal">Fréquentation</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 18px;">
                <?php foreach ($sallesOcc as $so): 
                    $pct = $maxOccupations > 0 ? round(($so['total_occupations'] / $maxOccupations) * 100) : 0;
                    $heuresSalle = round($so['minutes_salle'] / 60, 1);
                ?>
                    <div style="border-bottom: 1px solid var(--border); padding-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                            <strong style="color: var(--text-primary); font-size: 0.925rem;"><?= e($so['nom']) ?></strong>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">
                                <?= $so['total_occupations'] ?> RDV <span style="font-weight: 400; color: var(--text-muted);">(<?= $heuresSalle ?>h)</span>
                            </span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= max(6, $pct) ?>%;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 0.78rem; color: var(--text-muted);">
                            <span>📍 <?= e($so['etage']) ?></span>
                            <span>Statut : <span class="badge <?= $so['statut'] === 'disponible' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst(e($so['statut'])) ?></span></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
