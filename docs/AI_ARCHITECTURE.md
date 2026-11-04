# Architecture IA NEXORA

## Répartition des responsabilités

- Les contrôleurs valident les entrées, l’audience FO/BO et les permissions.
- Les services métier Laravel interrogent les modèles et calculent disponibilité, dates, tarifs, scores, agrégats et corrigés.
- `AiContextBuilder` construit un contexte limité selon le module et le rôle.
- `OpenRouterService` centralise la requête HTTP, la clé côté serveur, les délais, erreurs, limitation/cooldown et la validation de sortie JSON.
- Les vues affichent des suggestions échappées. Les opérations de sauvegarde, réservation, publication et changement de statut restent derrière une action explicite Laravel.

## Parcours spécialisés réutilisés

| Feature | Implémentation principale |
| --- | --- |
| SolarMatch et Booking Optimizer | `Front\AiRecommendationController`, `RecommendationScorer`, modèles Equipement/Reservation |
| Equipment Demand Radar et Booking Insights | `AiConversationController`, `AiConversationService`, `AiContextBuilder` et agrégats Eloquent |
| Complaint Doctor et Complaint Intelligence | `Front\ReclamationController`, `ComplaintIntelligenceController`, `ComplaintIntelligenceService`, `ReclamationHistory` |
| Event Matchmaker et Event Blueprint | recommandations événementielles, `EventBlueprintController`, `EventBlueprintService` et brouillon propriétaire |
| GreenMind / Article Intelligence et quiz | `AiConversationController`, `ArticleQuizService`, `ArticleQuizController`; corrigé conservé côté serveur |
| Editorial Copilot | `AiController` et formulaire de prévisualisation/apply administrateur |

## Confidentialité et sécurité

- Pas de clé API dans JavaScript, vues ou dépôt ; valeur lue depuis `OPENROUTER_API_KEY`.
- Les données FO sont bornées à l’utilisateur et aux ressources publiques/personal-owned ; les routes BO sont protégées par rôle.
- Les contextes doivent rester minimaux ; l’assistant conversationnel peut recevoir les messages du membre. Éviter les données sensibles.
- Un identifiant suggéré par un modèle doit être vérifié contre les lignes candidates côté Laravel.
- Une erreur fournisseur ou une sortie invalide ne doit jamais devenir un résultat simulé.
- Les tests utilisent `Http::fake` / `preventStrayRequests` ; aucun appel réel n’est effectué par la suite PHPUnit.

## Configuration

`OPENROUTER_API_KEY`, `OPENROUTER_MODEL` et le timeout configuré par l’application. Le transport réel a été testé une fois avec un contexte générique pendant la préparation ; la disponibilité/qualité des dix parcours dépend encore du fournisseur et du quota au moment de la démonstration.
