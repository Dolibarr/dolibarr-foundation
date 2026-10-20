# Association Dolibarr

**Dolibarr ERP CRM – Association à but non lucratif**
Rue de la Vallée, 45160 Olivet, France
TVA : FR87520339938 — https://www.dolibarr.org

---

# Bilan financier du projet « Certification Loi Finance »

**Projet :** Certification LNE du système de caisse Dolibarr (projet interne PJ2010-0001, tâche TK2010-0019 « Travaux Certification LNE »)
**Référence dossier LNE :** LNE21-QU2507-0131
**Périmètre :** Dolibarr 24+ et module « Journaux Inaltérables » (conformité loi de finances 2026, référentiel LNE v1.8)

## Introduction

Le présent document constitue le bilan financier du projet de certification « Loi Finance » mené par l'association Dolibarr sur le logiciel Dolibarr ERP CRM. Cette démarche vise à faire certifier par le LNE le système d'encaissement (caisse) de Dolibarr conformément aux exigences issues de la loi de finances (article 117, systémes de caisse sécurisés), rendant obligatoire la certification des logiciels gérant des encaissements de particuliers.

**Période couverte :** du 28 mai 2025 au 19 octobre 2026
**Date du bilan :** 19 octobre 2026

**Acteurs :**

| Acteur | Rôle |
| --- | --- |
| Laurent Destailleur | Chef de projet certification, développement, documentation, audits (association Dolibarr) |
| Daoud Mohamed | Travaux de préparation, tests, plans de test, audits (association Dolibarr) |
| Lucas Marcouiller | Travaux de préparation (association Dolibarr) |
| LNE | Organisme d'évaluation / laboratoire |
| BYCYB | Organisme certificateur (facturation des audits) |

**Principales étapes du projet :** préparation du dossier de recevabilité, mise en place des outils qualité (DigiQuali), développements sur le module Journaux Inaltérables (blockedlog, archivage, clôture de caisse, signature à distance), audits à blanc (février 2026), audit initial LNE (juin 2026) et traitement des non-conformités.

Les coûts du projet se décomposent en deux catégories :

1. **Frais directs** : prestations facturées par l'organisme certificateur (diagnostic, ATR et frais de déplacement) ;
2. **Frais indirects** : charge de travail interne (temps consacré par les membres de l'association), valorisée à partir du suivi des temps (fichier `timespent_20261019170621.csv`).

## 1. Frais directs

Les frais directs correspondent aux factures et à la facture proforma émises par **BYCYB** (organisme certificateur) pour le compte du LNE, dans le cadre du dossier LNE21-QU2507-0131. Chaque prestation facturée avait fait l'objet d'un acompte versé en septembre 2025 (devis DEV2510393, DEV2510397 et DEV2510400 du 09/09/2025), repris sur les factures.

| Facture | Date | Description | Montant HT | TVA 20 % | Montant TTC |
| --- | --- | --- | ---: | ---: | ---: |
| F-26-020489 (SI2602-4640) | 24/02/2026 | Système de caisse Dolibarr – Diagnostic 2512 | 5 076,00 € | 1 015,20 € | 6 091,20 € |
| F-26-020524 (SI2602-4641) | 27/02/2026 | Système de caisse Dolibarr – ATR 2512 | 1 811,00 € | 362,20 € | 2 173,20 € |
| F-26-060694 (SI2606-4730) | 11/06/2026 | Frais de déplacement de l'auditeur | 529,75 € | 105,95 € | 635,70 € |
| DEV2611361-V1 (proforma) | 24/09/2026 | Audit supplémentaire à distance (1 jour) – vérification des mesures correctives sur les non-conformités 1-2026 à 5-2026 et R26-1 à R26-6 | 2 615,00 € | 523,00 € | 3 138,00 € |
| **Total frais directs** | | | **10 031,75 €** | **2 006,35 €** | **12 038,10 €** |

> **Note :** la prestation « Audit initial » de la facture F-26-060694 (12 110,00 € HT) a été intégralement compensée par l'acompte déjà versé (devis DEV2510400) ; elle n'apparaît donc pas dans les frais directs. Les acomptes des devis DEV2510393 et DEV2510397 ont également été repris sur les factures F-26-020524 et F-26-020489. La facture proforma DEV2611361-V1 (audit supplémentaire suite à la décision du comité de lecture du 24/09/2026) est un engagement à régler à la commande, offre valable jusqu'au 24/12/2026.

## 2. Frais indirects (charge de travail interne)

Les frais indirects correspondent au temps investi par les membres de l'association sur le projet, valorisé à partir du fichier de suivi des temps (`timespent_20261019170621.csv`, 156 enregistrements sur la période du 28/05/2025 au 19/10/2026).

### 2.1 Résumé par personne

| Personne | Enregistrements | Heures | Taux horaire | Valeur |
| --- | ---: | ---: | ---: | ---: |
| Laurent Destailleur | 92 | 435,0 h | 100 €/h | 43 500,00 € |
| Daoud Mohamed | 57 | 321,5 h | 58 €/h | 18 962,00 € |
| Lucas Marcouiller | 7 | 38,0 h | 58 €/h | 2 204,00 € |
| **Total** | **156** | **794,5 h** | | **64 666,00 €** |

### 2.2 Résumé par type de tâche

| Type de tâche | Heures | Valeur |
| --- | ---: | ---: |
| Travaux de préparation / suivi projet (non qualifiés) | 330,0 h | 20 127,00 € |
| Documentation & dossiers LNE (recevabilité, SMC, maintenance, exploitation…) | 164,5 h | 16 450,00 € |
| Développement & débogage (Journaux Inaltérables, blockedlog, archivage, clôture de caisse, signature à distance…) | 142,0 h | 14 200,00 € |
| Audits & traitement des non-conformités (audit à blanc, audit initial LNE) | 85,0 h | 8 206,00 € |
| Tests & plans de test | 46,0 h | 2 983,00 € |
| Outils qualité (installation et développement DigiQuali) | 22,0 h | 2 200,00 € |
| Réunions & suivi (réunion ATR, échanges LNE, rédaction du bilan) | 5,0 h | 500,00 € |
| **Total** | **794,5 h** | **64 666,00 €** |

### 2.3 Répartition temporelle

- **Mai – août 2025** : préparation du dossier de recevabilité, travaux de fondation (docs, DigiQuali) — environ 380 h ;
- **Octobre 2025 – avril 2026** : itérations sur la recevabilité, développements majeurs (archivage des journaux, clôture de caisse, signature à distance), audits à blanc (19–20/02/2026) et plans de test ;
- **Mai – juin 2026** : audit initial LNE (1–5 juin 2026) et traitement des non-conformités (15–19 juin 2026) ;
- **Septembre – octobre 2026** : suivi de la certification et rédaction du présent bilan.

## 3. Total général

| Catégorie | Montant HT | Part |
| --- | ---: | ---: |
| Frais directs (certification BYCYB/LNE) | 10 031,75 € | 13,4 % |
| Frais indirects (charge de travail interne) | 64 666,00 € | 86,6 % |
| **Coût total du projet** | **74 697,75 €** | **100 %** |

**TTC, les frais directs représentent 12 038,10 €**, soit un coût total TTC de 76 704,10 €.

### Conclusion

Le coût total du projet de certification Loi Finance s'élève à environ **74 700 € HT**, dont près de 86 % correspondent à la charge de travail interne bénévole ou salariée de l'association. Ce chiffre illustre l'ampleur de l'effort de conformité (développement, documentation, qualité, audits) au-delà des seuls honoraires de l'organisme certificateur. Il pourra servir de référence pour les futures démarches de conformité (renouvellements, évolutions du référentiel LNE).

---

*Sources : factures BYCYB F-26-020489, F-26-020524 et F-26-060694 ; facture proforma DEV2611361-V1 ; fichier de suivi des temps `timespent_20261019170621.csv` ; dossier LNE21-QU2507-0131.*
