# Introduction générale

Les équipes internes doivent savoir qui travaille sur quoi, où en sont les tâches et ce qui bloque. Cette information se disperse souvent entre messageries, courriels et réunions. Il devient difficile d’en tirer un état des lieux fiable.

Le point quotidien oral ou écrit ne tient pas longtemps. Les blocages arrivent tard, parfois seulement en réunion. Les chefs d’équipe n’ont pas de tableau de bord à jour. Les outils du marché couvrent mal ce besoin. Certains privilégient la conversation. D’autres imposent une gestion de projet trop lourde pour un usage interne. Le chapitre 1 développe ce constat et compare les solutions existantes.

Nous avons conçu et réalisé Briefly, une application interne de suivi d’avancement. Elle existe en client web (Laravel) et en client mobile (Flutter). Les comptes sont créés par l’administrateur. Il n’y a pas d’inscription publique. Les utilisateurs sont administrateur, chef d’équipe ou membre. Les chapitres suivants détaillent les besoins, l’architecture et les écrans.

## Plan du rapport

Le premier chapitre présente le projet, la problématique et une critique de l’existant. Il justifie le besoin d’un outil interne dédié et situe le projet par rapport aux compétences du titre CDA.

Le deuxième chapitre analyse et spécifie les besoins. À partir des acteurs, nous formalisons les exigences fonctionnelles et non fonctionnelles, puis les cas d’utilisation et les maquettes fil de fer des écrans principaux.

Le troisième chapitre décrit la conception : architectures côté serveur et côté clients, diagrammes utilisés avant le développement et schéma relationnel de la base.

Le quatrième chapitre décrit la réalisation : environnement de travail, sprints, interfaces web et mobile, puis sécurité de l’application et intégration continue.

Le cinquième chapitre présente la mise en ligne chez alwaysdata, les contrôles sur le web et le branchement de l’application mobile sur ce serveur.

La conclusion générale dresse le bilan, les apports du projet et les pistes d’évolution.
