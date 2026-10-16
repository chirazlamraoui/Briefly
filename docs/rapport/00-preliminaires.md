![](captures/logo-adg-education.png){width=4.2cm}

::: {custom-style="CoverOrg"}
ADG Education
:::

::: {custom-style="CoverSub"}
Campus Paris · 15 rue du Louvre, 75001 Paris
:::

::: {custom-style="CoverLabel"}
Titre professionnel
:::

::: {custom-style="CoverDiploma"}
Concepteur développeur d’applications
:::

::: {custom-style="CoverSub"}
CDA · RNCP 37873 · niveau 6 (bachelor)
:::

::: {custom-style="CoverRule"}
 
:::

::: {custom-style="CoverLabel"}
Mémoire de fin de formation
:::

::: {custom-style="CoverTitle"}
Conception et développement de Briefly
:::

::: {custom-style="CoverSubtitle"}
Application web et mobile de suivi de l’avancement des équipes
:::

::: {custom-style="CoverRule"}
 
:::

::: {custom-style="CoverMeta"}
Présenté par Chiraz Lamraoui
:::

::: {custom-style="CoverMeta"}
Entreprise d’accueil : Drif Consult
:::

::: {custom-style="CoverMeta"}
Tuteur entreprise : M. Yasser El Habib Drif
:::

::: {custom-style="CoverMeta"}
Enseignant responsable : M. Anis BENAMMAR
:::

::: {custom-style="CoverMeta"}
Soutenance : 27 octobre 2026
:::

::: {custom-style="CoverMeta"}
Année académique 2025-2026
:::

\newpage

# Remerciements

Je tiens à remercier **ADG Education – Campus Paris**, où j’ai eu l’opportunité d’effectuer mon Bachelor et de construire mon parcours académique et professionnel.

Je remercie également **Anis Benammar**, mon encadrant, pour son accompagnement dans le cadre de ce projet de fin d’études, ainsi que **Yasser El Habib Drif**, mon tuteur d’alternance, pour sa confiance, ses conseils et son accompagnement tout au long de cette expérience.

Une pensée toute particulière va à **ma famille, mes sœurs et ma nièce**, pour leur présence, leurs encouragements et leur soutien tout au long de mon parcours.

Enfin, merci à **la personne qui partage mon quotidien**, pour sa présence, sa patience et son soutien durant cette aventure.

Merci à toutes les personnes qui ont contribué, de près ou de loin, à l’aboutissement de cette belle étape.

# Résumé

Ce mémoire présente Briefly, une application web et mobile de suivi de l’avancement des équipes, réalisée dans le cadre du titre professionnel Concepteur développeur d’applications, en alternance chez Drif Consult. Le projet part d’un constat. Le point quotidien (*stand-up*) se tient le plus souvent dans une messagerie, sans lien avec les tâches, et les chefs d’équipe n’ont pas de tableau de bord à jour.

Briefly s’adresse à une organisation fermée, sans inscription publique. L’administrateur crée les comptes, les équipes et les projets. Le chef d’équipe crée et suit les tâches de ses équipes. Le membre rend compte de chaque tâche en trois champs : ce qui a été réalisé, ce qui vient ensuite et ce qui bloque. Une tâche au statut bloqué exige une note de blocage. Les tableaux de bord calculent un taux d’avancement par membre, par équipe et par projet.

Le développement a suivi la méthode Scrum, en six sprints. Le serveur repose sur Laravel 13 (PHP 8.5), Blade, Bootstrap et MySQL. Le client mobile est écrit en Flutter. Un seul ensemble de routes Laravel sert le navigateur en HTML et l’application mobile en JSON, authentifiée par un jeton Sanctum. Les règles d’autorisation et de validation ne sont donc écrites qu’une fois. Une suite de tests PHPUnit vérifie ces règles, notamment les refus d’accès entre rôles. Le serveur a été mis en ligne chez alwaysdata ; l’application mobile installée sur iPhone s’y connecte en HTTPS.

**Mots-clés :** application web, application mobile, Laravel, Flutter, Sanctum, MySQL, suivi d’avancement, Scrum, UML.

# Abstract

This report presents Briefly, a web and mobile application for tracking team progress. It was built for the French professional title *Concepteur développeur d’applications* (application designer and developer) during a work-study placement at Drif Consult. The project starts from a common situation. The daily stand-up usually happens in a chat tool, with no link to the tasks, and team leads have no up-to-date dashboard.

Briefly targets a closed organisation with no public sign-up. The administrator creates accounts, teams and projects. The team lead creates and follows the tasks of their teams. Members report on each task in three fields: what was done, what comes next and what is blocking. A task marked as blocked requires a blocker note. Dashboards compute a completion rate per member, per team and per project.

Development followed Scrum over six sprints. The server is built with Laravel 13 (PHP 8.5), Blade, Bootstrap and MySQL. The mobile client is written in Flutter. A single set of Laravel routes serves HTML to the browser and JSON to the mobile app, which authenticates with a Sanctum token. Authorisation and validation rules are therefore written only once. A PHPUnit test suite checks these rules, including access refusals between roles. The Laravel application is hosted on alwaysdata; the Flutter app on a physical iPhone connects to that HTTPS server.

**Keywords:** web application, mobile application, Laravel, Flutter, Sanctum, MySQL, progress tracking, Scrum, UML.

\newpage
