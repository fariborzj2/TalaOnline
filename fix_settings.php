<?php
$content = file_get_contents('site/assets/js/push-notifications.js');
$content = str_replace("settingsForm.querySelector('[name=\"frequency_limit\"]').value = s.frequency_limit;", "const freqRadio = settingsForm.querySelector(`[name=\"frequency_limit\"][value=\"\${s.frequency_limit}\"]`);\n                    if (freqRadio) freqRadio.checked = true;", $content);
file_put_contents('site/assets/js/push-notifications.js', $content);
