function toggleNotif(key) {
  const checkbox = document.getElementById('notif_' + key);
  const track    = document.getElementById('notif_track_' + key);
  const knob     = document.getElementById('notif_knob_' + key);
  const label    = document.getElementById('notif_label_' + key);
  checkbox.checked = !checkbox.checked;
  if (checkbox.checked) {
    track.style.background = '#18181b';
    knob.style.left = '25px';
    label.textContent = 'On';
    label.style.color = '#16a34a';
  } else {
    track.style.background = '#e4e4e7';
    knob.style.left = '3px';
    label.textContent = 'Off';
    label.style.color = '#71717a';
  }
}
