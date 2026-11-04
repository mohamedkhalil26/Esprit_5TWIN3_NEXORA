"""Contrôles complémentaires sur les seuls comptes jetables de recette."""
import json
from recette_http import Client, WORK, RESULTS, check, row, save

RESULTS.extend(json.loads((WORK / 'resultats-http.json').read_text(encoding='utf-8')))
registered, member, admin = Client(), Client(), Client()
registered.login('inscription.recette@example.test', 'RecetteFinale2026!')
payload = {'_method': 'PUT', 'current_password': 'RecetteFinale2026!', 'password': 'ProfilRecette2026!', 'password_confirmation': 'ProfilRecette2026!'}
check('Profil : changement réel du mot de passe depuis le profil', registered.submit('/profil/mot-de-passe', payload, '/profil')[0] == 200)
registered.logout()
check('Profil : connexion avec le mot de passe modifié', registered.login('inscription.recette@example.test', 'ProfilRecette2026!')[1].endswith('/'))
check('Suppression du compte lié refusée sans détruire les participations', registered.submit('/profil', {'_method': 'DELETE', 'password': 'ProfilRecette2026!', 'confirmation': 'SUPPRIMER'}, '/profil', True)[0] == 422 and row('select count(*) from users where email=?', ('inscription.recette@example.test',))[0] == 1)
member.login('membre.recette@example.test')
claim = {'sujet': 'Pièce jointe privée en recette', 'description': 'Un document fictif permet de vérifier les droits de téléchargement de cette réclamation.', 'categorie': 'technique', 'priorite': 'moyenne'}
status, _, _ = member.upload('/mes-reclamations', claim, {'piece_jointe': ('recette.pdf', 'application/pdf', b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF')}, '/mes-reclamations/nouvelle')
record = row('select id,piece_jointe from reclamations where sujet=?', (claim['sujet'],))
check('M3 téléversement réel de pièce jointe privée', status == 200 and record and record[1].startswith('reclamations/') and (WORK / 'runtime/app/private' / record[1]).is_file())
check('M3 téléchargement réel autorisé au propriétaire', member.request('/pieces-jointes/' + str(record[0]))[0] == 200)
check('M3 téléchargement réel interdit à un autre membre', registered.request('/pieces-jointes/' + str(record[0]))[0] == 403)
admin.login('admin.recette@example.test')
check('M3 téléchargement réel autorisé à l’administrateur', admin.request('/pieces-jointes/' + str(record[0]))[0] == 200)
article = row('select id,user_id,titre,resume,categorie from articles where id=3')
short_article = {'_method': 'PUT', 'user_id': article[1], 'titre': article[2], 'resume': article[3], 'categorie': article[4], 'statut': 'publie', 'contenu': 'Une description insuffisante pour publier un véritable article de présentation sur le solaire. ' * 2}
check('M5 publication incomplète refusée par le formulaire réel', admin.submit('/admin/articles/' + str(article[0]), short_article, '/admin/articles/' + str(article[0]) + '/edit', True)[0] == 422)
save()
print(str(len(RESULTS)) + ' vérifications HTTP cumulées réussies.', flush=True)
