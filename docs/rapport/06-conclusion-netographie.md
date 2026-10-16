# Conclusion générale

Ce mémoire a présenté la conception et la réalisation de Briefly, une application interne de suivi d’avancement des équipes, disponible en version web (Laravel) et mobile (Flutter). Le point de départ était un besoin simple : outiller le *stand-up* quotidien, que les équipes tiennent le plus souvent dans une messagerie, sans structure ni lien avec les tâches.

Briefly répond à ce besoin pour des équipes déjà constituées, sans inscription publique. L’administrateur crée les comptes, les équipes et les projets. Le chef d’équipe crée et suit les tâches de ses équipes. Le membre rend compte de ses tâches en trois champs : ce qui a été réalisé, ce qui vient ensuite, et ce qui bloque. Une tâche bloquée exige une note. Les tableaux de bord de chaque rôle calculent ensuite un taux d’avancement par membre, par équipe et par projet.

Le travail a suivi l’étude préalable, l’analyse des besoins, la conception, puis six sprints (tableau 2.1). La communication unifiée (§3.1.1) sert le web en HTML et le mobile en JSON depuis le même serveur Laravel ; PHPUnit et GitHub Actions en vérifient les règles avant chaque évolution du dépôt.

Ce projet nous a permis de pratiquer l’ensemble du cycle d’une application : analyse UML, architecture en couches, conception d’une base relationnelle, développement web avec Laravel, développement mobile avec Flutter, authentification par jeton avec Sanctum, tests automatisés, intégration continue et mise en ligne du serveur (chapitre 5).

## Perspectives

Cette version n’inclut pas encore certaines fonctions. Plusieurs évolutions sont envisageables pour les prochaines versions de Briefly :

- **Notifications push.** Prévenir le chef d’équipe sur mobile lorsqu’une tâche passe au statut bloqué, et le membre lorsqu’une tâche lui est assignée.
- **Brief quotidien d’équipe.** Rassembler chaque jour les points d’avancement de tous les membres, à partir de l’historique déjà conservé dans `task_updates`.
- **Export PDF.** Générer un état d’avancement à partir des tableaux de bord, à joindre à un compte rendu de réunion.
- **Calendrier partagé.** Afficher sur une même vue les échéances des tâches et des projets.
- **Sécurité de l’authentification.** Limiter le nombre de tentatives sur `POST /login` et donner une durée de validité aux jetons Sanctum (§4.3.6).
- **Déploiement automatisé.** Prolonger le workflow d’intégration continue par une mise en production de l’application après le succès des tests.
- **Tests mobiles.** Étendre les tests Flutter, qui portent aujourd’hui sur l’accès authentifié et la validation de l’avancement, aux principaux écrans.

Les règles métier restant sur le serveur Laravel, chacune de ces évolutions profiterait aux deux clients sans dupliquer la logique.

# Netographie

[1] https://www.planzone.fr/blog/quest-ce-que-la-methodologie-agile, consulté le 08/09/2026.

[2] https://agiliste.fr/introduction-methodes-agiles/, consulté le 08/09/2026.

[3] https://scrumguides.org/scrum-guide.html, consulté le 08/09/2026.

[4] https://www.atlassian.com/fr/agile/scrum, consulté le 08/09/2026.

[5] https://www.atlassian.com/software/jira, consulté le 10/09/2026.

[6] https://trello.com/, consulté le 10/09/2026.

[7] https://monday.com/, consulté le 10/09/2026.

[8] https://linear.app/, consulté le 10/09/2026.

[9] https://slack.com/, consulté le 10/09/2026.

[10] https://laravel.com/docs/13.x, consulté le 12/09/2026.

[11] https://laravel.com/docs/13.x/sanctum, consulté le 12/09/2026.

[12] https://laravel.com/docs/13.x/blade, consulté le 12/09/2026.

[13] https://laravel.com/docs/13.x/eloquent, consulté le 12/09/2026.

[14] https://getbootstrap.com/docs/5.3/getting-started/introduction/, consulté le 12/09/2026.

[15] https://www.chartjs.org/docs/latest/, consulté le 12/09/2026.

[16] https://docs.flutter.dev/, consulté le 14/09/2026.

[17] https://riverpod.dev/docs/introduction/getting_started, consulté le 14/09/2026.

[18] https://pub.dev/packages/dio, consulté le 14/09/2026.

[19] https://pub.dev/packages/go_router, consulté le 14/09/2026.

[20] https://www.ibm.com/docs/fr/rsm/7.5.0?topic=uml-sequence-diagrams, consulté le 14/09/2026.

[21] https://www.drawio.com/doc/, consulté le 15/09/2026.

[22] https://dev.mysql.com/doc/refman/8.4/en/, consulté le 15/09/2026.

[23] https://docs.phpunit.de/, consulté le 15/09/2026.

[24] https://laravel.com/docs/13.x/hashing, consulté le 01/10/2026.

[25] https://docs.github.com/fr/actions, consulté le 01/10/2026.

[26] https://www.alwaysdata.com/fr/offers/, consulté le 03/10/2026.

[27] https://help.alwaysdata.com/fr/docs/hebergement-web/languages/php/, consulté le 03/10/2026.
