"""Emails reçus dans Mailpit et 2FA réelle, exclusivement avec un nouveau compte jetable."""
import base64
import hashlib
import hmac
import html
import json
import re
import struct
import time
import urllib.request

from recette_http import Client, WORK, row

BASE = 'http://127.0.0.1:8010'
MAILPIT = 'http://127.0.0.1:8025'
checks = []
captured = []


def check(label, value):
    checks.append({'verification': label, 'resultat': 'OK' if value else 'ECHEC'})
    print(('OK : ' if value else 'ECHEC : ') + label, flush=True)
    if not value:
        raise RuntimeError(label)


def api(path):
    with urllib.request.urlopen(MAILPIT + path, timeout=10) as response:
        return json.load(response)


def notification_link(email, path):
    messages = api('/api/v1/messages')['messages']
    matching = [m for m in messages if any(t['Address'] == email for t in m['To'])]
    for message in matching:
        detail = api('/api/v1/message/' + message['ID'])
        text = html.unescape(detail.get('Text', '') + '\n' + detail.get('HTML', ''))
        links = re.findall(re.escape(BASE + '/' + path + '/') + r'[^\s<>\"]+', text)
        if links:
            captured.append({'type': path, 'id_mailpit': message['ID'], 'destinataire_fictif': email, 'sujet': message['Subject']})
            return links[0].rstrip(').')
    raise RuntimeError('Notification non reçue dans Mailpit : ' + path)


client = Client()
email = 'smtp.recette.' + str(int(time.time())) + '@example.test'
password = 'SmtpRecette2026!'
status, _, _ = client.submit('/inscription', {'name': 'SMTP Recette Finale', 'email': email, 'password': password, 'password_confirmation': password}, '/inscription')
check('Inscription jetable avec SMTP réel', status == 200 and row('select id from users where email=?', (email,)) is not None)
verify = notification_link(email, 'verifier-email')
check('Notification de vérification réellement reçue dans Mailpit', bool(verify))
check('Lien reçu : adresse vérifiée réellement', client.request(verify)[0] == 200 and row('select email_verified_at from users where email=?', (email,))[0] is not None)
status, _, body = client.submit('/profil/2fa', {'password': password}, '/profil')
secret = re.search(r'<code>([A-Z2-7]{32})</code>', body)
check('QR code TOTP généré localement', status == 200 and secret is not None and 'data:image/svg+xml;base64,' in body)
secret = secret.group(1)


def otp():
    digest = hmac.new(base64.b32decode(secret), struct.pack('>Q', int(time.time()) // 30), hashlib.sha1).digest()
    offset = digest[-1] & 15
    return str((struct.unpack('>I', digest[offset:offset+4])[0] & 0x7fffffff) % 1000000).zfill(6)


code = otp()
status, _, body = client.submit('/profil/2fa/confirmer', {'code': code}, '/profil')
codes = re.findall(r'<code>([A-Za-z0-9]{20})</code>', body)
check('Confirmation TOTP et huit codes de récupération', status == 200 and len(codes) == 8)
client.logout()
check('Connexion arrêtée au challenge 2FA', '/double-authentification' in client.login(email, password)[1])
check('Code TOTP consommé refusé', client.submit('/double-authentification', {'code': code}, '/double-authentification', True)[0] == 422)
time.sleep(31 - int(time.time()) % 30)
client.submit('/double-authentification', {'code': otp()}, '/double-authentification')
check('Connexion réelle avec nouveau code TOTP', client.request('/profil')[1].endswith('/profil'))
client.logout()
client.login(email, password)
client.submit('/double-authentification', {'code': codes[0]}, '/double-authentification')
check('Connexion réelle par code de récupération', client.request('/profil')[1].endswith('/profil') and len(json.loads(row('select two_factor_recovery_codes from users where email=?', (email,))[0])) == 7)
client.logout()
client.login(email, password)
check('Réutilisation du code de récupération refusée', client.submit('/double-authentification', {'code': codes[0]}, '/double-authentification', True)[0] == 422)
client.submit('/double-authentification', {'code': codes[1]}, '/double-authentification')
client.submit('/profil/2fa', {'_method': 'DELETE', 'password': password, 'code': codes[2]}, '/profil')
check('Désactivation protégée effective', row('select two_factor_confirmed_at from users where email=?', (email,))[0] is None)
client.logout()
client.submit('/mot-de-passe-oublie', {'email': email}, '/mot-de-passe-oublie')
reset = notification_link(email, 'reinitialiser')
check('Notification de réinitialisation réellement reçue dans Mailpit', bool(reset))
from urllib.parse import urlsplit
parts = urlsplit(reset)
new_password = 'SmtpRecetteFinale2026!'
client.submit('/reinitialiser', {'email': email, 'token': parts.path.rsplit('/', 1)[-1], 'password': new_password, 'password_confirmation': new_password}, parts.path + '?' + parts.query)
check('Lien reçu : réinitialisation et nouvelle connexion', client.login(email, new_password)[1].endswith('/'))
client.logout()
result = {'verifications': checks, 'emails_recus': captured, 'messages_mailpit_total': api('/api/v1/messages')['total']}
(WORK / 'services-reels.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
print(str(len(checks)) + ' contrôles réels réussis ; ' + str(len(captured)) + ' notifications reçues dans Mailpit.', flush=True)
