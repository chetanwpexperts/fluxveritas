function checkOverride(id, recommended, value) {
    const diff = Math.abs(parseFloat(value) - parseFloat(recommended));
    document.getElementById('override_' + id).style.display = diff > 5 ? 'block' : 'none';
}
