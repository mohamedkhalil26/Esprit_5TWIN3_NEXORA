# Emails locaux avec Mailpit

La configuration SMTP locale envoie les notifications à Mailpit sur `127.0.0.1:1025`.
Les emails sont consultables sur <http://127.0.0.1:8025> ; ils ne sont pas transmis à Gmail ou à une autre boîte externe.

Après un redémarrage de Windows, lancer depuis la racine du projet :

```powershell
.\scripts\start-mailpit.ps1
```

Le script démarre Mailpit en arrière-plan, uniquement sur les interfaces locales, et conserve ses messages. Il peut être relancé si Mailpit fonctionne déjà.

Pour tester depuis l'application, demander la réinitialisation du mot de passe d'un compte de test existant, puis ouvrir le nouveau message dans Mailpit. Pour une adresse inconnue, Laravel affiche volontairement le même message de confirmation, mais aucun email n'est envoyé.

Un envoi vers une véritable boîte externe utilise Gmail SMTP : voir [CONFIGURATION_EMAIL_GMAIL.md](CONFIGURATION_EMAIL_GMAIL.md), y compris le passage de Gmail à Mailpit et inversement dans `.env`. Ne jamais publier ses identifiants.
