<?php

/**
 * Dictation form
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    cfapress <cfapress>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Robert Down <robertdown@live.com>
 * @copyright Copyright (c) 2008 cfapress <cfapress>
 * @copyright Copyright (c) 2013-2019 bradymiller <bradymiller@users.sourceforge.net>
 * @copyright Copyright (c) 2017-2023 Robert Down <robertdown@live.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 **/

require_once(__DIR__ . "/../../globals.php");
require_once("$srcdir/api.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;

$returnurl = 'encounter_top.php';
$languagesFile = file_get_contents(__DIR__ . '/language.json');
$languages = json_decode($languagesFile, true);
$defaultLanguage = "en-US";
?>
<html>

<head>
    <title><?php echo xlt("Dictation"); ?></title>

    <?php Header::setupHeader(); ?>
    <style>
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: end;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        #stopButtonDictation {
            background-color: #f44336;
            color: white;
        }

        #stopButtonDictation:hover {
            background-color: #da190b;
        }

        #clearButtonDictation {
            background-color: #808080;
            color: white;
        }

        #clearButtonDictation:hover {
            background-color: #666666;
        }

        .status {
            text-align: center;
            margin: 10px 0;
            font-style: italic;
            color: #666;
        }

        .error {
            color: #f44336;
            text-align: center;
            margin: 10px 0;
        }

        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="body_top">
    <?php
    $obj = formFetch("form_dictation", $_GET["id"]);
    ?>
    <div class="container">
        <div class="row">
            <div class="col-12 d-flex justify-content-between">
                <h2><?php echo xlt("Dictation"); ?></h2>
                <div>
                    <select name="language" class="form-control" id="language">
                        <option value="">-- Select Language --</option>
                        <?php foreach ($languages as $language): ?>
                            <option value="<?php echo htmlspecialchars($language['code']); ?>"
                                <?php echo ($language['code'] === $defaultLanguage) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($language['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="controls ">
                    <button type="button" class="btn btn-primary" id="startButtonDictation">Start Recording</button>
                    <button type="button" id="stopButtonDictation" disabled>Stop</button>
                    <button type="button" id="clearButtonDictation">Clear</button>
                </div>
                <form method=post action="<?php echo $rootdir ?>/forms/dictation/save.php?mode=update&id=<?php echo attr_url($_GET["id"]); ?>" name="my_form">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
                    <fieldset>
                        <legend class=""><?php echo xlt('Dictation') ?></legend>
                        <div id="error" class="error"></div>
                        <div id="status" class="status"></div>
                        <div class="form-group">
                            <div class="col-sm-10 offset-sm-1">
                                <textarea name="dictation" id="dictation_input" class="form-control" cols="80" rows="15"><?php echo text($obj["dictation"]); ?></textarea>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class=""><?php echo xlt('Additional Notes'); ?></legend>
                        <div class="form-group">
                            <div class="col-sm-10 offset-sm-1">
                                <textarea name="additional_notes" class="form-control" cols="80" rows="5"><?php echo text($obj["additional_notes"]); ?></textarea>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-group clearfix">
                        <div class="col-sm-12 offset-sm-1 position-override">
                            <div class="btn-group" role="group">
                                <button type='submit' onclick='top.restoreSession()' class="btn btn-secondary btn-save"><?php echo xlt('Save'); ?></button>
                                <button type="button" class="btn btn-link btn-cancel" onclick="top.restoreSession(); parent.closeTab(window.name, false);"><?php echo xlt('Cancel'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Get DOM elements
        const startButton = document.getElementById('startButtonDictation');
        const stopButton = document.getElementById('stopButtonDictation');
        const clearButton = document.getElementById('clearButtonDictation');
        const output = document.getElementById('dictation_input');
        const status = document.getElementById('status');
        const error = document.getElementById('error');
        const langauge = document.getElementById("language");


        // Initialize speech recognition
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        let recognition = null;

        // Check if browser supports speech recognition
        if (SpeechRecognition) {
            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = "en-US"

            // Configure recognition
            recognition.onstart = () => {
                status.textContent = 'Listening...';
                startButton.disabled = true;
                stopButton.disabled = false;
                error.textContent = '';
            };

            recognition.onerror = (event) => {
                error.textContent = `Error occurred: ${event.error}`;
                stopRecording();
            };

            recognition.onend = () => {
                status.textContent = 'Microphone is off.';
                stopRecording();
            };

            recognition.onresult = (event) => {
                let finalTranscript = '';
                let interimTranscript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        finalTranscript += transcript + ' ';
                    } else {
                        interimTranscript += transcript;
                    }
                }

                if (finalTranscript) {
                    output.value += finalTranscript;
                }
                status.textContent = interimTranscript ? `Recognizing: ${interimTranscript}` : 'Listening...';
            };
        } else {
            error.textContent = 'Speech recognition is not supported in this browser.';
            startButton.disabled = true;
        }

        langauge.addEventListener("change", ((event) => {
            recognition.lang = event.target.value;
        }))


        // Button click handlers
        startButton.addEventListener('click', () => {
            if (recognition) {
                recognition.start();
            }
        });

        stopButton.addEventListener('click', () => {
            if (recognition) {
                recognition.stop();
            }
        });

        clearButton.addEventListener('click', () => {
            output.value = '';
            error.textContent = '';
            status.textContent = '';
        });

        function stopRecording() {
            startButton.disabled = false;
            stopButton.disabled = true;
        }
    </script>
</body>

</html>