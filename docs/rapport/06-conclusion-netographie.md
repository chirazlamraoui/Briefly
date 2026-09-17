# Conclusion générale

Nous avons conçu et réalisé Briefly, une application interne de suivi d’avancement, en version web et mobile. Dans le périmètre des chapitres 2 à 4, l’avancement est lisible, structuré, et consultable depuis les deux clients, sans inscription publique.

Le travail a suivi l’étude préalable, l’analyse des besoins, la conception, puis cinq sprints (tableau 2.1). Nous y avons pratiqué Laravel, Flutter, UML, Scrum et des tests automatisés. La communication unifiée HTML/JSON (§3.1.1) évite de dupliquer les règles métier : un seul ensemble de routes sert le navigateur et l’application mobile.

Le livrable reste un prototype académique. Il n’y a pas de notifications push, de calendrier partagé ni d’export PDF. Un brief quotidien d’équipe, envisagé plus tôt, n’est pas dans cette version. L’intégration continue est trop simple pour un usage industriel.

Les pistes suivantes ne sont pas implémentées : notifications push, brief quotidien d’équipe optionnel, calendrier des tâches, export PDF des tableaux de bord, pipeline d’intégration continue plus complet.

Briefly fournit donc un socle de suivi d’avancement sur le web et sur mobile, dans le périmètre fixé. Des évolutions restent possibles ensuite.

---

# Résumé

Ce mémoire décrit la conception et le développement de Briefly, application interne de suivi d’avancement des équipes, en version web (Laravel) et mobile (Flutter). L’outil s’adresse à des équipes déjà constituées. Les utilisateurs sont administrateur, chef d’équipe ou membre. Les membres déposent des points d’avancement structurés. Des tableaux de bord en donnent une lecture d’ensemble. Il n’y a pas d’auto-inscription publique. Le travail a suivi Scrum. Un serveur unique sert les deux clients, en HTML ou en JSON selon le contexte (communication unifiée). Notifications, brief quotidien, calendrier, export PDF et intégration continue plus complète restent hors du livrable actuel.

**Mots clés :** Application web, Application mobile, Laravel, Flutter, MySQL, Sanctum, Scrum, suivi de tâches.

---

# Abstract

This dissertation describes the design and development of Briefly, an internal application for tracking team progress, as a web client (Laravel) and a mobile client (Flutter). The tool is for teams that already exist. Users are administrator, team lead, or member. Members report structured progress updates. Dashboards give an overall view. There is no public self-registration. The work followed Scrum. One server serves both clients, returning HTML or JSON according to the request (unified communication). Push notifications, a daily team brief, a calendar, PDF export, and fuller continuous integration are not part of this deliverable.

**Keywords:** Web application, Mobile application, Laravel, Flutter, MySQL, Sanctum, Scrum, task tracking.

---

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
