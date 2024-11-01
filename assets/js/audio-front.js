
const audioPlayer = document.getElementById('wa-front-player');
let playbackRate = 1;
const speedIndicator = document.getElementById('wa-speed-indicator');

function changeSpeed(value) {
  if (value == 'down') {
    if (playbackRate <= 0.5) {
      return;
    } else {
      playbackRate = playbackRate - 0.25;
      audioPlayer.playbackRate = playbackRate;
    }
  } else if (value == 'up') {
    if (playbackRate >= 2) {
      return;
    } else {
      playbackRate = playbackRate + 0.25;
      audioPlayer.playbackRate = playbackRate;
    }
  }

  speedIndicator.innerText = playbackRate + 'x';
}


if (document.getElementById("wa-controls")) {
  document.getElementById('wa-controls').addEventListener('click', function () {
    document.getElementById('wa-dropdown-controls').classList.toggle('wa-show-dd');
  })
}

