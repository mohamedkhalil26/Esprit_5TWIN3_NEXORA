"""Essais OpenRouter réels, sans mock et sans lecture/affichage de la clé."""
from datetime import date, timedelta
from html.parser import HTMLParser
import json
import subprocess
import argparse

from recette_http import Client, ROOT, WORK, row

php = "$app=require 'tests/Manual/bootstrap.php'; echo json_encode(['configure'=>filled(config('ai.key')),'modele'=>config('ai.model')]);"
status = json.loads(subprocess.check_output(['php', '-r', php], cwd=ROOT, text=True))
parser = argparse.ArgumentParser()
parser.add_argument('--module', choices=['equipements', 'locations', 'reclamations', 'evenements', 'blog'])
args = parser.parse_args()
target = ROOT / 'docs/resultats-assistants-reels.json'
previous = json.loads(target.read_text(encoding='utf-8'))['resultats'] if args.module and target.exists() else []
results = [r for r in previous if r['module'] != args.module]


class Suggestions(HTMLParser):
    def __init__(self):
        super().__init__()
        self.values = {}
        self.field = None

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'textarea' and attrs.get('id', '').startswith('result-'):
            self.field = attrs['id'][7:]
            self.values[self.field] = ''

    def handle_data(self, data):
        if self.field:
            self.values[self.field] += data

    def handle_endtag(self, tag):
        if tag == 'textarea':
            self.field = None


if status['configure']:
    member, admin = Client(), Client()
    member.login('membre.recette@example.test')
    admin.login('admin.recette@example.test')
for module in ['equipements', 'locations', 'reclamations', 'evenements', 'blog']:
    if args.module and module != args.module:
        continue
    if not status['configure']:
        outcome = {'module': module, 'etat': 'non_executé', 'erreur': 'OPENROUTER_API_KEY non renseignée', 'requetes_api': 0}
    else:
        data = {'besoin': 'Propose une aide claire et factuelle pour cette démonstration solaire fictive.', 'budget': 100, 'id': 1}
        if module == 'locations':
            data.update(date_debut=str(date.today()+timedelta(days=20)), date_fin=str(date.today()+timedelta(days=21)))
        client = member if module in ['equipements', 'locations'] else admin
        log_path = WORK / 'runtime/logs/laravel.log'
        log_offset = log_path.stat().st_size if log_path.exists() else 0
        code, _, body = client.submit('/assistants/' + module, data, '/assistants/' + module)
        if code == 200 and 'Résultat IA' in body:
            parser = Suggestions()
            parser.feed(body)
            outcome = {'module': module, 'etat': 'réponse_réelle_validée_par_Laravel', 'http': code, 'propositions': parser.values}
            if module in ['equipements', 'locations']:
                # Les valeurs affichées viennent de la base ; la sélection est générée par l'API.
                import re
                ids = sorted(set(int(x) for x in re.findall(r'/equipements/(\d+)', body)))
                outcome['equipements'] = [{'id': i, 'nom': row('select nom from equipements where id=?', (i,))[0]} for i in ids]
        else:
            errors = ['Le service IA n’est pas configuré.', 'Le service IA est indisponible ou a renvoyé une réponse invalide.', 'Aucun équipement disponible']
            outcome = {'module': module, 'etat': 'échec_réel', 'http': code, 'erreur': next((e for e in errors if e in body), 'Réponse refusée ou requête limitée ; aucun résultat simulé')}
            if log_path.exists():
                import re
                new_log = log_path.read_bytes()[log_offset:].decode('utf-8', errors='replace')
                lines = re.findall(r'openrouter\.request_failed (\{[^\n]*\})', new_log)
                if lines:
                    metadata = json.loads(lines[-1])
                    outcome['diagnostic'] = {k:metadata.get(k) for k in ['type','http_status','finish_reason','curl_error']}
    results.append(outcome)
    print(module + ' : ' + outcome['etat'] + (' - ' + outcome['erreur'] if 'erreur' in outcome else '') + (' ' + json.dumps(outcome['diagnostic']) if 'diagnostic' in outcome else ''), flush=True)
target.write_text(json.dumps({'modele': status['modele'], 'resultats': results}, ensure_ascii=False, indent=2), encoding='utf-8')
