<?php

error_reporting(E_ALL);

require_once __DIR__ . '/BurgerComponent.php';

require_once __DIR__ . '/Cheese.php';
require_once __DIR__ . '/Gollandian.php';
require_once __DIR__ . '/Russian.php';
require_once __DIR__ . '/Tvorog.php';

require_once __DIR__ . '/Bread.php';
require_once __DIR__ . '/Borobinsky.php';

require_once __DIR__ . '/Butter.php';
require_once __DIR__ . '/VoloskovePole.php';

require_once __DIR__ . '/Kotleta.php';
require_once __DIR__ . '/PigKotleta.php';

require_once __DIR__ . '/Sandwich.php';
require_once __DIR__ . '/Gamburger.php';

$bread = new Borobinsky();
$butter = new VoloskovePole();

$tvorogCheese = new Tvorog();
$gollandianCheese = new Gollandian();

$sandwich = new Sandwich($bread, $butter, $tvorogCheese);
$sandwich2 = clone $sandwich;
$sandwich2->__construct($bread, $butter, $gollandianCheese);

$kotleta = new PigKotleta();
$gamburger = new Gamburger($bread, $butter, $gollandianCheese, $kotleta);

echo $sandwich->create(), $gamburger->create();