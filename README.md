# bilemo
[![Maintainability](https://api.codeclimate.com/v1/badges/c96bdd0f7420b58d38b2/maintainability)](https://codeclimate.com/github/graboiide/bilemo/maintainability)
website : https://www.oc-p7.gregcodeur.fr

# BILEMO

Projet api openclassroom

## Installation

Clonez ou télécharger le projet github

```bash
git clone https://github.com/graboiide/bilemo.git
```
Pensez a modifier vos variables d'environnement dans le ficher .env

Installer les dépendances à l'aide de composer

```bash
composer install
```
Créez la base de données

```bash
php bin/console doctrine:database:create
```

Ajoutez les données

```bash
php bin/console doctrine:migrations:migrate
```
Inserez des données fictives 

```bash
php bin/console doctrine:fixtures:load
```

## Recupérer le token 

Appelez la route /api/login_check avec dans le corp de la page le json
```json
{"username":"adresse@mail.client" ,"password":"mot_de_passe_client"}
```

Copiez coller le token obtenu précédé du mot Bearer
(Bearer votre_ token) dans le header de la page pour la clé
Authorization avant chaque requêtes
