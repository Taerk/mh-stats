const weaponRows = document.getElementsByClassName("weapon-row");
for (row of weaponRows) {
  row.onclick = function() {
    let weapon_class = this.className.match(/wep\-type\-[^\s]+/)[0];
    // for (wRow of weaponRows) {
    //   wRow.classList.toggle("highlighted");
    // }

    const thisWeaponTypeRows = document.getElementsByClassName(weapon_class);
    for (wRow of thisWeaponTypeRows) {
      wRow.classList.toggle("highlighted");
    }
  }
}