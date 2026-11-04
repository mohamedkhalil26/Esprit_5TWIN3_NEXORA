# Configuration des emails réels via Gmail SMTP

SolarShare envoie ses emails par SMTP. Le choix du serveur se fait uniquement dans `.env` : **Gmail** pour l'envoi réel (démonstration), **Mailpit** pour les tests locaux. Aucun identifiant ne figure dans le code ni dans Git.

## 1. Emails envoyés par l'application

| Fonctionnalité | Déclencheur | Notification |
| --- | --- | --- |
| Inscription | `AuthController::register` → événement `Registered` | `VerifyEmail` (personnalisée dans `AppServiceProvider`) |
| Renvoi du lien de vérification | `POST /verifier-email` (`SecurityController::resend`) | `VerifyEmail` |
| Mot de passe oublié | `POST /mot-de-passe-oublie` (`SecurityController::forgot`) | `ResetPassword` (personnalisée dans `AppServiceProvider`) |
| Réinitialisation | `GET /reinitialiser/{token}` puis `POST /reinitialiser` | lien reçu par email, sans nouvel envoi |

Les modules SolarShare (événements, locations, réclamations, blog, équipements, assistants IA, 2FA) **n'envoient pas d'email** : il n'existe ni `Mailable`, ni notification personnalisée, ni service d'envoi dédié. Les routes de ces modules exigent en revanche une adresse vérifiée (middleware `verified`). La vérification par email est donc indispensable pour la démonstration.

### Files d'attente

`QUEUE_CONNECTION=database`, mais les notifications `VerifyEmail` et `ResetPassword` n'implémentent pas `ShouldQueue` : elles partent **immédiatement**, pendant la requête HTTP. **Aucun worker n'est nécessaire** pour les emails. Une erreur SMTP apparaît tout de suite (page d'erreur et `storage/logs/laravel.log`) au lieu de disparaître dans une file d'attente.

`composer run dev` lance quand même `queue:listen` pour d'éventuels futurs jobs. Pour vérifier qu'aucun job n'est en échec : `php artisan queue:failed`.

## 2. Préparer le compte Google

1. Ouvrez <https://myaccount.google.com/security> et activez la **validation en deux étapes**. Elle est obligatoire pour créer un mot de passe d'application.
2. Ouvrez <https://myaccount.google.com/apppasswords>, saisissez un nom (par exemple `SolarShare`) et cliquez sur **Créer**.
3. Copiez le mot de passe de 16 caractères affiché. Google l'affiche en 4 groupes : **retirez les espaces**.
4. Ne le partagez pas et ne le commitez jamais. Il peut être révoqué à tout moment sur la même page.

> Le mot de passe habituel du compte Gmail ne fonctionne pas en SMTP. Sur certains comptes (Workspace restreint, Protection avancée), les mots de passe d'application sont désactivés : utilisez alors un compte Gmail personnel.

## 3. Configurer `.env`

Le bloc Gmail est déjà en place dans `.env`. Complétez seulement ces trois lignes :

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_REQUIRE_TLS=true
MAIL_USERNAME=votre.adresse@gmail.com      # à compléter
MAIL_PASSWORD=abcdefghijklmnop             # à compléter : mot de passe d'application, sans espaces
MAIL_FROM_ADDRESS=votre.adresse@gmail.com  # à compléter : même adresse que MAIL_USERNAME
MAIL_FROM_NAME="${APP_NAME}"
MAIL_TIMEOUT=30
```

- `MAIL_SCHEME=smtp` + port `587` : connexion en clair, puis passage en **STARTTLS**. `smtps` correspond au port 465 (TLS implicite) et n'est pas utilisé ici.
- `MAIL_REQUIRE_TLS=true` : l'envoi est refusé si le serveur ne propose pas STARTTLS. Les identifiants ne circulent donc jamais en clair.
- `MAIL_FROM_ADDRESS` : si ce champ est vide, l'adresse de `MAIL_USERNAME` est utilisée. Gmail remplace de toute façon un expéditeur non autorisé par l'adresse du compte.
- La vérification des certificats TLS reste **toujours activée**. N'ajoutez jamais `verify_peer=false`.
- `APP_URL` doit correspondre à l'URL utilisée dans le navigateur (`http://127.0.0.1:8000`). Les liens des emails sont construits à partir de cette valeur.

Une sauvegarde de l'ancien `.env` (configuration Mailpit) se trouve dans `.env.backup-mailpit`. Ce fichier est ignoré par Git.

Après chaque modification de `.env` :

```powershell
php artisan config:clear
```

## 4. Basculer entre Gmail et Mailpit

Le fichier `.env` contient deux blocs : un bloc Gmail actif et un bloc Mailpit commenté.

**Gmail vers Mailpit** : commentez le bloc Gmail (préfixe `# `), décommentez le bloc Mailpit, puis :

```powershell
.\scripts\start-mailpit.ps1
php artisan config:clear
```

Les emails sont alors visibles sur <http://127.0.0.1:8025> et ne sortent pas de la machine.

**Mailpit vers Gmail** : remettez les valeurs Gmail ci-dessus et relancez `php artisan config:clear`.

Valeurs Mailpit : `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`, `MAIL_REQUIRE_TLS=false`, `MAIL_USERNAME=null`, `MAIL_PASSWORD=null`. Gardez vos identifiants Gmail dans le bloc commenté pour ne pas avoir à les ressaisir.

Les tests automatisés (`php artisan test`) utilisent toujours le mailer `array` (voir `phpunit.xml`) : ils n'envoient jamais d'email réel, quelle que soit la configuration de `.env`. Ne conservez pas un second mot de passe Gmail commenté dans `.env` : utilisez plutôt Mailpit tant que vous ne souhaitez pas envoyer de message réel.

## 5. Procédure de test réel

Un email n'est considéré comme **reçu** que lorsqu'il apparaît réellement dans la boîte Gmail de destination (vérifiez aussi le dossier **Spam**). Le message « accepté par le serveur » ne suffit pas.

### 5.1 Connexion, STARTTLS et authentification

```powershell
php artisan config:clear
php artisan mail:test votre.adresse@gmail.com --connection-only
```

Résultat attendu : tableau de configuration (le mot de passe n'est jamais affiché), puis `Connexion, chiffrement et authentification SMTP : OK`.

### 5.2 Envoi et réception d'un email de test

```powershell
php artisan mail:test votre.adresse@gmail.com
```

Résultat attendu : `Email de test accepté par le serveur`. Ouvrez ensuite Gmail : un email **« Test SMTP — SolarShare »** doit être présent. Dans Gmail, *Afficher l'original* doit indiquer `SPF: PASS` et `DKIM: PASS`.

### 5.3 Inscription et vérification de l'adresse

1. `php artisan serve`, puis ouvrez <http://127.0.0.1:8000/register>.
2. Inscrivez un compte de test avec une adresse Gmail réelle. Un alias de votre propre adresse convient, par exemple `votre.adresse+solarshare1@gmail.com`.
3. Vérifiez la réception de **« Vérifiez votre adresse e-mail — SolarShare »**.
4. Cliquez sur **Vérifier mon adresse e-mail** dans le même navigateur, où vous êtes connecté. Résultat attendu : message « Votre adresse e-mail est vérifiée » et accès aux modules protégés.

### 5.4 Renvoi du lien de vérification

1. Avec un compte non vérifié, ouvrez <http://127.0.0.1:8000/verifier-email> et cliquez sur le bouton de renvoi.
2. Vérifiez la réception d'un nouvel email de vérification et que son lien fonctionne. Le renvoi est limité à 3 demandes par minute.

### 5.5 Mot de passe oublié et réinitialisation

1. Déconnectez-vous, puis ouvrez <http://127.0.0.1:8000/mot-de-passe-oublie> et saisissez l'adresse du compte de test.
2. Vérifiez la réception de **« Réinitialisez votre mot de passe — SolarShare »**. Pour une adresse inconnue, aucun email n'est envoyé, mais le même message s'affiche volontairement.
3. Cliquez sur le lien, choisissez un nouveau mot de passe (8 caractères minimum, lettres et chiffres), puis connectez-vous avec. Le lien expire après 60 minutes et ne fonctionne qu'une seule fois.

## 6. Erreurs courantes

| Message | Cause | Solution |
| --- | --- | --- |
| `535-5.7.8 Username and Password not accepted` | Mot de passe d'application erroné, mot de passe Gmail habituel utilisé, ou validation en deux étapes désactivée | Créer un nouveau mot de passe d'application, le coller **sans espaces**, puis `php artisan config:clear` |
| `534-5.7.9 Application-specific password required` | Mot de passe du compte utilisé au lieu d'un mot de passe d'application | Voir la section 2 |
| `530-5.7.0 Must issue a STARTTLS command first` | `MAIL_SCHEME` ou `MAIL_PORT` incorrect | `MAIL_SCHEME=smtp`, `MAIL_PORT=587` |
| `Unable to connect with STARTTLS` / `certificate verify failed` | PHP ne trouve pas le bundle des autorités de certification | Vérifier `openssl.cafile` dans `C:\8.3.31\php.ini` (actuellement `C:/Program Files/Git/usr/ssl/certs/ca-bundle.crt`) ou y indiquer un `cacert.pem` à jour (<https://curl.se/docs/caextract.html>). **Ne jamais désactiver la vérification** |
| `Connection could not be established ... timed out` | Port 587 bloqué (pare-feu, réseau d'école ou d'entreprise, antivirus) | `Test-NetConnection smtp.gmail.com -Port 587` ; changer de réseau (partage de connexion) si `TcpTestSucceeded : False` |
| `Adresse d'expéditeur invalide` (commande `mail:test`) | `MAIL_FROM_ADDRESS` et `MAIL_USERNAME` vides | Compléter `.env` |
| `550-5.4.5 Daily user sending limit exceeded` | Quota Gmail dépassé (environ 500 emails par jour) | Attendre 24 h ou utiliser Mailpit pour les tests répétés |
| La modification de `.env` n'a pas d'effet | Configuration en cache | `php artisan config:clear` |
| Lien « invalide » ou erreur 403 sur la vérification | `APP_URL` différent de l'URL du navigateur, ou lien modifié | Aligner `APP_URL`, puis redemander un lien |
| Email absent de la boîte de réception | Filtrage anti-spam | Regarder dans **Spam** ou **Promotions**, marquer « Non spam » |
| Page d'erreur 500 à l'inscription | Envoi SMTP en échec (l'envoi est synchrone) | Lire `storage/logs/laravel.log`, puis lancer `php artisan mail:test ... --connection-only` |

## 7. Sécurité

- `.env` et `.env.*` sont ignorés par Git (`git check-ignore -v .env`). Seul `.env.example`, sans identifiants, est versionné.
- La commande `mail:test` n'affiche jamais le mot de passe.
- En cas de fuite, révoquez immédiatement le mot de passe d'application sur <https://myaccount.google.com/apppasswords>.
