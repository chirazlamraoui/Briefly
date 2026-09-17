# Chapitre 4 : Réalisation

## Introduction

Les chapitres précédents ont situé le besoin, fixé le périmètre fonctionnel et formalisé la conception de Briefly. Nous décrivons ici la mise en œuvre : l’environnement de travail, les outils et les technologies, puis les itérations qui ont produit l’application web Laravel et le client mobile Flutter.

Pour chaque sprint, nous indiquons l’objectif, les écrans réalisés (captures à insérer) et un jeu de tests fonctionnels correspondant aux scénarios réellement couverts par la suite PHPUnit du dépôt. L’architecture en couches figure déjà au chapitre 3, le catalogue des cas d’utilisation au chapitre 2.

## 4.1 Environnement du travail

### 4.1.1 Configuration matérielle

Nous avons développé Briefly en 2026 sur un poste Macintosh (noyau Darwin). Les caractéristiques précises du matériel, ainsi que d’éventuels postes complémentaires (salle de formation, machine de démonstration), restent à documenter.

**Tableau 4.1** – Configuration matérielle

| Poste | Type | Processeur | Mémoire | Stockage | Système d’exploitation |
| --- | --- | --- | --- | --- | --- |
| Poste de développement | Apple Macintosh | *À compléter* | *À compléter* | *À compléter* | macOS (Darwin 25, 2026) |
| Autre poste | *À compléter* | *À compléter* | *À compléter* | *À compléter* | *À compléter* |

### 4.1.2 Configuration logicielle

#### Outils

Le tableau 4.2 indique les outils utilisés pour rédiger le code, servir la base de données locale, préparer les diagrammes de conception et compiler le client iOS.

**Tableau 4.2** – Outils de développement

| Outil | Rôle dans le projet |
| --- | --- |
| Cursor / Visual Studio Code | Édition du code Laravel (PHP, Blade, CSS) et Flutter (Dart), assistance à la rédaction et à la revue |
| XAMPP (MySQL) | Serveur de base de données local pour l’application web ; le schéma `briefly` est créé puis migré par Artisan |
| diagrams.net | Élaboration des diagrammes présentés au chapitre de conception |
| Flutter SDK (Dart 3.13) | Compilation et exécution du client mobile |
| Xcode | Signature, simulation et déploiement iOS du projet `mobile/` |

Les tests automatisés PHPUnit s’exécutent sur une base SQLite en mémoire. Ils ne dépendent pas de l’instance MySQL de XAMPP.

#### Technologies

Le socle web repose sur PHP 8.3 ou supérieur et Laravel 13. Les vues sont rendues en Blade. L’interface web visible utilise Bootstrap 5.3 et Bootstrap Icons (CDN), plus une feuille de styles applicative. Les graphiques d’administration s’appuient sur Chart.js (CDN). Vite et Tailwind figurent dans la toolchain du dépôt mais ne sont pas branchés sur les pages servies (`@vite` n’est pas appelé). L’authentification de session (navigateur) et l’authentification par jeton (application mobile) s’appuient sur Laravel Sanctum. Les tests d’acceptation HTTP sont écrits avec PHPUnit 12. Les données métier sont persistées dans MySQL.

Le client mobile est une application Flutter (SDK Dart 3.13). L’état applicatif est géré avec Riverpod, la navigation avec GoRouter, les appels HTTP avec Dio. Le jeton Sanctum est stocké dans `flutter_secure_storage`. Les graphiques d’administration utilisent `fl_chart`. La typographie s’appuie sur `google_fonts`. L’interface est bilingue (français / anglais) grâce à `intl` et `flutter_localizations`.

Un même jeu de contrôleurs dessert le navigateur et le mobile (§3.1.1). L’extrait ci-dessous est la mise en œuvre de `respond()`. Lorsque le client envoie `Accept: application/json` (cas du mobile via Dio), la réponse est sérialisée. Sinon, l’utilisateur web reçoit la page Blade ou la redirection prévue.

```php
protected function respond(
    Request $request,
    View|RedirectResponse $html,
    array|JsonResource|ResourceCollection|JsonResponse $json,
    int $status = 200,
): View|RedirectResponse|JsonResponse {
    if (! $request->expectsJson()) {
        return $html;
    }

    if ($json instanceof JsonResponse) {
        return $json;
    }

    if ($json instanceof JsonResource || $json instanceof ResourceCollection) {
        return $json->response()->setStatusCode($status);
    }

    return response()->json($json, $status);
}
```

`TaskService::recordProgress` enregistre un point d’avancement. La méthode met à jour le statut de la tâche, y compris la date de clôture lorsque le statut passe à « terminé », et crée une entrée d’historique `TaskUpdate`. Le mobile et le web empruntent ce même service.

```php
public function recordProgress(Task $task, User $user, array $data): TaskUpdate
{
    $status = TaskStatus::from($data['status']);

    $completedAt = match (true) {
        $status === TaskStatus::Done => $task->completed_at ?? now(),
        default => null,
    };

    $task->update([
        'status' => $status,
        'progress_done' => $data['progress_done'] ?? null,
        'progress_next' => $data['progress_next'] ?? null,
        'blocker_note' => $status === TaskStatus::Blocked
            ? ($data['blocker_note'] ?? null)
            : null,
        'completed_at' => $completedAt,
    ]);

    return TaskUpdate::create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'status' => $status,
        // … mêmes champs d’avancement
    ]);
}
```

**Tableau 4.3** – Principales technologies

| Couche | Technologies |
| --- | --- |
| Serveur web | PHP 8.3+, Laravel 13, Blade, Bootstrap 5.3, Chart.js, Laravel Sanctum, PHPUnit 12 |
| Données | MySQL |
| Client mobile | Flutter (Dart 3.13), Riverpod, GoRouter, Dio, flutter_secure_storage, fl_chart, google_fonts, intl / flutter_localizations |

## 4.2 Réalisation des sprints

Nous avons découpé le travail en cinq sprints, selon le tableau 2.1. Les tableaux de tests qui suivent transposent, en scénarios fonctionnels, les cas réellement exercés par PHPUnit (`AuthenticationTest`, `AdminTest`, `ProjectTaskTest`, `JsonEndpointTest`, `LocaleTest`). La colonne « Résultat » indique la conformité constatée lors de l’exécution de la suite. Les parcours de réinitialisation du mot de passe, de mise à jour du profil, de l’option « se souvenir de moi » et du thème sont implémentés mais **non couverts** par cette suite.

### Sprint 1 : Authentification, profil, préférences

Un visiteur atteint `/login`. Après authentification, un administrateur est envoyé vers `/admin`. Un membre ou un chef d’équipe rejoint `/dashboard`.

> **Capture à insérer :** `captures/web-login.png`
>
> **Figure 4.1** – Page de connexion web (`/login`)

Le formulaire de demande de lien de réinitialisation n’exige que l’adresse du compte. La page suivante, atteinte depuis le courriel, permet de saisir le nouveau mot de passe à l’aide du jeton.

> **Capture à insérer :** `captures/web-forgot-password.png`
>
> **Figure 4.2** – Demande de réinitialisation du mot de passe (`/forgot-password`)

> **Capture à insérer :** `captures/web-reset-password.png`
>
> **Figure 4.3** – Saisie du nouveau mot de passe (`/reset-password/{token}`)

Le profil rassemble deux formulaires. L’un porte sur l’identité (nom, courriel), l’autre sur le changement de mot de passe (mot de passe actuel, nouveau mot de passe, confirmation). L’utilisateur y met à jour le nom et le courriel. L’intitulé de poste reste réservé à l’administration. Le rôle et l’équipe principale y sont affichés, sans pouvoir être modifiés par l’utilisateur.

> **Capture à insérer :** `captures/web-profile.png`
>
> **Figure 4.4** – Profil et changement de mot de passe (`/profile`)

La barre de préférences (langue FR/EN et bascule de thème) est visible sur toutes les pages, y compris la connexion et la récupération du mot de passe.

> **Capture à insérer :** `captures/web-preferences.png`
>
> **Figure 4.5** – Sélecteur de langue et bascule de thème (`briefly-theme`)

**Tableau 4.4** – Tests fonctionnels du sprint 1

| Cas de test | Démarche | Comportement attendu | Résultat |
| --- | --- | --- | --- |
| Affichage de la connexion | Ouvrir `/login` sans session | La page s’affiche | Conforme |
| Connexion membre | Saisir un courriel et un mot de passe valides d’un membre | Redirection vers `/dashboard` ; session authentifiée | Conforme |
| Connexion administrateur | Saisir les identifiants d’un administrateur | Redirection vers `/admin` | Conforme |
| Accès refusé au visiteur | Ouvrir `/dashboard` sans être connecté | Redirection vers `/login` | Conforme |
| Identifiants JSON invalides | Poster `/login` en JSON avec un mot de passe erroné | Réponse 422 ; message d’erreur sur le courriel | Conforme |
| Connexion JSON | Poster `/login` en JSON avec `device_name` | Réponse 200 contenant `token` et `user` | Conforme |
| Langue française | Appeler `/locale/fr` puis ouvrir la connexion | Session `locale=fr` ; libellé « Se connecter » | Conforme |
| Langue anglaise | Depuis une session française, appeler `/locale/en` | Session `locale=en` ; libellé « Sign in » | Conforme |
| Locale invalide | Appeler `/locale/de` | Réponse 404 | Conforme |

### Sprint 2 : Espace membre

Le tableau de bord `/dashboard` affiche un indicateur de taux d’avancement personnel et la liste des tâches assignées.

> **Capture à insérer :** `captures/web-dashboard.png`
>
> **Figure 4.6** – Tableau de bord membre : anneau d’avancement et tâches personnelles (`/dashboard`)

La liste `/tasks` présente les tâches assignées, triées et regroupées selon le statut (bloqué, en cours, à faire, terminé), avec le projet, l’équipe et la date de mise à jour. Un raccourci mène à l’historique.

> **Capture à insérer :** `captures/web-tasks.png`
>
> **Figure 4.7** – Liste des tâches personnelles (`/tasks`)

Le détail d’une tâche assignée propose le formulaire de progression du §2.1.2. Un membre ne peut pas modifier la tâche d’un collègue.

> **Capture à insérer :** `captures/web-task-detail.png`
>
> **Figure 4.8** – Détail d’une tâche et formulaire d’avancement (`/tasks/{id}`)

L’historique paginé des points d’avancement se trouve sur `/tasks/history`. Toute tentative d’accès à `/projects` est refusée.

> **Capture à insérer :** `captures/web-tasks-history.png`
>
> **Figure 4.9** – Historique des points d’avancement (`/tasks/history`)

**Tableau 4.5** – Tests fonctionnels du sprint 2

| Cas de test | Démarche | Comportement attendu | Résultat |
| --- | --- | --- | --- |
| Tableau de bord membre | Se connecter en membre et ouvrir `/dashboard` | Affichage du taux d’avancement et des tâches personnelles | Conforme |
| Liste des tâches | Ouvrir `/tasks` en tant que membre assigné | Les titres et projets des tâches assignées sont visibles | Conforme |
| Détail et formulaire | Ouvrir `/tasks/{id}` pour une tâche assignée | La page s’affiche avec le formulaire « Mettre à jour l’avancement » | Conforme |
| Enregistrement d’un avancement | Envoyer un statut « en cours » avec le réalisé et le suivant | Redirection vers le détail ; tâche et `task_updates` mis à jour | Conforme |
| Blocage sans note | Passer au statut « bloqué » sans `blocker_note` | Erreur de validation sur la note de blocage | Conforme |
| Marquer terminé | Passer au statut « terminé » | Statut `DONE` et `completed_at` renseigné | Conforme |
| Tâche d’un tiers | Tenter de mettre à jour une tâche assignée à un autre membre | Réponse 403 | Conforme |
| Historique | Enregistrer un avancement puis ouvrir `/tasks/history` | Le titre et le texte de progression apparaissent | Conforme |
| Plusieurs mises à jour | Enregistrer deux avancements successifs sur la même tâche | Deux lignes dans `task_updates` | Conforme |
| Accès projets refusé | Ouvrir `/projects` en tant que membre | Réponse 403 | Conforme |

### Sprint 3 : Espace chef d’équipe

Sur le web, les cartes d’équipe affichent le total, les tâches en cours, les bloquées et les terminées dans la semaine, plus le taux d’avancement et les dernières tâches bloquées.

> **Capture à insérer :** `captures/web-dashboard-lead.png`
>
> **Figure 4.10** – Tableau de bord chef d’équipe : cartes des équipes dirigées (`/dashboard`)

La liste des projets montre les projets rattachés aux équipes dirigées, y compris lorsqu’un projet est partagé entre plusieurs équipes. Le chef d’équipe ne crée pas de projets. Le détail d’un projet liste les tâches de l’équipe et permet d’en créer une, assignée exclusivement à un membre de cette équipe. Le chef peut aussi modifier une tâche existante.

> **Capture à insérer :** `captures/web-projects.png`
>
> **Figure 4.11** – Liste des projets du chef d’équipe (`/projects`)

> **Capture à insérer :** `captures/web-project-detail.png`
>
> **Figure 4.12** – Détail d’un projet et tâches de l’équipe (`/projects/{id}`)

> **Capture à insérer :** `captures/web-task-form.png`
>
> **Figure 4.13** – Création ou édition d’une tâche par le chef d’équipe

La page `/team/tasks` combine des compteurs, un filtre par statut (tous, bloqué, en cours, à faire, terminé) et un tableau (membre, tâche, projet, avancement, blocage). Lorsqu’un chef dirige plusieurs équipes, une colonne « Équipe » distingue les lignes. La fiche membre rassemble les tâches assignées et l’historique d’avancement de la personne.

> **Capture à insérer :** `captures/web-team-tasks.png`
>
> **Figure 4.14** – Suivi des tâches d’équipe avec filtre de statut (`/team/tasks`)

> **Capture à insérer :** `captures/web-team-member.png`
>
> **Figure 4.15** – Fiche d’un membre d’équipe (`/team/members/{user}`)

**Tableau 4.6** – Tests fonctionnels du sprint 3

| Cas de test | Démarche | Comportement attendu | Résultat |
| --- | --- | --- | --- |
| Création de projet interdite | Poster une création de projet en tant que chef d’équipe | Réponse 403 ; aucune ligne projet créée | Conforme |
| Création de tâche | Créer une tâche pour un membre de l’équipe, sur un projet rattaché | Redirection vers le projet ; tâche persistée | Conforme |
| Assignation hors équipe | Assigner la tâche à un membre d’une autre équipe | Erreur de validation sur `assigned_to` | Conforme |
| Liste des projets | Ouvrir `/projects` en tant que chef | Les projets des équipes dirigées s’affichent | Conforme |
| Projet partagé | Diriger deux équipes liées au même projet | Les deux noms d’équipe apparaissent sur l’index | Conforme |
| Tâches d’équipe | Ouvrir `/team/tasks` | Les tâches des membres et leurs noms sont visibles | Conforme |
| Plusieurs équipes | Chef de deux équipes, ouvrir `/team/tasks` | Colonne « Équipe » et tâches des deux équipes | Conforme |
| Tableau de bord chef | Ouvrir `/dashboard` avec des tâches d’équipe | Blocs « Avancement des membres » et « Avancement des projets » | Conforme |
| Tâches personnelles du chef | Ouvrir `/tasks` en tant que chef | Uniquement ses propres tâches ; pas celles des membres | Conforme |
| Consultation sans formulaire | Ouvrir le détail d’une tâche d’un membre | La page s’affiche sans le formulaire d’avancement | Conforme |
| Drapeau chef absent | Rôle chef d’équipe mais `is_team_lead` faux sur le pivot | Accès à `/projects` refusé (403) | Conforme |

### Sprint 4 : Espace administrateur

L’écran `/admin` présente les effectifs, le taux d’avancement global, les listes d’avancement par projet et par équipe, ainsi qu’un graphique de répartition des statuts (Chart.js). Un membre ne peut pas accéder à ces routes. Un administrateur qui ouvre `/tasks` est renvoyé vers `/admin`.

> **Capture à insérer :** `captures/web-admin-dashboard.png`
>
> **Figure 4.16** – Tableau de bord administrateur : statistiques, achèvement et graphiques (`/admin`)

Les pages utilisateurs, équipes et projets permettent de créer et de mettre à jour ces entités. La gestion des utilisateurs liste les comptes, autorise la création (nom, courriel, mot de passe, équipes) et la mise à jour (équipes d’appartenance, drapeaux chef d’équipe). La gestion des équipes permet de nommer l’équipe, d’y rattacher des membres, de désigner un chef et d’associer des projets. Un chef ne peut pas être retiré de sa dernière équipe. La gestion des projets crée ou met à jour un projet et synchronise les équipes associées.

> **Capture à insérer :** `captures/web-admin-users.png`
>
> **Figure 4.17** – Gestion des utilisateurs (`/admin/users`)

> **Capture à insérer :** `captures/web-admin-teams.png`
>
> **Figure 4.18** – Gestion des équipes (`/admin/teams`)

> **Capture à insérer :** `captures/web-admin-projects.png`
>
> **Figure 4.19** – Gestion des projets (`/admin/projects`)

**Tableau 4.7** – Tests fonctionnels du sprint 4

| Cas de test | Démarche | Comportement attendu | Résultat |
| --- | --- | --- | --- |
| Tableau de bord admin | Se connecter en administrateur et ouvrir `/admin` | Affichage de la vue d’ensemble, du taux d’avancement, du graphique de statuts et des listes par équipe et par projet | Conforme |
| Membre exclu | Ouvrir `/admin` en tant que membre | Réponse 403 | Conforme |
| Redirection de l’admin | Ouvrir `/tasks` en tant qu’administrateur | Redirection vers `/admin` | Conforme |
| Création et mise à jour d’un projet | Créer un projet multi-équipes puis n’en conserver qu’une | Redirections vers l’index puis l’édition ; équipes synchronisées | Conforme |
| Chef interdit de créer un projet | Poster `/admin/projects` en tant que chef d’équipe | Réponse 403 | Conforme |
| Création d’équipe | Poster un nom d’équipe | L’équipe est enregistrée ; redirection vers l’index | Conforme |
| Équipe avec chef et membres | Créer une équipe en désignant un chef et un membre | Rôle chef, pivot `is_team_lead`, appartenances correctes | Conforme |
| Utilisateur multi-équipes | Créer un utilisateur avec deux équipes | Les deux appartenances sont persistées | Conforme |
| Membre interdit de créer un compte | Poster `/admin/users` en tant que membre | Réponse 403 | Conforme |
| Assignation multi-équipes | Mettre à jour un membre avec deux `team_ids` | Appartenance aux deux équipes ; rôle membre conservé | Conforme |
| Chef de plusieurs équipes | Cocher deux `team_lead_ids` pour un utilisateur | Rôle chef d’équipe ; drapeaux vrais sur les deux équipes | Conforme |
| Retrait du dernier chef | Mettre à jour une équipe en vidant `user_ids` alors qu’un chef y est encore | Erreur de validation ; le chef reste rattaché | Conforme |
| URL d’équipe | Ouvrir `/admin/teams/{id}` | Redirection vers le formulaire d’édition | Conforme |

### Sprint 5 : Application mobile

Le client Flutter reprend les parcours du tableau 2.1. Le jeton Sanctum est stocké de façon sécurisée. Un administrateur est redirigé de `/dashboard` et `/tasks` vers `/admin`. Le thème et la langue FR/EN se règlent dans le profil (stockage local). Ils ne changent pas la locale Laravel.

> **Capture à insérer :** `captures/mobile-login.png`
>
> **Figure 4.20** – Connexion mobile (`LoginScreen`)

> **Capture à insérer :** `captures/mobile-forgot-reset.png`
>
> **Figure 4.21** – Mot de passe oublié et réinitialisation (`ForgotPasswordScreen`, `ResetPasswordScreen`)

> **Capture à insérer :** `captures/mobile-dashboard.png`
>
> **Figure 4.22** – Tableau de bord mobile : anneau d’achèvement et tâches (`DashboardScreen`)

Sur le tableau de bord chef d’équipe, les pastilles mobiles affichent en cours / bloqué / terminé, sans le total ni « terminé cette semaine » du web.

> **Capture à insérer :** `captures/mobile-tasks.png`
>
> **Figure 4.23** – Liste des tâches et historique (`TaskListScreen`, `TaskHistoryScreen`)

> **Capture à insérer :** `captures/mobile-task-detail.png`
>
> **Figure 4.24** – Détail de tâche et saisie d’avancement (`TaskDetailScreen`)

> **Capture à insérer :** `captures/mobile-projects-team.png`
>
> **Figure 4.25** – Projets, tâches d’équipe et fiche membre (`ProjectListScreen`, `TeamTasksScreen`, `TeamMemberScreen`)

> **Capture à insérer :** `captures/mobile-profile.png`
>
> **Figure 4.26** – Profil, thème et langue FR/EN (`ProfileScreen`)

> **Capture à insérer :** `captures/mobile-admin.png`
>
> **Figure 4.27** – Écrans d’administration mobile (`AdminDashboardScreen` et listes CRUD)

Les scénarios ci-dessous complètent le tableau 4.4. La connexion JSON y est déjà testée au sprint 1. Ils valident le contrat métier dont le client Flutter dépend.

**Tableau 4.8** – Tests fonctionnels du sprint 5 (API JSON consommée par le mobile)

| Cas de test | Démarche | Comportement attendu | Résultat |
| --- | --- | --- | --- |
| Tableau de bord sans jeton | GET JSON `/dashboard` sans `Authorization` | Réponse 401 | Conforme |
| Avancement avec jeton | GET JSON du tableau de bord puis PATCH d’une tâche assignée | 200 ; statut et réalisé mis à jour | Conforme |
| Blocage JSON sans note | PATCH JSON statut « bloqué » sans note | Réponse 422 sur `blocker_note` | Conforme |
| Admin hors tâches membre | GET JSON `/tasks` avec un jeton administrateur | Réponse 403 | Conforme |
| Membre hors projets | GET JSON `/projects` avec un jeton membre | Réponse 403 | Conforme |
| Création de tâche JSON | Poster une tâche en tant que chef d’équipe | Réponse 201 ; titre persisté | Conforme |
| Liste d’équipe JSON | GET JSON `/team/tasks` en tant que chef | Compteurs et fragment de titre présents | Conforme |
| CRUD admin JSON | Créer un utilisateur, une équipe et un projet en JSON | Réponses 201 ; enregistrements en base | Conforme |
| Déconnexion | POST JSON `/logout` puis GET `/dashboard` | 204 ; jeton révoqué ; accès ultérieur 401 | Conforme |

## Conclusion

Les captures des figures 4.1 à 4.27 restent à insérer dans `captures/` avant l’export Word. Leur absence n’empêche pas de relire les parcours.
