# SolarShare — Démonstration de validation finale

> **État au 3 novembre 2026 :** les résultats précédents ci-dessous sont historiques. La validation locale récente compte 37 tests / 224 assertions, dont 13 / 65 pour les assistants IA. Lors de la campagne live de clôture, les cinq appels synthétiques ont reçu HTTP 429 : aucune réponse IA ne peut être démontrée par cette campagne. Voir `rapport-final-nexora.md` et `audit-ia-live-20261103-210136.json` avant de présenter les résultats.

> **État des services à revalider avant la soutenance :** les preuves Mailpit et 2FA mentionnées plus bas correspondent à une recette antérieure. Cette clôture n'a pas renvoyé de nouveaux emails et n'a pas revalidé la réception SMTP; la configuration Gmail est documentée dans `CONFIGURATION_EMAIL_GMAIL.md`. N'affichez pas les affirmations historiques comme des tests exécutés le 3 novembre.

**Préparation : 30 octobre 2026 — Africa/Tunis**
**Présentation visée : semaine du 2 novembre 2026**
**Durée : 25 à 35 minutes**, puis questions et présentation des preuves techniques.

## 1. Ce qui a été vérifié et ce qu’il faut préparer

Une recette HTTP réelle a été exécutée sur une **instance isolée à `http://127.0.0.1:8010`**, avec SQLite persistant, sessions, CSRF, formulaires, notifications locales et écritures effectives. Les notifications utilisent le transport `log` ; leurs liens ont été utilisés sans afficher les tokens. Cette recette ne prouve pas une livraison SMTP ni le verrouillage concurrent MySQL. La base MySQL habituelle de `http://127.0.0.1:8000` n’a pas reçu ces données de recette.

Les cinq assistants sont accessibles et leur erreur « service non configuré » a été vérifiée réellement. **Aucune réponse OpenRouter réelle n’a été obtenue : la clé n’est pas configurée.** La qualité des suggestions et leur validation sont couvertes par des tests HTTP simulant l’API, à distinguer explicitement des essais réels.

Avant présentation, choisir une instance et garder la même URL pendant tous les parcours. Utiliser deux profils de navigateur ou deux navigateurs distincts : un administrateur et un membre. Un second membre est utile pour démontrer la capacité des événements. Préparer une photo JPG/PNG de moins de 2 Mo et un petit PDF fictif, sans donnée personnelle. Ne modifier ni supprimer les comptes et contenus historiques pour la démonstration.

### Instance de recette reproductible, facultative

Les scripts `tests/Manual/` sont des outils de recette, pas des fonctionnalités du produit. Ils ne sont pas inclus dans l’archive de déploiement.

```powershell
# Initialisation une seule fois : refuse d’écraser une base de recette existante.
php tests/Manual/init.php

# Terminal dédié : serveur isolé.
php -S 127.0.0.1:8010 -t public tests/Manual/router.php

# Autre terminal : parcours HTTP avec écritures sur la base jetable uniquement.
python tests/Manual/recette_http.py

# Après réussite du parcours complet, vérifications complémentaires sur ses comptes jetables.
python tests/Manual/complements_http.py
```

Les comptes propres à cette instance sont `admin.recette@example.test` et `membre.recette@example.test`, mot de passe de test `Recette2026!`. Ils n’existent pas automatiquement dans MySQL et ne doivent jamais être créés sur un serveur public. Le script HTTP crée un troisième membre, exerce la 2FA et change uniquement son mot de passe de test. Ne pas rejouer ce script sur des données conservées : archiver d’abord le dossier privé `storage/app/private/recette-finale` après arrêt du serveur, puis réinitialiser. Les journaux privés contiennent des liens email temporaires et ne doivent pas être partagés.

Le serveur de recette force uniquement les paramètres de sa base, de son stockage et de son transport email ; il ne modifie pas `.env` ni la clé OpenRouter. Le navigateur de recette ne doit pas être utilisé en parallèle du test HTTP pour soumettre des connexions répétées : les limites par adresse IP restent actives.

## 2. Activer les services nécessaires avant la soutenance

### 2.1. E-mails locaux avec Mailpit

Mailpit capture les messages sans les envoyer à un destinataire externe. Il n’était pas installé lors de la recette. Choisir une option après installation de l’outil correspondant.

Avec Docker disponible :

```powershell
docker run -d --name solarshare-mailpit -p 127.0.0.1:1025:1025 -p 127.0.0.1:8025:8025 axllent/mailpit
```

Sans Docker : télécharger la version Windows depuis le [projet officiel Mailpit](https://github.com/axllent/mailpit/releases), l’extraire dans un dossier d’outils et lancer le binaire avec les adresses locales :

```powershell
$env:MP_SMTP_BIND_ADDR = '127.0.0.1:1025'
$env:MP_UI_BIND_ADDR = '127.0.0.1:8025'
.\mailpit.exe
```

Dans le `.env` privé de l’instance principale uniquement, remplacer les paramètres email, sans toucher aux autres secrets :

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=demo@solarshare.test
MAIL_FROM_NAME=SolarShare
APP_URL=http://127.0.0.1:8000
```

Puis `php artisan config:clear` et redémarrer le serveur Laravel. Ouvrir `http://127.0.0.1:8025`, effectuer une inscription fictive et vérifier l’arrivée du message et l’ouverture du lien. Les ports par défaut de Mailpit sont documentés [ici](https://mailpit.axllent.org/docs/install/). Le serveur isolé `8010` reste volontairement sur `log` : il n’utilise pas automatiquement cette modification SMTP.

Avec un SMTP externe de test, saisir localement les paramètres fournis par le prestataire et adapter le schéma/port. Ne pas mettre d’identifiant SMTP dans ce document, une capture, un terminal partagé ou Git. Une boîte Mailpit remplie prouve une réception locale, pas la livraison d’un e-mail sur Internet.

### 2.2. OpenRouter : étape à effectuer par le propriétaire

1. Ouvrir `.env` dans un éditeur local privé.
2. Renseigner uniquement `OPENROUTER_API_KEY` avec sa clé personnelle. Ne pas la coller dans ce chat ni dans un formulaire SolarShare.
3. Conserver exactement `OPENROUTER_MODEL=openrouter/free` ; aucune variable par module n’est nécessaire.
4. Exécuter `php artisan config:clear`, puis redémarrer le serveur.
5. Faire **une demande par assistant**, avec les exemples ci-dessous, en espaçant les appels. La limite commune est de cinq demandes par minute et de cent par jour par membre. Les quotas du prestataire peuvent être plus stricts.
6. En cas d’indisponibilité ou de réponse invalide, montrer le message de l’application. Ne pas présenter un résultat des tests automatisés comme une génération réelle.

La clé n’a été ni modifiée ni affichée pendant la recette. Aucun appel ne doit être fait avec des réclamations ou contenus sensibles : utiliser exclusivement les données fictives préparées ci-dessous.

## 3. Préparer les données de démonstration dans le Back Office

Se connecter avec **un compte administrateur existant** et son mot de passe connu du propriétaire. Le créer par les outils administratifs habituels si nécessaire ; l’inscription publique ne permet pas de choisir le rôle admin. Cliquer sur le nom du compte → **Back office**, ou ouvrir `/admin`. Sur mobile, ouvrir d’abord le menu avec le bouton à trois barres.

### Catégorie et équipements

Dans le menu **Équipements → Catégories**, ouvrir le formulaire de création (`/admin/categories/create`). Saisir : nom `Démonstration solaire`, description `Équipements fictifs préparés pour la validation finale`, icône `Soleil`, puis **Enregistrer**. Résultat : fiche de catégorie et message de succès.

Dans **Équipements → Équipements**, ouvrir `/admin/equipements/create` et créer ces deux fiches. Choisir un propriétaire différent du membre qui réservera.

| Champ | Équipement A | Équipement B |
| --- | --- | --- |
| Nom | Démo — Station solaire 1200 Wh | Démo — Panneau portable 200 W |
| Catégorie | Démonstration solaire | Démonstration solaire |
| Propriétaire | Compte administrateur de démonstration | Même compte |
| Marque | SolarShare Démo | SolarShare Démo |
| Description | Station de secours fictive pour alimenter un ordinateur et un éclairage pendant la démonstration. | Panneau portable fictif pour illustrer la comparaison du catalogue et la réservation. |
| Puissance | 750 W | 200 W |
| Capacité | 1200 Wh | Laisser vide |
| Prix par jour | 15 DT | 10 DT |
| Caution | 75 DT | 50 DT |
| État | Bon état | Très bon état |
| Ville | Tunis | Tunis |
| Disponible à la location | Coché | Coché |

Cliquer **Enregistrer** pour chacun. Retenir leurs identifiants affichés dans les URL ; ne pas supposer qu’il s’agit de 1 et 2 sur MySQL. Vérifier aussi la modification d’une description depuis **Modifier**.

### Événement à capacité limitée

Ouvrir **Événements** puis `/admin/evenements/create`. Saisir :

- Titre : `Démo — Atelier énergie solaire` ; type : `Atelier pratique`.
- Description : `Atelier de démonstration pour apprendre à dimensionner une petite installation solaire et comparer des équipements.`
- Lieu : `ESPRIT — Salle de démonstration` ; ville : `Tunis`.
- Début : **5 novembre 2026 à 10 h** ; fin : **5 novembre 2026 à 12 h**.
- Capacité : **1** ; prix : **0** ; statut : **Planifié**.

Cliquer **Enregistrer**. Si la présentation a lieu après cette date, choisir un début futur et une fin deux heures plus tard. Les inscriptions d’un événement déjà commencé sont fermées.

### Article de démonstration

Dans **Blog → Articles**, ouvrir `/admin/articles/create`. Saisir :

- Titre : `Bien choisir sa batterie solaire pour un usage ponctuel`.
- Auteur : compte administrateur de démonstration.
- Résumé : `Une méthode simple pour estimer ses besoins, comparer les caractéristiques du catalogue et préparer une location adaptée.`
- Catégorie : `Énergie solaire` ; statut initial : **Brouillon** ; date de publication : vide.
- Contenu, à copier intégralement :

> Pour choisir une batterie solaire, commencez par dresser la liste des appareils que vous souhaitez utiliser. Relevez leur puissance et estimez leur durée de fonctionnement. Ce travail permet de comparer vos besoins aux caractéristiques enregistrées dans le catalogue. Vérifiez ensuite la puissance de sortie, la capacité de stockage, le prix journalier et la caution. Une capacité importante ne suffit pas si la puissance disponible ne convient pas à votre appareil. Pour un usage ponctuel, la location peut permettre de découvrir un équipement avant de prendre une décision. Choisissez des dates précises et consultez le calendrier des disponibilités. Relisez le montant estimé, vérifiez les conditions de récupération et gardez une marge pour les imprévus. Enfin, utilisez le matériel conformément aux consignes du propriétaire et contactez l’équipe si vous rencontrez un problème.

Cliquer **Enregistrer**. Le contenu dépasse les 80 mots requis pour une publication ultérieure. Garder le brouillon jusqu’à l’étape blog.

## 4. Parcours utilisateur et authentification — 6 minutes

| Étape | Où cliquer et données à saisir | Résultat attendu |
| --- | --- | --- |
| Inscription | Déconnecté → **Inscription**. Nom `Étudiant Démonstration`, e-mail `etudiant.demo@solarshare.test`, ville `Tunis`, téléphone `22333444`, mot de passe de démonstration choisi localement avec huit caractères minimum, lettres et chiffres ; saisir la confirmation identique. | Compte créé avec rôle `user`, session ouverte, notification de vérification générée. Ne pas montrer le mot de passe à l’écran partagé. |
| Validation des champs | Avant l’envoi valide, essayer une confirmation différente ou une adresse invalide. | Message français ; aucun compte incomplet créé. |
| Vérification e-mail | Menu du compte → **Mon profil et sécurité** → **Vérifier mon adresse e-mail**. Dans Mailpit, ouvrir le message et **Vérifier mon adresse e-mail**. | Confirmation de vérification ; réservations, favoris et participations accessibles. Sans SMTP configuré, annoncer que le test automatisé a utilisé le journal local, sans exposer ses tokens. |
| Protection avant vérification | Avec un compte nouvellement inscrit non vérifié, ouvrir `/mes-reservations`. | Redirection vers `/verifier-email` et possibilité de renvoyer le lien. |
| Profil | Menu du compte → **Mon profil et sécurité**. Modifier ville en `Ariana`, choisir une petite photo PNG, saisir le mot de passe actuel puis **Enregistrer le profil**. | Coordonnées enregistrées et photo affichée. Un changement d’adresse e-mail exige une nouvelle vérification. |
| Accès admin interdit | Avec le membre, saisir `/admin` dans l’adresse du navigateur. | HTTP 403 et message français d’accès non autorisé. |
| Mot de passe oublié | Se déconnecter → **Connexion** → **Mot de passe oublié ?**. Saisir l’adresse du membre. Ouvrir le message dans Mailpit et le lien de réinitialisation. | Formulaire de réinitialisation ; nouveau mot de passe et confirmation acceptés ; ancien mot de passe refusé, nouveau accepté. Le lien ne peut pas être réutilisé. |

## 5. Démonstration de la 2FA — 4 minutes

1. Connecté comme membre, ouvrir **Mon profil et sécurité**, section **Double authentification**.
2. Saisir le mot de passe actuel puis **Configurer la 2FA**. Montrer le QR local sans le diffuser dans une capture partagée : il contient la clé de sécurité du compte.
3. Scanner avec une application TOTP et saisir son code à six chiffres, puis **Vérifier**. Résultat : activation et huit codes de récupération affichés une seule fois. Les conserver hors de la présentation et ne pas les montrer au public.
4. Se déconnecter puis se reconnecter avec email et mot de passe. Résultat : écran **Code de sécurité** ; le profil reste inaccessible avant le second facteur.
5. Saisir un **nouveau** code TOTP. Attendre le code suivant si celui ayant servi à l’activation a déjà été consommé. Résultat : connexion complète.
6. Refaire une connexion et utiliser un code de récupération. Résultat : accès accordé. Refaire l’essai avec le même code : refus, puis utiliser un autre code pour poursuivre.
7. Dans le profil, saisir mot de passe actuel et code TOTP neuf ou code de récupération inutilisé, puis **Désactiver la 2FA**. Résultat : état désactivé. Réactiver ensuite si ce compte sera réutilisé.

Le TOTP et les codes ont été exercés par de vraies requêtes HTTP sur un compte jetable. Le scan avec un téléphone reste à faire manuellement. L’heure serveur doit être correcte ; les limites par action sont actives et une tentative excessive doit attendre leur expiration.

## 6. Les cinq modules — 12 à 18 minutes

### M1 — Catalogue, comparaison et assistant d’équipement

1. **Équipements** : rechercher `Démo`, ouvrir la station et relever **15 DT/jour**, **750 W**, **1200 Wh**, caution **75 DT**.
2. **Comparateur** : sélectionner les deux équipements préparés, puis **Comparer**. Résultat : tableau des marques, puissance, capacité, prix, caution, état et ville. Une capacité absente est indiquée comme non renseignée. Sur mobile, le tableau se défile dans son conteneur.
3. Menu du compte → **Assistant choix d’équipement**. Budget journalier : **20**. Besoin : `Je souhaite alimenter un ordinateur et deux lampes pendant une journée. Je cherche une station portable à Tunis, avec au moins 500 W de puissance.`
4. **Demander une suggestion IA**. Avec clé opérationnelle : identifiants du catalogue uniquement, fiches cliquables et données tirées de la base. Sans clé : message explicite, aucun résultat inventé. La sélection finale peut varier selon le modèle ; ne pas promettre un équipement déterminé.

### M2 — Calendrier, réservation, paiement et assistant

1. Menu du compte → **Calendrier des disponibilités**. Choisir la station à 15 DT/jour, début **5 novembre 2026**, fin **6 novembre 2026**, puis **Afficher les disponibilités**.
2. Résultat attendu pour une station de démonstration neuve : deux jours facturés, **30 DT**, hors caution **75 DT**, et calendrier des 60 prochains jours. Si les dates sont déjà occupées, choisir une autre période libre de deux jours.
3. **Préparer ma réservation** → vérifier les dates, ajouter `Location de démonstration pour un ordinateur et des lampes`, puis envoyer la demande. Résultat : réservation personnelle en attente et montant calculé par Laravel.
4. Avec le second membre, tenter la même période. Résultat : chevauchement refusé, pas de seconde réservation.
5. Dans le navigateur administrateur, **Locations → Réservations**, ouvrir la demande. **Locations → Paiements** → créer un paiement, sélectionner cette réservation, montant **30**, méthode **Espèces**, statut **Validé**, date/heure actuelle, référence vide pour génération automatique. Cliquer **Enregistrer**. Résultat : reste à payer nul. Une tentative supplémentaire de 1 DT doit être refusée. Il s’agit d’un enregistrement comptable fictif, pas d’une transaction bancaire.
6. Menu membre → **Assistant de réservation**. Budget total **40**, deux nouvelles dates libres, besoin `Je cherche une station de secours pour deux jours, pour un ordinateur et un éclairage léger.` Puis demander une suggestion. Résultat avec clé : candidats sous budget et sans chevauchement. L’assistant ne réserve rien ; l’envoi final suit toujours le formulaire classique.

### M3 — Réclamation, réponse, statistiques et analyse IA

1. Membre → **Mes réclamations** → nouvelle réclamation. Sujet : `La batterie de démonstration ne charge plus`. Description : `La batterie reste déchargée après plusieurs heures de branchement. Le voyant ne s’allume pas malgré le contrôle du câble.` Catégorie **Problème technique**, priorité **Haute**. Joindre facultativement le PDF fictif. Envoyer.
2. Résultat : fiche personnelle et statut ouvert. Un autre membre ne doit pas accéder à son URL ni à sa pièce jointe.
3. Administrateur → **Réclamations** : filtrer priorité haute et statut ouvert ; montrer les compteurs. Ouvrir la fiche.
4. Menu **ASSISTANTS IA → Analyse de réclamation** : sélectionner le sujet et saisir `Analyse cette réclamation, propose une catégorie, une priorité et une réponse courtoise sans promettre de remboursement.` Demander la suggestion.
5. Avec une réponse réelle disponible, relire et modifier les champs. Le résumé reste informatif. **Valider la catégorie, la priorité et envoyer la réponse** enregistre uniquement après cette action ; vider la réponse pour ne modifier que catégorie et priorité. Sans clé, revenir à la réponse manuelle sans prétendre que l’IA a répondu.
6. Réponse manuelle possible : `Nous allons examiner le fonctionnement de la batterie avec le propriétaire. Merci de conserver le matériel et son câble pour le contrôle.` Passer le statut **En cours**. Montrer l’historique avant/après, l’auteur et la date, puis la réponse dans l’espace membre.

### M4 — Événement, capacité et programme IA

1. Administrateur → **ASSISTANTS IA → Programme d’événement**. Choisir l’atelier préparé. Consigne : `Propose un programme d’accueil, une démonstration et un temps de questions en respectant les dates et le lieu enregistrés. N’invente aucun intervenant.`
2. Avec une réponse réelle, corriger description et programme puis **Enregistrer les propositions relues**. Résultat : programme ajouté à la description ; dates, prix, capacité et statut conservés.
3. Membre → **Événements** → atelier → **Je m’inscris**. Résultat : inscription et capacité restante à zéro.
4. Second membre → même fiche : inscriptions fermées ou refus serveur. Ne pas seulement montrer le bouton désactivé ; rappeler que le refus serveur est aussi vérifié par la recette HTTP.
5. Premier membre → annuler son inscription ; second membre → s’inscrire. Résultat : une place libérée puis réattribuée, sans dépassement de capacité. La liste d’attente n’est pas une fonctionnalité du projet.

### M5 — Brouillon, assistant, publication, commentaires et favoris

1. Administrateur → **ASSISTANTS IA → Assistant de rédaction**, sélectionner l’article préparé. Consigne : `Résume cet article, propose un titre clair, cinq mots-clés et des améliorations concrètes sans ajouter de faits externes.`
2. Avec une réponse réelle, modifier titre et résumé, puis **Enregistrer les propositions relues**. Résultat : ces deux champs sont enregistrés, le statut reste **Brouillon**. Les mots-clés et améliorations restent des suggestions, pas une publication automatique.
3. **Blog → Articles** → modifier → choisir **Publié**, laisser la date vide pour une publication immédiate, puis **Enregistrer**. Résultat : article visible dans le blog. Une date future le masque aussi par son URL directe depuis la correction de recette.
4. Membre → **Blog** → article → commentaire `Ces conseils me permettent de mieux comparer les équipements avant de réserver.` → envoyer. Résultat : commentaire affiché ; attendre 30 secondes avant un deuxième commentaire.
5. **Enregistrer dans mes favoris**, puis menu du compte → **Mes articles favoris**. Résultat : article présent une seule fois. **Retirer des favoris** le supprime de la liste personnelle. Le compte d’un autre membre ne voit pas ces favoris.
6. Administrateur → **Blog → Commentaires** : présenter la modération avec le commentaire fictif, sans toucher aux contenus historiques.

## 7. Présenter les tests et le DevOps — 4 minutes

Dans un terminal sans secret affiché :

```powershell
php artisan test --compact
php vendor/bin/pint --test
composer validate --strict
npm run build
```

La recette du 30 octobre a étendu la suite à **27 tests et 181 assertions**. Présenter séparément les **70 vérifications HTTP réelles** dans `docs/recette-http-resultats.json` : 62 pour le parcours complet et 8 complémentaires. Ce fichier ne contient que les libellés et résultats, pas les tokens. Ne pas ouvrir `runtime/logs/laravel.log` devant le public.

Ouvrir `.github/workflows/ci.yml` : expliquer checkout, PHP 8.3, installation Composer verrouillée, configuration de test SQLite en mémoire, migrations, audit de dépendances, Pint, PHPUnit/JUnit, compilation des routes et vues, Node 22, `npm ci`, Vite et artefacts. Le verrou npm a été réinstallé avec succès localement ; le workflow a été validé par `actionlint`. Cela ne constitue pas une exécution sur GitHub.

Ouvrir `.github/workflows/cd.yml`, `scripts/deploy.sh` et les deux diagrammes de `docs/DEVOPS_VALIDATION_FINALE.md`. Expliquer le déclenchement manuel, les environnements `staging`/`production`, la sauvegarde obligatoire, les releases séparées, les migrations additives, la bascule du lien et les contrôles HTTP. Aucun push ni déploiement n’a été réalisé pendant la recette.

### Ce qu’il reste pour GitHub et staging

1. Après autorisation du propriétaire, ajouter/committer les fichiers locaux, y compris les fichiers actuellement non suivis : workflows, services, migration, tests, vues et `package-lock.json`. Ne jamais ajouter `.env`, journaux, clés ni la base de recette.
2. Pousser vers le dépôt GitHub autorisé et activer Actions si nécessaire. Vérifier chaque job, le JUnit et l’artefact `solarshare-assets`.
3. Préparer un serveur Linux avec PHP 8.3, extensions, Composer, MySQL/InnoDB, Nginx/PHP-FPM, HTTPS, curl, tar et flock. Appliquer `docs/nginx.conf.example` après adaptation et donner les permissions d’écriture nécessaires au processus web.
4. Préparer `/srv/solarshare/shared/.env`, `shared/storage`, `shared/uploads` et un `shared/backup-before-deploy` exécutable qui sauvegarde réellement base et fichiers et dont la restauration a été testée.
5. Dans l’environnement GitHub `staging`, configurer les secrets `SSH_HOST`, `SSH_USER`, `SSH_PORT`, `SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS` et les variables `DEPLOY_ROOT`, `HEALTH_URL`. Les saisir dans l’interface privée, jamais dans les fichiers YAML.
6. Maintenir une `APP_KEY` stable, `APP_ENV=production`, `APP_DEBUG=false`, les cookies sécurisés, les accès MySQL, le SMTP et OpenRouter. Configurer les workers si utilisés et vérifier l’heure serveur pour le TOTP.
7. Après autorisation explicite de déploiement, déclencher le workflow manuel sur staging et réaliser la recette de connexion, email, IA et téléchargement. Mettre des approbateurs sur l’environnement production avant toute livraison de production.

## 8. Dernière répétition et transparence

Vérifier avant l’arrivée du jury : serveur démarré, Mailpit ouvert, message de vérification effectivement reçu, clé IA configurée localement, dates de démonstration futures, deux comptes membres, un admin et une application TOTP fonctionnelle. Ne pas ouvrir `.env`, un QR TOTP ou les codes de secours sur une projection publique.

Si l’IA est indisponible : montrer le message prévu et les tests API simulés en les nommant comme tels. Si SMTP n’est pas configuré : annoncer que seuls la génération locale de notification et le fonctionnement du lien ont été exercés. Si GitHub/staging ne sont pas configurés : présenter les fichiers et leurs validations statiques, sans annoncer une exécution distante réussie.
