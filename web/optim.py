#!/usr/bin/python
#-*- coding: utf-8 -*-

print 'Content-Type: text/html;charset=utf-8'
print ''
# Là commence votre code.
import cgi
import re
import MySQLdb
import numpy as np
from lsqnonneg import lsqnonneg

form = cgi.FieldStorage()

actifs = form.getvalue('act');
if actifs is None:
	actifs = '';
actifs_id = form.getvalue('act_id')
if actifs_id is not None:
	actifs_id = actifs_id.split("|")
else:
	actifs_id = ''
actifs_val = form.getvalue('act_val')
if actifs_val is not None:
	actifs_val = actifs_val.split("|")
else:
	actifs_val = ''
	
regions = form.getvalue('reg')
if regions is None:
	regions = '';
regions_id = form.getvalue('reg_id')
if regions_id is not None:
	regions_id = regions_id.split("|")
else:
	regions_id = ''
regions_val = form.getvalue('reg_val')
if regions_val is not None:
	regions_val = regions_val.split("|")
else:
	regions_val = ''

isin = form.getvalue('isin')
if isin is not None:
	isin = isin.split("|")
else:
	isin = ''

paramMysql = {
    'host'   : '*****',
    'user'   : '*****',
    'passwd' : '*****',
    'db'     : '*****',
	'charset': 'utf8',
	'use_unicode': True
}

sql_actifs = """SELECT isin, nom, pourcentage, id_actifs_complet, nom_actifs_complet
FROM
(
SELECT uc.isin, uc.nom, uc_actifs.pourcentage, actifs.id AS id_actifs_complet, actifs.nom AS nom_actifs_complet
FROM unitesdecompte uc
INNER JOIN bdd_unitesdecompte_actifs uc_actifs
ON uc_actifs.id_unitedecompte = uc.id
INNER JOIN bdd_actifs actifs
ON actifs.id = uc_actifs.id_actif
WHERE uc.isin IN (%s)
AND actifs.nom <> 'Actions'
UNION ALL
SELECT uc.isin, uc.nom, uc_actifs.pourcentage, CONCAT(actifs.id, '-', ((uc_actions.id_actions-1) DIV 3)) AS id_actifs_complet, CONCAT(actifs.nom, ((uc_actions.id_actions-1) DIV 3)) AS nom_actifs_complet
FROM unitesdecompte uc
INNER JOIN bdd_unitesdecompte_actifs uc_actifs
ON uc_actifs.id_unitedecompte = uc.id
INNER JOIN bdd_actifs actifs
ON actifs.id = uc_actifs.id_actif
INNER JOIN bdd_unitesdecompte_actions uc_actions
ON uc_actions.id_unitedecompte = uc.id
WHERE uc.isin IN (%s)
AND actifs.nom = 'Actions'
)resultat"""

sql_regions = """SELECT uc.isin, uc.nom, uc_regions.pourcentage, regions.id AS id_regions_complet, regions.nom AS nom_regions_complet
FROM unitesdecompte uc
INNER JOIN bdd_unitesdecompte_regions uc_regions
ON uc_regions.id_unitedecompte = uc.id
INNER JOIN bdd_regions regions
ON regions.id = uc_regions.id_region
WHERE uc.isin IN (%s)"""

longueurs = True
if actifs == '1' and (len(actifs_id)!=len(actifs_val) or len(actifs_id)==0):
	longueurs = False
if regions == '1' and (len(regions_id)!=len(regions_val) or len(regions_id)==0):
	longueurs = False
if actifs != '1' and regions != '1':
	longueurs = False
if len(isin) <= 1:
	longueurs = False

print('<div class="mt-3">')
	
if longueurs:
	r2 = re.compile("^[A-Z0-9]{12}$")
	if all(r2.search(item) for item in isin):
		conn = MySQLdb.connect(**paramMysql)
		cur = conn.cursor(MySQLdb.cursors.DictCursor)
		format_strings = ','.join(['%s'] * len(isin))
		actifs_ok = False
		regions_ok = False
		if actifs == '1':
			if all(item.isdigit() for item in actifs_val):
				actifs_val = [int(i) for i in actifs_val]
				if (sum(actifs_val) <= 100):
					r = re.compile("^1{1}\-{1}\d{1}$")
					actifs_id_filtre = [i for i in actifs_id if not r.search(i)]
					if all(item.isdigit() for item in actifs_id_filtre):
						cur.execute(sql_actifs%(format_strings,format_strings), tuple(isin+isin))
						resultat_actifs = cur.fetchall()
						liste_actifs = set([x['id_actifs_complet'] for x in resultat_actifs])
						liste_isin = set([x['isin'] for x in resultat_actifs])
						if set(actifs_id).issubset(liste_actifs) and set(isin).issubset(liste_isin):
							actifs_ok = True
							
							def transformer_nom_actifs( nom_entree ):
								if (nom_entree == 'Actions0'):
									nom_sortie = 'Actions (grandes)'
								elif (nom_entree == 'Actions1'):
									nom_sortie = 'Actions (moyennes)'
								elif (nom_entree == 'Actions2'):
									nom_sortie = 'Actions (petites)'
								elif (nom_entree == 'Actions3'):
									nom_sortie = 'Actions (divers)'
								else:
									nom_sortie = nom_entree
								return nom_sortie
							
							dict_nom_uc = {x['isin']:x['nom'] for x in resultat_actifs}
							dict_nom_actifs = {x['id_actifs_complet']:transformer_nom_actifs(x['nom_actifs_complet']) for x in resultat_actifs}
							
							reference_actifs = {x:0 for x in actifs_id}
							dict_actifs = {}
							
							for valeur in liste_isin:
								dict_actifs[valeur] = {x['id_actifs_complet']:x['pourcentage'] for x in resultat_actifs if x['isin']==valeur}
							for valeur in liste_isin:
								dict_actifs[valeur] = { k: dict_actifs[valeur].get(k, 0) + reference_actifs.get(k, 0) for k in set(reference_actifs) }
							
							dict_actifs_val = {}
							for index, valeur in enumerate(actifs_id):
								dict_actifs_val[valeur] = actifs_val[index]

							tableau_actifs = []
							tableau_actifs_val = []
							for index, valeur in sorted(dict_actifs_val.iteritems(), key=lambda x: x[1], reverse=True):
								tableau_actifs_ligne = [dict_actifs[x][index] for x in sorted(liste_isin)]
								tableau_actifs.append(tableau_actifs_ligne)
								tableau_actifs_val.append(dict_actifs_val[index])
							
							A_actifs = np.array(tableau_actifs)
							y_actifs = np.array(tableau_actifs_val)
						else:
							print('<div class="bg-danger p-2">Les actifs et les UCs fournis ne correspondent pas.</div>')
					else:
						print('<div class="bg-danger p-2">Le format des données relatives aux actifs est incorrect.</div>')
				else:
					print('<div class="bg-danger p-2">La somme des pourcentages souhaités pour les actifs n\'est pas inférieure ou égale à 100.</div>')
			else:
				print('<div class="bg-danger p-2">Le format des données relatives aux actifs est incorrect.</div>')
		if regions == '1':
			if all(item.isdigit() for item in regions_id) and all(item.isdigit() for item in regions_val):
				regions_id = [int(i) for i in regions_id]
				regions_val = [int(i) for i in regions_val]
				if (sum(regions_val) <= 100):
					cur.execute(sql_regions%format_strings, tuple(isin))
					resultat_regions = cur.fetchall()
					liste_regions = set([x['id_regions_complet'] for x in resultat_regions])
					liste_isin = set([x['isin'] for x in resultat_regions])
					if set(regions_id).issubset(liste_regions) and set(isin).issubset(liste_isin):
						regions_ok = True
						
						dict_nom_uc = {x['isin']:x['nom'] for x in resultat_regions}
						dict_nom_regions = {x['id_regions_complet']:x['nom_regions_complet'] for x in resultat_regions}
						
						reference_regions = {x:0 for x in regions_id}
						dict_regions = {}
					
						for valeur in liste_isin:
							dict_regions[valeur] = {x['id_regions_complet']:x['pourcentage'] for x in resultat_regions if x['isin']==valeur}
						for valeur in liste_isin:
							dict_regions[valeur] = { k: dict_regions[valeur].get(k, 0) + reference_regions.get(k, 0) for k in set(reference_regions) }
					
						dict_regions_val = {}
						for index, valeur in enumerate(regions_id):
							dict_regions_val[valeur] = regions_val[index]
					
						tableau_regions = []
						tableau_regions_val = []
						for index, valeur in sorted(dict_regions_val.iteritems(), key=lambda x: x[1], reverse=True):
							tableau_regions_ligne = [dict_regions[x][index] for x in sorted(liste_isin)]
							tableau_regions.append(tableau_regions_ligne)
							tableau_regions_val.append(dict_regions_val[index])
						
						A_regions = np.array(tableau_regions)
						y_regions = np.array(tableau_regions_val)
					else:
						print('<div class="bg-danger p-2">Les régions et les UCs fournis ne correspondent pas.</div>')
				else:
					print('<div class="bg-danger p-2">La somme des pourcentages souhaités pour les régions n\'est pas inférieure ou égale à 100.</div>')
			else:
				print('<div class="bg-danger p-2">Le format des données relatives aux régions est incorrect.</div>')
		if ((actifs=='1' and actifs_ok) or actifs=='0') and ((regions=='1' and regions_ok) or regions=='0') and (actifs=='1' or regions=='1'):
			tableau_somme = [[1]*len(liste_isin)]
			tableau_somme_val = [1]
			
			A_somme = np.array(tableau_somme)
			y_somme = np.array(tableau_somme_val)
			
			if actifs == '1' and regions == '1':
				A = np.concatenate((A_actifs, A_regions, A_somme), axis=0)
				y = np.concatenate((y_actifs, y_regions, y_somme), axis=0)
			elif actifs == '1':
				A = np.concatenate((A_actifs, A_somme), axis=0)
				y = np.concatenate((y_actifs, y_somme), axis=0)
			elif regions == '1':
				A = np.concatenate((A_regions, A_somme), axis=0)
				y = np.concatenate((y_regions, y_somme), axis=0)
			
			A = np.asfarray(A)
			y = np.asfarray(y)
			
			[solution, resnorm, residual] = lsqnonneg(A, y)
			solution = solution/np.sum(solution)*100
			solution = np.round(solution)
			
			valeurs_obtenues = np.round(np.dot(A,solution/100));
			
			dict_solution = {}
			for index, valeur in enumerate(sorted(liste_isin)):
				dict_solution[valeur] = int(solution[index])
			
			print('<div class="bg-success p-2 mb-3">Allocation optimale calculée :</div>')
			
			print('<table class="table table-shrink">')
			print('<thead class="bg-light"><tr>')
			print('<th class="cell-first">ISIN</th>')
			print('<th>Nom</th>')
			print('<th>Allocation optimale</th>')
			print('</tr></thead>')
			print('<tbody>')
			for index, valeur in sorted(dict_solution.iteritems(), key=lambda x: x[1], reverse=True):
				print('<tr><td class="cell-first isin">'+index+'</td>')
				print('<td>'+dict_nom_uc[index].encode("utf-8")+'</td>')
				print('<td><strong>'+str(valeur)+'%</strong></td></tr>')
			print('</tbody>')
			print('</table>')
			
			print('<div class="row">')
			debut_regions = 0
			if actifs == '1':
				debut_regions = len(actifs_id)
				print('<div class="col-6 pr-2">')
				print('<table class="table table-shrink">')
				print('<thead class="bg-light"><tr>')
				print('<th class="cell-first" style="width: 50%">Actifs</th>')
				print('<th style="width: 25%">Part souhaitée</th>')
				print('<th style="width: 25%">Part obtenue</th>')
				print('</tr></thead>')
				print('<tbody>')
				i = 0
				for index, valeur in sorted(dict_actifs_val.iteritems(), key=lambda x: x[1], reverse=True):
					print('<tr><td class="cell-first">'+dict_nom_actifs[index].encode("utf-8")+'</td>')
					print('<td>'+str(valeur)+'%</td>')
					print('<td>'+str(int(valeurs_obtenues[i]))+'%</td></tr>')
					i += 1
				print('</tbody>')
				print('</table>')
				print('</div>')
			if regions == '1':
				print('<div class="col-6')
				if actifs == '1':
					print(' pl-2')
				else:
					print(' pr-2')
				print('">')
				print('<table class="table table-shrink">')
				print('<thead class="bg-light"><tr>')
				print('<th class="cell-first" style="width: 50%">Régions</th>')
				print('<th style="width: 25%">Part souhaitée</th>')
				print('<th style="width: 25%">Part obtenue</th>')
				print('</tr></thead>')
				print('<tbody>')
				i = 0
				for index, valeur in sorted(dict_regions_val.iteritems(), key=lambda x: x[1], reverse=True):
					print('<tr><td class="cell-first">'+dict_nom_regions[index].encode("utf-8")+'</td>')
					print('<td>'+str(valeur)+'%</td>')
					print('<td>'+str(int(valeurs_obtenues[debut_regions+i]))+'%</td></tr>')
					i += 1
				print('</tbody>')
				print('</table>')
				print('</div>')
			
			print('</div>')
			print('<p class="m-0">Méthode utilisée : moindres carrés</p>')
	else:
		print('<div class="bg-danger p-2">Le format des ISINs est incorrect.</div>')
else:
	print('<div class="bg-danger p-2">Le nombre de données est incorrect.</div>')
	
print('</div>')