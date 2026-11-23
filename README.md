# 🏥 Centre Médical Hope — Application de Gestion des Rendez-vous Médicaux

Application web complète de gestion des consultations médicales, développée en **PHP / MySQL natif**, **HTML5**, **CSS3 (Design System v3.0)** et **JavaScript vanilla**.

---

## 🚀 Démarrage Rapide

1. Démarrez **Apache** et **MySQL** dans le panneau de contrôle **XAMPP**.
2. Importez la base de données depuis `database/gestion_rendez_vous.sql` dans phpMyAdmin (encodage `utf8mb4_general_ci`).
3. Ouvrez votre navigateur et accédez à l'URL :
   👉 **[http://localhost/gestion-rendez-vous-medicaux/](http://localhost/gestion-rendez-vous-medicaux/)**

---

## 🔑 Identifiants de Démonstration

### Espace Professionnel (Staff médical)
| Rôle | Adresse Email | Mot de passe |
| :--- | :--- | :--- |
| **Administrateur** | `admin@hopecmedical.cd` | `admin123` |
| **Réceptionniste** | `reception@hopecmedical.cd` | `admin123` |

### Espace Patient
| Rôle | Adresse Email | Mot de passe |
| :--- | :--- | :--- |
| **Patient Démo** | `patient.demo@hopecmedical.cd` | `Patient123!` |
| *(Ou création instantanée via la page Inscription Patient)* |

---

## 📋 Fonctionnalités Clés

- **Accueil & Campagnes de Prévention** : Carrousel interactif (Ébola, Octobre Rose, Paludisme, Santé Cardiovasculaire) avec swipe tactile.
- **Portail Patient Autonome** : Inscription, connexion sécurisée bcrypt, tableau de bord personnel, suivi des rendez-vous et annulation autonome.
- **Moteur de Prise de RDV Intelligent** :
  - Détection instantanée des conflits de créneaux (médecin ou salle occupée).
  - Contrôle d'adéquation des équipements médicaux requis.
  - Suggestions intelligentes d'alternatives libres (salles ou horaires alternatifs).
  - Transactions ACID avec verrouillage pessimiste MySQL (`FOR UPDATE`).
- **Confirmation Automatique par Email** : Client SMTP socket natif (compatible Gmail TLS/STARTTLS) avec récapitulatif complet et archivage HTML local.
- **Fiche Officielle Imprimable A4** : Génération avec QR Code de vérification, code-barres, consignes médicales et zone de signatures.
- **Application 100% Responsive Mobile** : Barre de navigation inférieure (*Bottom Navigation Bar*), tiroir latéral avec geste tactile swipe-to-close, formulaires ergonomiques anti-zoom iOS.
- **Planning & Gestion Staff** : Gestion des médecins, patients, salles, indisponibilités/congés et statistiques en temps réel.

---

## 🧪 Suites de Tests Automatisés

- **Validation des 7 Tests Métier Obligatoires** :  
  👉 `http://localhost/gestion-rendez-vous-medicaux/tests/run_tests.php`
- **Validation du Portail Patient** :  
  👉 `http://localhost/gestion-rendez-vous-medicaux/tests/test_portail_patient.php`
- **Validation de l'Envoi d'Email & Fiche Imprimable** :  
  👉 `http://localhost/gestion-rendez-vous-medicaux/tests/test_email_et_fiche.php`

---

## 📚 Documentations Détaillées

- 📄 **[GUIDE_TECHNIQUE_DOCUMENTATION.md](GUIDE_TECHNIQUE_DOCUMENTATION.md)** : Guide pédagogique complet expliquant chaque balise HTML, propriété CSS, fonction PHP et script JS.
- 📄 **[RAPPORT_TECHNIQUE.md](RAPPORT_TECHNIQUE.md)** : Rapport d'architecture logicielle, schéma relationnel et justification des choix techniques.
