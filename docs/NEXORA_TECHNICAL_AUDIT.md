# Audit technique NEXORA

> **Instantané historique (avant les lots IA récents).** Les constats ci-dessous datent du premier audit en lecture seule et ne décrivent plus l’état courant : plusieurs modules listés comme absents ont depuis été implémentés. Pour la matrice actuelle, utiliser [AUDIT_FINAL.md](AUDIT_FINAL.md), [VALIDATION_SOUTENANCE.md](VALIDATION_SOUTENANCE.md) et [PROGRESS.md](PROGRESS.md).

**Date :** 4 novembre 2026  
**Périmètre :** dépôt Laravel local, code, migrations, tests, configuration et commandes artisan.  
**Méthode :** lecture seule. Aucune migration, donnée métier, publication, réservation, déploiement ou requête OpenRouter réelle n'a été exécutée pendant cet audit.

## 1. Résumé exécutif

NEXORA est une application Laravel 12.69.3 structurée en Front Office et Back Office. Les modules métier suivants existent : utilisateurs, équipements, réservations, paiements, réclamations, réponses, événements, participations, blog, commentaires, favoris, authentification, vérification e-mail et 2FA.

Une première couche IA est déjà fonctionnelle : cinq expériences Front Office, cinq expériences Back Office, cinq formulaires de propositions métier, un service OpenRouter centralisé et un historique de conversations chiffré et isolé par utilisateur.

Cette base ne couvre pas encore les seize fonctionnalités demandées comme des produits complets. Les stocks/mouvements, notifications applicatives, quiz, feedback IA, recherche globale intelligente, recommandations persistées, scores explicables et préférences multilingues n'ont pas de structure dédiée identifiée.

### Validation exécutée pendant l'audit

| Contrôle | Résultat | Limite |
| --- | --- | --- |
| `php artisan test` | **PASS : 56 tests, 394 assertions** | SQLite mémoire, OpenRouter simulé |
| `php artisan route:list --except-vendor` | **PASS : 132 routes** | Inventaire local |
| `php artisan migrate:status` | **PASS : 16 migrations appliquées** | État de la base configurée localement |
| `php artisan about` | **PASS** | Laravel 12.69.3, PHP 8.3.31, locale fr, Africa/Tunis |
| `npm run build` | **PASS** | Build Vite local |
| OpenRouter réel | Non exécuté | Aucun quota consommé |
| Parcours navigateur responsive | Non certifié | Pas d'inspection visuelle dans cet audit |
| Docker/Kubernetes/Jenkins/Sonar | Non exécutés | Configuration relue statiquement |

Point de cohérence : l'environnement effectif expose encore `Application Name: SolarShare`, alors que le code et plusieurs documents utilisent NEXORA. La valeur de `.env` n'a pas été modifiée.

## 2. Architecture actuelle

- PHP `^8.2`, vérifié en PHP 8.3.31 ; Laravel `^12.0`.
- MySQL configuré pour l'environnement local ; SQLite mémoire utilisé par PHPUnit.
- Vite 7, Tailwind CSS 4, Axios et Bootstrap 5 dans les vues existantes.
- Authentification Laravel personnalisée avec rôles applicatifs `user` et `admin`.
- Vérification e-mail, mot de passe, 2FA TOTP et codes de récupération.
- Routes séparées par module dans `routes/modules`.
- Services métier existants : réservations, paiements, participations, 2FA, OpenRouter et conversations IA.
- Préparations Docker, Compose, Jenkins, Kubernetes, monitoring et Sonar présentes mais non exécutées sur une cible réelle.

### Flux IA actuel

1. Une route authentifiée et vérifiée reçoit une demande.
2. Laravel valide les entrées, le module et les permissions.
3. `AiContextBuilder` sélectionne les données publiques, personnelles ou agrégées autorisées.
4. `OpenRouterService` transmet un contexte JSON minimisé côté serveur.
5. La réponse est décodée et validée par les règles Laravel ; une seule reprise est possible pour une sortie tronquée ou invalide.
6. Les références sont rechargées depuis la base avant affichage.
7. Les actions métier restent soumises à une validation explicite.

Ce flux est une base saine : disponibilités, prix, capacités, stock et autorisations doivent rester déterministes côté Laravel.

## 3. Modules existants

| Module | Données et règles observées | État |
| --- | --- | --- |
| Utilisateurs | Profil, rôles, vérification, 2FA, favoris, réclamations, conversations IA | Fonctionnel et testé |
| Équipements | Catégorie, propriétaire, prix/jour, caution, puissance, capacité, état, ville, disponibilité | Fonctionnel ; pas de stock quantifié |
| Réservations | Dates, jours, montant, statuts, paiements, conflits, exclusion du propriétaire | Fonctionnel et testé |
| Réclamations | Sujet, catégorie, priorité, statut, pièce jointe, réponses, historique | Fonctionnel et testé |
| Événements | Type, lieu, dates, capacité, prix, statut, participations, places restantes | Fonctionnel et testé |
| Blog | Brouillon/publication, résumé, contenu, catégories, vues, commentaires, favoris | Fonctionnel et testé |
| Recherche | Filtres SQL et recherche textuelle par module | Présente, non globale et non sémantique |
| Notifications | E-mails de vérification et de réinitialisation Laravel | Présentes, pas de centre métier |

## 4. État des dix assistants IA

| Fonctionnalité cible | État constaté | Écart principal |
| --- | --- | --- |
| FO Smart Energy Matcher | Partiel : catalogue disponible filtré par budget | Pas de score reproductible, critères logement/consommation, alternatives et hypothèses d'économie |
| BO Equipment Demand Radar | Partiel : agrégats catalogue dans une conversation | Pas de stock, historique de demande, prévision ni écran radar |
| FO Smart Booking Optimizer | Partiel : équipements disponibles par dates et budget | Pas de parcours unifié équipement/événement, classement explicable ni revalidation dédiée à la confirmation |
| BO Booking Insights | Partiel : agrégats réservations/paiements | Pas de KPI, graphiques et comparaisons temporelles dédiés |
| FO Complaint Doctor | Partiel : conversation et brouillon | Pas d'éditeur structuré sentiment/urgence/champs manquants/correction |
| BO Complaint Intelligence Center | Partiel : agrégats et proposition sur dossier | Pas de tendances, récurrences, délais et file urgente dédiée |
| FO Event Matchmaker | Partiel : événements publics et futurs dans le contexte | Pas de profil d'intérêts, historique exploité ni score de compatibilité |
| BO Event Blueprint Generator | Partiel : description/programme sur événement existant | Pas de formulaire blueprint, checklist et brouillon de création dédié |
| FO Article Intelligence | Partiel : résumé et explication conversationnels | Pas de quiz, corrigé Laravel, tentatives, score et articles similaires |
| BO SEO & Editorial Copilot | Partiel : titre, résumé, mots-clés et améliorations | Pas d'analyse SEO calculée, lisibilité, répétitions, méta-description et score documenté |

### OpenRouter

`OpenRouterService` centralise la clé, le modèle, le timeout, les erreurs HTTP, `Retry-After`, le cooldown 429, les tentatives limitées et la validation JSON. Les journaux n'enregistrent ni secret, ni prompt, ni corps fournisseur.

Limites : configuration réduite à clé/modèle/timeout ; absence de registre versionné des prompts et schémas ; absence de `ai_feature_runs` pour statut, durée, langue, coût ou référence métier ; absence de tests dédiés FR/AR/EN et injection dans contenu fourni ; aucun résultat live actuel ne certifie disponibilité ou qualité fournisseur.

## 5. Base de données

Les migrations appliquées couvrent `users`, `cache`, `jobs`, `categories`, `equipements`, `reservations`, `paiements`, `reclamations`, `reponses_reclamations`, `evenements`, `participations`, `articles`, `commentaires`, sécurité/favoris, `ai_conversations` et `ai_messages`.

Relations importantes : utilisateur vers équipements/réservations/réclamations/favoris/conversations ; réservation vers équipement/locataire/paiements ; événement vers participations ; article vers auteur/commentaires/favoris ; conversation vers utilisateur/module/audience/messages.

Aucune structure dédiée n'a été trouvée pour :

- quantité totale/disponible/réservée, seuils et mouvements de stock ;
- exécutions IA, recommandations persistées et feedback IA ;
- analyses de réclamation ;
- préférences et notifications métier idempotentes ;
- quiz, corrigés, tentatives et scores ;
- préférences de langue/RTL ;
- brouillons de blueprint ou versions éditoriales.

Avant toute migration, il faudra confirmer le schéma MySQL réel, les volumes et les contraintes de production. Les migrations devront être additives, réversibles, indexées et testées sur une base isolée. Aucun changement de relation existante n'est actuellement justifié.

## 6. Permissions, sécurité et confidentialité

### Points solides

- routes IA protégées par `auth` et `verified`, routes BO par `admin` ;
- conversations filtrées par utilisateur, module et audience ;
- réclamations FO limitées à leur propriétaire ;
- agrégats BO conçus sans identité utilisateur ;
- CSRF et validation Laravel ;
- limitation IA de 5 appels/minute et 100/jour par utilisateur ;
- IDs retournés par l'IA contrôlés contre les candidats réels ;
- aucune réservation, publication ou modification automatique ;
- contenu IA échappé dans les vues observées.

### Risques à traiter dans les phases suivantes

- scores, confiance et explications doivent être calculés côté Laravel, jamais inventés par le modèle ;
- la recherche globale devra réappliquer les policies de propriété et de rôle ;
- les nouveaux endpoints devront avoir des contrôles de propriété explicites ;
- les prompts doivent minimiser les données personnelles et considérer les contenus comme non fiables ;
- les notifications doivent être dédupliquées et respecter les préférences ;
- les confirmations de réservation/participation devront revalider disponibilité/capacité dans une transaction verrouillée ;
- la différence d'identité SolarShare/NEXORA doit être clarifiée avant démonstration.

## 7. Interface, traduction et DevOps

Les vues FO/BO et le hub IA existent, mais les écrans spécialisés de radar, insights, quiz, feedback, notifications et dashboards enrichis n'existent pas encore. Le build Vite passe ; le responsive et l'accessibilité interactive n'ont pas été certifiés visuellement.

La configuration active est française. Aucune architecture complète de préférence utilisateur, réponses IA FR/AR/EN, RTL arabe et validation des composants arabes n'a été identifiée.

Docker, Compose, Jenkins, Kubernetes, monitoring et Sonar sont des préparations documentées. Les validations existantes indiquent qu'ils n'ont pas été exécutés sur une cible réelle. Les secrets doivent rester dans l'environnement ou un gestionnaire de secrets.

## 8. Réutilisation recommandée

Conserver et étendre : `OpenRouterService`, `AiContextBuilder`, `AiConversationService`, les modèles de conversations, `ReservationService`, `ParticipationService`, `PaymentService`, les scopes métier, les middleware de rôle, les layouts Blade, Vite et les tests existants.

Éviter de créer un second provider OpenRouter, un second système de conversations ou des doublons de modèles/routage. `AiController` pourra être découpé seulement si la croissance des propositions le justifie.

## 9. Lacunes et schéma à concevoir

Priorité de conception :

1. Contrat commun d'exécution IA : fonctionnalité, langue, version de prompt, contexte minimisé, statut, durée et erreurs bornées.
2. Services déterministes pour scores, statistiques, disponibilités, capacités et validation des références.
3. Tables additives uniquement après confirmation : exécutions IA, feedback, préférences/notifications, quiz, puis stock/mouvements.
4. Policies pour feedback, quiz, brouillons et ressources privées.
5. Contrats JSON par fonctionnalité et règles de validation versionnées.

## 10. Plan d'implémentation priorisé

### Phase 2 - Approbation et conception

Valider ce diagnostic, confirmer le périmètre de soutenance et la base MySQL cible, puis définir schéma, policies, contrats JSON et formules de scores avant toute modification importante.

### Phase 3 - Infrastructure IA commune

Conserver le provider actuel, ajouter observabilité sans secrets, versionner prompts/schémas, formaliser les erreurs et tester 400/401/402/403/429/5xx, timeout, JSON vide/invalide et cooldown.

### Phase 4 - Équipements et réservations

Construire les scores explicables, le Demand Radar, Booking Insights et le comparateur, avec agrégats Laravel, transactions et tests de concurrence.

### Phase 5 - Réclamations, événements et blog

Ajouter éditeurs et propositions modifiables, blueprint non publié, quiz à corrigé déterministe et copilot SEO sans modification automatique.

### Phase 6 - Fonctions transversales

Ajouter recherche autorisée, notifications idempotentes, dashboards réels, feedback IA et explicabilité commune.

### Phase 7 - Langues et interface

Ajouter traductions FR/EN/AR, préférence de langue, RTL, écrans dédiés, états vides/erreur/chargement et contrôles desktop/tablette/mobile.

### Phase 8 - Validation et documentation

Rejouer PHPUnit, Pint, routes, vues, migrations sur base isolée, build Vite, tests de sécurité et tests IA simulés. Effectuer un test OpenRouter réel uniquement si clé/quota/autorisation sont disponibles. Créer ensuite `docs/NEXORA_AI_ARCHITECTURE.md` et `docs/NEXORA_INSTALLATION.md`.

## 11. Conclusion

Le projet constitue un socle sérieux et testé, mais l'état actuel ne doit pas être présenté comme l'implémentation complète des dix assistants spécialisés et des six fonctions transversales. Il s'agit d'une première couche IA conversationnelle et de propositions éditables, à étendre progressivement.

La prochaine étape recommandée est votre approbation du diagnostic et du plan, suivie de la conception des contrats et migrations. Aucun fichier applicatif, schéma ou interface supplémentaire n'a été modifié dans cette phase ; seul ce rapport d'audit a été créé comme livrable demandé.
