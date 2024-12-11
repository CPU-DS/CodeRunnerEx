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
 * This JS file auto-detect all elements in page with class 'coderunnerex', and add class 'coderunner' to them to apply styles from CodeRunner question.
 */
define([], function() {
    function doStyleInherit(doc) {
        let elems = doc.querySelectorAll('.coderunnerex');
        for (let i = 0; i < elems.length; i++) {
            elems[i].classList.add('coderunner');
        }
        let elem = doc.getElementById('page-question-type-coderunnerex');
        if (elem)
            elem.id = 'page-question-type-coderunner';
    }

    function init(doc) {
        if (!doc)
            doc = document;
        if (document.readyState === "loading") {
            doc.addEventListener('DOMContentLoaded', function(e) {
                doStyleInherit(doc);
            });
        } else {
            doStyleInherit(doc);
        }
    }

    return { init };
});