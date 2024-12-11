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

define(['qtype_coderunnerex/localresmanager'], function({localResManager}) {

    function getTargetElements(rootElem, {
        displayCopyButtonForTestCaseInput, displayCopyButtonForTestCaseOutput,
        displayCopyButtonForTestCaseCode, displayCopyButtonForTestCaseExpected,
        displayCopyButtonForExampleTable
    }) {
        if (rootElem.querySelectorAll) {
            let selectors = [];
            if (displayCopyButtonForExampleTable)
                selectors.push('.coderunnerexamples tbody td');

            if (displayCopyButtonForTestCaseCode)
                selectors.push('.coderunner-test-results tbody td.col_testcode');
            if (displayCopyButtonForTestCaseInput)
                selectors.push('.coderunner-test-results tbody td.col_stdin');
            if (displayCopyButtonForTestCaseOutput)
                selectors.push('.coderunner-test-results tbody td.col_got');
            if (displayCopyButtonForTestCaseExpected)
                selectors.push('.coderunner-test-results tbody td.col_expected');

            const selector = selectors.join(', ');

            if (selector)
                return rootElem.querySelectorAll(selector);
            else
                return [];
        }
        else
            return [];
    }
    function createCopyButton(parentElem, caption, hint)
    {
        if (!parentElem.__$sampleCopyBtn__)
        {
            var doc = parentElem.ownerDocument;
            var btn = doc.createElement('button');
            btn.className = 'CodeRunner-SampleCopyBtn';
            btn.setAttribute('type', 'button');
            btn.setAttribute('title', hint);
            btn.innerHTML = '<span>' + caption + '</span>';
            btn.addEventListener('click', function(e) {
                var codeElem = parentElem.getElementsByTagName('pre')[0];
                if (codeElem) {
                    var text = codeElem.innerText;
                    copyText(btn.ownerDocument, text, btn);
                }
            });
            parentElem.appendChild(btn);
            parentElem.__$sampleCopyBtn__ = btn;
            return btn;
        }
        else
            return null;
    }
    function getClipboardTextHolder(doc)
    {
        if (doc.__$clipboardTextHolder$__)
            return doc.__$clipboardTextHolder$__;
        else
        {
            var elem = document.createElement('textarea');
            elem.className = 'CodeRunner-SampleCopyTextHolder'
            doc.body.appendChild(elem);
            doc.__$clipboardTextHolder$__ = elem;
            return elem;
        }
    }
    function copyText(doc, text, invokerElem)
    {
        var textHolder = getClipboardTextHolder(doc);
        // append to invoker parent, avoid page scrolling when focus to textholder
        var parentElem = invokerElem.parentNode;
        parentElem.appendChild(textHolder);
        textHolder.value = text;
        textHolder.focus();
        textHolder.select();
        doc.execCommand('copy');
        //doc.body.appendChild(textHolder);
        invokerElem.focus();
    }

    async function createTools({
        displayCopyButtonForTestCaseInput, displayCopyButtonForTestCaseOutput,
        displayCopyButtonForTestCaseCode, displayCopyButtonForTestCaseExpected,
        displayCopyButtonForExampleTable
    }) {
        const localStrings = await localResManager.getLocalStrings([
            'copy_to_clipboard_caption',
            'copy_to_clipboard_hint'
        ]);
        const targetElems = getTargetElements(document.body, {
            displayCopyButtonForTestCaseInput, displayCopyButtonForTestCaseOutput,
            displayCopyButtonForTestCaseCode, displayCopyButtonForTestCaseExpected,
            displayCopyButtonForExampleTable
        });
        if (targetElems)
        {
            for (var i = 0, l = targetElems.length; i < l; ++i)
            {
                createCopyButton(targetElems[i], localStrings['copy_to_clipboard_caption'], localStrings['copy_to_clipboard_hint']);
            }
        }
    }

    function init({
          displayCopyButtonForTestCaseInput = true, displayCopyButtonForTestCaseOutput = true,
          displayCopyButtonForTestCaseCode = true, displayCopyButtonForTestCaseExpected = true,
          displayCopyButtonForExampleTable = true
    }) {
        const doInit = () =>
            createTools({
                displayCopyButtonForTestCaseInput, displayCopyButtonForTestCaseOutput,
                displayCopyButtonForTestCaseCode, displayCopyButtonForTestCaseExpected,
                displayCopyButtonForExampleTable
            });

        if (displayCopyButtonForTestCaseInput
            || displayCopyButtonForTestCaseOutput
            || displayCopyButtonForTestCaseCode
            || displayCopyButtonForTestCaseExpected
            || displayCopyButtonForExampleTable
        ) {
            // TODO: when encrypt data transfer, the testcase result table may be created by JS code after DOM loaded, need to handle this
            if (document.readyState === "loading") {
                doc.addEventListener('DOMContentLoaded', function (e) {
                    doInit();
                });
            } else {
                doInit();
            }
        }
    }

    return {
        init
    }
});