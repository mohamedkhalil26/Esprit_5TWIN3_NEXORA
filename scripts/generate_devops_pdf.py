"""Génération locale du dossier DevOps. Requiert ReportLab, Pillow et Mermaid CLI.
Les diagrammes sont réellement interprétés et rendus par Mermaid, jamais simulés.
"""
import argparse
import html
from pathlib import Path
import re
import subprocess
import json

from PIL import Image as PILImage
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, Table,
    TableStyle, PageBreak, NextPageTemplate, Image, KeepTogether,
)

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / 'docs/DEVOPS_VALIDATION_FINALE.md'
OUTPUT = ROOT / 'docs/DEVOPS_VALIDATION_FINALE.pdf'
TMP = ROOT / 'storage/app/private/recette-tools/pdf/render'
TMP.mkdir(parents=True, exist_ok=True)
GREEN = colors.HexColor('#185b3c')
YELLOW = colors.HexColor('#e0b535')
TEXT = colors.HexColor('#23382f')
MUTED = colors.HexColor('#5b6c63')


def inline(value):
    value = html.escape(value.replace('—', '-').replace('–', '-').replace('\u2011', '-'))
    value = re.sub(r'`([^`]+)`', r'<font name="Mono" size="8">\1</font>', value)
    value = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', value)
    value = re.sub(r'\[([^\]]+)\]\((https?://[^)]+)\)', r'<link href="\2" color="#185b3c">\1</link>', value)
    value = re.sub(r'\[([^\]]+)\]\(([^)]+)\)', r'\1', value)
    return value


def render_diagrams(text, mmdc):
    blocks = re.findall(r'```mermaid\s*\n(.*?)```', text, re.S)
    if len(blocks) != 2:
        raise RuntimeError('Le document doit contenir exactement deux diagrammes Mermaid.')
    config = TMP / 'mermaid.json'
    config.write_text(json.dumps({'theme': 'base', 'themeVariables': {
        'fontFamily': 'Arial', 'fontSize': '17px', 'primaryColor': '#eef6ef',
        'primaryBorderColor': '#357453', 'primaryTextColor': '#163d29',
        'lineColor': '#587b66', 'clusterBkg': '#f8faf6', 'clusterBorder': '#c7d8cb',
    }, 'flowchart': {'curve': 'linear', 'nodeSpacing': 24, 'rankSpacing': 38}}), encoding='utf-8')
    browser = TMP / 'browser.json'
    browser.write_text(json.dumps({'executablePath': 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'}), encoding='utf-8')
    images = []
    for i, block in enumerate(blocks, 1):
        source = TMP / f'diagram-{i}.mmd'
        source.write_text(block, encoding='utf-8')
        target = ROOT / f'docs/recette-captures/devops-diagramme-{i}.png'
        target.parent.mkdir(exist_ok=True)
        # Le lanceur .cmd Windows doit passer par cmd ; aucun texte de document dans la commande.
        command = ['cmd', '/c', str(mmdc), '-i', str(source), '-o', str(target), '-p', str(browser), '-c', str(config), '-b', 'white', '-w', '3000', '-s', '2']
        subprocess.run(command, check=True, cwd=ROOT)
        images.append(target)
    return images


class Dossier(BaseDocTemplate):
    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph) and flowable.style.name == 'Section':
            title = flowable.getPlainText()
            key = 'section-' + str(self.page) + '-' + str(len(title))
            self.canv.bookmarkPage(key)
            self.canv.addOutlineEntry(title, key, 0, False)


def page_header(canvas, doc):
    width, height = canvas._pagesize
    canvas.setStrokeColor(YELLOW)
    canvas.setLineWidth(2)
    canvas.line(42, height - 39, width - 42, height - 39)
    canvas.setFont('Arial-Bold', 9)
    canvas.setFillColor(GREEN)
    canvas.drawString(42, height - 29, 'SOLARSHARE  /  VALIDATION FINALE')
    canvas.setFont('Arial', 8)
    canvas.setFillColor(MUTED)
    canvas.drawString(42, 27, 'ESPRIT - Applications Web Avancées | 30 octobre 2026')
    canvas.drawRightString(width - 42, 27, str(doc.page))


def create_pdf(images):
    for name, file in [('Arial', 'arial.ttf'), ('Arial-Bold', 'arialbd.ttf'), ('Arial-Italic', 'ariali.ttf'), ('Arial-BoldItalic', 'arialbi.ttf'), ('Mono', 'consola.ttf')]:
        pdfmetrics.registerFont(TTFont(name, 'C:/Windows/Fonts/' + file))
    pdfmetrics.registerFontFamily('Arial', normal='Arial', bold='Arial-Bold', italic='Arial-Italic', boldItalic='Arial-BoldItalic')
    styles = {
        'body': ParagraphStyle('Body', fontName='Arial', fontSize=10, leading=15, textColor=TEXT, spaceAfter=8, allowWidows=0, allowOrphans=0),
        'section': ParagraphStyle('Section', fontName='Arial-Bold', fontSize=17, leading=22, textColor=GREEN, spaceBefore=18, spaceAfter=12, keepWithNext=True),
        'sub': ParagraphStyle('Sub', fontName='Arial-Bold', fontSize=12, leading=17, textColor=GREEN, spaceBefore=12, spaceAfter=8, keepWithNext=True),
        'code': ParagraphStyle('Code', fontName='Mono', fontSize=8, leading=11, textColor=TEXT, leftIndent=10, rightIndent=8, spaceAfter=5),
        'cell': ParagraphStyle('Cell', fontName='Arial', fontSize=8.5, leading=12, textColor=TEXT),
        'small': ParagraphStyle('Small', fontName='Arial', fontSize=9, leading=13, textColor=MUTED, spaceAfter=9),
        'quote': ParagraphStyle('Quote', fontName='Arial', fontSize=10, leading=15, textColor=GREEN, borderColor=YELLOW, borderWidth=0, leftIndent=12, spaceAfter=12),
    }
    doc = Dossier(str(OUTPUT), pagesize=A4, title='SolarShare - Dossier DevOps de validation finale', author='Équipe SolarShare - ESPRIT', subject='CI/CD, tests, livraison, résultats et limites vérifiées', leftMargin=42, rightMargin=42, topMargin=58, bottomMargin=48)
    w, h = A4
    lw, lh = landscape(A4)
    doc.addPageTemplates([
        PageTemplate(id='portrait', frames=Frame(42, 48, w-84, h-106, id='body'), onPage=page_header, pagesize=A4),
        PageTemplate(id='diagram', frames=Frame(42, 48, lw-84, lh-106, id='diagram-body'), onPage=page_header, pagesize=landscape(A4)),
    ])
    story = [Spacer(1, 92), Paragraph('SolarShare', ParagraphStyle('Cover',fontName='Arial-Bold',fontSize=40,leading=48,textColor=GREEN)), Spacer(1,15), Paragraph('Dossier DevOps<br/>Validation finale', ParagraphStyle('CoverSub',fontName='Arial',fontSize=25,leading=33,textColor=TEXT)), Spacer(1,25)]
    status = [
        ['28 tests / 184 assertions', '70 contrôles HTTP réels'],
        ['4 emails fictifs dans Mailpit', '13 contrôles SMTP et 2FA réussis'],
        ['5 assistants IA testés réellement', 'Modèle OpenRouter : openrouter/free'],
        ['GitHub Actions : CI réelle réussie', 'Aucun déploiement revendiqué'],
    ]
    table = Table([[Paragraph(inline(x),styles['body']) for x in row] for row in status], colWidths=[(w-96)/2]*2)
    table.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),colors.HexColor('#f0f5ef')),('BOX',(0,0),(-1,-1),0.6,colors.HexColor('#cad9cd')),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),13),('RIGHTPADDING',(0,0),(-1,-1),13),('TOPPADDING',(0,0),(-1,-1),11),('BOTTOMPADDING',(0,0),(-1,-1),8)]))
    story += [table, Spacer(1,35), Paragraph('Applications Web Avancées - ESPRIT', styles['sub']), Paragraph('Échéance : semaine du 2 novembre 2026<br/>Édition du 30 octobre 2026 - Africa/Tunis', styles['body']), Spacer(1,18), Paragraph('Ce dossier distingue les contrôles exécutés, les tests simulés et les configurations externes encore nécessaires. Les données et secrets du projet ne sont pas inclus.', styles['small']), PageBreak()]
    lines = SOURCE.read_text(encoding='utf-8').splitlines()
    sections = [line[3:] for line in lines if line.startswith('## ')]
    story += [Paragraph('Sommaire', styles['section'])]
    story += [Paragraph(inline(x),styles['body']) for x in sections]
    story += [Spacer(1,18), Paragraph('Lecture des preuves', styles['sub']), Paragraph('Local : commandes et parcours réellement exécutés. Simulé : réponses API de test. Externe : service ou infrastructure nécessitant une configuration ou une autorisation. Les signets du PDF permettent de naviguer entre sections.',styles['body']), PageBreak()]
    i, diagram_index = 0, 0
    while i < len(lines):
        line = lines[i].strip()
        if not line or line.startswith('# '):
            i += 1
            continue
        if line.startswith('```'):
            kind = line[3:].strip()
            code = []
            i += 1
            while i < len(lines) and not lines[i].startswith('```'):
                code.append(lines[i])
                i += 1
            if kind == 'mermaid':
                if len(story) >= 2 and isinstance(story[-1], PageBreak) and isinstance(story[-2], NextPageTemplate):
                    story[-2] = NextPageTemplate('diagram')
                else:
                    story += [NextPageTemplate('diagram'), PageBreak()]
                story += [Paragraph('Processus DevOps' if diagram_index == 0 else 'Pipeline CI/CD', styles['section'])]
                path = images[diagram_index]
                iw, ih = PILImage.open(path).size
                scale = min((lw-110)/iw, (lh-225)/ih)
                story += [Image(str(path),width=iw*scale,height=ih*scale), Spacer(1,10), Paragraph('Diagramme Mermaid interprété et rendu localement. Le déploiement reste soumis aux autorisations et aux prérequis décrits dans le dossier.',styles['small']),NextPageTemplate('portrait'), PageBreak()]
                diagram_index += 1
            else:
                for code_line in code:
                    # Casser uniquement les longues commandes pour empêcher une sortie de marge.
                    while len(code_line) > 92:
                        story.append(Paragraph(html.escape(code_line[:92]),styles['code']))
                        code_line = code_line[92:]
                    story.append(Paragraph(html.escape(code_line) or ' ',styles['code']))
            i += 1
            continue
        if line.startswith('|'):
            rows=[]
            while i < len(lines) and lines[i].strip().startswith('|'):
                cells=[c.strip() for c in lines[i].strip().strip('|').split('|')]
                if not all(re.match(r'^:?-+:?$',c) for c in cells):
                    rows.append([Paragraph(inline(c),styles['cell']) for c in cells])
                i += 1
            n=max(len(r) for r in rows)
            widths=([135,w-231] if n==2 else [(w-96)/n]*n)
            t=Table(rows,colWidths=widths,repeatRows=1,hAlign='LEFT')
            t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),colors.HexColor('#e9f2e8')),('ROWBACKGROUNDS',(0,1),(-1,-1),[colors.white,colors.HexColor('#f8faf7')]),('GRID',(0,0),(-1,-1),0.4,colors.HexColor('#d5ded5')),('VALIGN',(0,0),(-1,-1),'TOP'),('TOPPADDING',(0,0),(-1,-1),8),('BOTTOMPADDING',(0,0),(-1,-1),8)]))
            story += [t,Spacer(1,12)]
            continue
        if line.startswith('## '):
            next_nonempty = next((x.strip() for x in lines[i+1:] if x.strip()), '')
            if next_nonempty != '```mermaid':
                if line.startswith(('## 2. ', '## 10. ')):
                    story.append(PageBreak())
                story.append(Paragraph(inline(line[3:]),styles['section']))
        elif line.startswith('### '):
            story.append(Paragraph(inline(line[4:]),styles['sub']))
        elif line.startswith('> '):
            story.append(Paragraph(inline(line[2:]),styles['quote']))
        elif line.startswith(('**Module', '**Échéance', '**État de préparation')):
            story.append(Paragraph(inline(line),styles['small']))
        elif re.match(r'^(\d+\. |[-*] )',line):
            story.append(Paragraph(inline(line),styles['body']))
        else:
            paragraph=[line]
            while i+1<len(lines) and lines[i+1].strip() and not re.match(r'^(#|\||```|>|\d+\. |[-*] )',lines[i+1].strip()):
                i+=1
                paragraph.append(lines[i].strip())
            story.append(Paragraph(inline(' '.join(paragraph)),styles['body']))
        i+=1
    doc.build(story)
    print('PDF généré : docs/DEVOPS_VALIDATION_FINALE.pdf')


if __name__ == '__main__':
    parser=argparse.ArgumentParser()
    parser.add_argument('--mmdc',type=Path,default=ROOT/'storage/app/private/recette-tools/pdf/node_modules/.bin/mmdc.cmd')
    args=parser.parse_args()
    create_pdf(render_diagrams(SOURCE.read_text(encoding='utf-8'),args.mmdc))
