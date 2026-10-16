# Chapitre 2 : Analyse et spécification des besoins

## Introduction

Le chapitre 1 a décrit Briefly comme une application interne de suivi d’avancement, fermée à l’inscription publique, destinée à des équipes déjà constituées. Nous en tirons ici des exigences exploitables, d’abord informelles, puis semi-formelles. La conception du chapitre 3 s’appuiera sur cette spécification.

La section 2.1 nomme les acteurs, les exigences fonctionnelles par rôle, puis les exigences non fonctionnelles. La section 2.2 propose les diagrammes UML, les maquettes fil de fer et le backlog produit (tableau 2.1).

## 2.1 Spécification informelle des besoins

Nous nommons d’abord les utilisateurs du système et relions chaque exigence à un rôle. L’accès est nominatif, les comptes sont créés par l’administration, et les droits découlent de l’appartenance aux équipes autant que du rôle.

### 2.1.1 Définition des acteurs

Nous retenons trois acteurs. Ils correspondent aux rôles métier distingués par Briefly. Aucun visiteur anonyme n’a de parcours utile au-delà de l’authentification et de la réinitialisation du mot de passe. Il n’existe pas d’auto-inscription.

**L’administrateur** crée et maintient les utilisateurs, les équipes et les projets. Il consulte un tableau de bord global. Il ne gère pas de tâches personnelles. Le middleware `EnsureNotAdmin` ferme les routes de liste, d’historique et de mise à jour des tâches (redirection web vers l’administration, ou refus JSON). Il ne couvre pas `/dashboard`. Les routes de chef d’équipe lui sont refusées (403). Le détail des middleware figure au chapitre 3.

**Le chef d’équipe** (*Team Lead*) est identifié par le drapeau `team_user.is_team_lead`. Un même utilisateur peut diriger plusieurs équipes. En l’état des règles métier, chaque équipe n’a qu’un chef à un instant donné. Le chef d’équipe peut se voir assigner des tâches et en mettre à jour l’avancement, comme un membre. Il ne crée ni les projets, ni les utilisateurs. Ces opérations restent du ressort de l’administrateur.

**Le membre** appartient à une ou plusieurs équipes. Il consulte son suivi, rend compte de l’avancement des tâches qui lui sont assignées et consulte l’historique de ses points d’avancement. Il ne crée pas de tâches pour autrui et ne pilote pas l’équipe. La politique `updateStatus` est explicite : un membre ne peut mettre à jour le progrès que des tâches dont il est l’assigné.

Le chef d’équipe étend le membre pour le suivi d’équipe. Les prérogatives de l’administrateur restent distinctes. L’administrateur n’emprunte pas le parcours d’exécution des tâches.

### 2.1.2 Analyse des besoins fonctionnels

Nous commençons par les fonctions d’authentification et de profil, communes aux trois rôles, puis nous détaillons celles du membre, du chef d’équipe et de l’administrateur.

#### Commun (authentification et profil)

Tous les utilisateurs authentifiables peuvent :

- Se connecter au moyen d’une adresse de courriel et d’un mot de passe.
- Cocher, sur le client web, l’option « se souvenir de moi », pour prolonger la session.
- Demander un lien de réinitialisation en cas de mot de passe oublié, puis définir un nouveau mot de passe.
- Se déconnecter.
- Modifier leur nom et leur adresse de courriel.
- Changer leur mot de passe.
- Choisir la langue de l’interface, entre le français et l’anglais.
- Basculer entre un thème clair et un thème sombre.

Sans compte créé par un administrateur, ces parcours restent inaccessibles. Le système ne propose aucun formulaire d’inscription publique.

#### Membre

Le membre accède à un tableau de bord et aux tâches qui lui sont assignées. Il peut :

- Consulter un tableau de bord personnel indiquant le taux d’avancement (tâches terminées / total) ainsi que la liste de ses tâches, chacune assortie d’un pourcentage dérivé du statut : à faire (`TODO`) 0 %, en cours (`IN_PROGRESS`) 60 %, bloqué (`BLOCKED`) 30 %, terminé (`DONE`) 100 %.
- Lister ses propres tâches, regroupées par statut.
- Consulter le détail d’une tâche.
- Mettre à jour l’avancement d’une tâche qui lui est assignée, en renseignant le statut, le texte « réalisé » (`progress_done`), le texte « à venir » (`progress_next`) et, le cas échéant, la note de blocage (`blocker_note`). Cette dernière est obligatoire lorsque le statut choisi est `BLOCKED`.
- Consulter l’historique de ses points d’avancement, sous la forme d’une liste paginée d’enregistrements `TaskUpdate`.

Le membre ne modifie pas le titre, la description ou l’assigné d’une tâche. Ces attributs relèvent du chef d’équipe. Le geste métier du membre est le *point d’avancement* structuré.

#### Chef d’équipe

Le chef d’équipe hérite des besoins du membre pour ses propres tâches. Il pilote uniquement les équipes dont il est le chef. Il peut :

- Consulter, sur son tableau de bord, chaque équipe dirigée, avec les statistiques suivantes : total des tâches, tâches en cours, tâches bloquées, tâches terminées dans la semaine (`done_this_week`), taux d’avancement, ainsi que les dernières tâches bloquées. Il n’y a pas de carte dédiée aux tâches terminées hors de ce décompte hebdomadaire.
- Lister les projets rattachés aux équipes qu’il gère.
- Consulter le détail d’un projet et y créer une tâche (titre, description, assigné, statut), l’assigné devant appartenir à l’équipe de contexte.
- Modifier une tâche existante relevant des équipes qu’il dirige.
- Consulter les tâches d’équipe, avec un filtre par statut.
- Consulter la fiche d’un membre (tâches et mises à jour récentes), uniquement si ce membre partage une équipe gérée par le chef.

Le chef d’équipe ne crée ni projets ni utilisateurs. Il n’administre pas le référentiel. Il organise le travail à l’intérieur des projets déjà rattachés à ses équipes et lit l’avancement de ses membres.

#### Administrateur

L’administrateur gère la structure (utilisateurs, équipes, projets) et consulte une vue d’ensemble. Il peut :

- Consulter un tableau de bord administrateur : effectifs (équipes, projets, tâches), taux d’avancement global, graphique de répartition par statut, listes de taux d’avancement par équipe et par projet.
- Gérer les utilisateurs non administrateurs : création, consultation et mise à jour (nom, intitulé de poste, courriel et mot de passe à la création, identifiants d’équipes avec un minimum d’une équipe, identifiants de chef d’équipe nécessairement inclus dans les équipes sélectionnées). Un nouvel utilisateur est créé en tant que membre. Son rôle est ensuite synchronisé à partir des drapeaux de chef d’équipe. Le compte administrateur n’apparaît pas dans cette liste. Le formulaire ne permet pas de le réaffecter.
- Gérer les équipes : création, consultation et mise à jour (nom, chef, membres, projets rattachés).
- Gérer les projets et y rattacher des équipes : création, consultation et mise à jour.

L’administrateur ne gère pas de tâches personnelles. S’il emprunte les routes d’exécution, le système le ramène vers son tableau de bord.

### 2.1.3 Analyse des besoins non fonctionnels

Nous retenons les besoins non fonctionnels suivants, qui contraignent autant la conception que la réalisation.

Sur le plan de la sécurité, Briefly est une application fermée. L’authentification web s’appuie sur une session. L’authentification JSON, destinée au client mobile, s’appuie sur un jeton. L’administration, le pilotage d’équipe et l’exécution des tâches sont cloisonnés par rôle. Les politiques d’accès précisent, au-delà du simple rôle, qui peut voir, créer ou modifier une ressource. Un chef ne voit un projet que s’il est rattaché à l’une de ses équipes. Un membre ne met à jour que ses propres tâches. Les formulaires web sont protégés contre les attaques CSRF. Le détail des middleware et des politiques relève de la conception (chapitre 3).

L’information d’avancement est présentée par regroupement par statut, pourcentages dérivés, tableaux de bord différenciés selon le rôle, filtre des tâches d’équipe et fiche membre. Les parcours d’erreur (identifiants invalides, note de blocage manquante) renvoient l’utilisateur vers le formulaire avec un message explicite.

L’interface est disponible en français et en anglais. Côté web, le choix de langue est mémorisé en session (`/locale/fr` ou `/locale/en`) et s’applique aux libellés comme aux messages de validation. Côté mobile, la langue est locale à l’appareil. Elle ne modifie pas la locale du serveur Laravel.

Les règles métier (qui peut assigner, qui peut mettre à jour un statut, qui peut voir une fiche membre) vivent côté serveur. Un nouveau client ne doit pas les réimplémenter. Le drapeau `is_team_lead` permet d’étendre le nombre d’équipes dirigées sans inventer un quatrième acteur.

Les listes d’historique sont paginées. Les tableaux de bord s’appuient sur des agrégats (comptages, taux) et n’y versent pas l’historique complet. Ces choix visent un temps de réponse acceptable pour un usage quotidien, y compris lorsque plusieurs équipes et projets coexistent.

En tant qu’outil interne de suivi, l’application doit rester joignable pendant les plages de travail des équipes. L’indisponibilité de l’authentification ou des tableaux de bord priverait les membres de leur point d’avancement et les chefs de leur vue d’ensemble.

Briefly s’adresse à un client web et à un client mobile. Les deux clients exposent les mêmes droits et les mêmes invariants (assigné membre de l’équipe de contexte, note de blocage obligatoire, redirection de l’administrateur, etc.). La logique métier est spécifiée une fois, puis servie aux deux canaux.

## 2.2 Spécification semi-formelle

Nous poursuivons en UML d’analyse. Les diagrammes de cas d’utilisation donnent une vue d’ensemble par acteur. Les diagrammes de séquences décrivent, en boîte noire, l’authentification et le mot de passe oublié. Les maquettes fil de fer donnent la disposition des écrans principaux. Le backlog produit traduit ces exigences en sprints.

### 2.2.1 Diagrammes de cas d’utilisation

La figure 2.1 donne une vue d’ensemble des trois acteurs et des cas principaux. Les figures 2.2 à 2.4 détaillent ensuite chaque rôle. Les relations « include » et « extend » n’apparaissent que lorsqu’elles correspondent à une règle réelle du système.

![Vue d’ensemble des cas d’utilisation](captures/diag-uc-overview.png){width=15cm}

**Figure 2.1** – Vue d’ensemble des cas d’utilisation de Briefly

L’administrateur s’authentifie, gère son profil, puis opère exclusivement dans l’administration. Un utilisateur ne peut pas être créé sans équipe : `StoreUserRequest` impose le champ `team_ids` avec au moins un identifiant. « Affecter au moins une équipe » est donc un « include » de « Gérer les utilisateurs », et non un « extend ». La création synchronise aussi le rôle à partir des drapeaux de chef. La gestion des projets inclut le rattachement d’équipes.

![Cas d’utilisation de l’administrateur](captures/diag-uc-admin.png){width=15cm}

**Figure 2.2** – Cas d’utilisation de l’administrateur

Le chef d’équipe hérite des cas du membre : tableau de bord personnel, tâches assignées, points d’avancement. Les cas propres au pilotage s’y ajoutent. La création d’une tâche inclut la vérification que l’assigné appartient à l’équipe de contexte. La consultation d’une fiche membre inclut la vérification d’une équipe gérée en commun.

![Cas d’utilisation du chef d’équipe](captures/diag-uc-lead.png){width=15cm}

**Figure 2.3** – Cas d’utilisation du chef d’équipe

Le membre s’authentifie, gère son profil, puis travaille sur son suivi personnel. « Valider la note de blocage » étend « Mettre à jour l’avancement ». Cette validation n’a lieu que lorsque le statut choisi est `BLOCKED`. Un « include » aurait signifié qu’elle s’exécute à chaque mise à jour.

![Cas d’utilisation du membre](captures/diag-uc-member.png){width=15cm}

**Figure 2.4** – Cas d’utilisation du membre

Les figures n’incluent pas la création de projet par le chef d’équipe, l’auto-inscription, ni la gestion de tâches personnelles par l’administrateur.

### 2.2.2 Diagrammes de séquences (analyse, boîte noire : utilisateur ↔ système)

Seuls l’utilisateur et le système apparaissent. Les collaborations internes (services d’administration, service des tâches, politiques) relèvent de la conception et seront traitées au chapitre 3. Les fragments UML « opt » et « alt » marquent une option (cocher « se souvenir de moi » sur le web) et une alternative (succès ou refus).

**Pré-conditions.** Un compte a été créé par un administrateur. L’utilisateur n’est pas authentifié, ou sa session (respectivement son jeton) a expiré. Il connaît l’adresse de courriel associée au compte.

**Scénario nominal.**

1. L’utilisateur ouvre l’écran de connexion.
2. Il saisit son courriel et son mot de passe.
3. Sur le client web, il peut cocher « se souvenir de moi ».
4. Il soumet le formulaire.
5. Le système vérifie les identifiants.
6. En cas de succès :
   - Sur le client web, le système établit une session et redirige vers le tableau de bord administrateur si l’utilisateur est administrateur, vers le tableau de bord personnel sinon.
   - Sur le client JSON, le système émet un jeton Sanctum et renvoie ce jeton ainsi que la ressource utilisateur.
7. En cas d’échec (identifiants invalides) :
   - Sur le client web, le système renvoie l’utilisateur au formulaire, avec les erreurs, sans réafficher le mot de passe.
   - Sur le client JSON, le système répond par le code 422, accompagné du message et des erreurs.

![Authentification](captures/diag-seq-auth.png){width=14cm}

**Figure 2.5** – Authentification

**Post-conditions (succès).** Session web ou jeton JSON actif ; accès au tableau de bord du rôle.

**Post-conditions (échec).** Aucune session ni jeton durable ; parcours de connexion inchangé.

**Pré-conditions.** L’utilisateur n’est pas authentifié. Il dispose de l’adresse de courriel de son compte.

**Scénario nominal.**

1. L’utilisateur demande un lien de réinitialisation en saisissant son courriel.
2. Le système enregistre la demande et, si l’adresse correspond à un compte, envoie un message contenant un jeton et un lien.
3. L’utilisateur ouvre le lien (ou saisit le jeton, côté mobile).
4. Il choisit un nouveau mot de passe, confirmé, d’une longueur minimale de huit caractères.
5. Le système vérifie le jeton, met à jour le mot de passe et confirme le succès.
6. L’utilisateur peut alors s’authentifier avec le nouveau mot de passe.

En cas d’adresse inconnue, le système refuse la demande avec une erreur de validation visible (retour au formulaire côté web, code 422 côté JSON). En cas de jeton invalide, le mot de passe n’est pas modifié.

![Mot de passe oublié](captures/diag-seq-forgot.png){width=14cm}

**Figure 2.6** – Mot de passe oublié

**Post-conditions (succès).** Mot de passe remplacé ; nouvelle connexion requise.

**Post-conditions (échec).** Mot de passe antérieur inchangé.

Les enchaînements impliquant les services internes, les politiques et les agrégats de tableau de bord seront formalisés au chapitre 3.

### 2.2.3 Maquettage des interfaces

Nous avons dessiné dans Figma des maquettes fil de fer (*wireframes*) des écrans principaux. Une maquette fil de fer fixe la disposition d’un écran sans couleur ni contenu réel. Un rectangle barré tient la place d’une icône ou d’une image, une barre grise celle d’un texte variable. Ces maquettes associent chaque cas d’utilisation de la section 2.2.1 à un écran et permettent de vérifier que chaque acteur y trouve les informations et les commandes dont il a besoin.

Nous avons retenu les écrans qui portent les cas d’utilisation principaux de chaque acteur : la connexion, commune à tous, le tableau de bord et le détail de tâche du membre, la vue d’équipe du chef et la vue d’ensemble de l’administrateur. Les écrans web partagent la même structure. Un menu latéral propose des entrées qui dépendent du rôle. Un en-tête rappelle la page courante et porte le sélecteur de langue. La zone de contenu est organisée en cartes.

L’écran de connexion ne propose aucun lien d’inscription, puisque seul l’administrateur crée les comptes. Il donne accès au parcours de mot de passe oublié.

![Maquette de la connexion](captures/wf-web-login.png){width=15cm}

**Figure 2.7** – Maquette fil de fer de l’écran de connexion (web)

Le tableau de bord du membre affiche son taux d’accomplissement, puis la liste de ses tâches. Chaque tâche porte son statut et une barre d’avancement dont le pourcentage découle du statut.

![Maquette du tableau de bord membre](captures/wf-web-dashboard.png){width=15cm}

**Figure 2.8** – Maquette fil de fer du tableau de bord du membre (web)

Le détail d’une tâche place à gauche les informations de la tâche et son historique, à droite le formulaire de mise à jour. Le champ de note de blocage n’est exigé que pour le statut « Bloqué ».

![Maquette du détail de tâche](captures/wf-web-task-detail.png){width=15cm}

**Figure 2.9** – Maquette fil de fer du détail de tâche et de la mise à jour d’avancement (web)

La vue d’équipe donne au chef quatre indicateurs, un filtre par statut et le tableau des tâches de ses équipes. La colonne « Blocage » lui permet de repérer les tâches à débloquer.

![Maquette des tâches d’équipe](captures/wf-web-team-tasks.png){width=15cm}

**Figure 2.10** – Maquette fil de fer de la vue des tâches d’équipe du chef (web)

La vue d’ensemble de l’administrateur présente les effectifs, les taux d’accomplissement par projet et par équipe, ainsi que la répartition des tâches par statut. Son menu ne contient pas d’entrée « Tâches », car l’administrateur n’exécute pas de tâche.

![Maquette de la vue d’ensemble administrateur](captures/wf-web-admin-dashboard.png){width=15cm}

**Figure 2.11** – Maquette fil de fer de la vue d’ensemble de l’administrateur (web)

Sur mobile, le menu latéral devient une barre de navigation inférieure à trois onglets. Les écrans conservent les champs du web. La saisie d’avancement comporte le statut, ce qui a été fait, la prochaine étape et la note de blocage.

![Maquettes mobiles](captures/wf-mobile.png){width=15cm}

**Figure 2.12** – Maquettes fil de fer mobiles : connexion, tableau de bord et détail de tâche

### 2.2.4 Planification des sprints

L’analyse se traduit en un backlog produit, découpé selon le plan Scrum du projet. Chaque sprint livre un ensemble cohérent, dans l’ordre des dépendances : le socle d’accès d’abord, puis l’exécution membre, le pilotage chef d’équipe, l’administration, la consolidation des tests et de la sécurité, enfin le client mobile. Les durées ci-dessous correspondent au calendrier 2026 du mémoire. Les sprints 1 à 4 livrent le client web. Le sprint 5 renforce la suite PHPUnit, formalise la sécurité (chapitre 4, §4.3) et branche l’intégration continue (§4.4). Le sprint 6 développe Flutter en parallèle du module mobile en formation, jusqu’à la mise en ligne alwaysdata début octobre.

**Tableau 2.1** – Backlog du produit

| Nom de sprint | Tâches | Durée |
| --- | --- | --- |
| Sprint 1 : Authentification, profil, langue et thème | Connexion par courriel et mot de passe. Option « se souvenir de moi » (web). Déconnexion. Mot de passe oublié et réinitialisation. Modification du nom et du courriel. Changement de mot de passe. Bascule français / anglais. Thème clair / sombre. Absence d’auto-inscription | du 2 au 22 février 2026 (3 semaines) |
| Sprint 2 : Espace membre | Tableau de bord personnel, liste et détail des tâches, mise à jour d’avancement (note de blocage obligatoire si statut bloqué), historique paginé. Restriction de la mise à jour à l’assigné | du 23 février au 15 mars 2026 (3 semaines) |
| Sprint 3 : Espace chef d’équipe | Enrichissement du tableau de bord par équipe dirigée (totaux, en cours, bloquées, terminées dans la semaine, taux, dernières tâches bloquées). Liste des projets des équipes gérées. Détail de projet. Création de tâche (titre, description, assigné membre de l’équipe de contexte, statut). Modification de tâche. Vue des tâches d’équipe avec filtre de statut. Fiche membre conditionnée au partage d’une équipe gérée. Exclusion de la création de projets et d’utilisateurs | du 16 mars au 5 avril 2026 (3 semaines) |
| Sprint 4 : Espace administrateur | Tableau de bord administrateur (effectifs, taux global, graphique de statuts, taux par équipe et par projet). Création, consultation et mise à jour des utilisateurs (équipe minimale, drapeaux chef, rôle initial membre puis synchronisation, interdiction de réaffecter un administrateur). Gestion des équipes. Gestion des projets et rattachement d’équipes. Redirection `EnsureNotAdmin` hors des routes d’exécution | du 6 au 26 avril 2026 (3 semaines) |
| Sprint 5 : Tests, sécurité et intégration continue | Consolidation de la suite PHPUnit (tableaux 4.4 à 4.7). Mesures de sécurité (§4.3). Workflow GitHub Actions sur le dépôt (§4.4). Captures de code des figures 4.35 à 4.42 | du 27 avril au 25 juillet 2026 (13 semaines) |
| Sprint 6 : Application mobile Flutter | Client mobile consommant les mêmes parcours (authentification par jeton, profil, espaces membre, chef d’équipe et administrateur). Module mobile en formation. Tests JSON (tableau 4.8). Mise en ligne alwaysdata début octobre | du 1er septembre au 10 octobre 2026 (6 semaines) |

Ce découpage ordonne la livraison des exigences déjà analysées. Le sprint 5 consolide le socle web avant le second client. Entre le 25 juillet et le 1er septembre, le calendrier ne prévoit pas de sprint actif : le client mobile reprend à la rentrée, en parallèle du module Flutter en formation. Le sprint 6 se termine début octobre, avant la soutenance du 27 octobre.

## Conclusion

Le système est nominatif. Hors connexion et mot de passe oublié, aucun visiteur n’a de parcours. L’administrateur crée les comptes, les équipes et les projets. Il n’exécute pas les tâches. Le chef d’équipe pilote ses équipes et crée des tâches. Il ne crée ni les projets ni les utilisateurs. Le membre ne met à jour que les tâches qui lui sont assignées. Une tâche bloquée exige une note. Les pourcentages d’avancement viennent du statut : 0 %, 60 %, 30 % ou 100 %.

Les cas d’utilisation, les séquences en boîte noire et les maquettes traduisent ces frontières en écrans web et mobile. Le backlog est réparti selon le tableau 2.1. Cet ensemble constitue le cahier des charges interne du projet.
