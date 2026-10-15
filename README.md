# Gestion des internes

Application web de gestion des internes en médecine : affectations par service et hôpital, périodes de rotation, planning des gardes et référentiels associés.

## Fonctionnalités

- **Internes** : gestion des fiches, import en masse depuis Excel.
- **Affectations** : rattachement des internes aux services et hôpitaux, par période de rotation.
- **Gardes** : planification des gardes, types de garde, calcul des horaires et prise en compte des jours fériés.
- **Référentiels** : hôpitaux, services, types de garde, périodes de rotation, jours fériés.
- **Tableau de bord** : vue d'ensemble de l'activité.
- **Utilisateurs et profil** : authentification, réinitialisation du mot de passe, gestion des comptes.
- **Espace public** : pages accessibles sans connexion.

## Architecture

| Dossier     | Description                                                       |
| ----------- | ----------------------------------------------------------------- |
| `backend/`  | API REST Laravel (PHP 8.5), Sanctum, tests Pest                   |
| `frontend/` | SPA Vue 3 + TypeScript, Vite, Pinia, Vue Router, Tailwind CSS 4   |

Le backend suit une organisation Controllers / Form Requests / Resources / Services (logique métier dans `app/Services`).
Le frontend est découpé en modules (`admin`, `auth`, `public`, `error`).

## Installation

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend

Node `^20.19.0` ou `>=22.12.0` requis.

```bash
cd frontend
npm install
npm run dev
```

## Commandes utiles

| Commande                     | Dossier     | Rôle                       |
| ---------------------------- | ----------- | -------------------------- |
| `php artisan test --compact` | `backend/`  | Lance les tests            |
| `vendor/bin/pint`            | `backend/`  | Formate le code PHP        |
| `npm run build`              | `frontend/` | Build de production        |
| `npm run type-check`         | `frontend/` | Vérification TypeScript    |
| `npm run format`             | `frontend/` | Formate le code avec Prettier |
