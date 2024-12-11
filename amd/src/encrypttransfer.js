// This file is part of CodeRunnerEx.
//
// CodeRunnerEx is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunnerEx is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CodeRunnerEx. If not, see <http://www.gnu.org/licenses/>.

/**
 * This JS file automatically transfer data post between coderunnerex question client and server.
 */
define([], function() {

    const ENCRYPT_HTML_PLACEHOLDER_CLASS_NAME = 'CodeRunnerEx-EncPlaceholder';
    const ENCRYPTED_FORM_CONTROL_NAME_SUFFIX = '_encrypted';

    function encryptString(string) {
        return btoa(String.fromCharCode(...new TextEncoder().encode(string)));
    }
    function decryptString(string) {
        return new TextDecoder().decode(Uint8Array.from(atob(string), (c) => c.charCodeAt(0)))
    }

    function getQuestionRootElem(doc, questionResponseFieldElem) {
        let currElem = questionResponseFieldElem;
        while (currElem && currElem !== doc.body && !currElem.classList.contains('que'))
            currElem = currElem.parentNode;
        return currElem;
    }

    function decryptPlaceHolderElem(doc, placeholder) {
        const data = placeholder.getAttribute('data-raw');
        const html = decryptString(data);
        const dummyElem = doc.createElement('div');
        dummyElem.innerHTML = html;
        const fragment = doc.createDocumentFragment();
        for (let i = 0, l = dummyElem.children.length; i < l; ++i) {
            fragment.appendChild(dummyElem.children[i]);
        }
        placeholder.replaceWith(fragment);
        return html;
    }
    function decryptCodeEditor(doc, placeholder, codeEditorPlugin) {
        // when handling code editor, we need to find the associated textarea element and fill the content of it.
        // if the related ace editor is created already, we even need to fill the ace editor manually
        const code = decryptString(placeholder.getAttribute('data-raw'));

        const targetId = placeholder.getAttribute('data-target-id');
        const targetElem = doc.getElementById(targetId);
        if (targetElem)
            targetElem.innerHTML = code;

        const codeContent = targetElem.value;  // convert the HTML entities in code to normal chars

        // check if the assoc ace editor has already be created
        if (codeEditorPlugin === 'none') {  // pure textarea
            // nothing need to be done here
        } else if (codeEditorPlugin === 'scratchpad') {
            _fillCodeEditor_scratchpad(doc, targetId, codeContent);
        } else {   // ace
            _fillCodeEditor_ace(doc, targetId, codeContent);
        }

        // finally remove the placeholder
        placeholder.remove();
    }
    function _fillCodeEditor_ace(doc, targetId, content) {
        if (window.ace) {
            const richEditorWrapperId = targetId + '_wrapper';
            const richEditorWrapperElem = doc.getElementById(richEditorWrapperId);
            if (richEditorWrapperElem) {
                const aceEditorElem = richEditorWrapperElem.getElementsByClassName('ace_editor')[0];
                if (aceEditorElem) {
                    _setAceEditorContent(aceEditorElem, content);
                }
            }
        }
    }
    function _fillCodeEditor_scratchpad(doc, targetId, content) {
        if (!content)  // empty content, no need to fill the editors
            return;
        try {
            const obj = JSON.parse(content);
            if (window.ace) {
                const codeEditorWrapperId = targetId + '_answer-code_wrapper';
                const codeEditorWrapperElem = doc.getElementById(codeEditorWrapperId);
                if (codeEditorWrapperElem) {
                    const aceEditorElem = codeEditorWrapperElem.getElementsByClassName('ace_editor')[0];
                    if (aceEditorElem) {
                        _setAceEditorContent(aceEditorElem, obj.answer_code.join('\n'));
                    }
                }
                const testEditorWrapperId = targetId + '_test-code_wrapper';
                const testEditorWrapperElem = doc.getElementById(testEditorWrapperId);
                if (testEditorWrapperElem) {
                    const aceEditorElem = testEditorWrapperElem.getElementsByClassName('ace_editor')[0];
                    if (aceEditorElem) {
                        _setAceEditorContent(aceEditorElem, obj.test_code.join('\n'));
                    }
                }
            }
        } catch (e) {
            // content not JSON, fallback
            _fillCodeEditor_ace(doc, targetId, content);
            return;
        }
    }
    function _setAceEditorContent(elem, content) {
        const aceEditor = ace.edit(elem);
        session = aceEditor.getSession();
        session.setValue(content);
    }

    function initServerDataDecryptor(doc, questionRootElem) {
        let targetParent = questionRootElem || doc.body;  // doc.getElementById('responseform');
        if (targetParent) {
            const encryptedPlaceHolders = targetParent.querySelectorAll('.' + ENCRYPT_HTML_PLACEHOLDER_CLASS_NAME);
            for (let i = 0, l = encryptedPlaceHolders.length; i < l; ++i) {
                const codeEditorPlugin = encryptedPlaceHolders[i].getAttribute('data-code-editor');
                if (codeEditorPlugin)
                    decryptCodeEditor(doc, encryptedPlaceHolders[i], codeEditorPlugin);
                else
                    decryptPlaceHolderElem(doc, encryptedPlaceHolders[i]);
            }
        }
    }

    // for debug
    function initServerDataDecryptorDelay(doc, questionRootElem) {
        setTimeout(initServerDataDecryptor.bind(null, doc, questionRootElem), 5000);
    }


    function prepareClientEncryptReponseControls(doc, form, responseFieldElem) {
        const hiddenElem = doc.createElement('input');
        hiddenElem.type = 'hidden';
        hiddenElem.name = responseFieldElem.name + ENCRYPTED_FORM_CONTROL_NAME_SUFFIX;
        hiddenElem.id = responseFieldElem.id + ENCRYPTED_FORM_CONTROL_NAME_SUFFIX;

        // clear the original response field name, avoid submitting to server
        responseFieldElem.name = '';

        responseFieldElem.parentNode.insertBefore(hiddenElem, responseFieldElem);
    }

    function initClientDataEncryptor(doc, questionRootElem, questionResponseFieldElem) {
        let targetForm = questionResponseFieldElem.form;
        if (targetForm && questionResponseFieldElem && !questionResponseFieldElem.__clientDataEncryptorPrepared__) {
            prepareClientEncryptReponseControls(doc, targetForm, questionResponseFieldElem);

            targetForm.addEventListener('submit', function(e) {
                encrypClientDataInForm(targetForm);
            });
            questionResponseFieldElem.__clientDataEncryptorPrepared__ = true;
        }
    }

    function getUserResponseControlsInForm(form) {
        let result = [];
        const elems = form.getElementsByTagName('textarea');
        for (let i = 0; i < elems.length; i++) {
            let elem = elems[i];
            if (elem.id && elem.id.match(/.+\_answer/)) {
                result.push(elem);
            }
        }
        return result;
    }
    function encrypClientDataInForm(form) {
        const elems = getUserResponseControlsInForm(form);
        for (let i = 0; i < elems.length; i++) {
            let elem = elems[i];
            let id = elem.id + ENCRYPTED_FORM_CONTROL_NAME_SUFFIX;
            let hiddenElem = document.getElementById(id);
            if (hiddenElem) {
                hiddenElem.value = encryptString(elem.value);
            }
        }
    }

    function init({doc, encryptServerDataTransfer, encryptClientDataTransfer, responseFieldId}) {
        if (!doc)
            doc = document;

        const doInit = (doc) => {
            const questionResponseFieldElem = doc.getElementById(responseFieldId);
            const questionRootElem = getQuestionRootElem(doc, questionResponseFieldElem);
            if (questionRootElem) {
                if (encryptServerDataTransfer)
                    initServerDataDecryptor(doc, questionRootElem);
                if (encryptClientDataTransfer)
                    initClientDataEncryptor(doc, questionRootElem, questionResponseFieldElem);
            }
        }

        if (document.readyState === "loading") {
            doc.addEventListener('DOMContentLoaded', function (e) {
                doInit(doc);
            });
        } else {
            doInit(doc);
        }

        /*
        // for debug
        doc.body.addEventListener('dblclick', function (e) {
            doInit(doc);
        });
        */

    }

    return { init };
});