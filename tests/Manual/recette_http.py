"""Recette HTTP réelle sur l'instance isolée 127.0.0.1:8010, sans mocks IA/email.
Les notifications sont lues dans le journal privé de cette instance, jamais affichées.
"""
import base64
import hashlib
import hmac
import html
import http.cookiejar
import json
from pathlib import Path
import re
import sqlite3
import struct
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[2]
WORK = ROOT / 'storage/app/private/recette-finale'
BASE = 'http://127.0.0.1:8010'
DB = sqlite3.connect(WORK / 'recette.sqlite')
RESULTS = []


def check(label, value):
    RESULTS.append({'verification': label, 'resultat': 'OK' if value else 'ECHEC'})
    print(('OK : ' if value else 'ECHEC : ') + label, flush=True)
    if not value:
        save()
        raise AssertionError(label)


def save():
    (WORK / 'resultats-http.json').write_text(json.dumps(RESULTS, ensure_ascii=False, indent=2), encoding='utf-8')


def row(query, values=()):
    return DB.execute(query, values).fetchone()


class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(self, path, data=None, json_errors=False, referer=None, files=None):
        url = path if path.startswith(BASE) else BASE + path
        headers = {'Accept': 'application/json' if json_errors else 'text/html'}
        if referer:
            headers['Referer'] = BASE + referer
        payload = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
        if files:
            boundary = 'solarshare-recette-boundary'
            payload = b''
            for name, value in data.items():
                payload += ('--' + boundary + '\r\nContent-Disposition: form-data; name="' + name + '"\r\n\r\n' + str(value) + '\r\n').encode()
            for name, (filename, mime, content) in files.items():
                payload += ('--' + boundary + '\r\nContent-Disposition: form-data; name="' + name + '"; filename="' + filename + '"\r\nContent-Type: ' + mime + '\r\n\r\n').encode() + content + b'\r\n'
            payload += ('--' + boundary + '--\r\n').encode()
            headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        req = urllib.request.Request(url, data=payload, headers=headers)
        try:
            response = self.opener.open(req, timeout=40)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.url, response.read().decode('utf-8', errors='replace')

    def submit(self, path, data, form='/', json_errors=False):
        status, _, page = self.request(form)
        token = re.search(r'name="_token" value="([^"]+)"', page)
        if not token:
            raise RuntimeError('Token CSRF introuvable sur ' + form + ' (' + str(status) + ')')
        return self.request(path, dict(data, _token=html.unescape(token.group(1))), json_errors, form)

    def login(self, email, password='Recette2026!'):
        return self.submit('/connexion', {'email': email, 'password': password}, '/connexion')

    def upload(self, path, data, files, form='/profil'):
        page = self.request(form)[2]
        token = re.search(r'name="_token" value="([^"]+)"', page).group(1)
        return self.request(path, dict(data, _token=html.unescape(token)), referer=form, files=files)

    def logout(self):
        return self.submit('/deconnexion', {}, '/profil')


def mail_link(kind):
    log = (WORK / 'runtime/logs/laravel.log').read_text(encoding='utf-8', errors='replace')
    log = log.replace('=\n', '').replace('=\r\n', '').replace('=3D', '=')
    links = re.findall(r'http://127\.0\.0\.1:8010/' + kind + r'/[^\s<>\"]+', log)
    if not links:
        raise RuntimeError('Notification locale introuvable : ' + kind)
    return html.unescape(links[-1]).rstrip(').')


def run():
    member, admin, newcomer = Client(), Client(), Client()
    check('Accueil réel HTTP 200', member.request('/')[0] == 200)
    check('CSRF actif : mutation sans token refusée', member.request('/connexion', {'email': 'membre.recette@example.test', 'password': 'Recette2026!'})[0] == 419)
    check('Connexion membre et session', member.login('membre.recette@example.test')[0] == 200)
    check('Accès administrateur interdit au membre', member.request('/admin')[0] == 403)
    check('Connexion administrateur réelle', admin.login('admin.recette@example.test')[1].endswith('/admin'))

    for area in ['categories', 'equipements', 'reservations', 'paiements', 'reclamations', 'reponses', 'evenements', 'participations', 'articles', 'commentaires']:
        check('Back Office liste et création : ' + area, admin.request('/admin/' + area)[0] == 200 and admin.request('/admin/' + area + '/create')[0] == 200)

    registration = {'name': 'Inscription Recette', 'email': 'inscription.recette@example.test', 'password': 'Recette2026!', 'password_confirmation': 'Recette2026!', 'role': 'admin'}
    check('Inscription réelle sans élévation de rôle', newcomer.submit('/inscription', registration, '/inscription')[0] == 200 and row('select role from users where email=?', (registration['email'],))[0] == 'user')
    check('Compte non vérifié bloqué sur réservation', '/verifier-email' in newcomer.request('/mes-reservations')[1])
    link = mail_link('verifier-email')
    check('Email généré localement et lien signé utilisable', newcomer.request(link)[0] == 200 and row('select email_verified_at from users where email=?', (registration['email'],))[0] is not None)
    check('Profil modifiable par formulaire réel', newcomer.submit('/profil', {'_method': 'PATCH', 'name': 'Profil Recette Modifié', 'email': registration['email'], 'ville': 'Tunis', 'current_password': 'Recette2026!'}, '/profil')[0] == 200 and row('select name from users where email=?', (registration['email'],))[0] == 'Profil Recette Modifié')

    equipment = {'nom': 'Station solaire de démonstration', 'categorie_id': 1, 'user_id': 1, 'description': 'Station de secours pour présenter la réservation solaire.', 'puissance_watts': 750, 'capacite_wh': 1200, 'prix_jour': '15', 'caution': '75', 'etat': 'bon', 'ville': 'Tunis', 'disponible': '1'}
    check('M1 création équipement par formulaire', admin.submit('/admin/equipements', equipment, '/admin/equipements/create')[0] == 200 and row('select count(*) from equipements where nom=?', (equipment['nom'],))[0] == 1)
    eid = row('select id from equipements where nom=?', (equipment['nom'],))[0]
    equipment.update(nom='Station solaire de démonstration modifiée', _method='PUT')
    check('M1 modification équipement', admin.submit('/admin/equipements/' + str(eid), equipment, '/admin/equipements/' + str(eid) + '/edit')[0] == 200 and row('select nom from equipements where id=?', (eid,))[0] == equipment['nom'])
    check('M1 comparateur réel', 'Puissance (W)' in member.request('/comparateur?ids[]=1&ids[]=2')[2])
    check('M1 création catégorie réelle', admin.submit('/admin/categories', {'nom': 'Catégorie créée en recette', 'description': 'Une catégorie réservée aux données de démonstration.', 'icone': 'bi-sun'}, '/admin/categories/create')[0] == 200 and row('select count(*) from categories')[0] == 2)
    start = time.strftime('%Y-%m-%d', time.localtime(time.time() + 86400 * 5))
    end = time.strftime('%Y-%m-%d', time.localtime(time.time() + 86400 * 6))
    reservation = {'equipement_id': eid, 'date_debut': start, 'date_fin': end, 'montant_total': '1'}
    check('M2 réservation réelle et montant calculé', member.submit('/reserver', reservation, '/reserver?equipement=' + str(eid))[0] == 200 and float(row('select montant_total from reservations where equipement_id=?', (eid,))[0]) == 30)
    rid = row('select id from reservations where equipement_id=?', (eid,))[0]
    status, _, body = newcomer.submit('/reserver', dict(reservation, statut='annulee'), '/reserver?equipement=' + str(eid), True)
    check('M2 conflit refusé malgré statut injecté', status == 422 and 'date_debut' in body)
    check('M2 calendrier avec estimation et conflit', 'Réservation impossible' in member.request('/disponibilites?' + urllib.parse.urlencode(dict(equipement=eid, date_debut=start, date_fin=end)))[2])
    payment = {'reservation_id': rid, 'montant': '30', 'methode': 'especes', 'statut': 'valide', 'date_paiement': time.strftime('%Y-%m-%d %H:%M:%S')}
    check('M2 enregistrement paiement réel', admin.submit('/admin/paiements', payment, '/admin/paiements/create')[0] == 200 and row('select count(*) from paiements where reservation_id=?', (rid,))[0] == 1)
    check('M2 dépassement du solde refusé', admin.submit('/admin/paiements', dict(payment, montant='1'), '/admin/paiements/create', True)[0] == 422)

    claim = {'sujet': 'Batterie recette ne charge plus', 'description': 'La batterie reste déchargée après plusieurs heures de branchement.', 'categorie': 'technique', 'priorite': 'haute'}
    check('M3 dépôt réel de réclamation', member.submit('/mes-reclamations', claim, '/mes-reclamations/nouvelle')[0] == 200 and row('select count(*) from reclamations')[0] == 1)
    cid = row('select id from reclamations')[0]
    check('M3 isolation des dossiers membres', newcomer.request('/mes-reclamations/' + str(cid))[0] == 403)
    reply = {'reclamation_id': cid, 'message': 'Nous examinons votre batterie et vous proposons un contrôle.', 'nouveau_statut': 'en_cours'}
    check('M3 réponse admin et historique', admin.submit('/admin/reponses', reply, '/admin/reponses/create')[0] == 200 and row('select count(*) from reclamation_histories')[0] == 1 and 'Nous examinons' in member.request('/mes-reclamations/' + str(cid))[2])
    event = {'titre': 'Programme solaire créé en recette', 'description': 'Atelier de démonstration sur le dimensionnement des installations solaires.', 'type': 'atelier', 'lieu': 'ESPRIT salle de démonstration', 'ville': 'Tunis', 'date_debut': time.strftime('%Y-%m-%d 10:00:00', time.localtime(time.time() + 86400 * 9)), 'date_fin': time.strftime('%Y-%m-%d 12:00:00', time.localtime(time.time() + 86400 * 9)), 'capacite': '3', 'prix': '0', 'statut': 'planifie'}
    check('M4 création événement via formulaire admin', admin.submit('/admin/evenements', event, '/admin/evenements/create')[0] == 200 and row('select count(*) from evenements')[0] == 2)
    check('M4 inscription événement réelle', member.submit('/evenements/1/inscription', {}, '/evenements/1')[0] == 200 and row('select count(*) from participations where statut="inscrit"')[0] == 1)
    check('M4 capacité atteinte refusée', newcomer.submit('/evenements/1/inscription', {}, '/evenements/1', True)[0] == 422)
    check('M4 annulation et place libérée', member.submit('/evenements/1/inscription', {'_method': 'DELETE'}, '/evenements/1')[0] == 200 and newcomer.submit('/evenements/1/inscription', {}, '/evenements/1')[0] == 200)

    article = row('select slug from articles where id=1')[0]
    check('M5 article publié et commentaire réel', member.submit('/blog/' + article + '/commentaires', {'contenu': 'Ces conseils sont très utiles pour préparer mon installation.'}, '/blog/' + article)[0] == 200 and row('select count(*) from commentaires')[0] == 1)
    check('M5 ajout favori et page personnelle', member.submit('/favoris/1', {}, '/blog/' + article)[0] == 200 and 'Conseils solaires' in member.request('/mes-favoris')[2])
    check('M5 retrait du favori', member.submit('/favoris/1', {'_method': 'DELETE'}, '/mes-favoris')[0] == 200 and row('select count(*) from article_user')[0] == 0)
    future = row('select slug from articles where id=2')[0]
    check('M5 publication programmée inaccessible avant sa date', member.request('/blog/' + future)[0] == 404)
    article_data = {'user_id': 1, 'titre': 'Article solaire créé pendant la recette', 'resume': 'Présentation pratique des usages du solaire pour les membres de SolarShare.', 'contenu': ('Une installation solaire adaptée permet de produire une énergie utile et de mieux comprendre les besoins quotidiens des utilisateurs. ' * 6), 'categorie': 'solaire', 'statut': 'publie'}
    check('M5 création et publication via formulaire admin', admin.submit('/admin/articles', article_data, '/admin/articles/create')[0] == 200 and row('select count(*) from articles')[0] == 3)

    for module in ['equipements', 'locations', 'reclamations', 'evenements', 'blog']:
        payload = {'besoin': 'Vérifier les besoins solaires de la démonstration.', 'budget': 100, 'id': cid if module == 'reclamations' else 1}
        if module == 'locations':
            payload.update(date_debut=start, date_fin=end)
        client = member if module in ['equipements', 'locations'] else admin
        status, _, body = client.submit('/assistants/' + module, payload, '/assistants/' + module)
        check('IA ' + module + ' : formulaire et absence de clé explicite', status == 200 and 'n’est pas configuré' in body)
        if module in ['reclamations', 'evenements', 'blog']:
            check('IA ' + module + ' : autorisation admin', member.request('/assistants/' + module)[0] == 403)

    status, _, body = newcomer.submit('/profil/2fa', {'password': 'Recette2026!'}, '/profil')
    secret = re.search(r'<code>([A-Z2-7]{32})</code>', body)
    check('2FA configuration réelle avec QR local', status == 200 and secret is not None and 'data:image/svg+xml;base64,' in body)
    secret = secret.group(1)


    def otp():
        counter = int(time.time()) // 30
        digest = hmac.new(base64.b32decode(secret), struct.pack('>Q', counter), hashlib.sha1).digest()
        offset = digest[-1] & 15
        return str((struct.unpack('>I', digest[offset:offset+4])[0] & 0x7fffffff) % 1000000).zfill(6)


    activation_code = otp()
    status, _, body = newcomer.submit('/profil/2fa/confirmer', {'code': activation_code}, '/profil')
    codes = re.findall(r'<code>([A-Za-z0-9]{20})</code>', body)
    check('2FA activation TOTP et huit codes de récupération', status == 200 and len(codes) == 8)
    newcomer.logout()
    check('2FA connexion bloque avant le second facteur', '/double-authentification' in newcomer.login(registration['email'])[1])
    check('2FA code déjà consommé refusé', newcomer.submit('/double-authentification', {'code': activation_code}, '/double-authentification', True)[0] == 422)
    time.sleep(31 - (int(time.time()) % 30))
    check('2FA connexion par nouveau TOTP réel', newcomer.submit('/double-authentification', {'code': otp()}, '/double-authentification')[0] == 200 and newcomer.request('/profil')[1].endswith('/profil'))
    newcomer.logout()
    newcomer.login(registration['email'])
    check('2FA connexion par récupération réelle', newcomer.submit('/double-authentification', {'code': codes[0]}, '/double-authentification')[0] == 200 and newcomer.request('/profil')[1].endswith('/profil'))
    newcomer.logout()
    newcomer.login(registration['email'])
    check('2FA récupération réutilisée refusée', newcomer.submit('/double-authentification', {'code': codes[0]}, '/double-authentification', True)[0] == 422)
    newcomer.submit('/double-authentification', {'code': codes[1]}, '/double-authentification')
    check('2FA désactivation protégée réelle', newcomer.submit('/profil/2fa', {'_method': 'DELETE', 'password': 'Recette2026!', 'code': codes[2]}, '/profil')[0] == 200 and row('select two_factor_confirmed_at from users where email=?', (registration['email'],))[0] is None)
    newcomer.logout()
    check('Mot de passe oublié : notification générée', newcomer.submit('/mot-de-passe-oublie', {'email': registration['email']}, '/mot-de-passe-oublie')[0] == 200)
    reset_link = mail_link('reinitialiser')
    parts = urllib.parse.urlsplit(reset_link)
    token = parts.path.rsplit('/', 1)[-1]
    reset = {'email': registration['email'], 'token': token, 'password': 'RecetteFinale2026!', 'password_confirmation': 'RecetteFinale2026!'}
    check('Réinitialisation réelle via lien email généré', newcomer.submit('/reinitialiser', reset, parts.path + '?' + parts.query)[1].endswith('/connexion'))
    check('Ancien mot de passe refusé', newcomer.submit('/connexion', {'email': registration['email'], 'password': 'Recette2026!'}, '/connexion')[1].endswith('/connexion'))
    check('Nouveau mot de passe accepté', newcomer.login(registration['email'], 'RecetteFinale2026!')[1].endswith('/'))
    png = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aKf8AAAAASUVORK5CYII=')
    profile_data = {'_method': 'PATCH', 'name': 'Profil Recette Modifié', 'email': registration['email'], 'ville': 'Tunis', 'current_password': 'RecetteFinale2026!'}
    status, _, _ = newcomer.upload('/profil', profile_data, {'photo': ('avatar.png', 'image/png', png)})
    photo = row('select photo_path from users where email=?', (registration['email'],))[0]
    check('Profil : téléversement et stockage réel de photo', status == 200 and photo and (WORK / 'runtime/app/public' / photo).is_file())
    check('Profil : photo servie sur le stockage de recette', newcomer.request('/storage/' + photo)[0] == 200)
    save()
    print(str(len(RESULTS)) + ' vérifications HTTP réelles réussies ; aucun contenu IA simulé.', flush=True)


if __name__ == '__main__':
    run()
