#!/usr/bin/python
#-*- coding: utf-8 -*-

print 'Content-Type: text/html;charset=utf-8'
print ''
# Là commence votre code.
import cgi
import MySQLdb
import numpy as np
from collections import defaultdict
from pprint import pprint
import operator

def euclideanDistance(instance1, instance2, length, a):
	distance = 0
	for x in range(length):
		distance += a[x]*abs(instance1[x] - instance2[x])
	return distance

def getNeighbors(trainingSet, testInstance, k, a):
	distances = []
	length = len(testInstance)-1
	for x in range(len(trainingSet)):
		dist = euclideanDistance(testInstance, trainingSet[x], length, a)
		distances.append((trainingSet[x], dist))
	distances.sort(key=operator.itemgetter(1))
	neighbors = []
	for x in range(k):
		neighbors.append(distances[x][0])
	return neighbors

def getResponse(neighbors):
	classVotes = {}
	for x in range(len(neighbors)):
		response = neighbors[x][-1]
		if response in classVotes:
			classVotes[response] += 1
		else:
			classVotes[response] = 1
	sortedVotes = sorted(classVotes.iteritems(), key=operator.itemgetter(1), reverse=True)
	return sortedVotes[0][0]

form = cgi.FieldStorage()
id_membre = form.getvalue('id');
nom_portefeuille_transmis = form.getvalue('port');
if id_membre is not None and nom_portefeuille_transmis is not None:
	if id_membre.isdigit() and (nom_portefeuille_transmis == 'defensif' or nom_portefeuille_transmis == 'reactif' or nom_portefeuille_transmis == 'dynamique'):
		id_portefeuilles = {'defensif':'1', 'reactif':'2', 'dynamique':'3'}
		nom_portefeuilles = {'1':'défensif', '2':'réactif', '3':'dynamique'}
		id_portefeuille = id_portefeuilles[nom_portefeuille_transmis]
	
		paramMysql = {
			'host'   : '*****',
			'user'   : '*****',
			'passwd' : '*****',
			'db'     : '*****',
			'charset': 'utf8'
		}

		conn = MySQLdb.connect(**paramMysql)
		cur = conn.cursor(MySQLdb.cursors.DictCursor)

		sql_region = """SELECT utuc.id_utilisateur, utuc.id_portefeuille, reg.id_region, SUM(utuc.pourcentage/100*reg.pourcentage) AS pourcentage_region
		FROM utilisateurs_unitesdecompte utuc
		INNER JOIN bdd_unitesdecompte_regions reg
		ON reg.id_unitedecompte = utuc.id_unitedecompte
		INNER JOIN
		(
			SELECT utilisateurs_unitesdecompte.id_utilisateur, utilisateurs_unitesdecompte.id_portefeuille, SUM(utilisateurs_unitesdecompte.pourcentage) AS somme_portefeuille
			FROM unitesdecompte
			INNER JOIN utilisateurs_unitesdecompte
			ON utilisateurs_unitesdecompte.id_unitedecompte = unitesdecompte.id
			WHERE unitesdecompte.date_donnees IS NOT NULL
			GROUP BY utilisateurs_unitesdecompte.id_utilisateur, utilisateurs_unitesdecompte.id_portefeuille
		) verif
		ON verif.id_utilisateur = utuc.id_utilisateur AND verif.id_portefeuille = utuc.id_portefeuille
		WHERE verif.somme_portefeuille = 100
		GROUP BY utuc.id_utilisateur, utuc.id_portefeuille, reg.id_region"""

		cur.execute(sql_region)
		resultat_region = cur.fetchall()

		dict_region = defaultdict(dict)
		dict_portefeuille = {}
		for x in resultat_region:
			dict_region[str(x['id_utilisateur'])+'-'+str(x['id_portefeuille'])][x['id_region']] = x['pourcentage_region']
			dict_portefeuille[str(x['id_utilisateur'])+'-'+str(x['id_portefeuille'])] = x['id_portefeuille']
		
		if id_membre+'-'+id_portefeuille in dict_portefeuille:
			sql_actif = """SELECT resultat.id_utilisateur, resultat.id_portefeuille, resultat.id_actif_complet, resultat.pourcentage_actif
			FROM
			(

			SELECT utuc.id_utilisateur, utuc.id_portefeuille, act.id_actif AS id_actif_complet, SUM(utuc.pourcentage/100*act.pourcentage) AS pourcentage_actif
			FROM utilisateurs_unitesdecompte utuc
			INNER JOIN bdd_unitesdecompte_actifs act
			ON act.id_unitedecompte = utuc.id_unitedecompte
			WHERE act.id_actif <> '1'
			GROUP BY utuc.id_utilisateur, utuc.id_portefeuille, act.id_actif

			UNION ALL

			SELECT utuc.id_utilisateur, utuc.id_portefeuille, CONCAT(act.id_actif, '-', ((actions.id_actions-1) DIV 3)) AS id_actif_complet, SUM(utuc.pourcentage/100*act.pourcentage) AS pourcentage_actif
			FROM utilisateurs_unitesdecompte utuc
			INNER JOIN bdd_unitesdecompte_actifs act
			ON act.id_unitedecompte = utuc.id_unitedecompte
			LEFT JOIN bdd_unitesdecompte_actions actions
			ON actions.id_unitedecompte = utuc.id_unitedecompte
			WHERE act.id_actif = '1'
			GROUP BY utuc.id_utilisateur, utuc.id_portefeuille, CONCAT(act.id_actif, '-', ((actions.id_actions-1) DIV 3))

			)resultat
			INNER JOIN
			(
				SELECT utilisateurs_unitesdecompte.id_utilisateur, utilisateurs_unitesdecompte.id_portefeuille, SUM(utilisateurs_unitesdecompte.pourcentage) AS somme_portefeuille
				FROM unitesdecompte
				INNER JOIN utilisateurs_unitesdecompte
				ON utilisateurs_unitesdecompte.id_unitedecompte = unitesdecompte.id
				WHERE unitesdecompte.date_donnees IS NOT NULL
				GROUP BY utilisateurs_unitesdecompte.id_utilisateur, utilisateurs_unitesdecompte.id_portefeuille
			) verif
			ON verif.id_utilisateur = resultat.id_utilisateur AND verif.id_portefeuille = resultat.id_portefeuille
			WHERE verif.somme_portefeuille = 100"""

			cur.execute(sql_actif)
			resultat_actif = cur.fetchall()

			dict_actif = defaultdict(dict)
			for x in resultat_actif:
				dict_actif[str(x['id_utilisateur'])+'-'+str(x['id_portefeuille'])][x['id_actif_complet']] = x['pourcentage_actif']

			sql_srri = """SELECT utuc.id_utilisateur, utuc.id_portefeuille, srri.srri, SUM(utuc.pourcentage) AS pourcentage_srri
			FROM utilisateurs_unitesdecompte utuc
			INNER JOIN bdd_srri srri
			ON srri.id_unitedecompte = utuc.id_unitedecompte
			INNER JOIN
			(
				SELECT utilisateurs_unitesdecompte.id_utilisateur, utilisateurs_unitesdecompte.id_portefeuille, SUM(utilisateurs_unitesdecompte.pourcentage) AS somme_portefeuille
				FROM unitesdecompte
				INNER JOIN utilisateurs_unitesdecompte
				ON utilisateurs_unitesdecompte.id_unitedecompte = unitesdecompte.id
				WHERE unitesdecompte.date_donnees IS NOT NULL
				GROUP BY utilisateurs_unitesdecompte.id_utilisateur, utilisateurs_unitesdecompte.id_portefeuille
			) verif
			ON verif.id_utilisateur = utuc.id_utilisateur AND verif.id_portefeuille = utuc.id_portefeuille
			WHERE verif.somme_portefeuille = 100
			AND srri.srri != 0
			GROUP BY utuc.id_utilisateur, utuc.id_portefeuille, srri.srri"""

			cur.execute(sql_srri)
			resultat_srri = cur.fetchall()

			dict_srri = defaultdict(dict)
			for x in resultat_srri:
				dict_srri[str(x['id_utilisateur'])+'-'+str(x['id_portefeuille'])][x['srri']] = x['pourcentage_srri']
				
			sql_euro = """SELECT *
			FROM fondseuro"""

			cur.execute(sql_euro)
			resultat_euro = cur.fetchall()

			dict_euro = {str(x['id_utilisateur'])+'-'+str(x['id_portefeuille']):x['pourcentage'] for x in resultat_euro}

			trainingSet = []
			for key, value in dict_portefeuille.iteritems():
				fondseuro = dict_euro.get(key, -100)
				
				if key in dict_actif:
					actif_actionsgrandes = dict_actif[key].get('1-0', 0)
					actif_obligations = dict_actif[key].get('2', 0)
					actif_autres = dict_actif[key].get('1-1', 0) + dict_actif[key].get('1-2', 0) + dict_actif[key].get('1-3', 0) + dict_actif[key].get('3', 0) + dict_actif[key].get('4', 0) + dict_actif[key].get('5', 0) + dict_actif[key].get('6', 0) + dict_actif[key].get('7', 0)
				else:
					actif_actionsgrandes = 0
					actif_obligations = 0
					actif_autres = 0
				
				if key in dict_region:
					region_amnord = dict_region[key].get(1, 0) + dict_region[key].get(8, 0)
					region_europe = dict_region[key].get(3, 0) + dict_region[key].get(4, 0) + dict_region[key].get(7, 0)
					region_asie = dict_region[key].get(5, 0) + dict_region[key].get(6, 0)
					region_autres = dict_region[key].get(2, 0) + dict_region[key].get(9, 0) + dict_region[key].get(10, 0) + dict_region[key].get(11, 0) + dict_region[key].get(12, 0) + dict_region[key].get(13, 0) + dict_region[key].get(14, 0)
				else:
					region_amnord = 0
					region_europe = 0
					region_asie = 0
					region_autres = 0
				
				if key in dict_srri:
					srri_3 = dict_srri[key].get(1, 0) + dict_srri[key].get(2, 0) + dict_srri[key].get(3, 0)
					srri_4 = dict_srri[key].get(4, 0)
					srri_5 = dict_srri[key].get(5, 0)
					srri_6 = dict_srri[key].get(6, 0) + dict_srri[key].get(7, 0)
				else:
					srri_3 = 0
					srri_4 = 0
					srri_5 = 0
					srri_6 = 0
				
				portefeuille = value
				
				ligne = [float(fondseuro), float(actif_actionsgrandes), float(actif_obligations), float(actif_autres), float(region_amnord), float(region_europe), float(region_asie), float(region_autres), float(srri_3), float(srri_4), float(srri_5), float(srri_6), portefeuille]
				
				if id_membre+'-'+id_portefeuille == key:
					inputVector = ligne
				else:
					trainingSet.append(ligne)
			
			# array([0.33906031, 0.63767859, 0.00847313, 0.74032933, 0.87086862, 0.23206664, 0.86133424, 0.15793552, 0.05688105, 0.32388366, 0.21235204, 0.11345851]) 31.132075471698116 
			k = 1
			a = [0.339, 0.638, 0.008, 0.740, 0.871, 0.232, 0.861, 0.158, 0.057, 0.324, 0.212, 0.113]
			neighbors = getNeighbors(trainingSet, inputVector, k, a)
			result = getResponse(neighbors)
			if result == inputVector[-1]:
				print('<span class="border border-light px-1"><a data-toggle="tooltip" title="La composition se rapproche de celle d\'autres portefeuilles '+nom_portefeuilles[id_portefeuille]+'s. L\'algorithme utilisé est toutefois en cours d\'apprentissage." data-container="body"><i class="fa fa-check"></i> Semble cohérent</a></span>')
			else:
				print('<span class="border border-light px-1"><a data-toggle="tooltip" title="Des similitudes ont été trouvées avec la composition de certains portefeuilles '+nom_portefeuilles[str(result)]+'s, alors qu\'il s\'agit d\'un portefeuille '+nom_portefeuilles[id_portefeuille]+'. L\'algorithme utilisé est toutefois en cours d\'apprentissage." data-container="body"><i class="fa fa-bell"></i> Semble différent</a></span>')
		else:
			print('<span class="border border-light px-1"><a data-toggle="tooltip" title="Pour pouvoir utiliser cette fonctionnalité : 1) votre portefeuille doit d\'abord être complet 2) les informations relatives aux ISINs qui le composent doivent d\'abord être disponibles" data-container="body"><i class="fa fa-exclamation-triangle"></i> Vérification repoussée</a></span>')
	else:
		print('<span class="border border-light px-1">Type de portefeuille incorrect</span>')
else:
	print('<span class="border border-light px-1">Information impossible à traiter</span>')