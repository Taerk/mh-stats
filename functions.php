<?php
function display_games($data) {
  $weapon_totals = [];
  $prev_weapon_totals = NULL;
  $prev_game_id = NULL;

  foreach ($data as $key=>$game) { ?>
    <section id="<?= $game["id"] ?>" class="game-section">
      <h2><?= $game["name"] ?> <a class="anchor" href="#<?= $game["id"] ?>">🔗</a></h2>
      <table class="game-data">
        <tr>
          <th>Play Time</th>
          <?php if (is_used($game["hr"])) { ?><th>Hunter Rank</th><?php } ?>
          <?php if (is_used($game["mr"])) { ?><th>Master Rank</th><?php } ?>
          <?php if (is_used($game["hunter_title"])) { ?><th>Hunter Title</th><?php } ?>
        </tr>
        <tr>
          <td class="data-playtime"><?= $game["playtime"] ?></td>
          <?php if (is_used($game["hr"])) { ?><td class="data-hunter-rank"><?= $game["hr"] ?></td><?php } ?>
          <?php if (is_used($game["mr"])) { ?><td class="data-master-rank"><?= $game["mr"] ?></td><?php } ?>
          <?php if (is_used($game["hunter_title"])) { ?><td class="data-hunter-title"><?= $game["hunter_title"] ?></td><?php } ?>
        </tr>
      </table>

      <?php if ($game["prog_context"]) { ?>
      <section id="<?= $game["id"] ?>_notes" class="subsection note-section">
        <h3>Progression Context</h3>
        <div class="section-container">
          <p><?= $game["prog_context"] ?></p>
        </div>
      </section>
      <?php } ?>

      <section id="<?= $game["id"] ?>_weapons" class="subsection weapon-section">
        <h3>Weapon Usage</h3>
        <div class="section-container">
        <?php
        increment_totals($weapon_totals, $game["weapons"]);
        // Compare to prev MH game
        if ($prev_game_id !== NULL) {
          $prev_game_data = find_data($data, $prev_game_id);
          echo show_weapon_usage("Compared to Prev Played Game" . ($prev_game_id ? strtoupper(" ($prev_game_id)") : ""), $game["weapons"], $prev_game_data["weapons"] ?? $prev_game_data ?? [], 20, 1);
        }

        // Compare to similar entry
        if ($game["prev"] !== NULL && $game["prev"] != $prev_game_id) {
          $prev_similar_game_weapon_data = find_data($data, $game["prev"]);
          echo show_weapon_usage("Compared to Prev Similar Entry (" . strtoupper($game["prev"]) .")", $game["weapons"], $prev_similar_game_weapon_data["weapons"] ?? [], 20, 1);
        }
        
        // Compare to all MH games
        echo show_weapon_usage("All Games So Far", $weapon_totals, $prev_weapon_totals ?? [], 3, 1);
        $prev_weapon_data = $game["weapons"];
        $prev_weapon_totals = $weapon_totals;
        ?>
        </div>
      </section>

      <section id="<?= $game["id"] ?>_images" class="subsection image-section">
        <h3>Images</h3>
        <div class="section-container">
        <?php foreach (get_images($game["id"], $game["images"]) as $image) { ?>
        <a href="<?= $image["src"] ?>"><img src="<?= $image["src"] ?>" alt="<?= $image["title"] ?>" title="<?= $image["title"] ?>"></a>
        <?php } ?>
        </div>
      </section>

      <?php if (!empty($game["sets"] ?? NULL)) { ?>
      <section id="<?= $game["id"] ?>_armor" class="subsection armor-section">
        <h3>Armor Sets</h3>
        <div class="section-container">
        <?php
        foreach ($game["sets"] ?? [] as $set_info) {
          echo set_info($set_info);
        }
        ?>
        </div>
      </section>
      <?php } ?>
    </section>
    <?php
    $prev_game_id = $game["id"];
  }
}

function show_weapon_usage($table_title, $weapon_data, $prev_weapon_data, $dull_threshold = 0, $display_threshold = 0) {
  $output = '';
  // $weapon_data = array_merge(get_empty_weapons_array(), $weapon_data);
  arsort($weapon_data);
  arsort($prev_weapon_data);
  $usage_sum = array_sum($weapon_data);
  $prev_usage_sum = array_sum($prev_weapon_data);

  $output .= '<table class="weapon-usage">';
  $output .= '<thead>';
  $output .= '<tr>';
  $output .= '<th class="game-span-def" colspan="' . (!empty($prev_weapon_data) ? "6" : "5") . '">' . $table_title . '</th>';
  $output .= '</tr>';
  $output .= '<tr>';
  $output .= '<th class="align-right">Pos</th>';
  $output .= '<th></th>';
  if (!empty($prev_weapon_data))
    $output .= '<th class="align-right">Change</th>';
  $output .= '<th class="align-right">Usage</th>';
  $output .= '<th class="align-right">Use%</th>';
  if (!empty($prev_weapon_data))
    $output .= '<th class="align-right"><span class="tooltip-border" title="Only shows changes over ' . $display_threshold . '% with emphasis on changes ' . $dull_threshold . '% or more">Change%</span></th>';
  $output .= '</tr>';
  $output .= '</thead>';

  $output .= '<tbody>';
  // Show usage for individual weapons
  foreach ($weapon_data as $id=>$usage) {    
    // Ignore unused weapons
    if ($usage === NULL)
      continue;

    $find_weapon_pos = array_search($id, array_keys($weapon_data));
    $weapon_pos = $find_weapon_pos + 1;
    $find_prev_weapon_pos = array_search($id, array_keys($prev_weapon_data));
    if ($find_prev_weapon_pos === false) {
      $weapon_pos_change = 0;
    } else {
      $prev_weapon_pos = $find_prev_weapon_pos + 1;
      $weapon_pos_change = ($weapon_pos != $prev_weapon_pos ? $prev_weapon_pos - $weapon_pos : 0);
    }

    $output .= '<tr class="weapon-row wep-type-' . $id . '">';
    $output .= '<td class="weapon-usage-pos align-right">' . $weapon_pos . '</td>';
    $output .= '<td class="weapon-usage-image"><img src="img/icons/' . $id . '.png" class="mh-icon" alt="' . $id . '" title="' . ucwords(str_replace("_", " ", $id)) . '"></td>';
    if (!empty($prev_weapon_data))
      $output .= '<td class="weapon-usage-pos-change align-right">' . display_change($weapon_pos_change, 0, 1, "", "") . '</td>';
    $output .= '<td class="weapon-usage-absolute align-right">' . ($usage ?? "n/a") . '</td>';
    $percentage_usage = $usage / $usage_sum * 100;
    $output .= '<td class="weapon-usage-percent align-right">' . number_format($percentage_usage, 1) . '%</td>';
    if (!empty($prev_weapon_data)) {
      $prev_percentage_usage = ($prev_weapon_data[$id] ?? 0) / ($prev_usage_sum + 0.001) * 100;
      $change_in_usage = $percentage_usage - $prev_percentage_usage;
      $output .= '<td class="weapon-usage-change align-right">' . display_change(number_format($change_in_usage), $dull_threshold, $display_threshold, "", "%") . '</td>';
    }
    $output .= '</tr>';
  }
  $output .= '</tbody>';
  $output .= '</table>';
  return $output;
}

function is_used($value) {
  return $value && $value >= 0;
}

function display_change($value, $dull_threshold = 0, $display_threshold = 1, $prepend = "", $append = "") {
  if ($value >= $display_threshold)
    return '<span class="usage-change-up change-' . ($value >= $dull_threshold ? "large" : "small") . '">' . $prepend . $value . $append . ' <span class="change-icon">&#9650;</span></span>';

  if ($value <= ($display_threshold * -1))
    return '<span class="usage-change-down change-' . ($value <= ($dull_threshold * -1) ? "large" : "small") . '">' . $prepend  . $value . $append . ' <span class="change-icon">&#9660;</span></span>';

  return '<span class="usage-change-none change-small"><span class="change-icon">&#149;</span></span>';
}

function get_empty_weapons_array() {
  return [
    "great_sword" => 0,
    "long_sword" => 0,
    "sword_shield" => 0,
    "dual_blades" => 0,
    "hammer" => 0,
    "hunting_horn" => 0,
    "lance" => 0,
    "gunlance" => 0,
    "switch_axe" => 0,
    "charge_blade" => 0,
    "insect_glaive" => 0,
    "light_bowgun" => 0,
    "heavy_bowgun" => 0,
    "bow" => 0,
    "prowler" => 0
  ];
}

function increment_totals(&$weapon_totals, $current_game_weapon_usage) {
  foreach ($current_game_weapon_usage as $id=>$usage) {
    // Skip weapons that didn't exist yet
    if ($usage === NULL)
      continue;
    
    if (!isset($weapon_totals[$id]))
      $weapon_totals[$id] = 0;

    $weapon_totals[$id] += $usage;
  }
}

function get_images($id, $specified_images = []) {
  $existing_images = [];
  $image_library = [];

  foreach($specified_images as $image_info) {
    array_push($image_library, $image_info);
    array_push($existing_images, $image_info["src"]);
  }

  foreach(["playtime", "guildcard", "hunter_profile", "armor", "weapon_usage"] as $image_type) {
    foreach(glob("img/screenshots/{$id}_{$image_type}.*") as $image_path) {
      if (in_array($image_path, $existing_images))
        break;

      array_push($image_library, [
        "src" => $image_path,
        "title" => "{$id}_{$image_type}"
      ]);
      array_push($existing_images, $image_path);
    }
  }

  foreach (glob("img/screenshots/{$id}_*") as $searched_image) {
    if (in_array($searched_image, $existing_images))
      continue;

    array_push($image_library, [
      "src" => $searched_image,
      "title" => preg_replace('/\.[a-z]{3,4}$/', "", $searched_image)
    ]);
  }

  return $image_library;
}

function find_data($data, $id) {
  if ($id === NULL)
    return [];

  $filter_array = array_filter($data, function($val) use ($id) {
    return $val["id"] == $id;
  });
  reset($filter_array);

  return current($filter_array) ?? [ "err" => NULL ];
}

function set_info($info) {
  $output = "";

  if (empty($info))
    return;

  if (is_string($info))
    return "<p>$info</p>";

  switch ($info["type"]) {
    case "image":
      $img = "img/screenshots/armor/{$info['src']}";
      return "<p><a href=\"$img\"><img src=\"$img\"></a></p>";
      break;

    case "details":
      $output .= "<details" . ($info["open"] ? " open" : "") . ">";
      if (is_string($info['name'])) {
        $output .= "<summary>{$info['name']}</summary>";
      }

      foreach ($info["items"] ?? [] as $item) {
        $output .= set_info($item);
      }
      $output .= "</details>";
      break;

    case "weapon":
      $output .= '<div class="set-display">'; // Start set display
      $output .= build_weapon($info["weapon"]);
      $output .= '</div>'; // End set display
      break;

    case "set":
      $output .= '<div class="set-display">'; // Start set display

      $output .= '<div class="set-name">' . $info["name"] . '</div>';

      $output .= '<div class="armor-block">'; // Armor block start

      /* -- WEAPON BLOCK -- */
      if (!empty($info["weapon"])) {
        $output .= build_weapon($info["weapon"]);
      }
      /* -- WEAPON BLOCK -- */

      /* -- DEFENSE BLOCK -- */
      $output .= '<div class="def-block' . (!empty($info["weapon"]) ? " left-border" : "") . '">'; // Defense block start
      // Defense
      if (!empty($info["defense"])) {
        $output .= "<div><img src=\"img/icons/shield.png\" class=\"mh-icon\"> Def: {$info['defense']}</div>";
      }
      // Elemental Res
      foreach ($info["res"] ?? [] as $res_type=>$res_value) {
        $output .= '<div><img src="img/icons/' . $res_type . '.png" class="mh-icon"> Res: <span class="res-' . ($res_value >= 0 ? "pos" : "neg") . '">' . $res_value . '</span></div>';
      }
      $output .= '</div>'; // Defense block end
      /* -- DEFENSE BLOCK -- */

      $output .= '<div class="piece-block left-border">'; // Pieces block start
      $output .= '<div><strong>Armor Pieces</strong></div>';
      $piece_imgs = ["head", "armor", "arm", "waist", "leg"];
      foreach ($info["pieces"] as $key=>$piece) {
        $output .= '<div>';
        $output .= '<img src="img/icons/' . $piece_imgs[$key] . '.png" class="mh-icon"> ';
        if (is_string($piece)) {
          $output .= $piece;
        } else if (is_array($piece)) {
          $output .= $piece[0] . ' &rarr; ' . $piece[1];
        }
        $output .= '</div>';
      }
      $output .= '</div>'; // Pieces block end

      $output .= '<div class="piece-block left-border">'; // Charm block start
      if ($info["charm"]) {
        $output .= '<div><strong><img src="img/icons/charm.png" class="mh-icon"> Charm</strong></div>';
        foreach ($info["charm"]["skills"] ?? [] as $skill_name=>$skill_value) {
          $output .= '<div>' . $skill_name . ": " . $skill_value . '</div>';
        }
        $output .= build_slots($info["charm"]["slots"] ?? []);
      }
      $output .= '</div>'; // Charm block end

      $output .= '<div class="skill-block left-border">'; // Skill block start
      $output .= '<div><strong>Active Skills</strong></div>';
      foreach ($info["skills"] as $skill_name=>$skill_value) {
        if ($skill_value == 0) {
          $output .= '<div class="legacy-skill">' . $skill_name . '</div>';
        } else if ($skill_value == -1) {
          $output .= '<div class="negative-skill">' . $skill_name . '</div>';
        } else {
          $output .= '<div>' . $skill_name . '</div>';
        }
      }
      $output .= '</div>'; // Skill block end

      $output .= '</div>'; // Armor block end

      $output .= '</div>'; // End set display
      break;
  }

  return "<p>" . $output . "</p>";
}

function build_slots($slot_info) {
  $output = "";
  $output .= '<div>Slots: <span class="slot-levels">';
  for ($i = 0; $i < 3; $i++) {
    $slot_level = ($slot_info ?? [])[$i] ?? -1;
    switch ($slot_level) {
      case 0:
        $output .= "<span>&#9711;</span>";
        break;
      case 4:
      case 3:
      case 2:
      case 1:
        $output .= '<img src="img/icons/slot_' . $slot_level . '.png" class="mh-icon">';
        break;
      default:
        $output .= "<span>&#8722;</span>";
        break;
    }
  }
  $output .= '</span></div>';

  return $output;
}

function build_weapon($info, $standalone = false) {
  $output = "";

  $output .= '<div class="weapon-display">';

  $output .= '<div><img src="img/icons/' . ($info["type"] ?? "question") . '.png" class="mh-icon"> <strong>' . $info["name"] . '</strong>' . get_hone($info["honing"] ?? NULL) . '</div>';
  
  if ($info["attack"])
    $output .= "<div>Attack: {$info['attack']}</div>";

  if ($info["element_type"] && $info["element_dmg"])
    $output .= "<div>Element: <img src=\"img/icons/{$info["element_type"]}.png\" class=\"mh-icon\">{$info['element_dmg']}</div>";

  if (is_int($info["affinity"]))
    $output .= "<div>Affinity: {$info['affinity']}%</div>";

  $output .= build_slots($info["slots"]);

  if (!empty($info["sharpness"])) {
    $output .= '<div class="sharpness-display">';
    $output .= '<div class="sharpness-bar' . ($info["sharpness_inc"] ? " sharpness-plus" : "") . '">';
    foreach (["red", "orange", "yellow", "green", "blue", "white", "purple"] as $i=>$color) {
      if (!empty($info["sharpness"][$i])) {
        $output .= '<div class="sharp-bar sharp-bar-' . $color . '" style="flex-grow: ' . $info["sharpness"][$i] . '"></div>';
      }
    }
    $output .= '</div>';
    if ($info["sharpness_inc"]) {
      $output .= '<span class="sharpness-inc-indicator">+' . $info["sharpness_inc"] . '</span>';
    }
    $output .= '</div>';
  }

  foreach(["reload", "recoil", "deviation"] as $gun_stat) {
    if (!empty($info[$gun_stat])) {
      $output .= '<div>' . ucfirst($gun_stat) . ": " . $info[$gun_stat] . '</div>';
    }
  }

  if (!empty($info["rapid"])) {
    ob_start();
  ?>
  <div class="rapid-info">
    <table>
      <thead>
        <tr>
          <th class="align-left">Rapid Fire</th>
          <th class="align-center">Shots</th>
          <th class="align-center">Wait</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($info["rapid"] as $rapid) { ?>
        <tr>
          <td><?= $rapid['type'] ?></td>
          <td class="align-center"><?= $rapid['shots'] ?></td>
          <td class="align-center"><?= $rapid['wait'] ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php
    $output .= ob_get_contents();
    ob_end_clean();
  }
  
  if (!empty($info["special"])) {
    $output .= '<div><em>' . $info["special"] . '</em></div>';
  }

  $output .= '</div>';

  return $output;
}

function get_hone($hone) {
  return "<div class=\"hone-bar hone-bar-$hone\">" . ucfirst($hone) . "</div>";
}