# Conception technique cible de NEXORA Intelligence

> **Document de conception initiale.** La proposition ci-dessous précède les implémentations récentes ; elle n’est pas un état des lieux. Voir [AI_ARCHITECTURE.md](AI_ARCHITECTURE.md) pour les services et parcours existants.

**Statut :** proposition de conception, phase 2  
**Date :** 4 novembre 2026  
**Prérequis :** approbation de la conception avant toute implémentation

Ce document décrit l'architecture cible des seize fonctionnalités IA sans modifier le code, le schéma ou les données existantes. Les opérations métier critiques restent déterministes et exécutées par Laravel. OpenRouter intervient uniquement pour analyser, classer, expliquer ou générer des propositions.

## 1. Principes d'architecture

### Responsabilités Laravel

- authentification, rôles, policies et propriété des ressources ;
- accès Eloquent et filtrage des données autorisées ;
- calculs de prix, dates, disponibilités, capacités, stocks et statistiques ;
- validation des entrées et sorties JSON ;
- transactions, verrouillages et confirmation des opérations ;
- persistance des brouillons, feedback, notifications et historiques ;
- calcul des scores reproductibles et de leurs critères ;
- traduction des libellés système et choix de la langue d'affichage.

### Responsabilités IA

- interprétation d'un besoin en langage naturel ;
- classification, résumé et extraction d'informations ;
- génération de recommandations à partir d'un contexte déjà filtré ;
- explication en langage naturel de résultats calculés par Laravel ;
- génération de brouillons non publiés et non confirmés.

### Règles communes

1. Aucun modèle IA ne choisit seul une opération critique.
2. Aucun prix, équipement, événement, disponibilité ou réservation n'est inventé.
3. Les références renvoyées par le modèle sont revalidées contre les candidats SQL.
4. Les données privées sont filtrées avant l'appel et ne sont jamais exposées entre utilisateurs.
5. Toute proposition éditoriale est modifiable avant enregistrement.
6. Toute recommandation importante expose critères, données utilisées, limites et confiance calculée.
7. Une indisponibilité OpenRouter produit une erreur explicite, jamais un faux résultat.

## 2. Architecture cible des couches

```text
HTTP / Blade / JS
        |
Controllers + Form Requests + Policies
        |
Application Services spécialisés
        |
Domain calculators / Query objects / Transactions
        |                         |
        |                         +--> Eloquent / MySQL
        |
        +--> AI Core --> OpenRouterProvider
                 |
                 +--> PromptRegistry
                 +--> ContextSanitizer
                 +--> JsonOutputValidator
                 +--> UsageLogger
                 +--> ExplainabilityBuilder
```

Organisation proposée, à adapter aux classes déjà présentes pour éviter les doublons :

```text
app/AI/Core/
  AIProvider.php
  OpenRouterProvider.php
  AIRequest.php
  AIResponse.php
  PromptRegistry.php
  AIContextSanitizer.php
  AIOutputValidator.php
  AIUsageLogger.php
  AIExceptionMapper.php
  ExplainabilityBuilder.php
app/AI/Equipment/
  SmartEnergyMatcher.php
  EquipmentDemandRadar.php
app/AI/Reservations/
  SmartBookingOptimizer.php
  BookingInsights.php
app/AI/Claims/
  ComplaintDoctor.php
  ComplaintIntelligence.php
app/AI/Events/
  EventMatchmaker.php
  EventBlueprintGenerator.php
app/AI/Blog/
  ArticleIntelligence.php
  EditorialCopilot.php
app/AI/GlobalSearch/
  GlobalSearchService.php
app/AI/Notifications/
  SmartNotificationService.php
app/AI/Dashboard/
  AIDashboardService.php
app/AI/Feedback/
  AIFeedbackService.php
app/AI/Localization/
  AILanguageService.php
```

`OpenRouterService`, `AiContextBuilder` et `AiConversationService` sont les points existants à réutiliser. La migration vers les noms proposés doit être progressive et ne doit pas créer un second chemin fournisseur en parallèle.

## 3. Comparatif existant / cible

| Fonctionnalité | Existant | À développer pour la cible | Priorité |
| --- | --- | --- | --- |
| Smart Energy Matcher FO | Catalogue filtré par budget, références réelles | critères logement/consommation, score sur 100, alternatives, hypothèses d'économie, explication | P1 |
| Equipment Demand Radar BO | agrégats catalogue dans une conversation | historique demande, stock, seuils, tendances, rupture, graphiques et recommandations | P2 |
| Smart Booking Optimizer FO | équipements libres filtrés par dates/budget | équipement + événement, classement, conflits explicables, revalidation à confirmation | P1 |
| Booking Insights BO | agrégats réservations/paiements | KPI, taux annulation/occupation, périodes, comparaisons, séries temporelles | P1 |
| Complaint Doctor FO | conversation et brouillon | analyse structurée, sentiment, urgence, correction, champs manquants, prévisualisation | P1 |
| Complaint Intelligence BO | agrégats et proposition sur dossier | récurrences, tendances, délais, urgences, réponses suggérées, tableau analytique | P2 |
| Event Matchmaker FO | événements publics dans le contexte | préférences, historique, dates, localisation, score, capacité et similaires | P2 |
| Event Blueprint Generator BO | description/programme sur événement | formulaire complet, objectifs, planning, activités, checklist, brouillon | P2 |
| Article Intelligence FO | résumé/explication conversationnels | idées clés, questions, quiz à corrigé Laravel, tentatives, articles similaires | P2 |
| SEO & Editorial Copilot BO | titre/résumé/mots-clés/améliorations | audit SEO, lisibilité, répétitions, méta-description, score et sélection de propositions | P2 |
| Global Search | recherches textuelles par module | intention, synonymes, classement, filtres et permissions globales | P3 |
| Smart Notifications | e-mails système uniquement | préférences, priorité, déduplication, notifications métier et historique | P3 |
| AI Dashboard | dashboard métier et conversations BO | synthèse FO/BO, recommandations, alertes et graphiques réels | P3 |
| Multilingual AI | locale FR principalement | FR/AR/EN, préférences, réponses dans la langue, RTL | P3 |
| Explainable AI | garde-fous et références réelles | critères, score reproductible, confiance, limites et provenance | P1 |
| AI Feedback | aucun stockage identifié | feedback par fonctionnalité/exécution, note, commentaire et analyse BO | P3 |

## 4. Conception des seize fonctionnalités

### 4.1 Équipements

#### Smart Energy Matcher FO

Entrées : type de logement, surface, occupants, consommation, budget, besoin et préférences. Laravel filtre `Equipement` par disponibilité, propriétaire, prix et caractéristiques connues. Un calculateur produit un score documenté, par exemple pondération besoin/capacité, budget, puissance, ville et état, sans laisser l'IA choisir la note.

OpenRouter reçoit les candidats et les critères non sensibles et retourne une explication, les avantages, les limites et les alternatives par IDs. Laravel revalide les IDs et affiche prix, caution et caractéristiques depuis la base.

#### Equipment Demand Radar BO

Laravel agrège réservations, périodes, équipements et, après ajout du stock, mouvements. Le radar produit volumes, évolution, taux d'utilisation, risque de rupture et seuil dépassé. L'IA rédige une synthèse et des pistes de réapprovisionnement ; aucune modification de stock n'est automatique.

### 4.2 Réservations

#### Smart Booking Optimizer FO

Un service de recherche gère équipements et événements dans deux sous-flux séparés. Il vérifie dates, conflits, capacité, statut publié et propriété. Les options sont classées par un score déterministe, puis expliquées par IA. La confirmation reste une action utilisateur avec nouvelle transaction et verrouillage.

#### Booking Insights BO

Des query objects calculent réservations totales, statuts, annulations, équipements demandés, occupation, événements populaires et remplissage par période. Les séries sont données aux graphiques ; l'IA interprète uniquement les tendances déjà calculées.

### 4.3 Réclamations

#### Complaint Doctor FO

Le texte est envoyé après suppression des champs inutiles. Le modèle propose catégorie, sentiment, urgence, résumé, correction et informations manquantes. Laravel vérifie les valeurs autorisées. L'utilisateur prévisualise et modifie ; aucune soumission ni changement de statut n'est déclenché.

#### Complaint Intelligence BO

Laravel agrège catégories, priorités, statuts, dates et délais de traitement. Un service de regroupement prépare les problèmes récurrents. L'IA propose une synthèse et des réponses, conservées comme proposition avant validation administrative.

### 4.4 Événements

#### Event Matchmaker FO

Le moteur sélectionne uniquement les événements publiés, futurs et éligibles. Le profil de préférence et l'historique de participations sont utilisés seulement si disponibles et autorisés. Le score tient compte de type, date, ville, intérêt et places restantes. Les recommandations similaires sont limitées aux IDs connus.

#### Event Blueprint Generator BO

Un formulaire admin collecte thème, objectif, cible, type, durée, capacité et ton. Le modèle renvoie titre, description, objectifs, planning, activités et checklist. Le résultat est stocké comme brouillon séparé ou dans un état non publié, jamais directement dans un événement publié.

### 4.5 Blog

#### Article Intelligence FO

Le contexte contient uniquement un article publié autorisé. Le modèle génère résumé, idées clés, explication et questions structurées. Laravel conserve le corrigé attendu, calcule le score et empêche le modèle de décider de la réussite. Les articles similaires sont sélectionnés par requête et non inventés.

#### SEO & Editorial Copilot BO

Laravel calcule longueur, structure, présence de titres, densité et répétitions simples. OpenRouter propose titres, méta-description, mots-clés et reformulations. L'admin choisit les champs à appliquer ; un article publié n'est jamais modifié automatiquement.

### 4.6 Fonctions transversales

#### Global Search

Une couche de recherche fédérée interroge séparément équipements, événements, articles et ressources privées autorisées. Laravel applique les policies avant classement. OpenRouter peut normaliser l'intention et les synonymes, mais ne reçoit que les résultats déjà admissibles et ne crée aucun résultat.

#### Smart Notifications

Des événements Laravel déclenchent des candidats de notification : réservation à venir, changement de statut, événement proche, stock faible ou réclamation urgente. Un déduplicateur et les préférences utilisateur précèdent l'envoi. L'IA peut personnaliser le texte, mais ne choisit ni destinataire ni événement déclencheur.

#### AI Dashboard

Le FO agrège activité personnelle, réservations, recommandations, articles et notifications. Le BO agrège réservations, équipements, stocks, réclamations, événements et blog. Tous les nombres viennent de query objects ; l'IA résume les agrégats et formule des actions à examiner.

#### Multilingual AI

Une préférence utilisateur choisit `fr`, `en` ou `ar`. Les prompts, messages système, erreurs et notifications sont localisés. La réponse IA porte explicitement la langue attendue et est contrôlée. Le layout applique `dir=rtl` en arabe avec tests de formulaires, tableaux et dates.

#### Explainable AI

Chaque recommandation porte un objet d'explicabilité : critères, valeurs prises en compte, formule, données sources, limites et confiance. La confiance est une mesure de couverture des critères disponibles, pas une probabilité inventée par le modèle.

#### AI Feedback

L'utilisateur ou l'admin peut évaluer une recommandation ou une réponse. Le feedback référence l'exécution IA, la fonctionnalité et éventuellement la ressource, sans stocker inutilement le prompt complet. Le BO fournit des agrégats d'utilité et de signalement d'erreurs.

## 5. Proposition de base de données

Les noms sont des propositions de conception, pas des migrations à créer maintenant. Toutes les tables doivent utiliser des clés étrangères, index, timestamps, suppression logique lorsque nécessaire et migrations réversibles.

### Tables transversales

| Table proposée | Colonnes principales | Relations |
| --- | --- | --- |
| `ai_feature_runs` | `user_id`, `feature`, `audience`, `module`, `provider`, `model`, `prompt_version`, `locale`, `status`, `input_hash`, `duration_ms`, `error_code`, `source_type`, `source_id` | `users`; morphisme optionnel vers ressource métier |
| `ai_feedback` | `user_id`, `ai_feature_run_id`, `feature`, `rating`, `feedback_type`, `comment`, `resource_type`, `resource_id` | `users`, `ai_feature_runs`, ressource polymorphe |
| `ai_recommendations` | `ai_feature_run_id`, `user_id`, `resource_type`, `resource_id`, `rank`, `score`, `criteria`, `explanation`, `confidence`, `expires_at` | `users`, `ai_feature_runs`, ressource polymorphe |
| `notification_preferences` | `user_id`, `locale`, catégories, canaux, fréquence, `enabled` | unique `user_id` vers `users` |
| `smart_notifications` | `user_id`, catégorie, priorité, titre, contenu, `resource_type`, `resource_id`, `dedupe_key`, `read_at`, `sent_at`, `expires_at` | `users`, ressource polymorphe |
| `user_ai_preferences` | `user_id`, `locale`, `rtl`, préférences de recommandations et d'analyses | unique `user_id` vers `users` |

`ai_conversations` et `ai_messages` restent utilisés pour le dialogue. `ai_feature_runs` sert à tracer une exécution structurée sans remplacer l'historique conversationnel.

### Tables métier conditionnelles

| Table proposée | Colonnes principales | Relations |
| --- | --- | --- |
| `equipment_stocks` | `equipement_id`, `quantity_total`, `quantity_available`, `quantity_reserved`, `minimum_threshold`, `version` | unique vers `equipements` |
| `stock_movements` | `equipment_stock_id`, `user_id`, `type`, `quantity`, `reason`, `occurred_at`, `reference_type`, `reference_id` | stock, utilisateur, référence polymorphe |
| `ai_quiz_definitions` | `article_id`, `version`, `locale`, `questions`, `answer_key`, `published_at` | article, version non destructive |
| `ai_quiz_attempts` | `ai_quiz_definition_id`, `user_id`, `score`, `answers`, `completed_at` | quiz, utilisateur |
| `event_blueprints` | `user_id`, `source_event_id`, `status`, `locale`, `input_data`, `proposal_data`, `approved_at` | auteur, événement optionnel |
| `article_ai_analyses` | `article_id`, `user_id`, `ai_feature_run_id`, scores, suggestions, `status` | article, auteur, exécution |
| `complaint_ai_analyses` | `reclamation_id`, `user_id`, `ai_feature_run_id`, catégorie, sentiment, urgence, résumé, suggestion | réclamation, auteur, exécution |

### Réutilisation des tables existantes

- `equipements` reste la source des prix, caractéristiques, propriétaire et disponibilité.
- `reservations` reste la source des conflits, statuts, dates et montants.
- `participations` et `evenements` restent la source de capacité et d'inscription.
- `reclamations`, `reponses_reclamations` et `reclamation_histories` restent la source du traitement.
- `articles` et `commentaires` restent la source du contenu public et de la modération.
- `users` reste la source des rôles et de la propriété ; aucune copie d'identité ne doit être créée.

La création de `equipment_stocks` n'est justifiée que si le métier confirme qu'un équipement peut représenter plusieurs unités. Sinon, la disponibilité booléenne existante doit rester la règle.

## 6. Services et contrats OpenRouter

### Contrat commun

Chaque feature appelle un orchestrateur avec :

```php
AIRequest {
    feature, audience, locale, task, context, validationRules,
    sourceType, sourceId, promptVersion
}
```

Le résultat contient : statut, données validées, métadonnées bornées, durée, modèle et identifiant d'exécution. Aucun prompt complet, secret ou contenu personnel inutile n'est journalisé.

### Services spécialisés

| Service | Calcul local obligatoire | Appel IA |
| --- | --- | --- |
| `SmartEnergyMatcher` | candidats, score, budget, références | explication et conseils |
| `EquipmentDemandRadar` | agrégats, seuils, tendances | synthèse et pistes |
| `SmartBookingOptimizer` | dates, conflits, capacité, classement | raisons et alternatives |
| `BookingInsights` | KPI et séries | interprétation |
| `ComplaintDoctor` | valeurs permises, taille, propriété | analyse et reformulation |
| `ComplaintIntelligence` | volumes, délais, urgences | synthèse et réponses proposées |
| `EventMatchmaker` | publication, date, capacité, score | justification |
| `EventBlueprintGenerator` | validation des limites et statut brouillon | contenu proposé |
| `ArticleIntelligence` | article public, corrigé, score quiz | résumé et questions |
| `EditorialCopilot` | métriques éditoriales et limites | suggestions SEO |
| `GlobalSearchService` | filtrage permissions et recherche fédérée | intention/synonymes |
| `SmartNotificationService` | déclencheur, préférences, déduplication | personnalisation optionnelle |
| `AIDashboardService` | agrégats et séries | synthèse |
| `AIFeedbackService` | propriété et agrégation | aucune, sauf analyse BO optionnelle |

## 7. Organisation des interfaces FO et BO

### Front Office

Navigation depuis le centre IA et les modules concernés :

- Équipements : formulaire de besoin, filtres, comparateur, score et critères ;
- Réservations : calendrier, disponibilité, options équipement/événement, confirmation séparée ;
- Réclamations : éditeur, analyse, champs manquants, aperçu et bouton d'ouverture du formulaire ;
- Événements : préférences, cartes recommandées, capacité restante et inscription séparée ;
- Blog : lecteur, résumé, explication, quiz, correction et score ;
- Transversal : notifications, préférences de langue, feedback et activité personnelle.

### Back Office

- Equipment Demand Radar : KPI, tendances, seuils et détail source ;
- Booking Insights : taux, séries, annulations et occupation ;
- Complaint Intelligence : filtres, récurrences, urgences, délais et réponses proposées ;
- Event Blueprint : formulaire, aperçu, checklist et sauvegarde brouillon ;
- Editorial Copilot : audit, propositions par champ et application choisie ;
- Dashboard : synthèse globale avec données réellement disponibles ;
- Feedback : qualité par feature, période et catégorie ;
- notifications : alertes stock/réclamations avec action manuelle.

Chaque écran doit prévoir chargement, erreur OpenRouter, résultat vide, données insuffisantes, aperçu avant action et accessibilité clavier. Les graphiques doivent être alimentés par Laravel, jamais remplis par l'IA.

## 8. Plan par module, dépendances et tests

| Étape | Dépendances | Tests minimaux |
| --- | --- | --- |
| Noyau IA | provider existant, config, validation | erreurs HTTP, timeout, JSON, schema, cooldown, logs sans secret |
| Équipements | `Equipement`, `Reservation`, éventuel stock | score, budget, IDs, propriété, stock, explicabilité |
| Réservations | `ReservationService`, `ParticipationService`, transactions | chevauchement, capacité, concurrence, double action |
| Réclamations | `Reclamation`, historique, policies | propriété, classification, champs, statut inchangé, réponse brouillon |
| Événements | `Evenement`, `Participation` | événement publié, capacité, dates, doublon, blueprint non publié |
| Blog | `Article`, commentaires | contenu public, quiz corrigé côté Laravel, score, SEO sans publication |
| Recherche | policies et requêtes fédérées | isolation privée, permissions, filtres, résultat réel |
| Notifications | préférences, queue éventuelle | déduplication, lecture, priorité, opt-out, langue |
| Dashboard | query objects des cinq modules | chiffres réels, dates, zéro donnée, non-divulgation |
| Langues | traductions et préférence utilisateur | FR/EN/AR, RTL, erreurs, dates, texte mixte |
| Feedback | `ai_feature_runs`, ownership | note, commentaire, exécution, confidentialité, agrégats BO |

Validation globale : PHPUnit, Pint, routes, cache/compilation Blade, build Vite, migrations sur base isolée, tests de permissions et test OpenRouter réel uniquement avec autorisation et quota.

## 9. Risques de régression

| Risque | Niveau | Cause | Réduction |
| --- | --- | --- | --- |
| Modification involontaire d'une réservation | Critique | IA branchée directement au métier | service de confirmation séparé, transaction, verrouillage, test de concurrence |
| Fuite de données privées | Critique | recherche ou contexte trop large | policies, query objects par audience, tests inter-utilisateurs |
| Faux prix/disponibilité | Critique | confiance excessive dans le modèle | valeurs calculées et références rechargées côté Laravel |
| Double réservation/participation | Critique | race condition | revalidation transactionnelle et index/contrainte adaptés |
| Stock incohérent | Élevé | plusieurs sources de quantité | une source de vérité, mouvements append-only, verrouillage |
| Régression OpenRouter | Élevé | remplacement du service existant | façade de compatibilité et tests actuels conservés |
| Coût/rate limit | Élevé | dashboards ou notifications trop bavards | agrégation locale, cache, throttle, queue et cooldown |
| Publication non autorisée | Élevé | application automatique d'une proposition | statut brouillon, confirmation admin et tests |
| Régression des vues | Moyen | nouveaux écrans FO/BO et RTL | composants isolés, compilation Blade, contrôle responsive |
| Traduction incorrecte de données métier | Moyen | traduction automatique de noms/prix | traduire uniquement les libellés, conserver les valeurs sources |
| Migration incompatible MySQL | Élevé | schéma réel non confirmé | migration additive, index vérifiés, backup et environnement isolé |
| Score trompeur | Moyen | formule non documentée | formule versionnée, critères visibles, confiance bornée |

## 10. Ordre recommandé après approbation

1. Valider ce document et confirmer le besoin réel de stock multi-quantité.
2. Figer les contrats JSON, formules de score, policies et événements métier.
3. Étendre le noyau IA sans casser `OpenRouterService` ni les conversations existantes.
4. Implémenter Équipements et Réservations, puis leurs tests spécialisés.
5. Implémenter Réclamations, Événements et Blog.
6. Ajouter les fonctionnalités transversales et les tables seulement lorsqu'un cas d'usage est implémenté.
7. Ajouter FR/EN/AR et les écrans spécialisés.
8. Rejouer la validation complète et actualiser la documentation d'installation.

## 11. Décision attendue

Cette conception est prête à être discutée et validée. Elle ne crée aucune migration et ne prescrit pas l'ajout automatique de toutes les tables proposées. Après approbation, chaque migration et chaque module devront être traités séparément, testés et documentés avant de passer au suivant.

## 12. Révision normative demandée

Cette section précise les règles qui devront être figées avant toute implémentation. Elle complète les propositions précédentes sans les transformer en migrations ou en code.

### 12.1 Vérification du socle existant

Les règles ci-dessous tiennent compte des structures effectivement auditées :

- `Equipement` possède actuellement `categorie_id`, `user_id`, `nom`, `marque`, `description`, `puissance_watts`, `capacite_wh`, `prix_jour`, `caution`, `etat`, `ville`, `disponible` et `image` ;
- `Reservation` possède un `equipement_id`, un `user_id`, des dates, `nb_jours`, `montant_total`, `statut` et `message`, avec les statuts actifs `en_attente`, `confirmee` et `en_cours` ;
- `Evenement` possède type, lieu, ville, dates, `capacite`, `prix` et statut ; les participations actives déterminent les places restantes ;
- `Reclamation` possède catégorie, priorité, statut, réponses et historique ;
- `Article` possède titre, slug, résumé, contenu, catégorie, statut et date de publication ;
- `User` porte le rôle applicatif `user` ou `admin`, les préférences personnelles n'existent pas encore ;
- `OpenRouterService`, `AiContextBuilder` et `AiConversationService` existent déjà et doivent rester les points de compatibilité ;
- les tests existants utilisent principalement SQLite mémoire et simulent le fournisseur IA.

Conséquence : la disponibilité booléenne actuelle reste la source de vérité pour un équipement unique. Une structure de stock multi-exemplaires ne sera étudiée qu'après confirmation que le métier autorise plusieurs unités par équipement.

## 13. Scores, versionnement et explicabilité

### 13.1 Règles communes des scores

Les scores sont calculés avant l'appel IA. La version initiale est `score-v1`. Chaque score est compris entre 0 et 100, est arrondi à deux décimales pour le stockage et expose ses composantes. Les critères absents ne valent jamais zéro par défaut : ils sont retirés du dénominateur, puis les poids disponibles sont renormalisés.

Pour une liste de critères disponibles $A$ et de poids $w_i$, le score est :

$$S = 100 \times \frac{\sum_{i \in A} w_i c_i}{\sum_{i \in A} w_i}$$

où chaque $c_i$ est dans $[0,1]$. Un filtre métier élimine auparavant toute option impossible ; un score élevé ne rend donc jamais une option indisponible éligible.

La confiance est distincte du score :

$$C = 100 \times (0.7 \times couverture + 0.3 \times qualité)$$

`couverture` est la proportion des critères demandés effectivement renseignés ; `qualité` est la proportion des données utilisées présentes, valides et datées dans les sources locales. Le résultat est plafonné à 100 et doit afficher les critères manquants. Une confiance faible ne bloque pas une recommandation admissible, mais impose un avertissement.

### 13.2 Smart Energy Matcher : `energy-v1`

Filtres bloquants Laravel : équipement disponible, prix dans le budget, propriétaire différent de l'utilisateur, catégorie compatible lorsqu'elle est demandée.

| Critère | Poids | Calcul local |
| --- | ---: | --- |
| Adéquation au besoin | 35 | correspondance catégorie/usage déterminée par les champs et une classification IA limitée aux termes fournis |
| Budget | 25 | `max(0, 1 - max(0, prix_jour - budget_unitaire) / budget_unitaire)` |
| Puissance/capacité | 20 | min entre couverture de la puissance demandée et couverture de la capacité demandée |
| Disponibilité | 10 | 1 si le filtre bloquant est satisfait, 0 sinon ; composante normalement constante après filtrage |
| Préférence/ville/état | 10 | moyenne des préférences effectivement renseignées |

Le calcul conserve `score_version=energy-v1`, les valeurs sources, les composantes, les critères absents et la confiance. L'IA reçoit les candidats et ces composantes, puis explique le résultat ; elle ne reçoit pas le droit de recalculer le score.

### 13.3 Smart Booking Optimizer : `booking-v1`

Filtres bloquants Laravel : dates valides, équipement non possédé par l'utilisateur, prix total dans le budget, aucun chevauchement avec un statut actif, événement planifié/futur, capacité disponible et absence de participation existante.

| Critère | Poids | Calcul local |
| --- | ---: | --- |
| Respect des dates | 35 | 1 si l'option couvre exactement la période demandée ; sinon ratio de couverture uniquement pour une suggestion alternative non confirmable |
| Budget | 25 | `min(1, budget_total / montant_total)` lorsque le montant est positif |
| Adéquation du besoin | 20 | correspondance entre catégorie/caractéristiques ou type d'événement et critères saisis |
| Distance/ville | 10 | 1 même ville, 0.5 ville inconnue, 0 autre ville si une ville est demandée |
| Disponibilité restante | 10 | équipement libre : 1 ; événement : places_restantes / capacité, borné à 1 |

Le score ne permet jamais de confirmer une option. Au clic utilisateur, Laravel refait les mêmes contrôles dans une transaction et verrouille la ligne pertinente avant création.

### 13.4 Event Matchmaker : `event-v1`

Seuls les événements `planifie`, futurs, non complets et accessibles publiquement sont candidats.

| Critère | Poids | Calcul local |
| --- | ---: | --- |
| Centres d'intérêt/type | 40 | proportion des intérêts ou types demandés présents dans l'événement |
| Type préféré | 20 | 1 si égal, 0.5 si non renseigné, 0 sinon |
| Date/période | 15 | 1 dans la période, 0.5 si proche, 0 hors contrainte stricte |
| Localisation | 15 | même ville 1, ville inconnue 0.5, autre ville 0 |
| Places restantes | 10 | `places_restantes / capacite`, borné à 1 |

L'historique de participations ne doit être utilisé que pour l'utilisateur courant et seulement sous forme d'agrégats non identifiants. Si aucun intérêt, date ou lieu n'est fourni, ces critères sont absents et les poids sont renormalisés.

### 13.5 Explicabilité affichée

Chaque carte recommandée expose : score, confiance, `score_version`, critères utilisés, critères manquants, données sources, raison courte générée par IA, limites et date de calcul. La phrase IA ne peut pas contredire les composantes calculées ; toute référence inconnue est supprimée côté serveur.

## 14. Gestion du stock, sans hypothèse imposée

### 14.1 Cas actuel : équipement unique

Le modèle actuel représente un équipement comme une ligne unique et `disponible` comme un booléen. La règle proposée est donc :

- source de vérité : `equipements.disponible` plus les réservations actives ;
- réservation active : statut dans `Reservation::STATUTS_ACTIFS` et période chevauchante ;
- aucune quantité, mouvement ou table de stock ajoutée ;
- lors d'une réservation, la disponibilité est recalculée sous transaction et la réservation est créée après verrouillage de l'équipement ou d'une ligne de contrôle ;
- annulation/fin de réservation ne doit pas rendre disponible un équipement si une autre réservation active existe ;
- le Demand Radar analyse la demande à partir des réservations existantes, sans parler de rupture de stock multi-unités.

### 14.2 Cas futur : plusieurs exemplaires

Ce cas n'est activé que si le métier confirme qu'un même catalogue peut représenter plusieurs unités réellement louables. La source de vérité deviendrait alors un registre de stock, et non une addition approximative de `disponible`.

- `quantity_total` est la quantité physiquement détenue ;
- `quantity_reserved` est la somme des allocations réservées dans les statuts actifs ;
- `quantity_available = quantity_total - quantity_reserved`, jamais saisie indépendamment ;
- toute entrée, sortie, correction ou perte est un mouvement append-only avec utilisateur, motif, date et référence ;
- une réservation multi-quantité doit posséder une allocation explicite ou un verrouillage équivalent ;
- seuil minimum et alertes sont calculés par Laravel ;
- une transaction verrouille le stock puis vérifie `quantity_available >= quantité_demandée` avant allocation ;
- une annulation libère l'allocation dans la même transaction ;
- l'IA ne peut ni créer de mouvement ni modifier une quantité.

Avant toute table `equipment_stocks`, il faudra vérifier les contrôleurs et formulaires d'équipements existants, confirmer la cardinalité métier et décider si l'équipement doit devenir un modèle de produit avec unités séparées. Aucun de ces choix n'est imposé par la présente conception.

## 15. Quiz et confidentialité du corrigé

Le fournisseur peut générer des questions et des propositions de réponses, mais `answer_key` reste exclusivement côté serveur :

1. Laravel transmet le contenu publié et demande un JSON de questions sans données personnelles.
2. Laravel valide le nombre de questions, les choix, la longueur, l'unicité des identifiants et la cohérence du corrigé reçu.
3. Le corrigé est conservé dans une zone serveur protégée, idéalement chiffrée ou sous forme de représentation non exposée ; il n'est jamais injecté dans la vue, l'API, le HTML, le journal ou le contexte d'une tentative.
4. Le navigateur reçoit uniquement `question_id`, énoncé et choix, jamais `answer_key`.
5. À la soumission, Laravel vérifie la version publiée du quiz, normalise les réponses, compare au corrigé serveur et calcule le score.
6. Une tentative est immuable après correction ; une nouvelle soumission crée une nouvelle tentative selon la politique choisie.
7. La correction reste possible même si OpenRouter est indisponible, car elle ne dépend pas d'un nouvel appel.

Le quiz est versionné par article, locale et numéro de version. Une nouvelle version ne réécrit pas les tentatives historiques. Une réponse malformée invalide la génération entière et ne crée aucun quiz publiable.

## 16. Permissions, propriété et données OpenRouter

| Fonctionnalité | FO autorisé | BO autorisé | Règle de propriété |
| --- | --- | --- | --- |
| Energy Matcher | utilisateur vérifié | admin pour agrégats | catalogue public, propriétaire de l'équipement jamais exposé inutilement |
| Booking Optimizer | utilisateur vérifié | admin pour analyse | réservations personnelles en FO ; événements publics seulement |
| Complaint Doctor | utilisateur vérifié | admin pour son propre outil | uniquement réclamations du compte courant |
| Event Matchmaker | utilisateur vérifié | admin pour analyse | événements publiés et participants agrégés |
| Article Intelligence | utilisateur vérifié | admin | articles publiés en FO ; brouillons admin uniquement |
| Demand/Insights/Complaint Intelligence | aucun accès BO pour membre | admin | agrégats sans noms, e-mails, téléphone ni contenu privé inutile |
| Blueprint/Editorial Copilot | aucun accès public | admin | brouillon appartenant à l'admin/organisation, publication manuelle |
| Search/Dashboard | résultats publics et ressources personnelles | admin selon rôle | chaque source applique sa policy avant fusion |
| Notifications/Feedback | ses notifications et feedbacks | agrégats admin | un utilisateur ne lit/modifie que ses lignes |

Le contexte OpenRouter peut contenir IDs techniques nécessaires, valeurs métier minimisées, dates, catégories, caractéristiques et texte explicitement soumis. Il ne doit pas contenir mots de passe, tokens, données de paiement, e-mails ou téléphones non nécessaires, identité d'un autre utilisateur, pièce jointe brute ou corpus privé complet.

Un hash d'entrée peut servir à la déduplication, mais il ne doit pas permettre de reconstruire le contenu. Les résultats privés sont rechargés après génération avec la policy de l'utilisateur courant. Toute réponse contenant un ID non candidat, une autre ressource ou une instruction d'action est rejetée.

## 17. Notifications : règles d'envoi

### Catégories et canaux

| Catégorie | Exemple | Canaux par défaut |
| --- | --- | --- |
| `reservation` | rappel, changement de statut, annulation | centre, e-mail optionnel |
| `event` | rappel, changement, suggestion | centre, e-mail optionnel |
| `claim` | réponse, urgence, changement de statut | centre, e-mail selon préférence |
| `equipment` | recommandation ou changement pertinent | centre uniquement |
| `stock` | seuil faible pour admin | centre, e-mail admin optionnel |
| `system` | sécurité et vérification | e-mail système obligatoire selon Laravel |

Les préférences portent sur catégorie, canal, langue, fréquence (`instant`, `daily_digest`, `weekly_digest`) et activation. Les alertes de sécurité ne sont pas désactivables par une préférence IA.

Chaque événement produit une `dedupe_key` déterministe, par exemple `reservation:{id}:reminder:{date}`. Une contrainte ou recherche atomique empêche le doublon. Les notifications sont mises en queue après commit (`afterCommit`) ; l'échec d'envoi conserve l'état `failed` et ne modifie pas la donnée métier.

Statuts recommandés : `pending`, `queued`, `sent`, `read`, `failed`, `expired`, `suppressed`. Les rappels sont planifiés par une commande Laravel ; l'IA ne décide ni de la condition ni du destinataire. Elle peut seulement proposer une formulation validée et limitée.

## 18. Global Search

### Ressources par rôle

- visiteur : équipements disponibles, événements publiés, articles publiés ;
- utilisateur vérifié : mêmes ressources publiques, ses réservations, ses réclamations et ses participations ;
- admin : ressources publiques et agrégats/données BO autorisés par les policies, jamais un accès implicite à tout contenu privé via l'IA.

Filtres obligatoires : statut public, disponibilité, propriétaire courant pour les ressources privées, date/période, catégorie, ville, pagination, limite de résultats et type de ressource. La recherche ne doit jamais utiliser une concaténation non contrôlée ; les scopes Eloquent et paramètres liés restent obligatoires.

Le moteur exécute d'abord les requêtes locales. OpenRouter peut corriger l'intention ou fournir des synonymes dans une liste bornée. Si OpenRouter est indisponible, la recherche locale exacte et les filtres continuent de fonctionner avec un indicateur `semantic_search_unavailable`; aucun résultat supplémentaire n'est inventé.

## 19. AI Dashboard et KPI

Tous les KPI et séries sont calculés par Laravel avec les tables existantes ou, après approbation, les tables de stock. Les périodes acceptées sont `7d`, `30d`, `90d`, `12m` et une plage validée ; les comparaisons utilisent la période précédente de même durée.

### Front Office

| KPI | Formule/source |
| --- | --- |
| Réservations personnelles | `count(reservations where user_id=current)` |
| Réservations actives | count des statuts actifs du compte |
| Taux d'annulation personnel | `annulees / total`, nul si total 0 |
| Participations à venir | participations du compte sur événements futurs planifiés |
| Réclamations ouvertes | réclamations du compte dont statut non résolu/fermé |
| Notifications non lues | count `read_at is null` du compte |
| Articles consultés | source existante disponible ; sinon ne pas afficher de chiffre |

Le FO ne montre jamais les agrégats d'un autre utilisateur. Les recommandations affichent uniquement les résultats calculés à partir des données accessibles.

### Back Office

| KPI | Formule/source |
| --- | --- |
| Réservations totales | count sur la période dans `reservations` |
| Taux d'annulation | `count(statut=annulee) / count(total)` |
| Taux d'occupation équipement | jours couverts par réservations actives ou terminées / jours observés, documenter le dénominateur |
| Événements populaires | somme des participations actives par événement |
| Taux de remplissage | `participants_actifs / capacite`, borné à 100 % |
| Réclamations ouvertes/urgentes | counts groupés sur `reclamations` |
| Délai moyen de traitement | moyenne entre création et résolution, uniquement dossiers résolus avec dates disponibles |
| Articles publiés | count `articles` publiés à date de publication échue |
| Disponibilité équipement | count `equipements.disponible=true` ; ne pas appeler cela stock |

Si une source n'existe pas, le KPI est `non disponible`, jamais zéro synthétique. Les graphiques exposent aussi la requête/source et la période dans la réponse serveur. L'IA ne reçoit que ces agrégats et ne peut pas modifier les chiffres.

## 20. Multilingue FR/EN/AR

La langue est déterminée par préférence utilisateur, puis session, puis configuration par défaut `fr`. Les clés de traduction couvrent navigation, formulaires, validations, erreurs provider, notifications, états vides et libellés de graphiques.

- Le prompt système reçoit `response_locale` et une instruction stricte de répondre dans cette langue.
- Le contenu métier, noms propres, prix, IDs, villes et titres d'articles ne sont pas traduits automatiquement sauf demande explicite.
- Les réponses structurées portent une locale et sont validées avec des limites identiques quelle que soit la langue.
- Les dates sont formatées par Laravel avec la locale et le fuseau `Africa/Tunis`; les nombres et montants utilisent une présentation locale sans modifier la valeur source.
- L'arabe active `dir=rtl`, les attributs `lang=ar`, l'alignement et l'ordre visuel adaptés. Les codes, unités et IDs restent protégés par une direction isolée lorsque nécessaire.
- Les tests couvrent textes mixtes, nombres, dates, validation, tableaux, navigation clavier et bascule sans fuite de contenu.

## 21. Historique IA, erreurs et feedback

### Statuts d'exécution

`created`, `context_built`, `sent`, `validated`, `completed`, `malformed`, `provider_error`, `rate_limited`, `timeout`, `rejected`, `cancelled`.

Une exécution possède un identifiant, feature, audience, locale, version de prompt, modèle, statut, durée, code d'erreur borné, hash de contexte et références métier minimales. Les réponses conversationnelles restent dans les tables existantes chiffrées ; les exécutions structurées ne doivent pas dupliquer automatiquement le prompt complet.

### Conservation

- métadonnées d'exécution : 90 jours par défaut, configurable ;
- feedback et agrégats anonymisés : 12 mois, selon politique de l'organisation ;
- conversations et messages : durée définie par la politique utilisateur/administrateur, suppression propriétaire conservée ;
- tokens, clés, prompts complets, pièces jointes et données personnelles inutiles : jamais conservés dans les logs techniques.

Le hash de contexte sert à détecter les doublons, pas à reconstituer les entrées. Les logs contiennent type d'erreur, statut HTTP, durée et modèle, jamais corps fournisseur ni secret. Un feedback doit référencer `ai_feature_run_id` lorsqu'il existe ; il ne doit pas copier la réponse complète. Le propriétaire peut consulter et supprimer ses feedbacks selon la policy ; le BO voit des agrégats et des signalements bornés.

## 22. Contrats JSON OpenRouter

Le provider demande une réponse JSON stricte sans Markdown. Chaque contrat possède une version et des règles Laravel équivalentes. Les exemples ci-dessous sont minimaux ; ils ne constituent pas une réponse de secours.

### Recommandations

```json
{
  "recommendations": [
    {"resource_id": 12, "reason": "Capacité adaptée au besoin fourni.", "benefits": ["..."], "limits": ["..."]}
  ],
  "summary": "Synthèse fondée sur les candidats transmis.",
  "missing_criteria": ["surface"]
}
```

Validation : `recommendations` tableau maximum 3 ; `resource_id` entier distinct et présent dans les candidats Laravel ; textes bornés ; aucune clé inconnue acceptée dans le DTO final. Le score, la confiance et le prix viennent de Laravel, même si le modèle tente de les fournir.

### Analyse de réclamation

```json
{
  "category": "equipement",
  "sentiment": "negatif",
  "urgency": "haute",
  "summary": "Résumé court.",
  "rephrased_text": "Reformulation proposée.",
  "missing_information": ["date de l'incident"]
}
```

Validation : enums serveur pour catégorie/sentiment/urgence, longueurs maximales, tableau de champs manquants borné et texte sans HTML actif. La priorité officielle et le statut restent inchangés jusqu'à décision humaine.

### Blueprint événement

```json
{
  "titles": ["Titre proposé"],
  "description": "Description sans fait externe.",
  "objectives": ["Objectif"],
  "schedule": [{"label": "Accueil", "minutes": 20}],
  "activities": ["Activité"],
  "checklist": ["Vérifier la capacité"]
}
```

Validation : tableaux bornés, `minutes` entier positif dans une limite, texte sans date/lieu/prix inventé, statut brouillon obligatoire.

### Article et quiz

```json
{
  "summary": "Résumé.",
  "key_points": ["Idée réellement présente dans l'article."],
  "questions": [
    {"id": "q1", "question": "...", "choices": ["A", "B", "C"], "answer": "B"}
  ]
}
```

`answer` peut être reçu par le serveur pour validation puis devenir `answer_key`, mais n'est jamais inclus dans la réponse navigateur, le HTML, le journal ou le contexte d'une tentative. Le serveur vérifie que la valeur appartient aux choix et rejette toute question ambiguë. La version du quiz est immuable après publication.

### Erreurs provider

- JSON vide, Markdown, champs inconnus, type incorrect ou ID invalide : statut `malformed` ; au plus une reprise bornée pour sortie invalide ; sinon échec explicite.
- Timeout/cURL : statut `timeout`, message traduit, aucun résultat de substitution et aucune reprise automatique illimitée.
- HTTP 429 : statut `rate_limited`, lecture de `Retry-After` valide et cooldown ; aucun retry immédiat.
- HTTP 400/401/402/403/5xx : mapping vers erreur utilisateur sans corps sensible ; aucun fallback fictif.
- fournisseur indisponible : les fonctions locales non IA restent utilisables ; l'écran distingue données locales et enrichissement IA indisponible.

## 23. Tests détaillés

### Tests communs

- authentification, e-mail vérifié, rôle et policy par endpoint ;
- contexte sans ressource, ressource supprimée ou non publiée ;
- ID inventé, champ inconnu, JSON vide, JSON tronqué, mauvais type et réponse trop longue ;
- erreurs 400, 401, 402, 403, 429, 500, 503, timeout et `Retry-After` ;
- absence de secret, prompt complet et données privées dans les logs ;
- langue FR/EN/AR, textes mixtes et RTL ;
- absence de faux résultat en cas d'échec provider.

### Tests par fonctionnalité

| Fonction | Scénarios obligatoires |
| --- | --- |
| Energy Matcher | score avec tous critères, critères manquants renormalisés, budget, équipement indisponible, ID inventé, explicabilité |
| Demand Radar | zéro réservation, seuil non applicable au stock unique, agrégats réels, admin seulement, aucune mutation |
| Booking Optimizer | chevauchement, équipement propriétaire, événement complet, double participation, revalidation après affichage, confirmation explicite |
| Booking Insights | statuts, taux avec dénominateur nul, périodes, comparaison, aucune identité dans contexte |
| Complaint Doctor | propriété, texte vide, catégorie invalide, PII minimisée, brouillon modifiable, aucune soumission |
| Complaint Intelligence | agrégats sans identité, urgence, délai sans date de résolution, réponse proposée non envoyée |
| Event Matchmaker | événement brouillon/annulé/passé, capacité, préférences absentes, score versionné |
| Blueprint | champs limites, contenu injecté, brouillon non publié, édition avant enregistrement |
| Article Intelligence | article brouillon interdit, quiz publié, corrigé non exposé, score serveur, nouvelle version |
| Editorial Copilot | métriques réelles, article publié protégé, sélection champ par champ, aucune publication automatique |
| Global Search | visiteur, membre, admin, ressource privée d'un autre compte, IA indisponible, filtres/pagination |
| Notifications | opt-out, fréquence, doublon, after-commit, échec queue, lecture, expiration, langue |
| Dashboard | FO/BO, périodes, zéro donnée, KPI recalculés, aucune valeur issue de l'IA |
| Multilingue | locale préférée, fallback, erreurs, prompts, dates, RTL, texte métier non traduit |
| Explainable AI | formule, poids, critères manquants, confiance, provenance et version |
| Feedback | propriétaire, exécution absente, note bornée, suppression, agrégat admin sans commentaire privé |

Les tests de confirmation utilisent une deuxième requête ou modification concurrente simulée entre recommandation et confirmation afin de prouver que Laravel revalide l'état courant.

## 24. Implémentation progressive par lots

### Lot 0 - Contrats et compatibilité

**Prérequis :** conception approuvée, environnement de test, aucune modification MySQL requise.  
**Contenu :** DTO, versions de scores, mapping d'erreurs, registre de prompts, compatibilité avec `OpenRouterService`.  
**Acceptation :** tests actuels toujours verts ; contrats invalides rejetés ; aucun appel externe dans la suite automatisée.

### Lot 1 - Scores et recommandations équipements/réservations

**Prérequis :** Lot 0, modèles existants confirmés.  
**Contenu :** calculateurs `energy-v1` et `booking-v1`, explicabilité, revalidation de confirmation.  
**Acceptation :** scores reproductibles, critères manquants visibles, aucune réservation automatique, conflits couverts.

### Lot 2 - Insights sans stock multi-quantité

**Prérequis :** Lot 1, validation du cas équipement unique.  
**Contenu :** KPI réservation, demande catalogue, événements et réclamations à partir des tables existantes.  
**Acceptation :** aucun terme de rupture/quantité affiché sans source ; dashboard vide correct ; données BO sans identité.

### Lot 3 - Réclamations, événements et éditorial

**Prérequis :** Lot 0 ; Lot 1 recommandé pour le socle d'explicabilité.  
**Contenu :** Doctor, Intelligence, Matchmaker, Blueprint et Copilot en aperçu éditable.  
**Acceptation :** aucun statut/publication automatique ; policies FO/BO ; tests de contenu malveillant et de limites.

### Lot 4 - Quiz versionné

**Prérequis :** articles publiés, contrat quiz approuvé, stockage serveur protégé.  
**Contenu :** définition, corrigé privé, tentative immuable et correction Laravel.  
**Acceptation :** `answer_key` absent du navigateur, score identique sans OpenRouter, historique de version cohérent.

### Lot 5 - Transversal local

**Prérequis :** Lots 1 à 3 pour disposer d'événements métier réels.  
**Contenu :** recherche locale autorisée, notifications idempotentes, dashboard FO/BO, préférences.  
**Acceptation :** fonctionnement dégradé sans IA, aucune fuite inter-utilisateur, doublons bloqués, KPI Laravel.

### Lot 6 - Historique et feedback IA

**Prérequis :** contrats du Lot 0 et politique de conservation approuvée.  
**Contenu :** exécutions bornées, feedback référencé, agrégats BO et purge.  
**Acceptation :** logs sans prompt/secret/PII inutile, statuts complets, conservation testée, ownership vérifié.

### Lot 7 - Multilingue et RTL

**Prérequis :** interfaces stabilisées et clés de traduction inventoriées.  
**Contenu :** FR/EN/AR, prompts, erreurs, notifications, dates, RTL.  
**Acceptation :** parcours complet dans les trois langues, absence de débordement RTL, valeurs métier intactes.

### Lot 8 - Stock multi-exemplaires conditionnel

**Prérequis :** décision métier écrite confirmant plusieurs unités, modèle de cardinalité et stratégie de migration approuvés.  
**Contenu :** uniquement les tables nécessaires, ledger de mouvements et allocations transactionnelles.  
**Acceptation :** invariants de quantités, verrouillage concurrent, audit des mouvements et compatibilité avec l'équipement unique.

Le passage d'un lot au suivant exige : tests du lot verts, tests de non-régression verts, validation des permissions, documentation mise à jour et absence de migration destructive. Une erreur provider ou un résultat live non disponible ne peut pas être masqué par des données fictives.

## 25. État d'implémentation après les lots réalisés

**Mise à jour :** 4 novembre 2026. Cet état décrit le code réellement intégré et testé ; il ne transforme pas les éléments encore partiels en fonctionnalités terminées.

| Fonctionnalité | État réel |
| --- | --- |
| Smart Energy Matcher FO | **Partielle intégrée** : filtres réels, score serveur, confiance et préférences FO ; explications IA dédiées encore limitées au contrat existant |
| Equipment Demand Radar BO | **Partielle intégrée** : agrégats réels et dashboard ; radar historique, prévision et stock multi-exemplaires non activés |
| Smart Booking Optimizer FO | **Partielle intégrée** : conflits/budget/dates réels et classement ; confirmation transactionnelle dédiée et parcours événement complet restent à renforcer |
| Booking Insights BO | **Partielle intégrée** : KPI de base et agrégats ; graphiques temporels dédiés non finalisés |
| Complaint Doctor FO | **Partielle intégrée** via Claim Companion et brouillons ; écran analytique sentiment/urgence dédié à compléter |
| Complaint Intelligence BO | **Partielle intégrée** via agrégats BO ; récurrences et délais dédiés non finalisés |
| Event Matchmaker FO | **Partielle intégrée** : événements publics, capacité et score dans le contexte ; interface de cartes enrichie à compléter |
| Event Blueprint Generator BO | **Partielle intégrée** via propositions éditables ; stockage de blueprint dédié non activé |
| Article Intelligence FO | **Intégrée partiellement et quiz livré** : article publié, génération, corrigé serveur et score Laravel ; recommandations similaires à compléter |
| SEO & Editorial Copilot BO | **Partielle intégrée** via propositions titre/résumé/mots-clés ; audit SEO complet non finalisé |
| AI Global Search | **Intégrée en mode local sécurisé** avec fallback indépendant d'OpenRouter et isolation FO ; classement sémantique optionnel reste à ajouter |
| AI Notifications | **Noyau intégré** : préférences, déduplication, boîte de réception et lecture ; déclencheurs métier planifiés à raccorder aux événements Laravel |
| AI Dashboard | **Partielle intégrée** : KPI BO réels et activité existante ; dashboard FO dédié et graphiques avancés à compléter |
| FR/EN/AR | **Socle intégré** : session, traductions de base et RTL ; traduction exhaustive des écrans et préférence persistée restent à compléter |
| Explainable AI | **Partielle intégrée** : scores, composantes, critères manquants et confiance pour les classements ; affichage homogène de toutes les recommandations à compléter |
| AI Feedback | **Intégrée** : table, endpoint protégé, formulaire conversation, vue BO et tests de propriété |

La traçabilité `ai_feature_runs` est intégrée aux conversations IA avec statuts, durée, modèle, locale, hash et codes d'erreur bornés. Les formulaires historiques de propositions utilisent encore directement le provider compatible et devront être raccordés à cette traçabilité dans un lot ultérieur.

### Compléments livrés dans la passe finale

- le dashboard FO affiche les KPI personnels réels ;
- la page BO `/admin/assistants/insights` expose les tendances mensuelles et la demande par catégorie sans stock fictif ;
- Global Search classe désormais localement les résultats par pertinence déterministe ;
- le feedback FO peut être consulté et supprimé par son propriétaire ;
- les notifications sont déclenchées après création d'une réservation ou inscription à un événement, avec déduplication ;
- les migrations et tests finaux sont passés avec 78 tests et 454 assertions.
