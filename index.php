<?php
require_once("data.php");
require_once("functions.php");
?><!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jabe's MH Stats</title>
    <link rel="stylesheet" href="style.css">
    <script type="text/javascript" src="scripts.js" defer></script>
    <meta property="og:title" content="Jabe's MH Stats" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://gaben.link/mh/" />
    <meta property="og:image" content="https://gaben.link/mh/img/kutku-cham-palico.png" />
    <meta property="og:description" content="S&S GOAT" />
  </head>
  <body>
    <h1>Jabe's MH Stats</h1>
    <ul>
    <?php foreach ($data as $game) { ?>
      <li>
        <a href="#<?= $game["id"] ?>"><?= $game["name"] ?></a>
        <ul class="sub-toc">
          <li><a href="#<?= $game["id"] ?>_weapons">Weapon Usage</a></li>
          <li><a href="#<?= $game["id"] ?>_images">Images</a></li>
        </ul>
      </li>
    <?php } ?>
    </ul>
    
    <hr>
    
    <p style="margin: 10px">Note: Click a weapon row to highlight that weapon type</p>
    
    <hr>

    <?php
    $total_weapon_usage = [];
    display_games($data);
    ?>
  </body>
</html>