<?php
// get audio id from database
global $wpdb;
$post_id = get_the_ID();

$audio_id = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wt_posts WHERE post_id = {$post_id}")[0]->audio_id;
$is_enabled = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wt_posts WHERE post_id = {$post_id}")[0]->enabled;
?>

<div class='wt-metabox-container'>
    <label class='enable'>

        <input id='wt-is-enabled' type='checkbox' <?php echo $is_enabled == 'true' ?  'checked' : (!isset($is_enabled) ? 'checked' : ''); ?> /> Enable Audio for this post

    </label>
    <p>Generate audio and listen to your entry. you can edit your text, generate it again & publish</p>

    <div style="display:none;">
        <div class='voice-actor' <?php # echo $audio_id &&  $audio_id != 'null' ? 'style="display:none;"' : '' 
                                    ?>>
            Sample voice actor</div>
        <div class='select-box' <?php # echo $audio_id &&  $audio_id != 'null' ? 'style="display:none;"' : '' 
                                ?>>
            <div class='sample-play' id='sample-play-button'>
                <img src="<?php echo plugins_url('/assets/images/play.svg', dirname(__FILE__, 1)) ?>">
            </div>
            <audio src='<?php echo plugins_url('/assets/Adam-Zviadi.wav', dirname(__FILE__, 1)) ?>' id="audio-sample-player" class="audio-sample-player">
                <!-- <source src="<?php echo plugins_url('/assets/Adam-Zviadi.wav', dirname(__FILE__, 1)) ?>" type="audio/mpeg">
            Your browser does not support the audio tag. -->
            </audio>

            <div class="version-container">
                <select name="version" id="versionSelect" onchange="getSelectedValue(event)">
                    <option value="ge_m0">Georgian (GE) - Male (Adam)</option>
                    <option value="ge_f0">Georgian (GE) - Female (Arya)</option>
                </select>
            </div>

            <!-- <label for='wa-select-box' id='wt-select-box-label' class='label select-box1'><span class='label-desc'>Georgian (GE) - Male (Adam)</span> </label>  -->
        </div>
    </div>


    <a class="wt-btn-blue" id='wt-generate-audio'>
        Generate Audio
    </a>
    <span id='wt-generate-status'></span>

    <input type='hidden' name='wt_audio_id' id='wt_audio_id' value="<?php echo $audio_id; ?>" />
    <input type='hidden' name='wt_is_enabled' id='wt_is_enabled' value="<?php echo isset($is_enabled) ? $is_enabled : 'true'; ?>" />

    <div class='front-player-container wt-admin-audio-player' id='wt-admin-audio-player'>
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
            <!-- <div class="top-player-content">
                <div>
                    <p class='wa-audio-title'>Listen to this article</p>
                    <p class='wa-audio-subtitle'>Read that article with audio assistance</p>
                </div>
                <p class='wa-powered-by'>Powered by <span><img src='<?php echo plugins_url('assets/images/wavetech-logo.svg', dirname(__FILE__, 1)) ?>' /></span></p>
            </div> -->
            <audio id="wa-front-player" src=''>
        </div>
    </div>
</div>

<script>
    // Constants
    var postInput = document.getElementById('title');
    const sampleAudioPlayer = document.getElementById('audio-sample-player');
    const post_title = `<?php echo get_the_title() ?>`;
    const post_content = `<?php echo  wp_strip_all_tags(get_the_content(), true); ?>`;
    const selectBoxLabel = document.getElementById('wt-select-box-label');
    const adminAudio = document.getElementById('wt-admin-audio-player');
    const generateButton = document.getElementById('wt-generate-audio');
    const samplePlayButton = document.getElementById('sample-play-button');
    const samplePlayer = document.querySelector('#audio-sample-player');


    const mainAudioPlayer = document.getElementById('wa-front-player');
    const progressBar = document.getElementById('wt-player-progress-bar');
    const outerContainer = document.getElementById('wt-player-progress-bar-outer');
    const hoverDuration = document.getElementById('wt-hover-duration');
    const trackPad = document.getElementById('wavetech-track');
    var post = {};
    var audios = [];
    let selectedFrame;
    let chunkIndex = 0;
    let stopped = false;
    let adder = 0;



    function isGutenbergActive() {
        return document.body.classList.contains('block-editor-page');
    }

    if (isGutenbergActive()) {
        let savedContent = `<?php echo get_the_content(); ?>`;

        if (savedContent.length) {
            document.body.addEventListener('keypress', function() {
                const gutenbergData = window.wp.data;

                var contentTextArea = document.querySelector('.is-root-container').innerHTML;

                // if (gutenbergData.select('core/block-editor').hasSelectedBlock()) {
                if (gutenbergData.select('core/editor').getCurrentPost().content.length != gutenbergData.select('core/editor').getEditedPostContent().length) {
                    generateButton.style.display = 'block';
                    generateButton.classList.add('wt-yellow-bg');
                    generateButton.innerText = "Re-generate Audio";
                    // adminAudio.style.display = 'none';
                }
                // }
            });
        }
        outerContainer.style.width = '160px';
    } else {
        jQuery(function($) {
            // Was needed a timeout since RTE is not initialized when this code run.
            setTimeout(function() {
                for (var i = 0; i < tinymce.editors.length; i++) {
                    if (post_content != '') {
                        tinymce.editors[i].onChange.add(function(ed, e) {
                            generateButton.style.display = 'block';
                            generateButton.classList.add('wt-yellow-bg');
                            generateButton.innerText = "Re-generate Audio";
                            // Update HTML view textarea (that is the one used to send the data to server).
                            ed.save();
                        });
                    }
                }
            }, 1000);
        });
    }

    // // handle Select Change
    // function getSelectedValue(event) {
    //     let adamSrc = '<?php echo plugins_url('/assets/Adam-Zviadi.wav', dirname(__FILE__, 1)) ?>';
    //     let aryaSrc = '<?php echo plugins_url('/assets/tamuna-welcome.wav', dirname(__FILE__, 1)) ?>';

    //     if (event.target.value == 'ge_m0') {
    //         samplePlayer.setAttribute('src', adamSrc);
    //         if (!samplePlayer.paused) {
    //             samplePlayer.pause();
    //             samplePlayButton.querySelector('img').setAttribute('src', '<?php echo plugins_url('assets/images/play.svg', dirname(__FILE__, 1)) ?>');
    //         }
    //     } else {
    //         samplePlayer.setAttribute('src', aryaSrc);
    //     }
    // }

    // Generate Audio

    function stripHtml(html) {
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    generateButton.addEventListener('click', function(e) {
        mainAudioPlayer.pause();
        if (audios.length) {
            mainAudioPlayer.setAttribute('src', '');
        }


        var contentTextArea;
        var generateStatus = document.getElementById('wt-generate-status');
        var postContent = document.createElement("div");

        if (isGutenbergActive()) {
            postInput = document.getElementById('post-title-0').value;
            contentTextArea = document.querySelector('.is-root-container').innerHTML;
            postContent.innerHTML = contentTextArea;
        } else {
            postInput = document.getElementById('title').value;
            postContent.innerText = stripHtml(tinymce.editors.content.getContent());
        }


        if (!postInput || postInput.length <= 10 || postContent.innerText.length <= 10) {
            generateStatus.innerHTML = 'Fill post title and content (min. 10 characters)';
            generateStatus.style.display = 'inline-block';
            generateStatus.classList.add('color-red');
            return;
        } else {
            generateStatus.style.display = 'none';
            adminAudio.style.display = 'none';
        }

        this.innerHTML = '<div class="wt-loader"></div>';
        this.setAttribute('disabled', 'true');
        this.style.padding = '12px 15px';

        var audioIdUrl = `https://api.wavetech.ai/v1/post/create?projectId=<?php echo get_option('wavetech_project_id'); ?>&title=${postInput}`;

        getData(audioIdUrl)
            .then(resp => {
            
                const chunk = 1;

                document.getElementById('wt_audio_id').setAttribute('value', resp.id);

                var generateUrl = `https://api.wavetech.ai/v1?version=ge_m0&productId=<?php echo get_option('wavetech_project_id'); ?>&postId=${resp.id}&apiKey=<?php echo get_option('wavetech_key'); ?>`;

                // selectBoxLabel.style.display = 'none';
                document.querySelector('.voice-actor').style.display = 'none';
                samplePlayButton.style.display = 'none';

                postData(generateUrl, {
                    text: postInput + '.',
                    chunk: chunk
                });


                let text = postContent.innerText;
                // new code
                const sentences = []
                const n = text.length
                let i = 0,
                    j = 0
                while (j < n) {
                    j = i + 200
                    if (j >= n) {
                        sentences.push(text.slice(i, n))
                    } else {
                        let comma = -1,
                            space = -1

                        while (j > i && !'.!?'.includes(text[j])) {
                            if (space === -1 && text[j] === ' ') {
                                space = j
                            }
                            if (comma === -1 && text[j] === 'j') {
                                comma = j
                            }
                            j--
                        }
                        if (j < i + 40) {
                            if (comma !== -1) {
                                j = comma
                            } else if (comma !== -1) {
                                j = comma
                            } else {
                                j += 200
                            }
                        }
                        sentences.push(text.slice(i, j + 1));
                        i = j + 1;
                    }
                }


                // const postContentArray = postContent.innerText.replace(/[\r\n]+/g, "").match(/\ ?(?:.{1,199}[.,?!]|.{1,200}$)/g);



                // if (postContentArray.slice(-1)[0].length < 10) {
                //     const lastString = postContentArray.slice(-2)[0] + postContentArray.slice(-1)[0];
                //     postContentArray.splice(-2);
                //     postContentArray.push(lastString);
                // }

                document.getElementById('wt-play-button').querySelector('img').setAttribute('src', '<?php echo plugins_url('assets/images/play.svg', dirname(__FILE__, 1)) ?>');
                progressBar.style.width = '0%';
                trackPad.style.left = '0%';
                chunkIndex = 0;
                adder = 0;

                sentences.map((text, index) => {
                    postData(generateUrl, {
                        text,
                        chunk: index + 2
                    }).then(data => {
                        if (data.statusCode == 400) {
                            this.innerHTML = 'Generate Audio';
                            this.setAttribute('disabled', '');
                            generateStatus.innerHTML = 'You are out of characters';
                            generateStatus.style.display = 'inline-block';
                            generateStatus.classList.add('color-red');

                            return;
                        } else {
                            // console.log(data); // JSON data parsed by `data.json()` call
                            axios.get(`https://api.wavetech.ai/v1/post/${resp.id}`)
                                .then(response => {
                                    audios = [];
                                    post = response.data;
                                    if (response.data.audios && response.data.audios[0].key.includes('-1')) {
                                        response.data.audios.map(item => {
                                            axios.get(`https://api.wavetech.ai/v1/bucket/${item.key}`).then(resp => {
                                                audios.push(resp.data)
                                            })
                                        });


                                        adminAudio.style.display = 'block';
                                        generateStatus.innerHTML = 'Audio Generated';
                                        generateStatus.style.display = 'inline-block';
                                        generateStatus.classList.remove('color-red');
                                        generateStatus.classList.add('color-green');

                                        this.style.display = 'none';
                                        this.innerHTML = 'Generate Audio';
                                        this.setAttribute('disabled', '');
                                        this.style.padding = '15px';

                                        setTimeout(() => {
                                            generateStatus.innerHTML = '';
                                            generateStatus.style.display = 'none';
                                            generateStatus.classList.remove('color-green');
                                        }, 5000);
                                    }
                                });
                        }
                    });
                });

            });
    })



    if ("<?php echo $audio_id; ?>" && "<?php echo $audio_id; ?>" != 'null') {
        axios.get('https://api.wavetech.ai/v1/post/<?php echo $audio_id ?>')
            .then(response => {
                audios = [];
                post = response.data;
                response.data.audios.map(item => {

                    axios.get(`https://api.wavetech.ai/v1/bucket/${item.key}`).then(resp => {
                        audios.push(resp.data)
                    })
                });
            });
        adminAudio.style.display = 'block';
    }



    /********************* ****************** ****************** /
    /*********************  Audio play button ******************/
    /********************* ****************** ******************/

    document.getElementById('wt-play-button').addEventListener('click', function() {

        trackPad.style.display = 'block';
        if (mainAudioPlayer.paused) {
            if (!mainAudioPlayer.getAttribute('src')) {
                mainAudioPlayer.setAttribute('src', audios[0]);
            }
            // stopped = false;
            mainAudioPlayer.play();
            this.querySelector('img').setAttribute('src', `<?php echo plugins_url('assets/images/pause.svg', dirname(__FILE__, 1)) ?>`);
        } else {
            // stopped = true;
            mainAudioPlayer.pause();
            this.querySelector('img').setAttribute('src', `<?php echo plugins_url('assets/images/play.svg', dirname(__FILE__, 1)) ?>`);
        }
    });



    mainAudioPlayer.addEventListener('ended', () => {
        if (chunkIndex < audios.length - 1) {
            mainAudioPlayer.src = audios[++chunkIndex]
            adder += post.audios[chunkIndex - 1].duration
        } else {
            stopped = true;
            chunkIndex = 0;
            adder = 0;
            mainAudioPlayer.src = audios[chunkIndex];
        }
    })

    mainAudioPlayer.addEventListener('timeupdate', () => {
        if (post) {
            progressBar.style.width = `${(100 / post.length) * (adder + mainAudioPlayer.currentTime)}%`
            trackPad.style.left = `${(100 / post.length) * (adder + mainAudioPlayer.currentTime) - 1}%`
        }
    })

    mainAudioPlayer.addEventListener('canplay', () => {
        if (chunkIndex && !stopped) {
            mainAudioPlayer.play()
        }
    })




    outerContainer.addEventListener('click', function(e) {
        selectedFrame = post.length * (e.clientX - outerContainer.getBoundingClientRect().left) / (outerContainer.getBoundingClientRect().width / 100) / 100;
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
        stopped = false
        chunkIndex = i
        adder = forAdder - selectedFrame
        mainAudioPlayer.src = audios[i];
        mainAudioPlayer.currentTime = selectedFrame;
        mainAudioPlayer.play();
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


    // sample player play-pause
    samplePlayButton.addEventListener('click', function() {
        if (sampleAudioPlayer.paused) {
            sampleAudioPlayer.play();
            this.querySelector('img').setAttribute('src', '<?php echo plugins_url('assets/images/pause.svg', dirname(__FILE__, 1)) ?>');
        } else {
            sampleAudioPlayer.pause();
            this.querySelector('img').setAttribute('src', '<?php echo plugins_url('assets/images/play.svg', dirname(__FILE__, 1)) ?>');
        }
    });

    sampleAudioPlayer.addEventListener('ended', function() {
        samplePlayButton.querySelector('img').setAttribute('src', '<?php echo plugins_url('assets/images/play.svg', dirname(__FILE__, 1)) ?>');
    })
</script>