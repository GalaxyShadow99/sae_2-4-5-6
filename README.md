# Viking Transport - Application de Gestion d'un réseau de transport en commun

<span style="color:red;"> <a href="https://sae2-456.xune.app/index.php" target="_blank"> Vous pouvez visualiser notre projet ICI ! </a> </span>

Ce projet a été réalisé dans le cadre de la première année de BUT Informatique (IUT) à l'Université de Caen. Développé en équipe, l'objectif était de concevoir une application web dynamique complète pour pouvoir gérer tout un réseau de transport normand fictif. 

> Dans ce projet, j'ai surtout joué un rôle de « chef de projet », dans le sens où je répartissais les tâches à venir et m'assurais que tout le monde arrive à travailler dans de bonnes conditions !
> J'ai aussi rapidement été désigné comme « chef du dépôt GIT », j'ai donc appris à gérer des *merge conflicts* et autres réjouissances d'un outil aussi puissant que GIT.
> Enfin, j'ai également assuré avec Nathan la présentation et la démo technique devant un amphi regroupant tous les membres de ma promo ainsi que les jurys pour notre évaluation.

---

## Fonctionnalités Implémentées

L'application se divise en deux grands espaces fonctionnels entièrement opérationnels :

### Espace Voyageurs (Grand Public)
* **Calcul d'Itinéraires Avancé :** Recherche multicritère permettant de trouver les trajets les plus courts (en distance) ou les plus rapides (en durée) entre deux communes.
* **Système Horaires Réels :** Suivi étape par étape du parcours d'un véhicule avec calcul automatique des heures de passage de station en station.
* **Réservation et Billetterie Multi-segments :** Module d'achat permettant de réserver un trajet complet, y compris lorsqu'il implique des correspondances entre plusieurs lignes de bus.
* **Fidélisation et Gestion de Cagnotte :** Attribution automatique de points en fonction de la distance parcourue, calcul de paliers de fidélité et conversion de points en réductions tarifaires.
* **Cartographie Interactive :** Visualisation dynamique du réseau, des lignes et des points d'arrêt sur une carte géographique.

### Espace Administration (Back-Office)
* **Gestion du Réseau :** Possibilité d'ajouter, de modifier ou de supprimer dynamiquement des lignes, des arrêts et d'éditer les grilles horaires.
* **Gestion de la Base Clients :** Module de modification et d'anonymisation des données clients, incluant des mécanismes d'alerte et de purge pour les comptes inactifs.
* **Tableau de Bord Statistique :** Visualisation des indicateurs de performance du réseau (chiffre d'affaires par ligne, statistiques de fréquentation, heures de pointe, top clients).

---

## Technologies et Outils Utilisés

Afin d'assimiler au mieux les couches fondamentales du développement web, le projet a été conçu sans framework back-end :

* **Back-End :** PHP 8 (programmation modulaire, sécurisation des formulaires, gestion des sessions et Base de données Oracle SQL).
* **Base de Données :** Oracle SQL (requêtes complexes, jointures multiples, fonctions d'agrégation, transactions).
* **Front-End :** HTML5, CSS3, JS, et framework Bootstrap pour le Responsive Design.
* **Composants Avancés :** API JavaScript Leaflet pour l'intégration et la manipulation de la carte interactive.
* **Gestion de Version :** Git et GitHub pour le suivi du code et le travail collaboratif.

---

## Compétences Techniques Acquises

La réalisation de ce projet d'envergure m'a permis d'acquérir et de consolider des compétences essentielles pour un développeur :

* **Lier une Base de Données à un Site Dynamique :** Conception et mise en œuvre de la communication entre le serveur web PHP et la base de données Oracle. Extraction de données relationnelles pour générer dynamiquement du contenu HTML (listes de lignes, profils utilisateurs, historiques).
* **Sécurisation et Traitement des Données :** Utilisation de PDO pour l'exécution de requêtes préparées afin de bloquer les injections SQL. Gestion rigoureuse des formulaires et des données provenant de l'utilisateur.
* **Gestion de Transactions SQL complexes :** Implémentation de mécanismes d'écriture sécurisés (systèmes de Commit et Rollback) pour garantir l'intégrité et la cohérence de la base de données lors d'opérations critiques (achats de billets, suppressions en cascade).
* **Mise en Application Algorithmique :** Exploitation et adaptation d'algorithmes de théorie des graphes (Dijkstra) directement en PHP pour calculer des chemins optimaux à partir des segments stockés en base de données.
* **Dynamisation Client (JavaScript) :** Manipulation du DOM pour concevoir des fonctionnalités fluides, telles que l'autocomplétion dynamique des gares de départ et d'arrivée, et la liaison entre les données géographiques (coordonnées GPS) et la carte Leaflet.

---

## Compétences Méthodologiques et Travail d'Équipe
Pendant ce projet, j'ai notamment appris à : 
* **Collaborer au sein d'une équipe de dev :** Répartition des modules en fonction des spécialités de chacun, réunions de suivi et synchronisation des objectifs.
* **Git :** Utilisation stricte des branches pour le développement de fonctionnalités, gestion des revues de code (Pull Requests) et résolution méthodique des conflits de fusion.
* **Rigueur de Spécification :** Analyse approfondie d'un cahier des charges pour livrer un produit fini et conforme aux besoins clients dans les temps impartis.

Je n'aurais jamais pu écrire cette application entière seul évidemment, un grand merci donc aux membres de mon groupe !

* **AHMADI Mohammad Elyas** — Développeur PHP & Conception générale
* **CHAIGNON Nathan** — Codéveloppeur Back-end
* **CHUQUET Anaël** — Architecte Logiciel & Intégration Front-end
* **COLLET Léo** — Designer d'interface (UI)
* **CONSTANTIN Thomas** — Architecte Global & Développement Back-end
> Aussi « chef de projet » et chef de la gestion de git et des merge conflicts :D
* **GRENECHE Mathéo** — Maquettage & Intégration Front-end
* **GUILBERT Joan** — Développeur PHP & Élaboration des Requêtes SQL Complexes
* **PRENVEILLE Noé** — Carte interactive & Base de Données
