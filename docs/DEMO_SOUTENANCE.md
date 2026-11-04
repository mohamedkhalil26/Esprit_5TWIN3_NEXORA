# Démonstration NEXORA — soutenance

## Parcours conseillé (12 à 15 minutes)

### 1. Vue d’ensemble

1. Ouvrir l’accueil, montrer les cinq modules : équipements, réservations, réclamations, événements et blog.
2. Se connecter comme membre, ouvrir **NEXORA Intelligence** depuis le menu.
3. Montrer les cinq cartes FO. Chaque carte mène directement à une fonctionnalité du module.
4. Se connecter comme administrateur et ouvrir le même centre : chaque module présente maintenant son entrée BO réservée à l’administration.

Le répertoire `/assistants` est également visible aux visiteurs depuis le bouton **Centre IA** de l’accueil et le menu **Assistants intelligents**. Les parcours membres continuent à demander une connexion; l’accès BO est filtré par rôle côté serveur.

### 2. Dix parcours IA FO/BO

| Module | Front Office — membre | Back Office — administrateur | Résultat à montrer |
| --- | --- | --- | --- |
| Équipements | SolarMatch, `/recommandations/energie` | Equipment Demand Radar, `/admin/assistants/equipements` | Résultats issus du catalogue, filtres et score explicable FO ; indicateurs SQL et recommandations BO. Ouvrir une vraie fiche. |
| Réservations | Smart Booking Optimizer, `/recommandations/reservation` | Booking Insights, `/admin/assistants/locations` | Choisir des dates et un budget ; montrer les candidats disponibles, le total calculé et l’absence de chevauchement. BO analyse statuts et paiements validés. |
| Réclamations | Complaint Doctor, `/mes-reclamations/nouvelle` | Complaint Intelligence Center, `/admin/reclamations-centre` | Saisir un brouillon, lancer l’analyse, accepter ou refuser les suggestions séparément ; ne pas envoyer. BO affiche agrégats, filtres et récurrences réelles. |
| Événements | Event Matchmaker, `/recommandations/evenements` | Event Blueprint, `/admin/assistants/blueprints/create` | Filtrer des événements publics futurs et vérifier les places. BO génère une proposition, la relire puis l’enregistrer en brouillon ; aucune publication automatique. |
| Blog | GreenMind / Article Intelligence depuis un article publié | Editorial Copilot, `/assistants/blog` | Ouvrir un article publié, demander un résumé puis son quiz ; le score est calculé côté serveur. BO relit et modifie les suggestions avant validation. |

Les espaces BO exigent le rôle administrateur côté serveur. Une carte FO ne crée ni réservation, ni réclamation, ni inscription sans l’action explicite prévue dans le module.

### 3. Démontrer une action métier classique

- FO : ouvrir une fiche équipement, préparer une réservation, puis vérifier le total recalculé par Laravel.
- BO : ouvrir une réclamation, consulter son fil et son historique, puis répondre seulement après relecture.
- Événement : enregistrer un blueprint comme brouillon et utiliser le préremplissage ; vérifier le formulaire avant sauvegarde.
- Blog : montrer un article publié et un article brouillon séparément.

### 4. Données de démonstration et comptes

Le `DatabaseSeeder` prévoit les comptes `admin@solarshare.tn` et `client@solarshare.tn`, mot de passe `password`. Ils ne sont utilisables que si un administrateur les a créés sur une base de démonstration isolée. Aucun compte ni donnée n’a été créé pour cette préparation. Ne lancez pas `db:seed` sur une base d’équipe ou de production ; le seeder n’est pas conçu pour être rejoué sur une base qui contient déjà ces comptes.

### 5. Configuration et limites à expliquer

- Les tests utilisent SQLite en mémoire et des réponses OpenRouter simulées. Un appel générique réel à OpenRouter a été validé dans l’environnement de préparation ; cela ne certifie pas la disponibilité du fournisseur pendant toute la soutenance.
- La clé est configurée par `OPENROUTER_API_KEY`; le modèle par `OPENROUTER_MODEL`. Si le fournisseur est indisponible, montrer le message d’erreur et continuer avec les parcours sans IA externe. Aucun faux résultat n’est présenté comme une réponse IA.
- Le mail SMTP est configuré dans l’environnement de préparation, mais la réception d’un email n’a pas été vérifiée. Pour la démonstration du reset et de la vérification, confirmer d’abord l’arrivée réelle du message ou utiliser Mailpit local.
- Les statistiques sans historique sont affichées comme insuffisantes ; ne pas présenter d’estimation comme une mesure réelle.

## Démarrage local

Suivre [INSTALLATION.md](INSTALLATION.md). Utiliser une base de démonstration dédiée. Vérifier le rôle, l’adresse vérifiée et l’état du service mail avant la séance. Garder `.env`, la clé API et les mots de passe hors écran et hors partage.
