<div class='wavetech-body-container'>
  <div style='display: flex; align-items:center;'>
    <h1 style='margin-right: 280px;'>wavetech. - Text-to-speech Settings</h1>
    <div>
      <a href='https://wavetech.ai/' target="_blank" class='wp-core-ui  button-secondary'>Go to Kernel</a>
    </div>
  </div>
  <div
    id='wavetech-plugin-form'
   >
    <div>
      <label>
      Enable wavetech. - Text-to-speech
      </label>
      <input 
          type='checkbox' 
          name='enable-wavetech'
          id='enable-wavetech'
         <?php echo get_option('wavetech_enabled') == 'true' ? 'checked="true"' : get_option('wavetech_enabled') != 'false' ? 'checked=true' : ''; ?>
        />

    </div>
    <br />
    <div>
    <label>Enter your API Key</label>
    <input placeholder='key' name='wavetech-key' class='wavetech-key-input' id='wavetech-key-input' value='<?php echo get_option('wavetech_key'); ?>' />
  
    <span class='wt-activation-status'>
       <span class='wt-successful'>
        <img src='<?php echo plugins_url('assets/images/checkmark.svg', dirname(__FILE__, 1)) ?>' alt='checkmark' />
       </span>
       <span class='wt-failed'>
        <img src='<?php echo plugins_url('assets/images/close.svg', dirname(__FILE__, 1)) ?>' alt='checkmark' />
       </span>
    </span>
    <div class="activation-status-text"></div>
    </div>
    <br />
    <br />
    <button class='button-primary' id='wt-activation-save-button' name='save'>Save Settings</button>
  </div>
</div>
