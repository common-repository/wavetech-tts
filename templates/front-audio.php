<?php
// get audio id from database
$post_id = get_the_ID();

$audio_id = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wt_posts WHERE post_id = {$post_id}")[0]->audio_id;
$is_enabled = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wt_posts WHERE post_id  = {$post_id}")[0]->enabled;
?>

<?php if ($audio_id != 'null' && $is_enabled == 'true') { ?>
    <div class='front-player-container' id='front-player-container' style='display: flex;'>
        <div class="wa-player">
            <div id='wt-play-button'>
                <img src='<?php echo plugins_url('assets/images/play.svg', dirname(__FILE__, 1)) ?>' />
            </div>
            <div id='wt-player-progress-bar-outer'>
                <div id="wavetech-track"></div>
                <div id='wt-player-progress-bar-fill'></div>
                <div id='wt-player-progress-bar-hover'></div>
                <div id='wt-player-progress-bar'></div>

                <div id='wt-hover-duration'></div>
            </div>
            <div class="top-player-content">
                <div>
                    <p class='wa-audio-title'>Listen to this article <span id="wt-whole-length"></span></p>
                    <p class='wa-audio-subtitle'>Read that article with audio assistance</p>
                </div>
                <a href='https://www.wavetech.ai/' target="_blank" class='wa-powered-by'>Powered by <span><img src='<?php echo plugins_url('/assets/images/wavetech-logo.svg', dirname(__FILE__, 1)) ?>' /></span></p>
            </div>
            <audio id="wa-front-player" src=''>
        </div>

        <a class="wa-controls" id='wa-controls'>
            <img src='<?php echo plugins_url('/assets/images/controls.svg', dirname(__FILE__, 1)) ?>' />
        </a>

        <div class="wt-dropdown-controls" id='wa-dropdown-controls'>
            <span class="wa-control-title">Speed Control</span>
            <div class="speed-control">
                <button onclick="changeSpeed('down')">
                    <img src="<?php echo plugins_url('assets/images/minus.svg', dirname(__FILE__, 1)) ?>">
                </button>
                <div class="indicator" id='wa-speed-indicator'>1.0x</div>
                <button onclick="changeSpeed('up')">
                    <img src="<?php echo plugins_url('assets/images/plus.svg', dirname(__FILE__, 1)) ?>">
                </button>
            </div>
        </div>

    </div>

    <script>
        // Audio play button
        const mainAudioPlayer = document.getElementById('wa-front-player');
        const progressBar = document.getElementById('wt-player-progress-bar');
        const outerContainer = document.getElementById('wt-player-progress-bar-outer');
        const trackPad = document.getElementById('wavetech-track');
        const frontAudioPlayer = document.getElementById('front-player-container');
        const wtPlayButton = document.getElementById('wt-play-button');
        const controlsButton = document.getElementById('wa-controls');
        const hoverDuration = document.getElementById('wt-hover-duration');

        outerContainer.style.width = (frontAudioPlayer.getBoundingClientRect().width - 130) + 'px';

        var post = {};
        var audios = [];
        wtPlayButton.addEventListener('click', function() {
            trackPad.style.display = 'block';
            if (mainAudioPlayer.paused) {
                if (!mainAudioPlayer.getAttribute('src')) {
                    // console.log(audios);
                    mainAudioPlayer.setAttribute('src', audios[0]);
                }
                stopped = false;
                mainAudioPlayer.playbackRate = playbackRate;
                mainAudioPlayer.play();
                this.querySelector('img').setAttribute('src', `<?php echo plugins_url('assets/images/pause.svg', dirname(__FILE__, 1)) ?>`);
            } else {
                stopped = true;
                mainAudioPlayer.pause();
                this.querySelector('img').setAttribute('src', `<?php echo plugins_url('assets/images/play.svg', dirname(__FILE__, 1)) ?>`);
            }
        });

        axios.get('https://api.wavetech.ai/v1/post/<?php echo $audio_id ?>')
            .then(response => {
                audios = [];
                post = response.data;
                if (response.data.audios.length) {
                    frontAudioPlayer.style.top = 'unset';
                    frontAudioPlayer.style.position = 'relative';
                    frontAudioPlayer.style.visibility = 'visible';
                }
                document.getElementById('wt-whole-length').innerText = (formatTime(Math.ceil(response.data.length)));
                response.data.audios.map(item => {
                    axios.get(`https://api.wavetech.ai/v1/bucket/${item.key}`).then(resp => {
                        audios.push(resp.data)
                    })
                });
            });

        let chunkIndex = 0;
        let stopped = false;
        let adder = 0;

        mainAudioPlayer.addEventListener('ended', () => {
            if (chunkIndex < audios.length - 1) {

                mainAudioPlayer.src = audios[++chunkIndex]
                adder += post.audios[chunkIndex - 1].duration;
                mainAudioPlayer.playbackRate = playbackRate;
            } else {
                wtPlayButton.querySelector('img').setAttribute('src', `<?php echo plugins_url('/assets/images/play.svg', dirname(__FILE__, 1)); ?>`);
                stopped = true;
                chunkIndex = 0;
                adder = 0;
                mainAudioPlayer.src = audios[chunkIndex];
                mainAudioPlayer.playbackRate = playbackRate;
            }
        })

        function formatTime(seconds) {
            return [
                    // parseInt(seconds / 60 / 60),
                    parseInt(seconds / 60 % 60),
                    parseInt(seconds % 60)
                ]
                .join(":")
                .replace(/\b(\d)\b/g, "0$1")
        }


        const isHover = e => e.parentElement.querySelector(':hover') === e;

        mainAudioPlayer.addEventListener('timeupdate', () => {
            if (post) {
                document.getElementById('wt-whole-length').style.visibility = 'hidden';
                document.querySelector('.wa-audio-subtitle').innerText = formatTime(Math.ceil(adder + mainAudioPlayer.currentTime)) + ' / ' + formatTime(Math.ceil(post.length));
                // if (!isHover(outerContainer)) {
                progressBar.style.width = `${(100 / post.length) * (adder + mainAudioPlayer.currentTime)}%`;
                // }

                trackPad.style.left = `${(100 / post.length) * (adder + mainAudioPlayer.currentTime) - 1}%` == "-1%" ? '0%' : `${(100 / post.length) * (adder + mainAudioPlayer.currentTime) - 1}%`;
            }
        })

        mainAudioPlayer.addEventListener('canplay', () => {
            if (chunkIndex && !stopped) {
                mainAudioPlayer.play()
            }
        })


        outerContainer.addEventListener('click', function(e) {
            document.getElementById('wavetech-track').style.display = 'block';

            let selectedFrame = post.length * (e.clientX - outerContainer.getBoundingClientRect().left) / (outerContainer.getBoundingClientRect().width / 100) / 100;
            const forAdder = selectedFrame;

            let i = 0
            while (true) {
                if (selectedFrame - post.audios[i].duration > 0) {
                    selectedFrame -= post.audios[i].duration;
                    i++;
                } else {
                    break
                }
            }
            stopped = false;
            chunkIndex = i;
            adder = forAdder - selectedFrame;
            mainAudioPlayer.src = audios[i];
            mainAudioPlayer.currentTime = selectedFrame;
            mainAudioPlayer.playbackRate = playbackRate;
            mainAudioPlayer.play();
            if (wtPlayButton.querySelector('img').getAttribute('src').includes('play.svg')) {
                wtPlayButton.querySelector('img').setAttribute('src', `<?php echo plugins_url('/assets/images/pause.svg', dirname(__FILE__, 1)) ?>`);
            }
        })


        outerContainer.addEventListener('mousemove', function(e) {
            let selectedFrame = (e.clientX - outerContainer.getBoundingClientRect().left) / (outerContainer.getBoundingClientRect().width / 100);
            let selectedSecond = post.length * (e.clientX - outerContainer.getBoundingClientRect().left) / (outerContainer.getBoundingClientRect().width / 100) / 100;
            hoverDuration.style.left = selectedFrame + '%';
            document.getElementById('wt-player-progress-bar-hover').style.width = selectedFrame + '%';
            hoverDuration.innerText = formatTime(Math.round(selectedSecond));
            hoverDuration.style.background = 'white';
        })

        outerContainer.addEventListener('mouseout', function(e) {
            hoverDuration.innerText = '';
            hoverDuration.style.background = 'transparent';
            document.getElementById('wt-player-progress-bar-hover').style.width = 0;
        })


        document.addEventListener('mouseup', function(e) {
            var element = document.getElementById('wa-dropdown-controls');

            if (!element.contains(e.target) && !controlsButton.contains(e.target)) {
                element.classList.remove('wa-show-dd');
            }
        });
    </script>

<?php } ?>