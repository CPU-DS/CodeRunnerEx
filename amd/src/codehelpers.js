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

    // const of element class names
    const CN_CODE_HELPER = 'CodeRunnerEx-CodeHelper';
    const CN_CODE_HELPER_NORMAL_MODE = 'NormalMode';
    const CN_CODE_HELPER_SIMPLE_MODE = 'SimpleMode';
    const CN_CODE_HELPER_AI_THUMBNAIL = 'Thumbnail';
    const CN_CODE_HELPER_INPUT_SECTION = 'InputSection';
    const CN_CODE_HELPER_INPUT_SUBMITTER = 'InputSubmitter';
    const CN_CODE_HELPER_USAGE_COUNT_REMINDER = 'UsageCountReminder';
    const CN_CODE_HELPER_PANEL_TOGGLER = 'PanelToggler';
    const CN_CODE_HELPER_AI_QINPUT_CONTAINER = 'QInputContainer';
    const CN_CODE_HELPER_AI_QINPUT = 'QInput';
    const CN_CODE_HELPER_AI_RESPONDER = 'Responder';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT = 'ResponderContent';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_GROUP = 'ResponderGroup';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_SECTION = 'ResponderSection';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_USER_QUESTION = 'UserQuestion' + ' comment';  // 'comment' for the background style of question block
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE = 'Response';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION = 'ResponseSection';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_TITLE = 'ResponseSectionTitle';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_CONTENT = 'ResponseSectionContent';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_CODE = 'ResponseSectionCode';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_CODE_OMITTED = 'ResponseSectionCodeOmitted';
    const CN_CODE_HELPER_AI_RESPONDER_CONTENT_USER_RATING = 'UserRating';
    const CN_CODE_HELPER_PANEL_BACKGROUND = 'BackgroundPanel';
    const CN_CODE_HELPER_BUTTON_TEXT = 'ButtonText';
    const CN_CODE_HELPER_BUTTON_ASSOC = 'ButtonAssoc';

    const CN_CODE_HELPER_COLLAPSED = 'Collapsed';
    const CN_CODE_HELPER_EXPANDED = 'Expanded';
    const CN_CODE_HELPER_ELEM_HIDE = 'Hide';

    const CN_CODE_HELPER_AI_RESPONDER_SECTION_STATE_PENDING = 'Pending';
    const CN_CODE_HELPER_AI_RESPONDER_SECTION_STATE_FULFILLED = 'Fulfilled';
    const CN_CODE_HELPER_AI_RESPONDER_SECTION_STATE_REJECTED = 'Rejected';

    // class for rating widget
    const CN_USER_RATING_WIDGET = 'UserRatingWidget';
    const CN_USER_RATING_WIDGET_RATED = 'Rated';
    const CN_USER_RATING_WIDGET_UNRATED = 'Unrated';
    const CN_USER_RATING_WIDGET_LINK = 'UserRatingLink';
    const CN_USER_RATING_WIDGET_LINK_POSITIVE = 'Positive';
    const CN_USER_RATING_WIDGET_LINK_NEGATIVE = 'Negative';
    const CN_USER_RATING_WIDGET_LINK_RATED = 'Active';

    class CodeHelperHistoryDisplayMode {
        // only display history of current interaction
        static SHOWN_SESSION = 0;
        // display all history data, loading them from database
        static SHOWN_ALL = 1;
        // hide all history, only displays the last interaction
        static SHOWN_ACTIVE = 2;
    }

    /**
     * Stores the data of request/response of Ai helper,
     * to be displayed in CodeHelper responser panel.
     */
    class AiHelperDataRecord {
        static STATE_PENDING = 0;
        static STATE_REJECTED = -1;
        static STATE_FULFILLED = 1;

        constructor(id, {userQuestion = null, response = null, userRating = null, dbId = null, state = AiHelperDataRecord.STATE_PENDING}) {
            this._id = id;
            this.update({userQuestion, response, userRating, dbId, state});

            this._onupdate = null;
        }

        update({id = undefined, userQuestion = undefined, response = undefined, userRating = undefined, dbId = undefined, state = undefined}) {
            if (id !== undefined)
                this._id = id;
            if (dbId !== undefined)
                this._dbId = dbId;
            if (userQuestion !== undefined)
                this._userQuestion = userQuestion;
            if (response !== undefined)
                this._response = response;
            if (userRating !== undefined)
                this._userRating = userRating;
            if (state !== undefined)
                this._state = state;

            if (this.onupdate)
                this.onupdate(this);
        }

        get id() {
            return this._id;
        }
        set id(value) {
            this._id = value;
        }
        get dbId() {
            return this._dbId;
        }
        set dbId(value) {
            this._dbId = value;
        }
        get userQuestion() {
            return this._userQuestion;
        }
        get response() {
            return this._response;
        }
        get userRating() {
            return this._userRating;
        }
        get state() {
            return this._state;
        }
        get onupdate() {
            return this._onupdate;
        }
        set onupdate(callback) {
            this._onupdate = callback;
        }
    }

    class AiHelperDataRecordList {
        constructor() {
            this._data = [];
            this._onupdate = null;
        }

        _reactRecordUpdate(record) {
            if (this._onupdate)
                this._onupdate(record.id, record);
        }

        getRecordById(id) {
            return this._data.find(record => record.id === id);
        }

        // returns the newly pushed record id
        push(id, {dbId = null, userQuestion = null, response = null, userRating = null, state = AiHelperDataRecord.STATE_PENDING}) {
            if (!id) {
                const timestamp = Date.now();
                id = timestamp + '-' + this.length;
            }
            const data = new AiHelperDataRecord(id, {dbId, userQuestion, response, userRating, state});
            this._data.push(data);
            data.onupdate = this._reactRecordUpdate.bind(this);
            data.onupdate(data);  // trigger the update callback, since the data has newly been created
            return data.id;
        }
        updateRecord(oldId, {id = undefined, dbId = undefined, userQuestion = undefined, response = undefined, userRating = undefined, state = undefined}) {
            const record = this.getRecordById(oldId);
            if (record) {
                if (id === undefined)
                    id = oldId;
                record.update({id, dbId, userQuestion, response, userRating, state});
                return id;
            } else {
                return null;
            }
        }
        clear() {
            this._data = [];
        }

        get(index) {
            return this._data[index];
        }
        get length() {
            return this._data.length;
        }
        get data() {
            return this._data;
        }
        get onupdate() {
            return this._onupdate;
        }
        set onupdate(callback) {
            this._onupdate = callback;
        }
    }

    class UserRatingWidget {
        constructor (doc, parentElem, rateOnlyOnce = true, initialValue = null) {
            this._rateOnlyOnce = rateOnlyOnce;
            this._value = initialValue;
            this._createElems(doc).then(
                elem => {
                    this._elem = elem;
                    if (parentElem)
                        parentElem.appendChild(this._elem);
                }
            );
        }

        async _createElems(doc) {
            const result = doc.createElement('div');
            result.className = CN_USER_RATING_WIDGET;

            // leading label
            const labelElem = doc.createElement('label');
            result.appendChild(labelElem);

            // element to vote up/down
            const ratingParent = doc.createElement('ul');

            // initialize all local strings needed for this class
            const localStrings = await localResManager.getLocalStrings([
                'codehelper_user_rate_positive',
                'codehelper_user_rate_negative',
                'codehelper_user_rate_label_before_rating',
                'codehelper_user_rate_label_after_rating'
            ]);

            const linkProps = [
                {value: 1, text: localStrings['codehelper_user_rate_positive'], title: localStrings['codehelper_user_rate_positive'], className: CN_USER_RATING_WIDGET_LINK_POSITIVE},
                {value: -1, text: localStrings['codehelper_user_rate_negative'], title: localStrings['codehelper_user_rate_negative'], className: CN_USER_RATING_WIDGET_LINK_NEGATIVE}
            ];

            linkProps.forEach(prop => {
                const item = doc.createElement('li');
                item.classList.add(CN_USER_RATING_WIDGET_LINK);
                item.classList.add(prop.className);
                item.setAttribute('data-value', prop.value);
                item.setAttribute('title', prop.title);

                const textElem = doc.createElement('span');
                textElem.innerText = prop.text;
                item.appendChild(textElem);

                ratingParent.appendChild(item);
                item.onclick = this._reactRateLinkClick.bind(this);
            });

            result.appendChild(ratingParent);

            await this._updateWithRatingStatus(result);

            return result;
        }

        async _updateWithRatingStatus(targetElem) {
            const localStrings = await localResManager.getLocalStrings([
                'codehelper_user_rate_label_before_rating',
                'codehelper_user_rate_label_after_rating'
            ]);

            if (!targetElem)
                targetElem = this._elem;
            if (!targetElem)  // haven't intialized yet
                return;

            if (this.isRated) {
                targetElem.classList.add(CN_USER_RATING_WIDGET_RATED);
                targetElem.classList.remove(CN_USER_RATING_WIDGET_UNRATED);
            }
            else {
                targetElem.classList.add(CN_USER_RATING_WIDGET_UNRATED);
                targetElem.classList.remove(CN_USER_RATING_WIDGET_RATED);
            }

            // label
            const labelElem = targetElem.querySelector('label');
            labelElem.innerText = this.isRated? localStrings['codehelper_user_rate_label_after_rating']: localStrings['codehelper_user_rate_label_before_rating'];

            // vote links
            const links = targetElem.querySelectorAll('.' + CN_USER_RATING_WIDGET_LINK);
            links.forEach(link => {
                const rating = parseInt(link.getAttribute('data-value'));
                if (rating === this.value) {
                    link.classList.add(CN_USER_RATING_WIDGET_LINK_RATED);
                } else {
                    link.classList.remove(CN_USER_RATING_WIDGET_LINK_RATED);
                }
            })
        }

        _reactRateLinkClick(event) {
            if (!this.isRated || !this.rateOnlyOnce) {
                const target = event.currentTarget;
                const rating = parseInt(target.getAttribute('data-value'));
                const oldValue = this.value;
                if (oldValue !== rating) {
                    this.value = rating;
                    if (this.onchange) {
                        this.onchange(this, this.value, oldValue);
                    }
                }
            }
        }
        get value() {
            return this._value;
        }
        set value(rating) {
            const newValue = parseInt(rating);
            if (newValue !== this.value) {
                this._value = newValue;
                this._updateWithRatingStatus(); // await
            }
        }

        get rateOnlyOnce() {
            return this._rateOnlyOnce;
        }
        set rateOnlyOnce(value) {
            this._rateOnlyOnce = !!value;
        }

        get isRated() {
            return !!this.value;
        }
    }


    class CodeHelper {
        constructor(doc, { placeHolderId, targetEditorId, questionDataEmbedderId, aiRequestUrl, aiRateUrl, aiHelperPredefinedQuestions, aiHelperRemainingUsageCount,
            enableCustomQuestion, enableUserRating, readOnly, historyDisplayMode, useSimpleMode }) {
            this._readOnly = readOnly;  // if readonly, means we are in review mode
            this._historyDisplayMode = historyDisplayMode;  // in review mode, all history will be displayed regardless of this option
            this._simpleMode = useSimpleMode;

            this._aiRequestUrl = aiRequestUrl;
            this._aiRateUrl = aiRateUrl;
            this._aiHelperPredefinedQuestions = aiHelperPredefinedQuestions || [];
            this._targetRawEditor = this._getTargetRawCodeEditorElem(doc, targetEditorId);
            this._targetEditor = this._getTargetCodeEditorWrapperElem(doc, targetEditorId);
            this._id = placeHolderId + '_cr_ex_codehelper';
            this._component = {};
            this._qMetaData = this._retrieveQuestionMetaData(doc, questionDataEmbedderId);
            this._enableCustomQuestion = enableCustomQuestion;
            this._enableUserRating = enableUserRating;

            this._aiHelperRecords = new AiHelperDataRecordList();  // stores the question and response of AI helper interactions
            this._aiHelperRecords.onupdate = this.reactAiHelperRecordsUpdate.bind(this);
            this._aiHelperRecordsHistoryRetrieved = false;

            // this._aiHelperRequestThrottledDelay = 1500;  // TODO: now fixed

            this._createAndInsertWidget(doc, placeHolderId, {useSimpleMode, enableCustomQuestion})
                .then(() => {
                    // update the usage count reminder after widgets created
                    this.aiHelperRemainingUsageCount = aiHelperRemainingUsageCount;
                });
        }

        // methods about retrieving question information
        _retrieveQuestionMetaData(doc, questionDataEmbedderId) {
            const elem = doc.getElementById(questionDataEmbedderId);
            let result = {};
            if (elem) {
                result.questionAttemptId = elem.getAttribute('data-question-attempt-id');
                result.questionAttemptStepId = elem.getAttribute('data-question-attempt-step-id');
                result.questionUsageId = elem.getAttribute('data-question-usage-id');
                result.slot = elem.getAttribute('data-slot');
                result.cmId = elem.getAttribute('data-cm-id');
            }

            return result;
        }

        // methods about widget creation
        _getTargetRawCodeEditorElem(doc, id) {
            return doc.getElementById(id);
        }
        _getTargetCodeEditorWrapperElem(doc, rawId) {
            const wrapperId = rawId + '_wrapper';
            return doc.getElementById(wrapperId);
        }

        async _createWidget(doc, {useSimpleMode, enableCustomQuestion}) {
            const self = this;

            // initialize all local strings needed for this class
            const localStrings = await localResManager.getLocalStrings([
                'codehelper_thumbnail_caption',
                'codehelper_thumbnail_hint',
                'codehelper_thumbnail_caption_simple_mode',
                'codehelper_thumbnail_caption_simple_mode_hint',
                'codehelper_collapsor_hint',
                'codehelper_ask_submit_caption',
                'codehelper_ask_submit_hint',
                'codehelper_ask_input_placeholder',
                'codehelper_ask_select_placeholder',
                'err_code_helper_empty_ask',
                'codehelper_ai_usage_count_reminder_hint',
                'codehelper_user_rate_positive',
                'codehelper_user_rate_negative'
            ]);

            const result = doc.createElement('section');
            result.className = CN_CODE_HELPER + ' ' + CN_CODE_HELPER_COLLAPSED;
            if (useSimpleMode)
                result.classList.add(CN_CODE_HELPER_SIMPLE_MODE);
            else
                result.classList.add(CN_CODE_HELPER_NORMAL_MODE);
            result.id = this.id;
            this.component.panel = result;

            const bgPanel = doc.createElement('div');
            bgPanel.className = CN_CODE_HELPER_PANEL_BACKGROUND + ' outcome';
            result.appendChild(bgPanel);

            const inputSection = doc.createElement('div');
            inputSection.className = CN_CODE_HELPER_INPUT_SECTION;

            const thumbnail = doc.createElement('button');
            thumbnail.className = CN_CODE_HELPER_AI_THUMBNAIL + ' btn btn-secondary';
            let innerTextElem = doc.createElement('span');
            innerTextElem.className = CN_CODE_HELPER_BUTTON_TEXT;
            innerTextElem.innerHTML = useSimpleMode? localStrings['codehelper_thumbnail_caption_simple_mode']: localStrings['codehelper_thumbnail_caption'];
            thumbnail.appendChild(innerTextElem);
            let assocElem = doc.createElement('span');
            assocElem.className = CN_CODE_HELPER_BUTTON_ASSOC;
            thumbnail.appendChild(assocElem);
            thumbnail.title = useSimpleMode?
                this._aiHelperPredefinedQuestions[0] || localStrings['codehelper_thumbnail_caption_simple_mode_hint']:
                localStrings['codehelper_thumbnail_hint'];
            thumbnail.onclick = (e) => {
                // this.togglePanel();
                e.preventDefault();
                if (useSimpleMode) {
                    if (!this.isPanelExpanded())
                        this.startViewTransition(self.expandPanel.bind(self));
                    this.requestAiHelper();
                } else {
                    this.startViewTransition(self.togglePanel.bind(self));
                }
            }
            inputSection.appendChild(thumbnail);
            this.component.thumbnail = thumbnail;

            if (!useSimpleMode) {
                if (!this.readOnly) {
                    // question inputter or selector
                    const datalistContainer = doc.createElement('div');
                    datalistContainer.className = CN_CODE_HELPER_AI_QINPUT_CONTAINER + ' d-md-inline-block position-relative';
                    let inputter;
                    if (enableCustomQuestion) {
                        const datalist = doc.createElement('datalist');
                        datalist.id = this.id + '_input_datalist';
                        // ['能提示我这道题的思路么？', '为什么我的答案错了？', '我要如何改进我目前的代码？']
                        (this._aiHelperPredefinedQuestions || []).forEach(s => {
                            const option = doc.createElement('option');
                            option.value = s;
                            datalist.appendChild(option);
                        });
                        datalistContainer.append(datalist);

                        inputter = doc.createElement('input');
                        inputter.type = 'text';
                        inputter.setAttribute('list', datalist.id);
                        inputter.onkeypress = (e => {
                            if (e.code === 'Enter') {
                                // this.emulateResponse();
                                this.requestAiHelper();
                                e.preventDefault();
                            }
                        });
                        datalistContainer.appendChild(inputter);
                    } else {
                        inputter = doc.createElement('select');
                        const selectorPlaceholder = doc.createElement('option');
                        selectorPlaceholder.value = '';
                        selectorPlaceholder.innerText = localStrings['codehelper_ask_select_placeholder'];
                        selectorPlaceholder.setAttribute('disabled', 'disabled');
                        selectorPlaceholder.setAttribute('selected', 'selected');
                        inputter.appendChild(selectorPlaceholder);
                        (this._aiHelperPredefinedQuestions || []).forEach(s => {
                            const option = doc.createElement('option');
                            option.value = s;
                            option.innerText = s;
                            inputter.appendChild(option);
                        });
                        inputter.onchange = (e => {
                            this.requestAiHelper();
                            e.preventDefault();
                        });
                        datalistContainer.appendChild(inputter);
                    }
                    inputter.className = CN_CODE_HELPER_AI_QINPUT + ' form-control';
                    inputter.setAttribute('placeholder', localStrings['codehelper_ask_input_placeholder']);
                    inputSection.appendChild(datalistContainer);
                    this.component.inputter = inputter;
                }

                const btnSubmit = doc.createElement('button');
                btnSubmit.className = CN_CODE_HELPER_INPUT_SUBMITTER + ' btn btn-secondary';
                innerTextElem = doc.createElement('span');
                innerTextElem.className = CN_CODE_HELPER_BUTTON_TEXT;
                innerTextElem.innerHTML = localStrings['codehelper_ask_submit_caption'];
                btnSubmit.appendChild(innerTextElem);
                btnSubmit.title = localStrings['codehelper_ask_submit_hint'];
                btnSubmit.onclick = (e) => {
                    // this.emulateResponse();
                    this.requestAiHelper();
                    e.preventDefault();
                };
                inputSection.appendChild(btnSubmit);
                this.component.submitter = btnSubmit;
            }
            const usageCountReminder = doc.createElement('div');
            usageCountReminder.className = CN_CODE_HELPER_USAGE_COUNT_REMINDER;
            if (this.component.submitter)
                inputSection.insertBefore(usageCountReminder, this.component.submitter);
            else
                inputSection.appendChild(usageCountReminder);
            this.component.usageCountReminder = usageCountReminder;

            result.appendChild(inputSection);

            const responder = doc.createElement('div');
            responder.className = CN_CODE_HELPER_AI_RESPONDER;

            const responderContent = doc.createElement('p');
            responderContent.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT;
            responder.appendChild(responderContent);
            result.appendChild(responder);
            this.component.responder = responder;
            this.component.responderContent = responderContent;

            if (this.inSimpleMode) {
                const panelToggler = doc.createElement('div');
                panelToggler.className = CN_CODE_HELPER_PANEL_TOGGLER;
                panelToggler.title = localStrings['codehelper_thumbnail_hint'];
                panelToggler.onclick = (e) => {
                    this.startViewTransition(self.togglePanel.bind(self));
                }
                result.appendChild(panelToggler);
            }

            return result;
        }
        _insertWidgetToDom(doc, widgetElem, placeHolder) {
            // insert the created toolbar element to DOM, replace the placeholder
            const parent = placeHolder.parentNode;
            parent.replaceChild(widgetElem, placeHolder);
        }
        async _createAndInsertWidget(doc, placeHolderId, {useSimpleMode, enableCustomQuestion}) {
            let placeHolder = doc.getElementById(placeHolderId);
            if (placeHolder) {
                let elem = await this._createWidget(doc, {useSimpleMode, enableCustomQuestion});
                this._insertWidgetToDom(doc, elem, placeHolder);
            }
        }

        startViewTransition(func, ...args) {
            if (!document.startViewTransition) {
                func(...args);
            } else {
                const transition = document.startViewTransition(() => func(...args));
            }
        }
        expandPanel() {
            this.component.panel.classList.remove(CN_CODE_HELPER_COLLAPSED);
            this.component.panel.classList.add(CN_CODE_HELPER_EXPANDED);
            if (this.component.inputter)
                this.component.inputter.focus();

            if (!this._aiHelperRecordsHistoryRetrieved && this.historyDisplayMode === CodeHelperHistoryDisplayMode.SHOWN_ALL) {
                try {
                    this.requestAiHelperHistoryData();
                } finally {
                    this._aiHelperRecordsHistoryRetrieved = true;
                }
            }
        }
        collapsePanel() {
            this.component.panel.classList.add(CN_CODE_HELPER_COLLAPSED);
            this.component.panel.classList.remove(CN_CODE_HELPER_EXPANDED);
        }
        togglePanel() {
            if (this.isPanelExpanded())
                this.collapsePanel();
            else
                this.expandPanel();
        }
        isPanelExpanded() {
            return !this.component.panel.classList.contains(CN_CODE_HELPER_COLLAPSED);
        }

        _stripHtmlTags(content, doc) {
            if (!doc) {
                const elem = this.component.responderContent;
                doc = elem.ownerDocument;
            }
            const dummyElem = doc.createElement('div');
            dummyElem.innerHTML = content;
            return dummyElem.innerText;
        }
        _jsonToFormData(jsonObj) {
            let result = new FormData();
            for (let key of Object.keys(jsonObj)) {
                let value = jsonObj[key];
                if (value instanceof Array) {
                    for (let item of value) {
                        result.append(key, item);
                    }
                } else {
                    result.append(key, value);
                }
            }
            return result;
        }

        outputLines(lines, outputElem) {
            if (!outputElem)
                outputElem = this.component.responderContent;
            const doc = outputElem.ownerDocument;
            const docFrag = doc.createDocumentFragment();
            // let lastLineElem;
            lines.forEach(line => {
                const elem = doc.createElement('div');
                elem.innerText = line || ' ';
                docFrag.appendChild(elem);
                // lastLineElem = elem;
            });
            outputElem.appendChild(docFrag);

            setTimeout(() => {
                const scrollElem = this.component.responder;
                scrollElem.scrollTo({top: scrollElem.scrollHeight, behavior: 'smooth'});
            }, 10);
        }

        outputSection(sectionLines, outputType) {
            const parent = this.component.responderContent;
            const doc = parent.ownerDocument;
            const sectionElem = doc.createElement('section');
            sectionElem.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT_SECTION;
            this.outputLines(sectionLines, sectionElem);
            parent.appendChild(sectionElem);
        }

        // emulate the AI response
        getEmulatedResponseTextLines() {
            let result = [];

            result.push('服务器返回的回答将在这里显示。');
            result.push('');
            result.push('注意：当前不会完成实际工作，仅是模拟AI helper的输出。');
            result.push('与题目相关的以下各项数据将被发送至服务器，进而获取服务器响应：');
            result.push('');

            result.push('[Current answer]');
            const answer = this.currAnswer || '<empty>';
            result = result.concat(answer.split('\n'));

            result.push('[Question]');
            const sQuestion = JSON.stringify(this.questionData, null, '  ');
            result = result.concat(sQuestion.split('\n'));

            result.push('[Last Attempt]')
            const sAttempt = JSON.stringify(this.lastAttemptData, null, '  ');
            result = result.concat(sAttempt.split('\n'));

            result.push('[Output End]')

            return result;
        }

        emulateResponse() {
            const batchLineCount = {min: 3, max: 7};
            const batchDuration = {min: 500, max: 1500};
            const outputLines = this.getEmulatedResponseTextLines();

            let currLineIndex = 0;
            let self = this;

            function responseStep() {
                const lineCount = Math.round(Math.random() * (batchLineCount.max - batchLineCount.min)) + batchLineCount.min;
                const delay = Math.round(Math.random() * (batchDuration.max - batchDuration.min)) + batchDuration.min;
                // console.log('step', currLineIndex, lineCount, delay);
                setTimeout(() => {
                    const lines = outputLines.slice(currLineIndex, currLineIndex + lineCount);
                    self.outputLines(lines);
                    currLineIndex += lineCount;
                    if (currLineIndex < outputLines.length)
                        responseStep();
                }, delay);
            }
            responseStep();
        }

        // interface to AI helper server

        async requestAiHelper_old(userQuestion = '') {
            if (!userQuestion)
                userQuestion = this.component.inputter.value;

            if (this.aiRequestUrl) {
                // extract data to send to server
                const questionBody = this._stripHtmlTags(this.questionData['questiontext']);
                const questionLanguage = this.questionData['language'];
                const testCases = this.questionData['testcases'];
                const userCode = this.currAnswer;

                const data = {
                    questionBody, questionLanguage, testCases, userCode, userQuestion,
                    omitCodeSnippet: true
                };

                let response = await fetch(this.aiRequestUrl, {
                    method: 'post',
                    headers: {
                        'Content-Type': 'application/json;charset=utf-8'
                    },
                    body: JSON.stringify(data)
                });
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        const output = result.result.response;
                        this.outputSection(output.split('\n'));
                    } else {
                        console.error(result.message);
                        this.outputSection([result.message], 'Error');
                    }
                } else {
                    console.error('Fetch failed');
                    this.outputSection(['Fetch failed'], 'Error');
                }
            }
        }

        async requestAiHelper() {
            if (this.readOnly)
                return;

            const localStrings = await localResManager.getLocalStrings([
                'err_code_helper_empty_ask',
                'err_code_helper_server_fetch_failed',
                'err_code_helper_usage_count_exceeded'
            ]);

            if (this.isAiHelperUsageCountExceeded()) {
                await this.pushAiHelperRecord({id: null, userQuestion: '', response: localStrings['err_code_helper_usage_count_exceeded'], state: AiHelperDataRecord.STATE_REJECTED});
                return;
            }

            const userQuestion = this.inSimpleMode?
                this._aiHelperPredefinedQuestions[0]:
                (this.component.inputter && this.component.inputter.value);
            const userQuestionIndex = (!this.enableCustomQuestion && this.component.inputter)?
                (this.component.inputter.selectedIndex - 1): null;

            if (!userQuestion && !userQuestionIndex) {  // asked nothing, do not send request to server, just response with error
                await this.pushAiHelperRecord({id: null, userQuestion: '', response: localStrings['err_code_helper_empty_ask'], state: AiHelperDataRecord.STATE_REJECTED});
                return;
            }

            if (this.aiRequestUrl) {
                if (this.requestAiHelper.isFetching)
                    return;

                this.requestAiHelper.isFetching = true;

                // extract data to send to server
                const cmId = this.questionData.cmId;
                const questionAttemptId = this.questionData.questionAttemptId;
                const questionAttemptStepId = this.questionData.questionAttemptStepId;
                const questionUsageId = this.questionData.questionUsageId;
                const questionSlot = this.questionData.slot;
                const userCode = this.currAnswer;

                const data = {
                    cm: cmId,
                    question_attempt: questionAttemptId,
                    question_attempt_step: questionAttemptStepId,
                    question_usage: questionUsageId,
                    slot: questionSlot,
                    user_code: userCode,
                    omitCodeSnippet: true
                };
                if (this._enableCustomQuestion)
                    data.user_question = userQuestion;
                else
                    data.user_question_index = userQuestionIndex;

                // console.log('send to AI server', this.aiRequestUrl, data);
                const currAiHelperRecordId = await this.pushAiHelperRecord({id: null, userQuestion});

                try {
                    let response = await fetch(this.aiRequestUrl, {
                        method: 'post',
                        credentials: 'include',  // IMPORTANT, post cross-site cookies to server, handling the http/https mangling of *.cpu.edu.cn
                        /*
                        headers: {
                            'Content-Type': 'application/json;charset=utf-8'
                        },
                        */
                        body: this._jsonToFormData(data) //JSON.stringify(data)
                    });
                    if (response.ok) {
                        const result = await response.json();
                        // console.log('the result', result);
                        if (result.success) {
                            const output = result.result.response;
                            const newResponseId = ((result.result.id !== null) || (result.result.id !== undefined)) ? result.result.id : undefined;
                            // this.outputSection(output.split('\n'));
                            this.updateAiHelperRecord(currAiHelperRecordId, {
                                dbId: newResponseId,
                                response: output,
                                state: AiHelperDataRecord.STATE_FULFILLED
                            });
                        } else {
                            console.error(result.message);
                            // this.outputSection([result.message], 'Error');
                            this.updateAiHelperRecord(currAiHelperRecordId, {
                                response: result.message,
                                state: AiHelperDataRecord.STATE_REJECTED
                            });
                        }

                        if (result.result && typeof (result.result.remainingUsageCount) === 'number') {
                            // server returns a remaining usage count info, update the internal property
                            this.aiHelperRemainingUsageCount = result.result.remainingUsageCount;
                        } else {
                            this.decreaseRemainingUsageCount();
                        }

                    } else {
                        console.error(localStrings['err_code_helper_server_fetch_failed']);
                        // this.outputSection([localStrings['err_code_helper_server_fetch_failed']], 'Error');
                        this.updateAiHelperRecord(currAiHelperRecordId, {
                            response: localStrings['err_code_helper_server_fetch_failed'],
                            state: AiHelperDataRecord.STATE_REJECTED
                        });
                    }
                } catch(e) {
                    console.error(e.message);
                    this.updateAiHelperRecord(currAiHelperRecordId, {
                        response: e.message,
                        state: AiHelperDataRecord.STATE_REJECTED
                    });
                } finally {
                    this.requestAiHelper.isFetching = false;
                }
            }
        }

        async rateAiHelperResponse(id, responseDbId, value) {
            if (this.aiRateUrl) {
                const localStrings = await localResManager.getLocalStrings([
                    'err_code_helper_server_fetch_failed'
                ]);

                const data = {
                    // cm: this.questionData.cmId,
                    // question_attempt: this.questionData.questionAttemptId,
                    // question_attempt_step: this.questionData.questionAttemptStepId,
                    question_usage: this.questionData.questionUsageId,
                    slot: this.questionData.slot,
                    id: responseDbId,
                    value: value,
                    mode: 'set'
                }
                let response = await fetch(this.aiRateUrl, {
                    method: 'post',
                    credentials: 'include',
                    body: this._jsonToFormData(data)
                });
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        const newRate = result.result;
                        console.log('rated', responseDbId, newRate);
                        this.updateAiHelperRecord(responseDbId, {userRating: newRate});
                    } else {
                        throw new Error(result.message);
                    }
                } else {
                    throw new Error(localStrings['err_code_helper_server_fetch_failed'])
                }
            }
        }

        async requestAiHelperHistoryData() {
            if (this.aiRequestUrl) {
                const data = {
                    cm: this.questionData.cmId,
                    question_attempt: this.questionData.questionAttemptId,
                    question_attempt_step: this.questionData.questionAttemptStepId,
                    question_usage: this.questionData.questionUsageId,
                    slot: this.questionData.slot,
                    history: true
                };

                let response = await fetch(this.aiRequestUrl, {
                    method: 'post',
                    credentials: 'include',
                    body: this._jsonToFormData(data)
                });
                if (response.ok) {
                    const result = await response.json();
                    // console.log('the result', result);
                    if (result.success) {
                        const historyItems = result.result || [];
                        historyItems.sort((a, b) => a.timestamp - b.timestamp);

                        const historyRecords = historyItems.map((item, index) => {
                            const id = (item.id === undefined)? ('his-' + index): item.id;
                            return new AiHelperDataRecord(id, {
                                dbId: item.id,
                                userQuestion: item.user_question,
                                response: item.response,
                                userRating: item.user_rating,
                                state: AiHelperDataRecord.STATE_FULFILLED
                            });
                        });
                        // put history items at the head of current record list
                        this._aiHelperRecords.data.unshift(...historyRecords);
                        await this.repaintAiHelperRecords();

                        if (!historyItems.length && this.readOnly) {
                            // if the history is empty and currently in review mode,
                            // add an empty item to explicit tells the reviewer that these is no history item
                            localResManager.getLocalStrings([
                                'codehelper_no_history_item',
                            ]).then(localStrings => {
                                this.pushAiHelperRecord({id:null, userQuestion: '', response: localStrings['codehelper_no_history_item'], state: AiHelperDataRecord.STATE_FULFILLED});
                            });
                        }
                    } else {
                        console.error(result.message);
                    }
                } else {
                    console.error(localStrings['err_code_helper_server_fetch_failed']);
                    this.updateAiHelperRecord(currAiHelperRecordId, {response: localStrings['err_code_helper_server_fetch_failed'], state: AiHelperDataRecord.STATE_REJECTED});
                }
            }
        }

        decreaseRemainingUsageCount() {
            if (this.readOnly)
                return;

            if (typeof(this.aiHelperRemainingUsageCount) === 'number') {
                if (this.aiHelperRemainingUsageCount > 0)
                    this.aiHelperRemainingUsageCount--;
            }
        }

        // methods about ai helper record display
        async pushAiHelperRecord({id, dbId, userQuestion = null, response = null, state = AiHelperDataRecord.STATE_PENDING}) {
            if (this.historyDisplayMode === CodeHelperHistoryDisplayMode.SHOWN_ACTIVE) {
                // only preserve the latest record, clear all olds
                this._aiHelperRecords.clear();
                await this.repaintAiHelperRecords();
            }
            return this._aiHelperRecords.push(id, {dbId, userQuestion, response, state});
        }
        updateAiHelperRecord(id, {dbId, userQuestion = undefined, response = undefined, state = undefined}) {
            return this._aiHelperRecords.updateRecord(id, {dbId, userQuestion, response, state});
        }
        async repaintAiHelperRecords() {
            const rootElem = this._component.responderContent;
            rootElem.innerHTML = '';

            for (let record of this._aiHelperRecords.data) {
                await this.updateAiHelperRecordOnElem(record.id, record);
            }

            this.requestScrollToResponderBottom();
        }

        async reactAiHelperRecordsUpdate(id, record) {
            await this.updateAiHelperRecordOnElem(id, record);
            this.requestScrollToResponderBottom();
        }

        requestScrollToResponderBottom() {
            setTimeout(() => {
                const scrollElem = this.component.responder;
                scrollElem.scrollTo({top: scrollElem.scrollHeight, behavior: 'smooth'});
            }, 10);
        }

        _needToDisplayAiHelperRecordUserAskedQuestion() {
            return !(this.historyDisplayMode === CodeHelperHistoryDisplayMode.SHOWN_ACTIVE && this.inSimpleMode)
        }
        async updateAiHelperRecordOnElem(/*groupId,*/ recordId, record) {
            const rootElem = this._component.responderContent;
            const doc = rootElem.ownerDocument;

            // check if an element of this record id already exists
            // if so, we need to update this element, otherwise create a new one
            let recordElem = rootElem.querySelector(`section[data-record-id="${recordId}"]`);
            if (!recordElem) {   // create new elem and child elements
                recordElem = doc.createElement('section');
                recordElem.setAttribute('data-record-id', recordId);

                if (this._needToDisplayAiHelperRecordUserAskedQuestion()) {
                    const elemUserQuestion = doc.createElement('p');
                    elemUserQuestion.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT_USER_QUESTION;
                    recordElem.appendChild(elemUserQuestion);
                }

                const elemResponse = doc.createElement('p');
                elemResponse.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE;
                // elemResponse.innerText = record.response;
                recordElem.appendChild(elemResponse);

                if (this.enableUserRating || this.enableViewUserRating) {
                    const elemUserRating = doc.createElement('div')
                    elemUserRating.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT_USER_RATING;
                    const localStrings = await localResManager.getLocalStrings([
                        'codehelper_user_rate_positive',
                        'codehelper_user_rate_negative'
                    ]);

                    // rating widget of record
                    const ratingWidget = new UserRatingWidget(doc, elemUserRating, true);
                    ratingWidget.onchange = async (widget, newValue, oldValue) => {
                        try {
                            await this.rateAiHelperResponse(record.id, record.dbId, newValue);
                        } catch (e) {
                            // failed, reverse to old value
                            ratingWidget.value = oldValue;
                            console.error(e);
                        }
                    }
                    recordElem._ratingWidget = ratingWidget;
                    recordElem.appendChild(elemUserRating);
                }

                rootElem.appendChild(recordElem);
            }
            {  // update content of elem
                const hasDbId = record.dbId || record.dbId === 0;
                if (!hasDbId)
                    recordElem.removeAttribute('data-db-id');
                else
                    recordElem.setAttribute('data-db-id', record.dbId);

                const elemUserQuestion = recordElem.getElementsByClassName(CN_CODE_HELPER_AI_RESPONDER_CONTENT_USER_QUESTION)[0];
                if (elemUserQuestion) {
                    elemUserQuestion.innerText = record.userQuestion;
                    if (!record.userQuestion)
                        elemUserQuestion.classList.add(CN_CODE_HELPER_ELEM_HIDE);
                    else
                        elemUserQuestion.classList.remove(CN_CODE_HELPER_ELEM_HIDE);
                }

                const elemResponse = recordElem.getElementsByClassName(CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE)[0];
                // elemResponse.innerText = record.response;
                this._updateAiHelperResponseOnElem(doc, elemResponse, record.response);

                const elemUserRating = recordElem.getElementsByClassName(CN_CODE_HELPER_AI_RESPONDER_CONTENT_USER_RATING)[0];
                if (elemUserRating) {
                    this.startViewTransition(() => {
                        const displayUserRating = (record.state === AiHelperDataRecord.STATE_FULFILLED && hasDbId)
                            && (this.enableUserRating || (this.enableViewUserRating && record.userRating))
                        if (displayUserRating) {
                            elemUserRating.classList.remove(CN_CODE_HELPER_ELEM_HIDE);
                        } else {
                            elemUserRating.classList.add(CN_CODE_HELPER_ELEM_HIDE);
                        }
                        if (recordElem._ratingWidget) {
                            recordElem._ratingWidget.value = record.userRating;
                        }
                    });
                }
            }

            // update elem state
            const elemStateClass = (record.state === AiHelperDataRecord.STATE_PENDING)? CN_CODE_HELPER_AI_RESPONDER_SECTION_STATE_PENDING:
                (record.state === AiHelperDataRecord.STATE_REJECTED)? CN_CODE_HELPER_AI_RESPONDER_SECTION_STATE_REJECTED:
                    CN_CODE_HELPER_AI_RESPONDER_SECTION_STATE_FULFILLED;
            recordElem.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT_SECTION + ' ' + elemStateClass;
        }
        _updateAiHelperResponseOnElem(doc, elemResponse, response) {
            if (!response) {
                // empty response, just clear
                elemResponse.innerText = '';
            } else if (typeof(response) !== 'object') {
                // simple string
                elemResponse.innerText = response;
            } else {
                // structurized response, actually an array of sections
                elemResponse.innerText = '';  // clear old first
                for (let section of response) {
                    if (!section)
                        continue;
                    const elemSection = doc.createElement('section');
                    elemSection.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION;
                    if (section.title) {
                        const elemTitle = doc.createElement('h3');
                        elemTitle.className = CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_TITLE;
                        elemTitle.innerText = section.title;
                        elemSection.appendChild(elemTitle);
                    }
                    if (section.contents) {
                        const elemContent = doc.createElement('div');
                        elemContent.className = section.is_code_snippet?
                            CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_CODE:
                            CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_CONTENT;
                        if (section.is_code_omitted)
                            elemContent.className += ' ' + CN_CODE_HELPER_AI_RESPONDER_CONTENT_RESPONSE_SECTION_CODE_OMITTED;
                        elemContent.innerText = section.contents.join('\n');
                        elemSection.appendChild(elemContent);

                        if (section.is_code_snippet && !section.is_code_omitted && window.ace) {
                            // since coderunner uses ace as editor, we can highlight the code with ace now
                            const aceOptions = {
                                selectionStyle: "text",
                                readOnly: true,
                                minLines: 2,
                                maxLines: 999
                            };
                            if (section.code_language)
                                aceOptions.mode = this._getAceEditorModeOption(section.code_language);
                            setTimeout(() => {
                                // console.log('initialize ace editor', aceOptions);
                                // alert(elemContent.innerText);
                                const editor = window.ace.edit(elemContent, aceOptions);
                                editor.session.setValue(section.contents.join('\n') + '\n');  // add a tailing blank line for better display
                            }, 50);
                        }
                    }
                    elemResponse.appendChild(elemSection);
                }
            }
        }
        _getAceEditorModeOption(codeSectionLanguage) {
            const prefix = 'ace/mode/';
            const map = {
                'nodejs': 'javascript',
                'js': 'javascript',
                'python3': 'python',
                'python2': 'python',
                'delphi': 'pascal'
            }
            const lanName = map[codeSectionLanguage] || codeSectionLanguage;
            return prefix + lanName;
        }

        isAiHelperUsageCountExceeded() {
            const value = this.aiHelperRemainingUsageCount;
            if (typeof(value) === 'number') {
                return value <= 0;
            } else
                return false;
        }

        // properties
        get id() {
            return this._id;
        }
        get targetEditor() {
            return this._targetEditor || this._targetRawEditor;
        }
        get component() {
            return this._component;
        }
        get enableUserRating() {
            return this._enableUserRating && !this.readOnly;
        }
        get enableViewUserRating() {
            // in review mode, we display rating results
            return this.enableUserRating || this.readOnly;
        }
        get readOnly() {
            return this._readOnly;
        }
        get historyDisplayMode() {
            if (this.readOnly)
                return CodeHelperHistoryDisplayMode.SHOWN_ALL;
            else
                return this._historyDisplayMode;
        }
        get inSimpleMode() {
            return this._simpleMode;
        }

        get currAnswer() {
            return this._targetRawEditor?.value;
        }
        get questionData() {
            return this._qMetaData;
        }
        get lastAttemptData() {
            // return this._qMetaData.lastAttemptStepData;
            return [];
        }

        get aiRequestUrl() {
            return this._aiRequestUrl;
        }
        get aiRateUrl() {
            return this._aiRateUrl;
        }

        get aiHelperRemainingUsageCount() {
            return this._aiHelperRemainingUsageCount;
        }
        set aiHelperRemainingUsageCount(value) {
            this._aiHelperRemainingUsageCount = value;
            if (typeof(value) === 'number') {
                if (this.component.usageCountReminder) {
                    const displayedValue = (value > 99) ? '99+' : value;
                    // update this.component.usageCountReminder
                    this.component.usageCountReminder.classList.remove('Hidden');
                    this.component.usageCountReminder.innerText = displayedValue;
                    localResManager.getLocalStrings([
                        'codehelper_ai_usage_count_reminder_hint'
                    ]).then(
                        localStrings => {
                            this.component.usageCountReminder.title = localStrings['codehelper_ai_usage_count_reminder_hint'].replace('{}', value);
                        }
                    );
                }
                // if exceeded, disable the inputter and submit button
                if (this.component.submitter) {
                    this.component.submitter.disabled = this.isAiHelperUsageCountExceeded();
                }
                if (this.component.inputter) {
                    this.component.inputter.disabled = this.isAiHelperUsageCountExceeded();
                }
            } else {
                if (this.component.usageCountReminder)
                    this.component.usageCountReminder.classList.add('Hidden');
            }
        }
    }


    function init({codeHelperPlaceHolderId, questionMetaElemId, targetEditorId, codeHelperInSimpleMode,
                      aiHelperRequestUrl, aiHelperRateUrl, aiHelperPredefinedQuestions,
                      aiHelperRemainingUsageCount,
                      historyDisplayMode, enableCustomQuestion, enableUserRating, readOnly
                  }) {
        return new CodeHelper(document, {
            placeHolderId: codeHelperPlaceHolderId,
            targetEditorId,
            questionDataEmbedderId: questionMetaElemId,
            aiRequestUrl: aiHelperRequestUrl,
            aiRateUrl: aiHelperRateUrl,
            aiHelperPredefinedQuestions,
            aiHelperRemainingUsageCount,
            enableCustomQuestion,
            enableUserRating,
            readOnly,
            historyDisplayMode,
            useSimpleMode: codeHelperInSimpleMode
        });
    }

    return {
        CodeHelper,
        init
    }
});