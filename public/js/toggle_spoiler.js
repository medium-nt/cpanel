function toggleSpoiler() {
    var spoiler = document.getElementById('spoilerText');
    if (spoiler.style.display === 'none') {
        spoiler.style.display = 'block';
    } else {
        spoiler.style.display = 'none';
    }
}
