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
          echo show_weapon_usage("Compared to Prev Played Game" . ($prev_game_id ? strtoupper(" ($prev_game_id)") : ""), $game["weapons"], $prev_game_data ?? [], 20, 1);
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
    $output .= '<td class="weapon-usage-image"><img src="img/icons/' . $id . '.png" alt="' . $id . '" title="' . ucwords(str_replace("_", " ", $id)) . '"></td>';
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