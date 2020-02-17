#!/usr/bin/python
#-*- coding: utf-8 -*-

print 'Content-Type: text/html;charset=utf-8'
print ''
# Là commence votre code.
import cgi
import MySQLdb
import numpy as np
import math
import json
import urllib
from pprint import pprint

def texte_recommandations(id_membre, id_portefeuille, resultat, dict_isin_unitedecompte, dict_nom_utilisateur, dict_reco_utilisateur):
	liste_id_utilisateur = sorted(set([x['id_utilisateur'] for x in resultat if x['id_portefeuille']==id_portefeuille]))
	liste_id_unitedecompte = sorted(set([x['id_unitedecompte'] for x in resultat if x['id_portefeuille']==id_portefeuille]))

	dict = {}						
	for valeur in liste_id_utilisateur:
		dict[valeur] = {x['id_unitedecompte']:x['pourcentage'] for x in resultat if x['id_utilisateur']==valeur and x['id_portefeuille']==id_portefeuille}

	tableau = []
	for valeur in liste_id_utilisateur:
		tableau_ligne = [dict[valeur].get(x, 0) for x in liste_id_unitedecompte]
		tableau.append(tableau_ligne)
		
	matrice = np.array(tableau)
	
	if id_membre in liste_id_utilisateur:
		index_utilisateur = liste_id_utilisateur.index(id_membre)
		portefeuille_utilisateur = matrice[index_utilisateur]
		
		if np.sum(portefeuille_utilisateur) == 100:
			corr_utilisateur = np.zeros(len(liste_id_utilisateur))
			for i in range(len(liste_id_utilisateur)): 
				if i != index_utilisateur:
					portefeuille_a_comparer = matrice[i]
					for j in range(len(liste_id_unitedecompte)):
						if portefeuille_utilisateur[j] != 0 and portefeuille_a_comparer[j] != 0:
							corr_utilisateur[i] += 2-math.fabs(portefeuille_utilisateur[j]-portefeuille_a_comparer[j])/100

			if np.sum(corr_utilisateur) != 0:
				dict_corr_utilisateur = {i: v for i, v in enumerate(corr_utilisateur) if (not math.isnan(v) and v>0 and i!=index_utilisateur)}
				# dict_corr_utilisateur = dict((i, v) for i, v in enumerate(corr_utilisateur) if (not math.isnan(v) and v>0 and i!=index_utilisateur))
				recommandations = np.zeros(len(liste_id_unitedecompte));
				somme_corr = 0

				for index, valeur in dict_corr_utilisateur.iteritems():
					recommandations += (dict_reco_utilisateur.get(liste_id_utilisateur[index])+1)*valeur*matrice[index]
					somme_corr += (dict_reco_utilisateur.get(liste_id_utilisateur[index])+1)*valeur
				
				recommandations /= somme_corr
				
				dict_recommandations = {i: v for i, v in enumerate(recommandations) if (v>0 and portefeuille_utilisateur[i]==0)}
				# dict_recommandations = dict((i, v) for i, v in enumerate(recommandations) if (v>0 and pourcentage_utilisateur[i]==0))
				
				texte = ''
								
				i = 1
				for index, valeur in sorted(dict_recommandations.iteritems(), key=lambda x: x[1], reverse=True):
					if i <= 3:
						if i > 1:
							texte += ' '
						texte += '['
						texte += '<a class="sans_decoration" style="cursor: pointer; cursor: hand;" tabindex="0" data-toggle="popover" data-trigger="focus" data-html="true"  title="'
						texte += '<a href=\'testeur.php?pct['+dict_isin_unitedecompte.get(liste_id_unitedecompte[index]).encode("utf-8")+']=100\'>'+cgi.escape(dict_nom_unitedecompte.get(liste_id_unitedecompte[index])).encode("utf-8")+'</a>'
						texte += '" data-content="'
						texte += 'Portefeuilles similaires avec cet ISIN:'
						j = 1
						for index2, valeur2 in sorted(dict_corr_utilisateur.iteritems(), key=lambda x: x[1], reverse=True):
							if j <= 3:
								if matrice[index2][index] > 0:
									texte += '<br />'
									texte += '- <a href=\'/membres/'+urllib.quote_plus(dict_nom_utilisateur.get(liste_id_utilisateur[index2])).encode("utf-8")+'\'>'+dict_nom_utilisateur.get(liste_id_utilisateur[index2]).encode("utf-8")+'</a>'
									j += 1
						texte += '">'
						texte += dict_isin_unitedecompte.get(liste_id_unitedecompte[index]).encode("utf-8")
						texte += '</a>'
						texte += ']'
						i += 1
			else:
				texte = 'Nombre de portefeuilles similaires trop faible.'
		else:
			texte = 'Portefeuille incomplet.'
	else:
		texte = 'Portefeuille vide.'
		
	return texte
	
form = cgi.FieldStorage()
id_membre = form.getvalue('id');
portefeuilles = form.getvalue('port');
tableau_texte_recommandations = [];
if id_membre is not None and portefeuilles is not None:
	portefeuilles = portefeuilles.split("|")
	if id_membre.isdigit() and all(item.isdigit() for item in portefeuilles):
		id_membre = int(id_membre)
		portefeuilles = [int(i)+1 for i in portefeuilles]
	
		paramMysql = {
			'host'   : '*****',
			'user'   : '*****',
			'passwd' : '*****',
			'db'     : '*****',
			'charset': 'utf8'
		}

		conn = MySQLdb.connect(**paramMysql)
		cur = conn.cursor(MySQLdb.cursors.DictCursor)
		
		sql3 = """SELECT ut.id, ut.nom, IFNULL(rec.nbr_recommandations,0) AS nbr_recommandations 
		FROM utilisateurs ut 
		LEFT JOIN
		(
			SELECT id_recommandation, COUNT(*) AS nbr_recommandations
			FROM recommandations
			GROUP BY id_recommandation
		) rec
		ON rec.id_recommandation = ut.id"""

		cur.execute(sql3)
		resultat3 = cur.fetchall()
		dict_nom_utilisateur = {x['id']:x['nom'] for x in resultat3}
		dict_reco_utilisateur = {x['id']:x['nbr_recommandations'] for x in resultat3}
		
		if id_membre in dict_nom_utilisateur:
			sql = """SELECT * FROM utilisateurs_unitesdecompte"""

			cur.execute(sql)
			resultat = cur.fetchall()

			sql2 = """SELECT id, isin, nom FROM unitesdecompte"""

			cur.execute(sql2)
			resultat2 = cur.fetchall()
			dict_isin_unitedecompte = {x['id']:x['isin'] for x in resultat2}
			dict_nom_unitedecompte = {x['id']:x['nom'] for x in resultat2}
			
			for valeur in portefeuilles:
				tableau_texte_recommandations.append(texte_recommandations(id_membre, valeur, resultat, dict_isin_unitedecompte, dict_nom_utilisateur, dict_reco_utilisateur))
		else:
			tableau_texte_recommandations.append('Membre inexistant.')
	else:
		tableau_texte_recommandations.append('Le membre et les portefeuilles doivent être fournis sous forme d\'entiers.')
else:
	tableau_texte_recommandations.append('Un membre et des portefeuilles doivent être fournis.')

print(json.dumps(tableau_texte_recommandations))