# Briefly

Application de suivi de l'avancement des tâches d'équipe. Le site web (Laravel, Blade, Bootstrap) et l'application mobile (Flutter) utilisent les mêmes routes de `routes/web.php` : le navigateur reçoit du HTML, le téléphone envoie `Accept: application/json` et reçoit du JSON, avec un jeton Sanctum.

Trois rôles : administrateur, chef d'équipe (`team_user.is_team_lead`) et membre.

## Prérequis

- PHP 8.3 ou plus récent, avec les extensions `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`
- Composer 2
- MySQL ou MariaDB (en local : XAMPP, MySQL sur le port 3306)
- Flutter 3.x (SDK Dart 3.13+) et Xcode pour l'application mobile sur iPhone ; le SDK Android reste optionnel pour un APK

Le CSS et le JavaScript de Bootstrap sont chargés depuis un CDN : aucune compilation front (`npm`) n'est nécessaire.

## Installation en local

```bash
git clone git@github.com:chirazlamraoui/Briefly.git
cd Briefly
composer install
cp .env.example .env
php artisan key:generate
/Applications/XAMPP/xamppfiles/bin/mysql -u root < database/sql/xampp-setup.sql
php artisan migrate --seed
php artisan serve
```

Le site répond sur http://localhost:8000. Les comptes de démonstration créés par le seeder ont tous le mot de passe `password` (par exemple `admin@briefly.test` et `emma.nguyen@briefly.test`).

## Tests

```bash
php artisan test
cd mobile && flutter analyze && flutter test
```

Les tests PHP tournent sur une base SQLite en mémoire (voir `phpunit.xml`) : ils ne touchent pas à la base MySQL.

## Déploiement sur un serveur

1. Récupérer le code et installer les dépendances sans les paquets de développement :

   ```bash
   git clone git@github.com:chirazlamraoui/Briefly.git briefly && cd briefly
   composer install --no-dev --optimize-autoloader
   ```

2. Créer le fichier `.env` à partir de `.env.example` et changer au minimum :

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://adresse-du-serveur
   DB_DATABASE=briefly
   DB_USERNAME=utilisateur_dedie
   DB_PASSWORD=mot_de_passe_fort
   LOG_LEVEL=warning
   ```

   Puis générer la clé : `php artisan key:generate --force`. Le fichier `.env` n'est jamais versionné.

3. Créer la base (`database/sql/xampp-setup.sql` donne la commande `CREATE DATABASE` en `utf8mb4`) et lancer les migrations :

   ```bash
   php artisan migrate --force
   ```

   Le seeder de démonstration s'arrête si `APP_ENV` n'est pas `local` : les comptes de test ne peuvent pas arriver en production. Le premier administrateur se crée avec Tinker :

   ```bash
   php artisan tinker --execute 'App\Models\User::create(["name" => "Administrateur", "email" => "admin@exemple.fr", "password" => "mot_de_passe_fort", "role" => App\Enums\UserRole::Admin])'
   ```

4. Mettre en cache la configuration, les routes et les vues :

   ```bash
   php artisan optimize
   ```

   Après chaque modification du `.env`, relancer `php artisan optimize:clear` puis `php artisan optimize`.

5. Configurer le serveur web (Apache ou Nginx) pour que la racine du site pointe sur `public/`, et donner à l'utilisateur du serveur web les droits d'écriture sur `storage/` et `bootstrap/cache/`.

6. Vérifier : `GET /up` doit répondre 200. Avec `APP_DEBUG=false`, une erreur renvoie seulement un message, sans trace de pile ni chemin de fichier.

### Mise à jour

Sur alwaysdata, un push sur `main` déclenche cette mise à jour après les tests (voir plus bas). Le script `scripts/deploy-alwaysdata.sh` fait, sur le serveur :

```bash
php artisan down --retry=15
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Le fichier `.env` déjà présent sur le serveur est conservé. Pour lancer le même script depuis le Mac, la clé `~/.ssh/alwaysdata_lamraoui` doit être autorisée sur le compte :

```bash
bash scripts/deploy-alwaysdata.sh
```

## Application mobile

Le code Flutter est dans `mobile/`. Par défaut, l'application pointe vers le serveur de production (`https://lamraoui.alwaysdata.net`, voir `mobile/lib/core/api_config.dart`). Pour le développement local ou un autre hébergeur, passer l'URL à la compilation :

```bash
cd mobile
flutter run --dart-define=API_BASE_URL=http://192.168.x.x:8000
```

Sur iPhone, ouvrir le projet dans Xcode pour la signature et l'installation sur appareil. Build Android optionnel :

```bash
flutter build apk --release --dart-define=API_BASE_URL=https://adresse-du-serveur
```

## Intégration continue

Le workflow `.github/workflows/ci.yml` se lance à chaque push sur `main`, à chaque pull request, et à la demande depuis l'onglet Actions. Il installe PHP et Composer, puis exécute `php artisan test`. Dans une seconde tâche, il installe Flutter et exécute `flutter analyze` et `flutter test`. Le résultat apparaît dans l'onglet Actions de GitHub et sur chaque pull request.

Si les deux suites passent et que l'événement est un push sur `main` (ou un lancement manuel de cette branche), une troisième tâche exécute `scripts/deploy-alwaysdata.sh`. Elle installe les dépendances de production, copie les fichiers vers `lamraoui@ssh-lamraoui.alwaysdata.net:www/briefly/` et vérifie que `GET /up` et `GET /login` répondent 200. La clé privée SSH est le secret de dépôt `ALWAYSDATA_SSH_KEY`. Les dossiers `mobile/` et `docs/` ne sont pas envoyés.
