# Mission : Développer le backend Laravel d'une application de gestion des internes hospitaliers

## 1. Ton rôle

Agis comme un développeur backend senior spécialisé en Laravel, Eloquent ORM, validation des données, architecture REST API et conception de bases de données relationnelles.

Tu dois examiner le projet existant, respecter ses conventions et générer une implémentation propre, maintenable, sécurisée et cohérente.

**Ne te contente pas de proposer du code ou des exemples : implémente réellement les fichiers dans le projet.**

## 2. Contexte du projet

Je développe une application de gestion des internes d'un hôpital.

L'application doit permettre de :

* Gérer les informations des internes.
* Gérer les services hospitaliers.
* Définir les périodes de rotation communes aux internes.
* Affecter chaque interne à un service pour une période donnée.
* Gérer les différents types de gardes.
* Définir des règles horaires variables selon le service et le type de jour.
* Planifier manuellement les gardes.
* Affecter plusieurs internes à une même garde, même lorsqu'ils travaillent dans des services différents.
* Consulter les affectations actuelles, les rotations futures et passées, ainsi que les plannings de garde.

Les internes ne sont rattachés à aucune promotion dans la base de données. **Ne crée aucune table `promotions`, aucun champ `promotion_id` et aucune relation avec une promotion.**

Les périodes de rotation sont définies directement dans une table indépendante. Leur calendrier est commun à l'ensemble des internes concernés par l'application.

## 3. Contraintes impératives

Avant de coder :

1. Examine la structure actuelle du projet, la version de Laravel, les modèles existants, les migrations, les contrôleurs, les routes et les conventions déjà utilisées.
2. Réutilise les composants existants lorsqu'ils sont pertinents.
3. Ne modifie pas les fichiers sans rapport avec cette fonctionnalité.
4. Si des tables ou des classes existent déjà, adapte-les au lieu de créer des doublons.
5. Ne supprime aucune donnée existante.
6. N'invente pas de fonctionnalités non demandées.

### Minimalisme de la base de données

Je veux uniquement les colonnes nécessaires au fonctionnement.

Ne crée pas de colonnes `code`, `description`, `observation`, `photo`, `lieu` ou autres champs facultatifs sans justification fonctionnelle explicite.

Ne crée pas de table de promotion, de table de calendrier annuel supplémentaire ou de table de liaison inutile.

Utilise les colonnes techniques Laravel `id`, `created_at` et `updated_at` lorsque cela est cohérent avec le projet.

Les clés étrangères doivent être correctement définies et les contraintes d'unicité doivent empêcher les doublons pertinents.

## 4. Schéma de données demandé

### A. Table `internes`

Champs :

* `id`
* `name`
* `phone`, nullable
* `created_at`
* `updated_at`

Ne lie pas les internes à des promotions.

Si le projet possède déjà un modèle utilisateur et un système d'authentification, ne crée pas un second système d'authentification pour les internes. Examine la structure existante et propose la meilleure intégration sans imposer de relation utilisateur-interne si elle n'est pas nécessaire.

### B. Table `services`

Champs :

* `id`
* `name`, unique
* `created_at`
* `updated_at`

Exemples : Cardiologie, Neurologie, Chirurgie digestive, Pédiatrie, Médecine interne.

### C. Table `periodes_rotation`

Champs :

* `id`
* `name`
* `start_date`
* `end_date`
* `created_at`
* `updated_at`

Les périodes sont communes à l'application. Il suffit de les définir une fois.

L'application doit empêcher les périodes incohérentes, notamment une date de fin antérieure à la date de début. Évite également les chevauchements accidentels entre périodes, sauf si le métier les autorise explicitement.

### D. Table `affectations`

Champs :

* `id`
* `interne_id`
* `service_id`
* `periode_rotation_id`
* `created_at`
* `updated_at`

Règles :

* Un interne ne peut avoir qu'une seule affectation par période.
* Un service peut accueillir plusieurs internes pendant la même période.
* Un interne peut avoir plusieurs affectations historiques.
* Les affectations passées doivent être conservées.

Ajoute une contrainte d'unicité sur `interne_id` et `periode_rotation_id`.

### E. Table `types_garde`

Champs :

* `id`
* `name`, unique
* `start_time`
* `end_time`
* `decalage_end`
* `created_at`
* `updated_at`

Le champ `decalage_end` indique le nombre de jours à ajouter à la date de début pour calculer la date de fin :

* `0` : fin le même jour.
* `1` : fin le lendemain.

Les horaires de cette table constituent les horaires par défaut. Les horaires effectifs peuvent varier selon le service et le type de jour.

### F. Table `regles_horaires_garde`

Champs :

* `id`
* `service_id`
* `type_garde_id`
* `categorie_day`
* `start_time`
* `end_time`
* `decalage_end`
* `created_at`
* `updated_at`

Valeurs possibles pour `categorie_day` :

* `semaine`
* `weekend`
* `ferie`

Une règle définit les horaires applicables à un service pour un type de garde et une catégorie de jour.

Ajoute une contrainte d'unicité sur :
`service_id + type_garde_id + categorie_day`.

La règle du jour férié est prioritaire sur celle du week-end. Si aucune règle spécifique n'existe, utilise les horaires par défaut du type de garde, ou retourne une erreur métier explicite si le service exige une règle obligatoire.

### G. Table `jours_feries`

Champs :

* `id`
* `date`, unique
* `name`
* `created_at`
* `updated_at`

Ce référentiel permettra d'identifier les jours fériés, notamment ceux du Togo.

Ne génère pas automatiquement les jours fériés sans source fiable. Prévois leur gestion via les opérations CRUD.

### H. Table `gardes`

Champs :

* `id`
* `type_garde_id`
* `service_id`, nullable uniquement si les gardes sans service sont autorisées
* `date_garde`
* `start`
* `end`
* `statut`
* `created_at`
* `updated_at`

Le champ `start` est un datetime et le champ `end` est un datetime.

Lors de la création d'une garde, calcule automatiquement les horaires à partir du service, du type de garde, de la date et des règles applicables.

Copie les horaires effectifs dans la garde afin qu'une modification ultérieure des règles ne change pas rétroactivement les gardes déjà planifiées.

Ne demande pas à l'utilisateur de saisir manuellement les horaires lorsqu'une règle permet de les déterminer.

Les statuts doivent être limités à des valeurs métier cohérentes, par exemple `prevue`, `effectuee` et `annulee`.

### I. Table pivot `garde_interne`

Champs :

* `id`
* `garde_id`
* `interne_id`
* `created_at`
* `updated_at`

Une garde peut concerner plusieurs internes et un interne peut participer à plusieurs gardes.

Ajoute une contrainte d'unicité sur `garde_id + interne_id`.

L'association doit pouvoir être créée, consultée et supprimée indépendamment de la rotation de l'interne.

Ne lie jamais automatiquement les participants d'une garde au service de rotation de l'interne.

## 5. Éléments Laravel à générer

Pour toutes les entités pertinentes, génère :

1. Les migrations.
2. Les modèles Eloquent.
3. Les relations Eloquent.
4. Les Form Requests de création et de mise à jour.
5. Les API Resources.
6. Les classes de service métier.
7. Les contrôleurs REST API.
8. Les routes API.
9. Les tests automatisés pertinents.

Respecte les conventions de nommage Laravel.

### Modèles

Configure :

* Les relations `belongsTo`, `hasMany` et `belongsToMany` appropriées.
* Les casts des dates, datetime et heures si nécessaire.
* Les relations de la table pivot `garde_interne`.
* Les attributs nécessaires à l'API, sans logique métier complexe dans les modèles.

### Form Requests

Crée des Form Requests distinctes pour les opérations de création et de mise à jour lorsqu'elles ont des règles différentes.

Valide notamment :

* L'existence des clés étrangères.
* Les champs obligatoires.
* L'unicité des noms et matricules.
* La cohérence des dates.
* Les catégories de jour autorisées.
* Les statuts autorisés.
* L'existence des internes sélectionnés pour une garde.
* L'absence de doublons dans la liste des participants.
* L'absence d'affectations en double pour un même interne et une même période.

### API Resources

Utilise les Resources pour contrôler les données exposées.

Ne renvoie pas inutilement toutes les relations imbriquées. Prévois des représentations utiles pour afficher :

* Un interne avec son service actuel.
* Un service avec les internes actuellement affectés.
* Une période avec les affectations correspondantes.
* Une garde avec son type, son service et la liste de ses participants.

Évite les chargements N+1 grâce au chargement anticipé des relations.

### Services métier

Crée des services dédiés pour les opérations nécessitant de la logique métier, notamment :

**Gestion des affectations**

* Affecter un interne à un service pour une période.
* Empêcher une deuxième affectation pour cette même période.
* Consulter les internes affectés à un service à une date donnée.

**Gestion des horaires**

* Déterminer si la date est fériée.
* Sinon, déterminer si elle tombe un week-end ou un jour de semaine.
* Rechercher la règle correspondant au service, au type de garde et à la catégorie du jour.
* Appliquer les horaires par défaut du type de garde lorsqu'aucune règle spécifique n'est applicable.
* Calculer les datetimes de début et de fin, y compris lorsque la garde traverse minuit.
* Retourner une erreur métier claire si aucune règle applicable n'est disponible et qu'aucun défaut n'est autorisé.

**Gestion des gardes**

* Créer une garde avec calcul automatique des horaires.
* Associer plusieurs internes à une même garde.
* Modifier les participants.
* Consulter les gardes par date, période, type, service ou interne.
* Vérifier les conflits horaires pour un interne.
* Gérer les remplacements sans créer de doublons de participants.

Utilise des transactions de base de données pour les opérations qui modifient plusieurs tables.

### Contrôleurs

Génère des contrôleurs REST API minces.

Les contrôleurs doivent :

* Recevoir les Form Requests.
* Appeler les services métier.
* Retourner les API Resources.
* Utiliser des codes HTTP cohérents.
* Gérer proprement les erreurs de validation et les erreurs métier.

Ne place pas toute la logique métier dans les contrôleurs.

### Routes

Organise les routes dans `routes/api.php`, selon les conventions du projet.

Prévois les opérations CRUD nécessaires pour :

* Internes.
* Services.
* Périodes de rotation.
* Affectations.
* Types de garde.
* Règles horaires.
* Jours fériés.
* Gardes.

Prévois également les opérations spécifiques :

* Consulter les internes présents dans un service à une date donnée.
* Consulter le service actuel d'un interne.
* Consulter les affectations d'une période.
* Ajouter ou retirer des participants à une garde.
* Consulter le planning des gardes d'un interne.
* Consulter les gardes d'un service entre deux dates.

Respecte le système d'authentification et les middlewares existants. N'invente pas un nouveau mécanisme d'authentification.

## 6. Règles métier essentielles

### Rotations

* Une affectation concerne un seul interne, un seul service et une seule période.
* Chaque interne ne peut avoir qu'une affectation par période.
* Les périodes sont communes à tous les internes et indépendantes de toute promotion.
* Les affectations historiques ne doivent pas être supprimées automatiquement.
* Pour connaître le service actuel, utilise la date courante et les périodes pertinentes plutôt qu'un champ `service_actuel` dupliqué.

### Gardes

* Une garde est indépendante de l'affectation de rotation.
* Plusieurs internes peuvent participer à une même garde.
* Les participants peuvent travailler dans des services différents.
* Une même personne ne peut pas être inscrite deux fois à la même garde.
* Vérifie les conflits entre intervalles horaires, y compris lorsqu'une garde traverse minuit.
* Le chevauchement des gardes doit être vérifié sur les dates et heures effectives, et non uniquement sur `date_garde`.
* Les horaires calculés sont enregistrés dans la garde.
* Une modification des règles horaires ne doit pas modifier les gardes déjà créées.
* Une date fériée prévaut sur la catégorie week-end.

### Sécurité et intégrité

* Utilise des transactions lorsqu'une opération touche plusieurs tables.
* Protège les opérations de suppression par des clés étrangères et des règles de suppression adaptées.
* Évite les suppressions en cascade susceptibles d'effacer l'historique des gardes ou des affectations.
* Ne retourne pas les champs internes non nécessaires.
* N'expose pas de données fictives en production.

## 7. Tests attendus

Écris des tests automatisés couvrant au minimum :

1. La création et la modification d'un interne.
2. La création d'une période et le rejet de dates incohérentes.
3. L'affectation d'un interne à un service.
4. Le rejet d'une deuxième affectation pour la même période.
5. La recherche des internes d'un service à une date donnée.
6. Le calcul des horaires d'une garde en semaine.
7. Le calcul des horaires d'une garde le week-end.
8. La priorité des jours fériés sur les week-ends.
9. Le calcul correct d'une garde qui se termine le lendemain.
10. L'association de plusieurs internes à une garde.
11. Le rejet d'un participant dupliqué.
12. La détection de chevauchements de gardes pour un même interne.
13. La conservation des horaires d'une garde existante après modification d'une règle.
14. Le comportement lorsqu'aucune règle horaire applicable n'existe.

## 8. Méthode de travail imposée

Procède dans cet ordre :

**Étape 1 : audit**

* Inspecte le projet et résume brièvement son architecture existante.
* Identifie les éventuelles tables, classes ou routes déjà présentes.
* Signale les conflits avec le schéma demandé.

**Étape 2 : plan**

* Présente la liste exacte des fichiers à créer ou modifier.
* Vérifie l'ordre des migrations et les dépendances entre tables.

**Étape 3 : implémentation**

* Crée les fichiers réellement dans le projet.
* Respecte les conventions et le style existants.
* Ne modifie pas les fonctionnalités étrangères à ce module.

**Étape 4 : vérification**

* Vérifie la cohérence des relations Eloquent.
* Exécute les tests pertinents disponibles.
* Exécute les vérifications de syntaxe et d'analyse disponibles.
* N'exécute pas de migration destructive sur une base de données contenant des données réelles.
* Signale explicitement les commandes non exécutées ou les tests échoués.

**Étape 5 : compte rendu**
Fournis un résumé :

* Des fichiers créés ou modifiés.
* Des relations implémentées.
* Des routes ajoutées.
* Des règles métier couvertes.
* Des tests exécutés et de leurs résultats.
* Des éventuelles actions manuelles restantes.

## 9. Consigne finale

La priorité est la cohérence du modèle relationnel, la simplicité du code et la fiabilité des règles métier.

Ne sur-ingénierie pas le projet. N'ajoute ni colonnes ni tables non demandées sans expliquer pourquoi elles sont indispensables.

Commence par l'audit du projet, puis implémente la solution complète.">
