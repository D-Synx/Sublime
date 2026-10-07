# Contribuer à Sublime

PHP ≥ 8.3 et Composer sont nécessaires. Le périmètre 1.0 est un petit constructeur HTML, avec une syntaxe directe et des conversions prévisibles.

## Préparer le projet

```bash
git clone https://github.com/D-Synx/Sublime.git
cd Sublime
git checkout codex/sublime-1.0-foundation
composer install
```

## Vérifier une modification

```bash
composer test
composer cs
composer stan
composer validate --strict
composer dump-autoload --optimize --strict-psr
```

`composer cs-fix` applique le style. La CI vérifie PHP 8.3, 8.4 et 8.5 ; les tests exécutent le démarrage rapide du README et les exemples.

Ajoutez un test de comportement pour une correction ou une nouvelle règle de conversion. Conservez la simplicité de `Sublime(body_(...))`, sans callback obligatoire. Documentez toute modification du contrat public et expliquez les éventuelles ruptures de compatibilité.

## Proposer une contribution

Travaillez sur une branche dédiée et ouvrez une pull request décrivant le problème, le comportement obtenu et les vérifications effectuées. Une fusion, un tag ou une publication restent des étapes distinctes de la préparation du code.
