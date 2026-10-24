# Chapitre 3 : Conception

## Introduction

Le chapitre 2 a fixé le périmètre fonctionnel de Briefly et les exigences qui en découlent. Nous traduisons ici ces spécifications en une architecture et en des collaborations d’objets assez précises pour orienter la réalisation, du déploiement physique jusqu’aux règles métier et aux données persistées.

Nous concevons Briefly comme une application interne unique, accessible depuis un navigateur et depuis une application mobile, et portée par un même serveur d’application. Les règles de validation, d’autorisation et de persistance forment une source de vérité unique, quel que soit le client. La conception formalise cette organisation, d’abord à l’échelle globale (architectures physique et logique), puis à l’échelle des collaborations (séquences, activité, classes) et du schéma relationnel.

La section 3.1 présente l’architecture globale : le déploiement 3-tiers, le principe de communication unifiée et le découpage logique en couches. La section 3.2 descend au niveau des objets : trois scénarios métier représentatifs, le flux d’activité de mise à jour d’une tâche, le modèle de classes du domaine, le schéma relationnel qui en découle, puis le rôle du client mobile dans cette architecture.

## 3.1 Conception globale

Cette section pose où s’exécute chaque responsabilité, comment les clients dialoguent avec le serveur, et selon quelles couches l’application est organisée. Les collaborations de la section 3.2 s’appuient sur ces frontières.

### 3.1.1 Architecture physique

Nous retenons une architecture physique **3-tiers**. Les responsabilités de présentation, de traitement et de persistance sont déployées sur des niveaux distincts.

Le **premier niveau** est celui des clients. Deux supports d’interface coexistent : le navigateur, qui affiche des pages interactives produites par le serveur, et l’application mobile, qui affiche des écrans alimentés par des réponses structurées du même serveur. Ces deux clients n’embarquent pas le métier. Ils formulent des requêtes vers le serveur d’application et en restituent le résultat à l’utilisateur.

Le **deuxième niveau** est le serveur d’application. Il accueille le routage, l’authentification, l’autorisation, la validation des entrées, les traitements métier et l’accès aux données. C’est à ce niveau que se concentrent les règles que nous ne voulons pas dupliquer d’un client à l’autre.

Le **troisième niveau** est le serveur de données relationnel. Il conserve les utilisateurs, les équipes, les projets, les tâches et l’historique des points d’avancement. Les clients n’y accèdent jamais directement.

![Architecture physique 3-tiers](captures/diag-arch-physical.png){width=15cm}

**Figure 3.1** – Architecture physique de Briefly (déploiement 3-tiers)

Ce découpage se justifie, dans le contexte de Briefly, par trois critères.

**Flexibilité.** Les deux clients évoluent à des rythmes différents : l’interface web et l’interface mobile peuvent être enrichies indépendamment, tant que le contrat d’échange avec le serveur reste stable. Le serveur d’application peut être déployé ou mis à jour indépendamment du schéma de données, dès lors que les évolutions de structure restent maîtrisées.

**Sécurité.** Les identifiants de base de données, les secrets d’authentification et les données sensibles ne quittent pas le périmètre serveur. Les clients ne reçoivent que les informations autorisées pour leur rôle. L’autorisation s’exerce avant tout accès aux données métier, ce qui empêche un client, même authentifié, d’outrepasser son rôle. L’absence d’auto-inscription, déjà posée au chapitre 2, se traduit par un point d’entrée unique : la création d’utilisateur est une opération d’administration, pas un parcours public.

**Performance.** Le serveur d’application concentre les agrégations utiles aux tableaux de bord, le chargement des relations et la pagination de l’historique. Il évite aux clients de multiplier les accès directs à la base. Le navigateur peut s’appuyer sur un rendu préparé par le serveur pour les pages métier. Le mobile ne récupère que les données nécessaires à l’écran courant. Le dimensionnement du serveur de données reste indépendant des clients.

#### Communication unifiée : un catalogue d’opérations, deux formes de réponse

Briefly ne duplique pas deux catalogues d’opérations, l’un pour le web et l’autre pour le mobile. Les opérations métier (connexion, consultation d’un tableau de bord, mise à jour d’avancement, administration des comptes, etc.) sont exposées une seule fois côté serveur. Selon le client, la même opération produit soit une page ou une redirection adaptée au navigateur, soit une réponse structurée adaptée au mobile.

Ce choix unifie le contrat fonctionnel. Une création de tâche, une mise à jour d’avancement ou une consultation de tableau de bord empruntent le même chemin logique, que l’acteur soit devant le navigateur ou devant l’application mobile. Les règles de validation et d’autorisation s’appliquent une seule fois, ce qui limite les écarts entre clients.

L’authentification s’adapte au mode d’usage, tout en protégeant les mêmes opérations :

- **Navigateur.** L’utilisateur s’authentifie par session. Après une connexion réussie, il accède au tableau de bord correspondant à son rôle (administration ou suivi opérationnel).
- **Mobile.** Le même parcours de connexion peut aboutir à un jeton d’accès durable, transmis avec les requêtes suivantes. La déconnexion invalide ce jeton côté serveur.

Le navigateur s’appuie sur une session, le mobile sur un jeton. Ces deux modes protègent les mêmes opérations : le serveur distingue le contexte du client sans réécrire le métier.

![Communication unifiée](captures/diag-arch-unified.png){width=15cm}

**Figure 3.2** – Communication unifiée entre clients et serveur

**Tableau 3.1** – Filtres d’accès (conception)

| Filtre | Rôle de conception |
| --- | --- |
| Espace administrateur | Les opérations de gestion des comptes, des équipes et des projets sont réservées à l’administrateur. |
| Espace chef d’équipe | La création de tâches et la vue d’ensemble d’équipe sont réservées au chef d’équipe. |
| Exclusion de l’administrateur des tâches | L’administrateur ne suit pas les tâches comme un membre : il est tenu hors des parcours d’exécution et de mise à jour d’avancement. |
| Langue d’interface | Sur le web, la langue est une préférence de l’utilisateur. Sur mobile, elle est choisie sur l’appareil et ne modifie pas les données métier. |

Ces filtres s’appliquent aux groupes de parcours. Des règles plus fines, liées à une ressource précise (un projet, une tâche), complètent ce dispositif au moment de l’action, comme nous le précisons à la sous-section suivante.

### 3.1.2 Architecture logique

Nous organisons le serveur d’application en couches à responsabilités séparées. La couche de coordination reçoit la requête, vérifie les droits et la forme des données, puis délègue le traitement métier. Les règles « qui peut agir sur quoi » ne se confondent pas avec les règles « comment persister ou calculer ».

Les couches retenues sont les suivantes.

**Présentation (côté clients).** Le navigateur et le mobile collectent la saisie, affichent les tableaux de bord et transmettent les actions utilisateur. Cette couche n’embarque pas les invariants métier : elle peut toutefois guider la saisie (par exemple signaler qu’une note de blocage manque) sans se substituer au serveur.

**Coordination.** Cette couche achemine la requête vers le bon traitement, contrôle la forme des données (champs obligatoires, formats, cohérence des listes) et choisit la réponse adaptée au client (page ou données structurées).

**Autorisation.** Elle répond à la question : l’utilisateur authentifié a-t-il le droit d’effectuer cette action sur cette ressource ? Exemples structurants pour Briefly : un chef d’équipe n’accède à un projet que si l’une de ses équipes y est rattachée ; seul le membre assigné peut mettre à jour l’avancement d’une tâche ; l’administrateur ne crée pas de tâche pour son propre compte d’exécution.

**Application / métier.** Elle regroupe les traitements qui dépassent une simple écriture en base : synchronisation des appartenances aux équipes et dérivation du rôle chef, vérification que l’assigné d’une tâche appartient à l’équipe de contexte, enregistrement d’un point d’avancement avec historique, calculs des indicateurs de tableaux de bord.

**Domaine.** Entités et énumérations portent l’état métier (utilisateur, équipe, projet, tâche, point d’avancement, rôles, statuts de tâche). Elles expriment les relations du modèle analysé au chapitre 2, indépendamment du canal utilisé par le client.

**Persistance.** Le modèle relationnel matérialise le domaine. Tables, clés étrangères et tables associatives traduisent les cardinalités du diagramme de classes. Les jetons d’accès mobile et les artefacts techniques (session, cache) relèvent aussi de cette couche, sans porter de règle métier propre.

![Architecture logique en couches](captures/diag-arch-layers.png){width=14cm}

**Figure 3.3** – Architecture logique en couches (dépendances descendantes)

Un scénario métier se lit de haut en bas : interface, coordination, autorisation, métier, persistance. Les diagrammes de séquence de la section 3.2 suivent cette chaîne. Dans certains cas simples, la coordination peut déléguer directement la persistance après autorisation, sans traitement métier intermédiaire.

## 3.2 Conception détaillée

Nous détaillons les collaborations d’objets pour l’entrée d’un utilisateur dans le système (par l’administrateur), la création d’une tâche par un chef d’équipe, et le point d’avancement saisi par le membre assigné. Suivent le flux d’activité de mise à jour, le diagramme de classes du domaine, le schéma relationnel, puis la place du client mobile.

### 3.2.1 Partie métier

Les trois séquences ci-dessous mettent en scène les mêmes types d’objets : **Acteur**, **Interface**, **Contrôleur**, **Service**, **Modèle**. L’interface désigne indifféremment l’écran web ou mobile ; le contrôleur représente le point d’entrée serveur commun aux deux clients.

#### Création d’un utilisateur par l’administrateur

Il n’existe pas de parcours public d’inscription. Seul un administrateur authentifié peut créer un compte.

Le contrat de saisie impose :

- identité et adresse électronique (unique dans l’organisation) ;
- mot de passe confirmé, d’une longueur minimale fixée ;
- appartenance à au moins une équipe existante ;
- indication optionnelle des équipes dont l’utilisateur est chef, choisies parmi celles auxquelles il appartient.

Le service métier crée le compte avec un rôle initial de membre, rattache les équipes, pose le statut de chef lorsque demandé, puis dérive le rôle « chef d’équipe » si au moins une équipe est dirigée. Un compte administrateur existant n’est ni créé ni réaffecté par ce parcours.

**Pré-conditions.** L’acteur est authentifié en tant qu’administrateur. Au moins une équipe existe. L’adresse électronique n’est pas déjà utilisée.

**Scénario nominal.** L’administrateur ouvre le formulaire de création, saisit l’identité, le mot de passe confirmé et les équipes, éventuellement en marquant certaines d’entre elles comme équipes de chefferie, puis valide. Le serveur contrôle les droits et la forme des données, délègue au service métier, qui persiste le compte et les appartenances. Le client affiche la confirmation ou la liste mise à jour.

Le fragment « alt » sépare la saisie acceptée de la saisie refusée. Dans le cas accepté, « loop » parcourt chaque équipe pour attacher l’utilisateur. « opt » pose le statut de chef seulement pour les équipes également cochées comme équipes dirigées.

**Post-conditions.** Un compte utilisateur existe, lié à au moins une équipe. Le rôle est membre ou chef d’équipe selon les équipes qu’il dirige. Aucune session n’est ouverte pour le nouvel utilisateur : il devra s’authentifier.

![Création d’un utilisateur](captures/diag-seq-create-user.png){width=15cm}

**Figure 3.4** – Diagramme de séquence : création d’un utilisateur par l’administrateur

#### Création d’une tâche par le chef d’équipe

La création d’une tâche s’inscrit dans un contexte d’équipe. Le chef d’équipe n’accède au projet que si l’une des équipes qu’il dirige y est rattachée. Il doit diriger au moins une équipe pour créer une tâche. Le serveur retient alors une équipe de contexte parmi les équipes qu’il dirige et qui sont liées au projet. Si aucune ne l’est, l’action est refusée.

Il reste à vérifier que l’assigné appartient à cette équipe de contexte. C’est le seul invariant métier qui s’ajoute à l’autorisation sur le projet.

Les données de saisie comprennent un titre obligatoire, une description optionnelle, un membre assigné et un statut initial parmi les valeurs du cycle de vie de la tâche.

**Pré-conditions.** L’acteur est authentifié en tant que chef d’équipe. Le projet visé est lié à l’une de ses équipes. L’équipe de contexte compte au moins un membre assignable.

**Scénario nominal.** Le chef ouvre le formulaire rattaché au projet, choisit un membre de l’équipe, un titre, un statut initial, éventuellement une description, puis valide. Après contrôle des droits et des données, le service métier vérifie le projet et l’assigné, puis crée la tâche rattachée au projet.

Le fragment « alt » sépare le cas où l’assigné appartient à l’équipe de contexte du cas où cette condition n’est pas remplie (refus).

**Post-conditions.** Une tâche existe, rattachée au projet et à l’assigné, avec le statut initial saisi. Aucun point d’avancement historisé n’existe encore : l’historique commence au premier enregistrement de progression.

![Création d’une tâche](captures/diag-seq-create-task.png){width=15cm}

**Figure 3.5** – Diagramme de séquence : le chef d’équipe crée une tâche

#### Mise à jour de l’avancement par le membre assigné

Seul le membre auquel la tâche est assignée peut enregistrer un point d’avancement. Le contrôle porte sur l’égalité entre l’utilisateur courant et l’assigné.

La saisie comprend un statut, des textes optionnels « réalisé » et « à venir », et une **note de blocage obligatoire** lorsque le statut est « bloqué ».

Le service métier applique ensuite les effets attendus :

- mise à jour de la tâche (statut, textes, note de blocage le cas échéant) ;
- création d’un enregistrement d’historique qui fige le même instantané ;
- date de clôture renseignée lorsque la tâche passe au statut « terminé », effacée si l’on sort de ce statut ;
- effacement de la note de blocage sur la tâche lorsque le statut n’est plus « bloqué ».

Le pourcentage affiché dans les tableaux de bord n’est pas saisi : il est **dérivé du statut** selon la convention retenue (à faire 0 %, en cours 60 %, bloqué 30 %, terminé 100 %). Cette convention évite un second indicateur contradictoire avec le statut.

**Pré-conditions.** L’acteur est authentifié, n’est pas administrateur, et la tâche lui est assignée.

**Scénario nominal.** Le membre ouvre la tâche, choisit un statut, renseigne le réalisé et le prochain pas, ajoute une note de blocage si nécessaire, puis enregistre. Le service met à jour la tâche et insère un point d’avancement dans l’historique.

Le fragment « alt » refuse la requête lorsque le statut est « bloqué » et que la note est absente. Dans l’autre branche, « opt » ne pose la date de clôture que si le statut retenu est « terminé ».

**Post-conditions.** La tâche reflète le nouveau statut et les textes d’avancement. Un enregistrement d’historique relie la tâche et l’utilisateur. La date de clôture est cohérente avec le statut terminé. Le pourcentage dérivé correspond au statut.

![Mise à jour de l’avancement](captures/diag-seq-progress.png){width=15cm}

**Figure 3.6** – Diagramme de séquence : le membre met à jour l’avancement

#### Diagramme d’activité : mettre à jour une tâche

Le diagramme de séquence décrit les objets. Le diagramme d’activité isole la décision qui structure le métier d’avancement. Si le statut choisi est « bloqué », le système exige une note de blocage. Sans cette note, la mise à jour est rejetée. Pour tout autre statut, ou lorsque la note est présente, le système met à jour la tâche et crée un enregistrement d’historique. La date de clôture est posée à l’entrée dans le statut « terminé » et remise à vide lorsqu’on en sort.

![Diagramme d’activité](captures/diag-activity-progress.png){width=14cm}

**Figure 3.7** – Diagramme d’activité : mise à jour d’une tâche

Cette activité est la même pour le navigateur et pour le mobile. Un client peut signaler à l’avance une note manquante pour améliorer l’ergonomie, mais le serveur demeure l’autorité : une demande incomplète est refusée de la même façon quel que soit le support.

#### Diagramme de classes

La figure 3.8 modélise le domaine Briefly. La classe Utilisateur regroupe ce que tous les comptes partagent : identité, adresse électronique, mot de passe et rôle. Deux classes en héritent. Administrateur gère les comptes, les équipes et les projets. Employé désigne un membre ou un chef d’équipe, c’est-à-dire tout compte qui reçoit des tâches.

Un employé est lié aux équipes par deux associations. L’association « membre de » indique qu’un employé appartient à au moins une équipe, condition posée à la création de compte. L’association « chef » indique qu’une équipe a au plus un chef à un instant donné : la conception prévoit de retirer l’ancien chef avant d’en désigner un nouveau. Une équipe travaille sur plusieurs projets, et un projet peut être partagé entre plusieurs équipes ou n’en avoir encore aucune.

Un projet contient ses tâches. Nous représentons ce lien par une composition, car la suppression d’un projet supprime ses tâches en cascade. Une tâche est assignée à un seul employé, ce qui permet de décider sans ambiguïté qui peut la mettre à jour. La classe d’association `Avancement` est attachée à ce lien. Elle décrit un point d’avancement saisi par l’employé sur sa tâche : statut, travail réalisé, travail à venir, note de blocage et date. Un employé saisit plusieurs points sur la même tâche au fil du temps. La date distingue les avancements successifs d’un même couple employé et tâche.

L’association « /participe à » entre `Employé` et `Projet` est une association dérivée, signalée par la barre oblique. Aucune structure de stockage ne la duplique. Elle se calcule à partir des équipes de l’employé et des projets liés à ces équipes, ce qui fonde l’accès d’un chef d’équipe à un projet.

![Diagramme de classes](captures/diag-classes.png){width=16cm}

**Figure 3.8** – Diagramme de classes du domaine Briefly

**Tableau 3.2** – Détails des classes du domaine

| Classe | Rôle dans la conception |
| --- | --- |
| Utilisateur | Compte interne : identité, intitulé de poste, adresse électronique unique, mot de passe, rôle et équipe de référence. |
| Administrateur | Spécialisation responsable de la création et de la maintenance des comptes, des équipes et des projets. Il n’exécute pas de tâches opérationnelles. |
| Employé | Spécialisation membre ou chef d’équipe. Le rôle chef est dérivé de l’association « chef » sur les équipes. |
| Équipe | Regroupe des employés et des projets. C’est l’équipe de contexte qui encadre la création d’une tâche et le tableau de bord du chef. |
| Projet | Regroupe les tâches. Un chef n’y accède que si l’une des équipes qu’il dirige y est liée. |
| Tâche | Unité de travail : titre, description, statut, textes d’avancement, note de blocage et date de clôture. Elle appartient à un projet et à un employé assigné. |
| Avancement | Point d’avancement historisé : statut et textes au moment de la saisie, lié à une tâche et à l’employé auteur. |
| Rôle | Trois valeurs : membre, chef d’équipe, administrateur. Elles distinguent les spécialisations du compte. |
| Statut de tâche | Quatre valeurs : à faire, en cours, bloqué, terminé. Chacune porte un pourcentage d’avancement affiché (0, 60, 30 et 100). |

#### Schéma relationnel

Le passage du diagramme de classes au modèle relationnel suit les règles habituelles. Chaque classe du domaine devient une table, et son identifiant une clé primaire. Une association 1-N place la clé de la classe côté 1 dans la table côté N. Une association N-N devient une table associative dont la clé primaire réunit les deux clés étrangères. Les énumérations de rôles et de statuts sont stockées sous forme de codes dans les colonnes concernées.

Quatre éléments du diagramme demandent une règle particulière. L’héritage est ramené à une table unique : `Utilisateur`, `Administrateur` et `Employé` partagent la table des comptes, et une colonne de rôle indique la spécialisation. Les associations « membre de » et « chef » sont réunies dans une table associative, avec un indicateur de chefferie. La classe d’association `Avancement` devient une table d’historique, avec son propre identifiant, puisqu’un même couple employé et tâche produit plusieurs points dans le temps. Enfin, l’association dérivée « /participe à » ne produit aucune table.

Le tableau 3.3 donne la forme logique du schéma. Les clés primaires sont soulignées et les clés étrangères précédées de `#`.

**Tableau 3.3** – Schéma relationnel logique

| Table | Attributs | Contraintes |
| --- | --- | --- |
| Compte | [id]{.underline}, nom, intitulé de poste, courriel, mot de passe, rôle, #équipe de référence | Courriel unique. L’équipe de référence peut disparaître sans supprimer le compte |
| Équipe | [id]{.underline}, nom | Aucune |
| Appartenance | [#équipe, #compte]{.underline}, est chef | Un compte appartient à une ou plusieurs équipes. La suppression de l’équipe ou du compte retire l’appartenance |
| Projet | [id]{.underline}, nom, description | Aucune |
| Rattachement projet–équipe | [#projet, #équipe]{.underline} | Un projet peut être lié à plusieurs équipes. La suppression du projet ou de l’équipe retire le rattachement |
| Tâche | [id]{.underline}, #projet, #assigné, titre, description, statut, réalisé, à venir, note de blocage, date de clôture | L’assigné est un compte. La suppression du projet ou de l’assigné retire la tâche |
| Avancement | [id]{.underline}, #tâche, #auteur, statut, réalisé, à venir, note de blocage | Un même couple tâche et auteur peut avoir plusieurs points. La suppression de la tâche ou de l’auteur retire l’historique |

### 3.2.2 Client mobile dans l’architecture

Le client mobile est une **seconde interface de présentation**. Il invoque les mêmes opérations métier que le navigateur et s’appuie sur les mêmes règles d’autorisation et de validation côté serveur. Aucune règle métier n’est dupliquée dans le mobile : les choix locaux concernent l’ergonomie (navigation entre écrans, langue et thème affichés, guidage de saisie), pas le calcul des droits ni des indicateurs d’avancement.

![Chaîne de communication du client mobile](captures/diag-flutter-chain.png){width=15cm}

**Figure 3.9** – Chaîne de communication du client mobile

Le téléphone n’accède pas directement au serveur de données. Il dialogue avec le même serveur d’application que le navigateur. Les trois scénarios de la sous-section 3.2.1 s’appliquent donc sans transcription parallèle des règles.

## Conclusion

Le métier est centralisé sur le serveur d’application. Le navigateur et le mobile ne font que présenter les opérations et transmettre les actions des utilisateurs. Ils n’accèdent pas à la base de données.

La communication unifiée garantit un seul catalogue d’opérations et un seul jeu de règles, avec des réponses adaptées à chaque client. Les séquences de création d’utilisateur, de création de tâche et de mise à jour d’avancement, le diagramme d’activité et le modèle de classes matérialisent les exigences du chapitre 2 : exclusion de l’administrateur des tâches, assignation unique, note de blocage obligatoire, historisation des points d’avancement, pourcentage dérivé du statut. Le chapitre 4 détaille les choix techniques et la réalisation de cette conception.
