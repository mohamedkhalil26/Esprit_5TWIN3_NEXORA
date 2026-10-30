# SolarShare — Rapport de préparation à la validation finale

> **État final après essais externes autorisés :** cinq réponses OpenRouter réelles validées via les formulaires Laravel ; recommandations et contenus relus. Quatre messages fictifs reçus dans Mailpit sur deux passages. La première CI GitHub de la branche autorisée a réussi. Suite étendue à **28 tests / 184 assertions**. Les limites « clé absente / CI non exécutée » des états historiques ci-dessous sont levées. Le scan physique du QR, la concurrence MySQL et le déploiement staging restent à faire. Voir `docs/FINALISATION_AUTOMATIQUE.md` et les preuves JSON pour le détail.

> **Finalisation automatique ultérieure le 30 octobre :** Mailpit a été installé et configuré. Deux notifications ont été réellement reçues, leurs liens utilisés, et 13 contrôles SMTP/2FA supplémentaires ont réussi. Le PDF DevOps a été généré et contrôlé. La clé OpenRouter manque toujours ; aucun appel IA réel n’est revendiqué. Voir `docs/FINALISATION_AUTOMATIQUE.md` pour l’état courant ; les limites SMTP indiquées dans la recette historique ci-dessous sont désormais levées pour la réception locale uniquement.

## Recette complémentaire du 30 octobre 2026

Cette section actualise les résultats historiques du 29 octobre conservés plus bas. Le scénario détaillé, les données à saisir et les étapes de configuration sont dans [DEMONSTRATION_VALIDATION_FINALE.md](DEMONSTRATION_VALIDATION_FINALE.md).

### Vérifications réellement exécutées

| Périmètre | Méthode et résultat | Limite de la preuve |
| --- | --- | --- |
| Suite automatisée | **27 tests réussis, 181 assertions**, exclusivement SQLite en mémoire | Réponses OpenRouter et notifications de ces tests simulées |
| Recette HTTP | **70 contrôles réussis** : 62 lors du parcours complet et 8 complémentaires ; vraies requêtes HTTP, CSRF, cookies, sessions, base SQLite persistante séparée et vrais contrôleurs | Pas une recette concurrente MySQL ni un test de charge |
| Authentification réelle | Inscription, rôle imposé, accès admin refusé, lien email signé, profil, activation TOTP, challenge, connexion par nouveau TOTP, récupération consommée une seule fois, désactivation et reset réellement exercés | Les liens email ont été lus dans le journal privé de recette ; aucune réception SMTP revendiquée ; scan téléphonique non réalisé |
| Profil et pièces jointes, compléments réels | Changement de mot de passe depuis le profil, nouvelle connexion, compte lié protégé contre la suppression ; photo téléversée et servie ; PDF privé téléversé, téléchargé par propriétaire/admin et refusé à un tiers | Le serveur de recette utilise son propre stockage isolé ; aucune donnée historique supprimée |
| M1 réel | Création catégorie, création et modification d’équipement, catalogue et comparaison | Seules les données jetables ont été modifiées |
| M2 réel | Réservation et montant serveur, chevauchement refusé, calendrier, paiement fictif et solde dépassé refusé | Enregistrement de paiement interne, aucun paiement bancaire réel |
| M3 réel | Dépôt, isolation des dossiers, réponse administrateur et historique | Génération IA non disponible sans clé |
| M4 réel | Création admin, inscription, refus de surcapacité, annulation et réattribution de place | Écritures séquentielles sur SQLite |
| M5 réel | Article publié, commentaire, favoris et retrait, publication admin et refus d’une URL programmée future | Suggestions IA uniquement testées avec mocks |
| Cinq formulaires IA | Affichage et soumission réels ; message d’absence de clé vérifié dans les cinq modules, droits admin contrôlés | **Aucune requête réelle OpenRouter : clé non renseignée** |
| Responsive | Navigateur à 390 px et 1440 px ; catalogue, événements, blog, comparaison, connexion, mot de passe oublié et assistant administratif inspectés ; absence de débordement horizontal sur les pages mesurées | Pas de recette exhaustive sur tous les appareils physiques |
| DevOps local | Composer valide, Pint réussi, installation `npm ci --ignore-scripts` et build Vite réussis ; audit npm sans vulnérabilité ; workflows validés par **actionlint 1.7.12** ; Bash vérifié avec `bash -n` | Actionlint utilisé sans ShellCheck/Pyflakes ; aucun job exécuté sur GitHub, aucun push ni déploiement |

Les preuves HTTP exportables sont dans `docs/recette-http-resultats.json`. Ce fichier ne doit contenir que les libellés de contrôles et leurs états. Les tokens, codes TOTP, clés et corps d’emails restent hors du rapport, dans le stockage de recette privé lorsqu’une trace est nécessaire. Les captures `docs/recette-captures/assistant-sans-cle.jpg` et `assistant-mobile.jpg` montrent l’interface sur des données fictives.

### Erreurs corrigées pendant cette recette

1. **Publication programmée contournée par URL directe.** Un article futur répondait réellement HTTP 200. Les routes de lecture et commentaire vérifient maintenant aussi `publie_le`, comme la liste et les favoris ; un test de régression et la recette HTTP confirment HTTP 404 avant l’échéance.
2. **Compteur de limitation partagé entre actions de sécurité.** Les limites génériques sans préfixe mélangeaient connexion et challenge, provoquant un blocage 429 prématuré dans le parcours complet. Chaque action possède désormais son préfixe ; les plafonds ne sont pas augmentés. Un test vérifie qu’épuiser la limite des emails n’empêche pas la connexion, tout en maintenant le refus de la sixième demande d’email.
3. **Contraintes différentes pour l’application des suggestions blog.** La validation IA permettait un titre et un résumé plus courts que le formulaire normal. Les minima sont alignés sur 10 et 30 caractères ; un test confirme le refus des valeurs trop courtes. Cette correction est couverte par tests, sans génération IA réelle.

Aucune nouvelle fonctionnalité métier n’a été ajoutée pendant la recette. Les scripts `tests/Manual/` sont uniquement des outils de test sur une instance séparée. La base MySQL et les comptes historiques n’ont pas été réinitialisés ou remplacés.

### Blocages et configuration externe

- **OpenRouter** : clé absente ; le modèle actif est `openrouter/free`. Le propriétaire doit la renseigner localement, vider le cache de configuration et effectuer une demande réelle par module. La clé n’a pas été modifiée ni affichée.
- **Emails** : transport principal `log`. La génération et les liens sont vérifiés ; la réception SMTP ne l’est pas. Le guide propose Mailpit lié uniquement à `127.0.0.1`, ou un SMTP externe de test dont les accès restent privés.
- **GitHub** : workflows contrôlés statiquement et commandes locales exécutées, mais fichiers non poussés. Il faut autoriser le commit/push, activer Actions et contrôler les artefacts du premier run.
- **Staging** : fournir le serveur Linux, son HTTPS, les secrets SSH par environnement, les dossiers partagés et le programme de sauvegarde testé. Une autorisation de déploiement reste nécessaire.

---

**Date :** 29 octobre 2026 — Africa/Tunis
**Validation :** semaine du 2 novembre 2026
**Périmètre :** projet Laravel existant, cinq modules, authentification et DevOps.

## 1. Audit initial et décisions

Le dépôt contenait les dix entités métier, leurs migrations et factories, des seeders par module, les CRUD administratifs, des parcours Front Office et une navigation modulaire par hooks. Les rôles `admin` et `user`, les contrôles de propriété sur les réservations/réclamations et des validations de chevauchement, de solde et de capacité existaient déjà. Les réclamations possédaient déjà des filtres et compteurs ; ils ont été conservés.

L’inscription/connexion/déconnexion existaient, mais pas la vérification email, les formulaires de réinitialisation, le profil complet ni la 2FA. Aucun service ni parcours IA n’était présent dans `app/`, `config/`, `routes/` et les vues. Le dépôt ne contenait pas de pipeline CI/CD ni de verrou npm ; les deux tests initiaux provenaient du squelette Laravel et le test d’accueil échouait faute de tables dans sa base de test.

Les corrections concernent notamment : statut client pouvant éviter une vérification de chevauchement, vérification de capacité hors transaction, paiements concurrents pouvant dépasser le solde, extensions de fichiers prises dans le nom original, pièces jointes publiques, accès admin répondant par une redirection, et absence de réinitialisation/vérification native. La dépendance `symfony/mime` a également été mise à jour vers 7.4.22 après identification d’un avis de sécurité.

Les modèles, routes existantes, tables et données ont été conservés. Une seule migration additive a été créée et appliquée sur MySQL local, au batch 4. Aucun `migrate:fresh`, seeding de remplacement, push GitHub ou déploiement en production n’a été effectué.

## 2. Lecture des états

- **Terminé** : code implémenté et contrôles locaux correspondants exécutés. Cela ne garantit pas une recette navigateur ni une livraison externe.
- **Partiel** : implémentation disponible mais configuration externe ou recette supplémentaire nécessaire.
- **Bloqué** : action externe impossible sans cible, secret ou autorisation manquants.

Les chemins du tableau sont relatifs à la racine du dépôt pour faciliter la lecture du document exporté.

## 3. Couverture des critères et démonstration

| Fonctionnalité demandée | Fonctionnalité réalisée | Fichiers principaux | État | Tests effectués | Démonstration courte |
| --- | --- | --- | --- | --- | --- |
| Inscription, connexion, déconnexion | Validation existante conservée, rôle imposé à `user`, limitation des tentatives, renouvellement de session | `Auth/AuthController.php`, `routes/web.php`, requêtes Auth | Terminé | Inscription sans élévation de rôle, authentification et sessions dans `AccountSecurityTest` | Créer un compte ; vérifier l’e-mail ; se connecter et se déconnecter |
| Mot de passe oublié | Broker Laravel, token haché en base, expiration et usage unique ; formulaires français | `Auth/SecurityController.php`, `routes/modules/security.php`, `auth/security.blade.php` | Partiel | Token valide, réutilisé et expiré ; notification simulée | Cliquer « Mot de passe oublié » ; utiliser le lien ; essayer de le réutiliser. SMTP réel à configurer |
| Vérification email | `MustVerifyEmail`, envoi après inscription et changement d’adresse, lien signé, renvoi limité et middleware `verified` | `Models/User.php`, `SecurityController`, routes des modules | Partiel | Notification, signature, refus d’un lien non signé, protection des fonctionnalités | Créer un membre non vérifié et tenter une réservation ; ouvrir le lien reçu après configuration SMTP |
| Rôles serveur | Accès admin protégé et réponse HTTP 403 ; inscription/profil sans modification du rôle | `EnsureUserIsAdmin.php`, `routes/modules/*.php` | Terminé | Refus d’accès admin et assistants administratifs à un membre | Ouvrir `/admin` avec un compte utilisateur puis avec un admin |
| Sessions sécurisées | Regénération, invalidation à la déconnexion, validation du hash du mot de passe, contrôle 2FA des sessions existantes | `bootstrap/app.php`, `EnsureTwoFactorAuthenticated.php`, Auth et Profil | Terminé | Parcours d’authentification, challenge et profil | Activer la 2FA puis ouvrir une autre session pour obtenir un challenge |
| 2FA complète | Activation après mot de passe, QR code local, confirmation TOTP, anti-réutilisation, huit codes de secours hachés à usage unique et désactivation protégée | `TwoFactorService.php`, `SecurityController.php`, migration additive | Terminé | QR SVG, activation, confirmation, chiffrement de la clé, refus du même code, désactivation, récupération à usage unique | Profil → configurer 2FA ; scanner le QR ; confirmer ; conserver les codes ; se reconnecter |
| Profil | Nom, email, téléphone, ville ; mot de passe ; photo avec taille/type contrôlés et stockage public dédié | `ProfileController.php`, `auth/profile.blade.php`, migration | Terminé | Profil sans élévation de rôle ; pages rendues ; lien de stockage créé | Modifier les coordonnées avec le mot de passe actuel, puis choisir une photo |
| Suppression du compte | Mot de passe et saisie `SUPPRIMER`, suppression des sessions ; refus des comptes admin ou liés à des équipements, locations, articles ou participations actives | `ProfileController.php` | Terminé | Mauvais mot de passe refusé ; compte simple supprimé sans recréation accidentelle | Utiliser un compte sans historique ; tenter ensuite avec un compte possédant des données liées |
| Service IA commun | Un service HTTP OpenRouter, une configuration, timeout, limites par membre, validation JSON et messages d’indisponibilité | `OpenRouterService.php`, `config/ai.php`, `AppServiceProvider.php`, `.env.example` | Partiel | Mocks HTTP, absence de clé, erreur API, JSON invalide, limitation des requêtes | Renseigner la clé privée ; utiliser les menus assistants ; retirer la clé pour montrer le message clair |
| M1 : IA équipements | Sélection d’identifiants réels parmi les candidats disponibles sous budget ; caractéristiques affichées depuis la base | `AiController.php`, `ai/assistant.blade.php` | Partiel | Identifiants existants acceptés, identifiant inventé refusé | Indiquer usage, puissance attendue et budget journalier ; consulter les fiches proposées |
| M2 : IA réservation | Dates et budget total filtrés par Laravel ; équipements en conflit exclus ; réservation créée uniquement par le workflow classique | `AiController.php`, `ReservationService.php`, formulaire de réservation | Partiel | Catalogue transmis excluant conflits et prix excessifs ; aucune réservation IA automatique | Demander une batterie sur deux dates ; ouvrir « Préparer la réservation » puis valider normalement |
| M3 : IA réclamations | Catégorie, priorité, résumé et réponse proposée ; champs modifiables ; enregistrement et réponse uniquement après bouton explicite | `AiController.php`, `routes/modules/assistants.php`, vue IA | Partiel | Réponse structurée, droits admin, absence d’écriture à la génération, validation explicite et réponse créée | Admin → assistant réclamations → générer → corriger → valider ; vider la réponse pour ne changer que catégorie/priorité |
| M4 : IA événements | Description et programme modifiables, ajout à la description après validation administrative | `AiController.php`, vue IA | Partiel | Génération simulée sans écriture, enregistrement explicite | Choisir un événement ; demander un programme ; relire puis enregistrer |
| M5 : IA blog | Titre, résumé, mots-clés et améliorations proposés ; titre/résumé enregistrés explicitement sans changer le statut | `AiController.php`, vue IA | Partiel | Suggestions simulées, brouillon conservé après validation | Choisir un brouillon ; générer et corriger ; enregistrer le titre/résumé ; publier séparément avec le formulaire existant |
| M1 avancé | Comparateur de deux à quatre équipements fondé sur les champs enregistrés | `ToolsController.php`, `tools/compare.blade.php`, menu équipements | Terminé | Comparaison réelle et nombre minimal validé | Ouvrir Comparateur ; sélectionner deux équipements ; comparer puissance, capacité, prix et caution |
| M2 avancé | Calendrier sur 60 jours, périodes occupées et estimation serveur ; revalidation transactionnelle lors de l’enregistrement | `ToolsController.php`, `tools/availability.blade.php`, `ReservationService.php` | Terminé | Calcul du montant, conflits, statut injecté, durée maximale et propriété | Menu utilisateur → calendrier ; choisir dates et équipement ; essayer une période déjà réservée |
| M3 avancé | Statistiques/filtres existants conservés ; nouvel historique des changements de statut, priorité et catégorie avec auteur/date | `Reclamation.php`, `ReclamationHistory.php`, vue partielle historique, migration | Terminé | Historique créé lors d’un changement ; accès au détail | Modifier la priorité ou le statut ; retrouver avant/après dans la fiche |
| M4 avancé | Capacité revérifiée avec verrou de l’événement ; doublon idempotent, annulation et réinscription conservées ; début passé refusé | `ParticipationService.php`, contrôleurs événements/participations | Terminé | Événement à une place, double clic sans doublon, second membre refusé, place libérée après annulation | Créer un événement à une place ; inscrire deux comptes ; annuler le premier puis inscrire le second |
| M5 avancé | Favoris privés, ajout idempotent, liste personnelle paginée, retrait | relation `User::favoris`, `ToolsController.php`, vues favoris et article, migration | Terminé | Ajout double sans doublon, isolation entre utilisateurs, retrait | Depuis un article publié, enregistrer ; ouvrir Mes articles favoris ; retirer |
| Paiements | Calcul du solde existant conservé ; revalidation transactionnelle pour les paiements validés | `PaymentService.php`, `Admin/PaiementController.php` | Terminé | Refus d’un montant supérieur au solde | Sur une réservation, tenter un paiement supérieur au reste à payer |
| Pièces jointes | Nouveaux fichiers privés, téléchargement réservé au propriétaire ou admin, extension MIME serveur ; protections Apache/Nginx pour l’historique | `ToolsController::attachment`, contrôleurs réclamations, `.htaccess`, exemple Nginx | Terminé | Téléchargement autorisé au propriétaire et refusé à un tiers | Ouvrir la pièce jointe avec son auteur, puis avec un autre compte |
| Cohérence française | Layouts Creative/Bootstrap et AdminLTE conservés, navigation ajoutée, messages et emails français, pages d’erreur françaises | vues Auth/IA/Tools, navbar/sidebar, `resources/views/errors`, `AppServiceProvider.php` | Partiel | Rendu HTTP des nouvelles pages et compilation Blade ; recette visuelle mobile non réalisée | Parcourir les nouveaux menus en desktop puis mobile et vérifier les messages |
| CI complète | Checkout, PHP, Composer, environnement éphémère, migrations, audit/style, PHPUnit, Node, npm ci, Vite et artefacts | `.github/workflows/ci.yml`, `package-lock.json`, `phpunit.xml`, `tests/TestCase.php` | Partiel | Commandes locales exécutées ; aucune exécution GitHub | Après push autorisé, ouvrir Actions et présenter les étapes et le JUnit |
| CD préparé | Déclenchement manuel après CI, secrets par environnement, sauvegarde obligatoire, releases et contrôles HTTP | `.github/workflows/cd.yml`, `scripts/deploy.sh`, `docs/nginx.conf.example` | Partiel | Syntaxe Bash contrôlée ; serveur non utilisé | Montrer le workflow et les variables requises, puis effectuer une recette staging après configuration |
| Déploiement réel | Aucun déploiement revendiqué | Environnement GitHub et serveur à fournir | Bloqué | Non exécuté | Fournir une cible staging, ses accès, une sauvegarde vérifiée et l’autorisation de déployer |
| Document DevOps | Processus, deux diagrammes Mermaid, étapes, tests, secrets, déploiement et reproduction en français | `docs/DEVOPS_VALIDATION_FINALE.md` | Terminé | Contenu rédigé à partir des commandes réellement exécutées | Ouvrir le document avec rendu Mermaid puis exporter en PDF |

Tous les états IA « partiel » correspondent à une intégration codée et testée avec mocks, dont la recette réelle reste bloquée par l’absence de clé API. Aucun faux résultat n’est généré pour masquer cette absence.

## 4. Vérifications obtenues

- PHP 8.3.31 ; Laravel 12.69.3 ; PHPUnit 11.5.56 dans l’environnement local.
- **24 tests réussis, 162 assertions** à la dernière exécution complète consignée ; les tests s’exécutent exclusivement sur SQLite en mémoire.
- `composer validate --strict` valide ; audit Composer sans avis après la mise à jour ciblée de `symfony/mime`.
- Installation npm réussie ; audit npm sans vulnérabilité ; build Vite réussi sous Node 20.19.6. La CI utilise Node 22.
- `route:cache`, `route:clear` et `view:cache` exécutés avec succès.
- Migration additive locale exécutée et lien `public/storage` créé.
- Contrôle Pint final et `git diff --check` consignés après les dernières retouches.
- `bash -n scripts/deploy.sh` exécuté avec Git Bash, sans erreur.
- Recette navigateur locale en vue étroite : accueil, comparaison réelle de deux batteries, connexion du compte administrateur de démonstration, affichage du profil et de l’assistant réclamations, calendrier et estimation de 44 DT pour les 2 et 3 novembre 2026, menus responsive et déconnexion. Aucune réservation ni modification des contenus métier n’a été créée pendant cette recette.

## 5. Bilan des phases et fichiers

| Phase | Réalisation et fichiers | Contrôles |
| --- | --- | --- |
| 1 — audit et authentification | Auth/Security/Profile, middlewares, modèle User, vues Auth, routes et migration additive | Première étape : 7 tests, 38 assertions ; extensions ultérieures TOTP et expiration intégrées à la suite finale |
| 2 — IA | Service OpenRouter, configuration, contrôleur partagé, routes assistants, vue adaptable Front/Back, limiteur | À cette étape : 12 tests, 65 assertions ; mocks et aucune requête IA réelle |
| 3 — fonctions avancées | Tools, favoris, comparateur, calendrier, historique et services métier transactionnels | À cette étape : 20 tests, 123 assertions |
| 4 — CI/CD et tests | Workflows, script de livraison, configuration Nginx, verrou npm, extension des tests et correction Mime | Build réel ; tests étendus ; validation locale Composer et Bash |
| 5 — intégration et documentation | Deux rapports, menus, pages d’erreur et validation explicite IA | Compilation Blade, routes, contrôle du diff et suite finale |

Les corrections de style dans `ArticleRequest.php` et `EquipementFactory.php` sont limitées aux chaînes signalées par Pint ; leur comportement reste identique. Aucun template principal n’a été remplacé.

## 6. Limites et étapes avant soutenance

1. Configurer SMTP et confirmer la réception réelle d’un lien de vérification et d’un lien de réinitialisation. Le transport actuel est `log`.
2. Configurer la clé OpenRouter dans `.env`, vider le cache de configuration et effectuer une recette IA réelle pour chaque module. Le modèle gratuit peut avoir des quotas ou être indisponible ; les erreurs sont gérées sans simulation.
3. Vérifier le QR code avec une application TOTP sur téléphone et conserver les codes de récupération. Garder une heure serveur synchronisée et l’`APP_KEY` stable.
4. Compléter la recette navigateur réalisée en vue étroite par les autres résolutions desktop/mobile, le chargement IA réel, les erreurs et la photo de profil. Les tests de rendu HTTP et de compilation Blade ne remplacent pas une recette visuelle complète.
5. Exécuter des scénarios simultanés sous MySQL sur staging. Les tests SQLite vérifient les règles mais pas le comportement de verrouillage en charge.
6. Configurer l’environnement GitHub, les secrets SSH, le serveur et le programme de sauvegarde. Ne déclencher le déploiement qu’après autorisation explicite du propriétaire.

La liste d’attente événementielle n’a pas été ajoutée : le besoin avancé est satisfait par une gestion renforcée de capacité et l’inscription/annulation existante. Les suppressions de comptes liés exigent un traitement administratif pour éviter les cascades destructrices prévues dans le schéma historique. Les mots-clés et améliorations du blog restent des propositions ; ils n’ont pas créé de nouveaux champs métier inutiles.

Les nouvelles pièces jointes sont privées. Pour une autre instance ayant d’anciens fichiers publics, appliquer les règles serveur livrées ; leur URL applicative passe désormais par l’autorisation du contrôleur. Aucune pièce jointe historique n’était présente dans le dossier public local lors de l’inspection.

## 7. Scénario de présentation proposé

Présenter d’abord le catalogue et le comparateur, puis créer un compte pour montrer la vérification email et le profil. Activer la 2FA et refaire une connexion. Utiliser le calendrier et l’assistant de réservation, confirmer une réservation et montrer le refus d’un chevauchement. Passer au Back Office pour analyser une réclamation, modifier puis valider la suggestion et consulter l’historique. Générer un programme d’événement et démontrer la capacité à une place. Terminer par l’assistant blog, le maintien d’un article en brouillon, les favoris, puis présenter la suite de tests et les diagrammes CI/CD.

Les emails et l’IA doivent être configurés avant cette démonstration réelle. Si ce n’est pas fait, annoncer explicitement la limite et montrer les tests simulés comme des tests, sans les présenter comme un service externe opérationnel.
