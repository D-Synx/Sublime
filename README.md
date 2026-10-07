# Sublime

**Du HTML simple, écrit en PHP.**

Un petit constructeur HTML : composez vos balises, passez votre contenu à `Sublime`, récupérez une chaîne HTML. Pas de moteur de templates à apprendre, aucune dépendance à l’exécution.

**Version 1.0 en préparation · PHP ≥ 8.3 · licence MIT**

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use function Sublime\{Sublime, body_, div_, p_};

echo Sublime(body_(
    data: div_(class: 'app', data: p_('Hello'))
));
```

Résultat exact :

```html
<body><div class="app"><p>Hello</p></div></body>
```

## Un enfant, une expression

Un seul enfant s’écrit directement. Plusieurs enfants s’écrivent dans un tableau ; leur ordre est conservé.

```php
echo Sublime(div_(class: 'app', data: p_('Hello')));

echo Sublime(div_(class: 'app', data: [
    p_('One'),
    p_('Two'),
]));
```

Les arguments positionnels sont aussi du contenu : `p_('Hello')`, `div_('Hello', p_('World'))`. L’argument `data:` désigne le contenu ; un attribut HTML de données s’écrit par exemple `...['data-id' => 42]`.

## Le mode HTML

HTML est le mode par défaut. Pour l’expliciter :

```php
echo Sublime(
    class: 'html',
    data: body_(data: div_(class: 'app', data: p_('Hello')))
);
```

`class: 'html'` sélectionne le mode de rendu de Sublime ; `div_(class: 'app')` définit une classe CSS. Chaque argument appartient à son propre appel.

Ces deux formes ne demandent ni `fn`, ni `callback:`, ni instance de `TagFactory`. En 1.0, seul le mode `'html'` existe ; les autres noms sont rejetés. Sublime crée sa fabrique en interne. PHP évalue d’abord les appels aux helpers, puis Sublime rend l’arbre construit.

PHP impose de placer les arguments positionnels avant les arguments nommés. Utilisez `Sublime(body_(...), class: 'html')` ou la forme entièrement nommée ci-dessus. Le contenu est obligatoire.

## Installation de cette version

Cette branche prépare 1.0 ; aucune publication Packagist ou étiquette 1.0.0 n’est annoncée ici. Pour essayer le projet et lancer ses tests, avec PHP ≥ 8.3 et Composer :

```bash
git clone https://github.com/D-Synx/Sublime.git
cd Sublime
git checkout codex/sublime-1.0-foundation
composer install
php examples/basic.php
```

`vendor/autoload.php` charge les classes **et** les fonctions. Aucun deuxième include n’est nécessaire. Le fichier `src/Sublime.php` peut aussi être chargé seul pour un prototype.

## Valeurs du contenu

Les mêmes règles s’appliquent à `data:`, aux enfants positionnels, à `fragment()` et au résultat d’un callback de compatibilité.

| Valeur | Rendu |
|---|---|
| `HtmlElement` | Élément HTML imbriqué |
| `RawHtml` | HTML fourni tel quel |
| `string`, `int`, `float` | Texte échappé |
| `Stringable` | Conversion en texte une seule fois, puis échappement |
| `null`, `false` | Rien |
| `true` | `1` |
| Tableau ou `Traversable` fini | Enfants aplatis dans l’ordre, clés ignorées |
| Autre objet, ressource, closure comme enfant | `InvalidArgumentException` |

Les itérateurs sont consommés à la construction. La limite est de 128 conteneurs imbriqués ; les cycles et dépassements sont rejetés. Fournissez uniquement des itérateurs finis.

`Sublime()` accepte directement un `HtmlElement`, un `RawHtml` ou `null`. Pour rendre du texte ou plusieurs éléments sans balise englobante, utilisez `fragment(...)`.

## Attributs lisibles

```php
use function Sublime\{div_, input_};

echo div_(
    ...['aria-hidden' => false, 'data-ready' => true],
    class: ['app' => true, 'active' => true, 'hidden' => false],
    style: ['color' => 'red', 'margin-top' => 0],
    data: 'Hello'
);
// <div aria-hidden="false" data-ready="true" class="app active" style="color:red;margin-top:0">Hello</div>

echo input_(disabled: true, required: false);
// <input disabled>
```

| Attribut | Valeurs acceptées |
|---|---|
| Ordinaire | `string`, `int`, `float`, `Stringable` ; `null`/`false` omettent l’attribut |
| Booléen HTML reconnu | `true` produit le nom seul ; `false`/`null` l’omettent ; autres valeurs rejetées |
| `aria-*`, `data-*` | Règles ordinaires, plus booléens rendus `"true"`/`"false"` |
| `class` | Chaîne, liste de chaînes, ou tableau associatif `nom => bool` |
| `style` | Chaîne ou tableau associatif `propriété => string|int|float` ; `null`/`false`/`''` omis dans ce tableau |

Les noms de classe vides sont omis dans les tableaux. Les tableaux mixtes de classes et les structures imbriquées de classes/styles sont rejetés. Les attributs ordinaires n’acceptent pas de tableaux, d’objets non `Stringable` ou de `true`. Les noms d’attributs sont normalisés en minuscules. Les valeurs `Stringable` sont figées à la construction.

Booléens reconnus : `disabled`, `readonly`, `required`, `checked`, `selected`, `multiple`, `autofocus`, `autoplay`, `controls`, `loop`, `muted`, `open`, `reversed`, `novalidate`, `formnovalidate`, `async`, `defer`, `ismap`, `itemscope`, `allowfullscreen`, `inert`, `nomodule`, `playsinline`, `default`. Un attribut énuméré tel que `hidden` ou `contenteditable` se passe avec une chaîne, pas un booléen.

## Échappement et limites de sécurité

Le texte et les valeurs d’attributs sont échappés en UTF-8 avec `ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE`. Les octets UTF-8 invalides sont remplacés ; une apostrophe devient `&apos;`. Passez du texte brut : les entités déjà encodées sont à nouveau échappées.

Les noms invalides et les attributs `on*` sont rejetés. Les enfants des balises vides, comme `img` et `input`, sont rejetés au lieu d’être perdus.

Sur `href`, `src`, `action` et `formaction`, Sublime rejette les préfixes `javascript:`, `vbscript:` et `data:text/html` sans tenir compte de la casse et des espaces/contrôles ASCII. Cela reste une vérification ciblée, pas une liste de destinations autorisées.

`raw_html()` est une échappatoire pour du **HTML de confiance**. Les valeurs CSS, le contenu de `script`/`style` et les attributs qui interprètent du HTML comme `srcdoc` demandent leur propre politique de confiance. Sublime n’est pas un assainisseur HTML/CSS, ni un encodeur JavaScript, ni un validateur de conformité du document.

```php
use function Sublime\{div_, raw_html};

echo div_(data: [
    '<Texte échappé>',
    raw_html('<strong>HTML de confiance</strong>'),
]);
```

## Composer des composants

Un composant peut simplement être une fonction qui retourne un `HtmlElement` :

```php
use Sublime\HtmlElement;
use function Sublime\{Sublime, section_, h2_, p_};

function card(string $title, string $text): HtmlElement
{
    return section_(class: 'card', data: [
        h2_($title),
        p_($text),
    ]);
}

echo Sublime(card('Simple', 'Un seul enfant sans tableau.'));
```

Le trait facultatif `Component` fournit `__toString()` à une classe implémentant `render(): HtmlElement`. Pour imbriquer sa structure HTML, passez `$component->render()` ; l’objet lui-même est traité comme du texte `Stringable` et échappé.

## Rendu et copies

- `$element->render()` et `(string) $element` retournent le HTML ; le résultat est mis en cache.
- `$element->stream()` fournit les morceaux du même HTML, sans cache de sortie. Il ne rend pas la collecte des enfants paresseuse.
- `fragment(...)` rend du contenu sans balise supplémentaire.
- `document(html_(...))` ajoute `<!DOCTYPE html>` suivi d’un saut de ligne.
- `withChildren(...)` ajoute des enfants sur une nouvelle instance.
- `withAttributes([...])` ajoute/remplace des attributs sur une nouvelle instance ; `null` retire un attribut. L’original reste inchangé.

## Fabrique explicite et compatibilité

Pour un usage dynamique, `TagFactory` reste disponible :

```php
use Sublime\TagFactory;
use function Sublime\Sublime;

$tags = new TagFactory();
echo Sublime($tags->body(
    data: $tags->div(class: 'app', data: $tags->p('Hello'))
));
```

Ses méthodes `p()` et `p_()` correspondent au helper `p_()`. `$tags->tag('my-card', ...)` et `_tag('my-card', ...)` créent des balises personnalisées ; `raw()`, `fragment()` et `document()` sont aussi disponibles.

Les anciens appels `Sublime($callback)` sont conservés. Un callback déclare zéro paramètre ou un paramètre non typé/`TagFactory` (nullable admis), auquel Sublime fournit la fabrique. Les fonctions nommées, méthodes et objets invocables suivent la même règle. Les autres types, signatures variadiques, références et plusieurs paramètres sont rejetés avant invocation. Les exceptions du callback restent propagées.

`sublime_($callback)` reste un alias historique déprécié. PHP ignore la casse des noms de fonction : `sublime()` et `Sublime()` désignent déjà la même fonction.

## Tests et exemples

```bash
composer test
composer cs
composer stan
composer validate --strict
composer dump-autoload --optimize --strict-psr
php examples/basic.php
php examples/components.php
php examples/conditions.php
php examples/conditions.php admin empty
php index.php
```

La CI vérifie PHP 8.3, 8.4 et 8.5 : PHPUnit, PHPStan, règles de code et autoload Composer optimisé. Le démarrage rapide et les exemples sont exécutés par les tests. `composer cs-fix` applique les corrections de style.

## Périmètre 1.0 et suite

Cette version couvre la composition HTML côté serveur, les types documentés, l’échappement, les attributs, les composants PHP et les surfaces de rendu. Elle n’inclut ni routage, ni cache applicatif, ni hydratation, ni validation complète du standard HTML.

Après 1.0 : publication Composer, puis étude de nouveaux modes et de meilleurs outils pour l’éditeur, en gardant cette syntaxe simple.

[Contribuer](CONTRIBUTING.md) · [Sécurité](SECURITY.md) · [Changements](CHANGELOG.md) · [Licence MIT](LICENSE)
