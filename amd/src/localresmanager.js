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

define(['core/str'], function(CoreStr) {
    /**
     * A special class to manage the localization strings from Moodle.
     */
    class LocalResManager {
        constructor() {
            this._localRes = {};
        }
        // methods about retrieving localization strings from PHP server
        async _getLocalString(stringId, componentName = 'coderunnerex') {
            let result = this._getCachedLocalString(stringId, componentName);
            if (result !== undefined)
                return result;

            // load Moodle Local Strings by AJAX
            result = await CoreStr.get_string(stringId, componentName);
            this._setCachedLocalString(stringId, componentName, result);
            return result;
        }
        async getLocalStrings(stringIds, componentName = 'qtype_coderunnerex') {
            let result = {};
            const uncachedIds = [];
            for (const stringId of stringIds) {
                result[stringId] = this._getCachedLocalString(stringId, componentName);
                if (result[stringId] === undefined)
                    uncachedIds.push(stringId);
            }
            const params = uncachedIds.map(id => { return {key: id, component: componentName} });
            const fetchResult = await CoreStr.get_strings(params);
            uncachedIds.forEach((id, index) => {
                result[id] = fetchResult[index];
                this._setCachedLocalString(id, componentName, fetchResult[index]);
            });
            return result;
        }
        _getCachedLocalString(stringId, componentName) {
            const key = `${componentName}:${stringId}`;
            return this._localRes[key];
        }
        _setCachedLocalString(stringId, componentName, value) {
            const key = `${componentName}:${stringId}`;
            this._localRes[key] = value;
        }
    }
    const localResManager = new LocalResManager();

    return { localResManager };
});