
# Evolvify Web

**Evolvify Web** est une application web développée avec Symfony, destinée à améliorer la gestion des ressources humaines, des transports et des projets IT dans une entreprise. Elle permet une organisation fluide entre les différents acteurs : RH, employés et chefs de projet.

---

## 👥 Acteurs du système

- **RH (Ressources Humaines)** : supervise les employés, gère les absences, les recrutements et valide les affectations.
- **Employé** : peut consulter ses trajets, absences, projets, soumettre des demandes ou rapports.
- **Chef de projet** : planifie, affecte et suit l’avancement des projets et des tâches.

---

## 🧩 Modules Principaux

### 1. Gestion des Utilisateurs
- CRUD des comptes utilisateurs avec rôles (RH, Chef de projet, Employé).
- Gestion des droits d'accès.
- Mise à jour des informations personnelles.
- Système d'authentification sécurisé.
- Notifications internes selon les rôles.

### 2. Module Recrutement
- Consultation et gestion des offres d’emploi et stages.
- Postulation des candidats avec CV.
- Affectation automatique aux RH pour traitement.
- Suivi de l’état des candidatures.
- Génération automatique de CV pour les employés internes.

### 3. Gestion des Projets
- Création et suivi des projets par les chefs de projet.
- Découpage des projets en tâches affectées aux employés.
- Système de notification lors de l'affectation d'une tâche.
- Rapport de test intégré pour les testeurs.
- Suivi de l’avancement, des livrables et de la clôture des projets.

### 4. Gestion des Absences
- Déclaration et suivi des absences et congés.
- Validation des absences par le RH.
- Historique des absences et statut.
- Notifications à l’employé sur l'état de validation.

### 5. Gestion du Transport
- Création de trajets avec point de départ et d’arrivée.
- Calcul automatique de la distance et durée estimée.
- Affectation automatique à un moyen de transport.
- Gestion des abonnements employés.
- Carte interactive avec Leaflet pour visualisation.

---

## 🚀 Installation

### Prérequis

- PHP ≥ 8.1
- Composer
- Symfony CLI
- MySQL ou équivalent

### Étapes

```bash
git clone https://github.com/votre-utilisateur/evolvify-web.git
cd evolvify-web
composer install
cp .env .env.local
# Modifier .env.local pour configurer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve
```

---

## 📈 Fonctionnalités avancées

- Tableau de bord dynamique pour chaque rôle
- Intégration de cartes (Leaflet.js + OpenStreetMap)
- Statistiques avec Chart.js
- Notifications internes
- Filtres et recherche avancée pour tous les modules
- Sécurité (hash des mots de passe, authentification par rôle)

---

## 🧪 Tests

```bash
php bin/phpunit
```

---

## 📄 Licence

Projet sous licence **MIT**. Voir `LICENSE` pour plus de détails.

## 👩‍💻 Développé par

L’équipe Evolvify
